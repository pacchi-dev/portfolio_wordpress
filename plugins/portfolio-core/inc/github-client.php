<?php
/**
 * GitHub API クライアント。
 *
 * 外部 API 連携の実装例として、以下を含んでいる。
 *   - wp_remote_get() による HTTP 通信（cURL / Guzzle を直接使わない理由は下記）
 *   - WP_Error によるエラー表現（WordPress は例外をほぼ使わない）
 *   - Transients API によるキャッシュ
 *   - API 障害時に古いキャッシュへフォールバックする二段構え
 *
 * なぜ wp_remote_get() か:
 *   1. cURL が無い環境では自動で fsockopen へフォールバックする
 *      （共用サーバーでは実際に起こる）
 *   2. http_request_args / pre_http_request フィルタで通信を差し替えられる
 *      （テストや他プラグインからの介入が可能）
 *   3. wp-config.php のプロキシ定数を尊重する
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 通常キャッシュの有効期間（秒）。
 * GitHub の未認証レート制限は 60 リクエスト/時のため、
 * 1 時間キャッシュすれば実質的に制限に触れない。
 */
const PC_GITHUB_CACHE_TTL = HOUR_IN_SECONDS;

/**
 * フォールバック用キャッシュの有効期間（秒）。
 * 通常キャッシュが切れても、API 障害時はこちらを表示に使う。
 */
const PC_GITHUB_STALE_TTL = WEEK_IN_SECONDS;

/**
 * GitHub のユーザー名を返す。
 *
 * 環境変数を優先し、無ければ Contact に登録された GitHub URL から推測する。
 * ハードコードを避けつつ、環境変数が未設定でも動くようにしている。
 *
 * @return string
 */
function pc_github_username(): string {
	$from_env = (string) getenv( 'GITHUB_USERNAME' );

	if ( '' !== $from_env ) {
		return $from_env;
	}

	$page_id = (int) get_option( 'page_on_front' );

	if ( $page_id > 0 && function_exists( 'get_field' ) ) {
		$url = (string) get_field( 'contact_github', $page_id );

		if ( '' !== $url ) {
			$path = (string) wp_parse_url( $url, PHP_URL_PATH );
			$name = trim( $path, '/' );

			if ( '' !== $name && ! str_contains( $name, '/' ) ) {
				return $name;
			}
		}
	}

	return '';
}

/**
 * GitHub API へリクエストを送る。
 *
 * @param string $endpoint /users/foo のようなパス。
 * @return array<mixed>|WP_Error デコード済みのレスポンス、または WP_Error。
 */
function pc_github_request( string $endpoint ) {
	$args = array(
		'timeout' => 8,
		'headers' => array(
			'Accept'               => 'application/vnd.github+json',
			'X-GitHub-Api-Version' => '2022-11-28',
			// User-Agent は GitHub API で必須。無いと 403 が返る。
			'User-Agent'           => 'portfolio-core/' . PC_VERSION,
		),
	);

	// トークンがあれば認証する。レート制限が 5000 リクエスト/時に上がる。
	$token = (string) getenv( 'GITHUB_TOKEN' );

	if ( '' !== $token ) {
		$args['headers']['Authorization'] = 'Bearer ' . $token;
	}

	$response = wp_remote_get( 'https://api.github.com' . $endpoint, $args );

	/*
	 * wp_remote_get は「通信自体の失敗」（DNS 解決不能、タイムアウト等）で
	 * WP_Error を返す。HTTP 4xx/5xx は成功扱いで返るため、
	 * ステータスコードは別途確認する必要がある。ここを忘れると
	 * エラーページの HTML をデータとして扱ってしまう。
	 */
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );

	if ( 200 !== $status ) {
		return new WP_Error(
			'pc_github_http_error',
			sprintf(
				/* translators: 1: HTTP ステータスコード, 2: エンドポイント */
				__( 'GitHub API がステータス %1$d を返しました (%2$s)', 'portfolio-core' ),
				$status,
				$endpoint
			),
			array( 'status' => $status )
		);
	}

	$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $decoded ) ) {
		return new WP_Error(
			'pc_github_invalid_json',
			__( 'GitHub API のレスポンスを解析できませんでした。', 'portfolio-core' )
		);
	}

	return $decoded;
}

/**
 * 表示に必要な情報だけを抜き出したリポジトリ一覧を返す。
 *
 * キャッシュの流れ:
 *   1. 通常キャッシュがあればそれを返す（API を叩かない）
 *   2. 無ければ API を叩き、成功したら両方のキャッシュを更新する
 *   3. 失敗したら古いキャッシュ（stale）を返す
 *   4. 古いキャッシュも無ければ WP_Error を返す
 *
 * 3 の段階があることで、GitHub 側の障害中もサイトが空にならない。
 *
 * @param bool $force_refresh true でキャッシュを無視して取得し直す。
 * @return array<int, array<string, mixed>>|WP_Error
 */
function pc_github_get_repositories( bool $force_refresh = false ) {
	$username = pc_github_username();

	if ( '' === $username ) {
		return new WP_Error(
			'pc_github_no_username',
			__( 'GitHub のユーザー名が設定されていません。', 'portfolio-core' )
		);
	}

	$cache_key = 'pc_github_repos_' . md5( $username );
	$stale_key = 'pc_github_repos_stale_' . md5( $username );

	if ( ! $force_refresh ) {
		$cached = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = pc_github_request(
		sprintf( '/users/%s/repos?per_page=100&sort=updated', rawurlencode( $username ) )
	);

	if ( is_wp_error( $response ) ) {
		// 期限切れの古いキャッシュがあれば、それを使って表示を維持する。
		$stale = get_transient( $stale_key );

		if ( is_array( $stale ) ) {
			return $stale;
		}

		return $response;
	}

	$repositories = array();

	foreach ( $response as $repo ) {
		if ( ! is_array( $repo ) ) {
			continue;
		}

		// フォークとアーカイブ済みは実績として弱いため除外する。
		if ( ! empty( $repo['fork'] ) || ! empty( $repo['archived'] ) ) {
			continue;
		}

		$repositories[] = array(
			'name'        => (string) ( $repo['name'] ?? '' ),
			'url'         => (string) ( $repo['html_url'] ?? '' ),
			'description' => (string) ( $repo['description'] ?? '' ),
			'language'    => (string) ( $repo['language'] ?? '' ),
			'stars'       => (int) ( $repo['stargazers_count'] ?? 0 ),
			'updated_at'  => (string) ( $repo['pushed_at'] ?? '' ),
		);
	}

	// 更新日の新しい順。
	usort(
		$repositories,
		static fn( array $a, array $b ): int => strcmp( (string) $b['updated_at'], (string) $a['updated_at'] )
	);

	set_transient( $cache_key, $repositories, PC_GITHUB_CACHE_TTL );
	set_transient( $stale_key, $repositories, PC_GITHUB_STALE_TTL );

	return $repositories;
}

/**
 * 言語ごとのリポジトリ数を集計する。
 *
 * @param array<int, array<string, mixed>> $repositories リポジトリ一覧。
 * @return array<string, int> 言語名 => 件数（多い順）。
 */
function pc_github_language_stats( array $repositories ): array {
	$stats = array();

	foreach ( $repositories as $repo ) {
		$language = (string) ( $repo['language'] ?? '' );

		if ( '' === $language ) {
			continue;
		}

		$stats[ $language ] = ( $stats[ $language ] ?? 0 ) + 1;
	}

	arsort( $stats );

	return $stats;
}

/**
 * キャッシュを削除する。
 *
 * @return void
 */
function pc_github_flush_cache(): void {
	$username = pc_github_username();

	if ( '' === $username ) {
		return;
	}

	delete_transient( 'pc_github_repos_' . md5( $username ) );
}
