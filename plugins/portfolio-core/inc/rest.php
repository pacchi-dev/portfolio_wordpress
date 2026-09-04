<?php
/**
 * 独自 REST API エンドポイント。
 *
 * WordPress は /wp-json/ 以下に REST API を持っており、
 * register_rest_route() で独自のエンドポイントを追加できる。
 * 管理画面の Ajax（admin-ajax.php）より新しく、推奨される方式。
 *
 * 重要: permission_callback は必須。
 * 省略すると WordPress が _doing_it_wrong 警告を出し、
 * 実質「誰でも叩ける」エンドポイントになる。
 * 公開してよいものは明示的に __return_true を書く。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * ルートを登録する。
 *
 * rest_api_init は REST API の初期化時に発火する action。
 * init ではなくこちらで登録する。
 */
add_action( 'rest_api_init', 'pc_register_rest_routes' );
function pc_register_rest_routes(): void {

	/*
	 * GET /wp-json/portfolio/v1/github
	 *
	 * 公開エンドポイント。フロントから取得内容を確認できる。
	 * 個人情報を含まず、元データも公開されているため __return_true とする。
	 */
	register_rest_route(
		'portfolio/v1',
		'/github',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'pc_rest_get_github',
			'permission_callback' => '__return_true',
			'args'                => array(
				'limit' => array(
					'description'       => __( '取得件数', 'portfolio-core' ),
					'type'              => 'integer',
					'default'           => 6,
					'sanitize_callback' => 'absint',
					'validate_callback' => static fn( $value ): bool => $value >= 1 && $value <= 100,
				),
			),
		)
	);

	/*
	 * POST /wp-json/portfolio/v1/github/refresh
	 *
	 * キャッシュを破棄して取得し直す。
	 * 状態を変更する操作なので、権限チェックを必須にする。
	 * current_user_can( 'manage_options' ) は管理者のみを通す。
	 *
	 * REST API は Cookie 認証の場合 X-WP-Nonce ヘッダによる
	 * nonce 検証も併せて要求する（WordPress が自動で行う）。
	 * これが CSRF 対策になっている。
	 */
	register_rest_route(
		'portfolio/v1',
		'/github/refresh',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'pc_rest_refresh_github',
			'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
		)
	);
}

/**
 * リポジトリ一覧を返す。
 *
 * @param WP_REST_Request $request リクエスト。
 * @return WP_REST_Response|WP_Error
 */
function pc_rest_get_github( WP_REST_Request $request ) {
	$repositories = pc_github_get_repositories();

	if ( is_wp_error( $repositories ) ) {
		/*
		 * WP_Error をそのまま返すと、REST API が
		 * data['status'] を見て適切な HTTP ステータスへ変換してくれる。
		 * 未指定なら 500 になる。
		 */
		$repositories->add_data( array( 'status' => 502 ), 'pc_github_upstream_error' );

		return $repositories;
	}

	$limit = (int) $request->get_param( 'limit' );

	return new WP_REST_Response(
		array(
			'username'  => pc_github_username(),
			'count'     => count( $repositories ),
			'languages' => pc_github_language_stats( $repositories ),
			'items'     => array_slice( $repositories, 0, $limit ),
		),
		200
	);
}

/**
 * キャッシュを破棄して取得し直す。
 *
 * @return WP_REST_Response|WP_Error
 */
function pc_rest_refresh_github() {
	pc_github_flush_cache();

	$repositories = pc_github_get_repositories( true );

	if ( is_wp_error( $repositories ) ) {
		$repositories->add_data( array( 'status' => 502 ), 'pc_github_upstream_error' );

		return $repositories;
	}

	return new WP_REST_Response(
		array(
			'refreshed' => true,
			'count'     => count( $repositories ),
		),
		200
	);
}
