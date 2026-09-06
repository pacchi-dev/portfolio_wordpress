<?php
/**
 * セキュリティ・不要出力の抑制。
 *
 * WordPress は初期状態で「攻撃者に有用な情報」や「使わない機能」を
 * 出力している。実案件でも納品前に整理する定番項目をまとめている。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * wp_head から不要なタグを削除する。
 *
 * remove_action() は、WordPress 本体が登録済みの action を外す仕組み。
 * 「本体の挙動を止める」のは hooks の代表的な使い方で、
 * 既存サイトの改修案件でも頻繁に使う。
 */
add_action( 'init', 'pp_clean_wp_head' );
function pp_clean_wp_head(): void {

	/*
	 * <meta name="generator" content="WordPress 7.1" />
	 * バージョンが露出すると、既知の脆弱性を狙った探索の対象になりやすい。
	 */
	remove_action( 'wp_head', 'wp_generator' );

	// Really Simple Discovery / Windows Live Writer。現在は利用されていない。
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// 隣接記事への rel リンク。1 ページ構成では不要。
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'start_post_rel_link', 10 );

	/*
	 * 絵文字用の JS / CSS。
	 * 初期状態では全ページで読み込まれるが、実用上ほぼ不要で
	 * パフォーマンス改善の定番の削除対象。
	 */
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

/**
 * XML-RPC を無効化する。
 *
 * 外部クライアントからの投稿用 API だが、現在はほぼ使われず、
 * 総当たり攻撃（system.multicall による増幅）の入口として悪用される。
 * 実案件でもレンタルサーバー側で塞がれていることが多い。
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * ログイン失敗時のメッセージを共通化する。
 *
 * 初期状態では「ユーザー名が違う」「パスワードが違う」を区別して返すため、
 * ユーザー名の存在有無を推測できてしまう（ユーザー列挙）。
 */
add_filter( 'login_errors', 'pp_generic_login_error' );
function pp_generic_login_error(): string {
	return __( 'ログイン情報が正しくありません。', 'pacchi-portfolio' );
}

/**
 * REST API 経由のユーザー一覧取得を、ログインユーザーに限定する。
 *
 * /wp-json/wp/v2/users は初期状態で未認証でも閲覧でき、
 * 投稿者のログイン名が漏れる。ユーザー列挙の代表的な経路。
 *
 * REST API 自体は無効化しない。ブロックエディタが依存しており、
 * 本テーマでも Phase 6 で独自エンドポイントを追加するため。
 *
 * @param WP_Error|null|true $result 認証結果。
 * @return WP_Error|null|true
 */
add_filter( 'rest_authentication_errors', 'pp_restrict_rest_user_endpoint' );
function pp_restrict_rest_user_endpoint( $result ) {
	// 既に他のフィルタがエラーを返している場合はそれを尊重する。
	if ( ! empty( $result ) ) {
		return $result;
	}

	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
		? (string) $GLOBALS['wp']->query_vars['rest_route']
		: '';

	if ( str_starts_with( $route, '/wp/v2/users' ) && ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'このエンドポイントの利用には認証が必要です。', 'pacchi-portfolio' ),
			array( 'status' => 401 )
		);
	}

	return $result;
}

/**
 * 著者アーカイブ（/?author=1）を無効化する。
 *
 * ID から投稿者のログイン名を含む URL へリダイレクトされるため、
 * これもユーザー列挙の経路になる。1 人運用のサイトでは不要な機能。
 */
add_action( 'template_redirect', 'pp_disable_author_archive' );
function pp_disable_author_archive(): void {
	if ( is_author() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
