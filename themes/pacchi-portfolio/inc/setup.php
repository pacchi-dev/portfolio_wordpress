<?php
/**
 * テーマの基本機能を WordPress に宣言する。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * after_setup_theme はテーマ読み込み直後に発火する action hook。
 * add_theme_support() は必ずこのタイミングで呼ぶ必要がある
 * （init より前でないと WordPress 側の初期化に間に合わない）。
 */
add_action( 'after_setup_theme', 'mp_theme_setup' );
function mp_theme_setup(): void {

	/*
	 * <title> タグの出力を WordPress に任せる。
	 * これを宣言しないと wp_head() が <title> を出力せず、
	 * テーマ側で自前生成することになる。SEO プラグインとの連携もこれが前提。
	 */
	add_theme_support( 'title-tag' );

	/*
	 * アイキャッチ画像（featured image）を有効化する。
	 * 制作実績のサムネイルに使う。ACF の画像フィールドではなく
	 * 標準機能を使う理由は docs/decisions.md D-8 を参照。
	 */
	add_theme_support( 'post-thumbnails' );

	/*
	 * WordPress が出力するマークアップを HTML5 準拠にする。
	 * 指定しないと検索フォーム等が XHTML 形式で出力される。
	 */
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	/*
	 * ブロックエディタ（Gutenberg）用の設定。
	 * 本テーマはクラシックテーマだが、投稿本文でブロックエディタは使うため
	 * 幅の指定と余計なカラーパレットの無効化だけ行う。
	 */
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'disable-custom-font-sizes' );

	/*
	 * ナビゲーションメニューの位置を登録する。
	 * これにより「外観 > メニュー」から項目を追加・並べ替えできるようになる。
	 * ハードコードしないのは、実案件で最も多い依頼のひとつが
	 * 「メニューに項目を足したい」だから。
	 */
	register_nav_menus(
		array(
			'primary' => __( 'ヘッダーナビゲーション', 'pacchi-portfolio' ),
		)
	);

	// 添付画像のサイズ。制作実績のカード用。
	add_image_size( 'mp-work-card', 720, 405, true );
}

/**
 * コンテンツ幅。埋め込みコンテンツの最大幅として WordPress が参照する。
 */
add_action( 'after_setup_theme', 'mp_content_width', 0 );
function mp_content_width(): void {
	$GLOBALS['content_width'] = 1024;
}
