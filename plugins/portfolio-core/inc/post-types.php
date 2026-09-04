<?php
/**
 * カスタム投稿タイプとタクソノミーの登録。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * カスタム投稿タイプを登録する。
 *
 * init は WordPress の初期化完了時に発火する action hook。
 * register_post_type() は必ず init で呼ぶ必要がある
 * （早すぎると WordPress 側の準備が済んでおらず、
 *   遅すぎるとクエリの解決に間に合わない）。
 */
add_action( 'init', 'pc_register_post_types' );
function pc_register_post_types(): void {

	/*
	 * work — 制作実績
	 *
	 * public       : フロントに公開し、検索対象にもする
	 * has_archive  : /works/ の一覧ページを自動生成する
	 * rewrite      : URL のスラッグ。投稿タイプ名（work）と別にできる
	 * show_in_rest : ブロックエディタと REST API を有効にする。
	 *                これが false だと管理画面がクラシックエディタになる
	 * supports     : 編集画面に出す機能。使わないものは外す（revisions は残すと安全）
	 * menu_position: 管理メニューの表示位置（5 = 投稿の直下）
	 */
	register_post_type(
		'work',
		array(
			'labels'        => array(
				'name'               => __( '制作実績', 'portfolio-core' ),
				'singular_name'      => __( '制作実績', 'portfolio-core' ),
				'add_new'            => __( '新規追加', 'portfolio-core' ),
				'add_new_item'       => __( '制作実績を追加', 'portfolio-core' ),
				'edit_item'          => __( '制作実績を編集', 'portfolio-core' ),
				'view_item'          => __( '制作実績を表示', 'portfolio-core' ),
				'all_items'          => __( '制作実績一覧', 'portfolio-core' ),
				'search_items'       => __( '制作実績を検索', 'portfolio-core' ),
				'not_found'          => __( '制作実績が見つかりません。', 'portfolio-core' ),
				'featured_image'     => __( 'サムネイル画像', 'portfolio-core' ),
				'set_featured_image' => __( 'サムネイル画像を設定', 'portfolio-core' ),
			),
			'public'        => true,
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'works',
				'with_front' => false,
			),
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 5,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes' ),
		)
	);

	/*
	 * experience — 実務経験
	 *
	 * has_archive を false にしているのは、トップページの
	 * サブループで一覧表示し、独立したアーカイブページを持たないため。
	 * 個別ページ（/experience/{slug}/）は残す。
	 */
	register_post_type(
		'experience',
		array(
			'labels'        => array(
				'name'          => __( '実務経験', 'portfolio-core' ),
				'singular_name' => __( '実務経験', 'portfolio-core' ),
				'add_new'       => __( '新規追加', 'portfolio-core' ),
				'add_new_item'  => __( '実務経験を追加', 'portfolio-core' ),
				'edit_item'     => __( '実務経験を編集', 'portfolio-core' ),
				'all_items'     => __( '実務経験一覧', 'portfolio-core' ),
				'not_found'     => __( '実務経験が見つかりません。', 'portfolio-core' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array(
				'slug'       => 'experience',
				'with_front' => false,
			),
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-businessperson',
			'menu_position' => 6,
			'supports'      => array( 'title', 'revisions', 'page-attributes' ),
		)
	);
}

/**
 * タクソノミーを登録する。
 *
 * タクソノミーはカテゴリー・タグを一般化した「分類」の仕組み。
 * 使用技術を ACF のテキストフィールドで持つと表記ゆれが起き、
 * 「この技術を使った実績一覧」も作れない。
 * タクソノミーにすればアーカイブが自動生成され、
 * Skills セクションとも同じデータを参照できる。
 */
add_action( 'init', 'pc_register_taxonomies' );
function pc_register_taxonomies(): void {

	/*
	 * technology — 使用技術
	 *
	 * hierarchical false = タグ形式（カテゴリー形式なら true）。
	 * カテゴリ分け（Backend / Cloud など）はターム側のメタデータで持つため、
	 * 階層構造にはしない。
	 *
	 * show_admin_column で投稿一覧に列として表示される。
	 */
	register_taxonomy(
		'technology',
		array( 'work', 'experience' ),
		array(
			'labels'            => array(
				'name'          => __( '使用技術', 'portfolio-core' ),
				'singular_name' => __( '使用技術', 'portfolio-core' ),
				'add_new_item'  => __( '技術を追加', 'portfolio-core' ),
				'search_items'  => __( '技術を検索', 'portfolio-core' ),
				'not_found'     => __( '技術が見つかりません。', 'portfolio-core' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'tech',
				'with_front' => false,
			),
		)
	);
}

/**
 * 制作実績の管理画面一覧にサムネイル列を追加する。
 *
 * manage_{post_type}_posts_columns は列の定義を差し替える filter、
 * manage_{post_type}_posts_custom_column は各行の中身を出力する action。
 * 「管理画面を運用しやすくする」のは実案件で評価されやすい細部。
 *
 * @param array<string, string> $columns 既存の列定義。
 * @return array<string, string>
 */
add_filter( 'manage_work_posts_columns', 'pc_work_admin_columns' );
function pc_work_admin_columns( array $columns ): array {
	$new = array();

	foreach ( $columns as $key => $label ) {
		// チェックボックスの次にサムネイル列を差し込む。
		if ( 'title' === $key ) {
			$new['pc_thumbnail'] = __( 'サムネイル', 'portfolio-core' );
		}
		$new[ $key ] = $label;
	}

	return $new;
}

/**
 * サムネイル列の中身を出力する。
 *
 * @param string $column  列のキー。
 * @param int    $post_id 投稿 ID。
 */
add_action( 'manage_work_posts_custom_column', 'pc_work_admin_column_content', 10, 2 );
function pc_work_admin_column_content( string $column, int $post_id ): void {
	if ( 'pc_thumbnail' !== $column ) {
		return;
	}

	if ( has_post_thumbnail( $post_id ) ) {
		// get_the_post_thumbnail() はエスケープ済みの img タグを返す。
		echo get_the_post_thumbnail( $post_id, array( 60, 60 ) );
		return;
	}

	echo '<span aria-hidden="true">—</span>';
}

/**
 * 実務経験は「新しい順」に並べたいが、日付ではなく手動の並び順で管理する。
 *
 * page-attributes を supports に含めているため、各投稿に menu_order が付く。
 * pre_get_posts はメインクエリを実行前に書き換えるための filter 相当の action。
 * テンプレート側で WP_Query を書き直さずに並び順を変えられるため、
 * 既存サイトの改修でも多用する。
 *
 * @param WP_Query $query 実行前のクエリ。
 */
add_action( 'pre_get_posts', 'pc_adjust_archive_order' );
function pc_adjust_archive_order( WP_Query $query ): void {
	// 管理画面とサブクエリには適用しない。ここを忘れると管理画面の並び順まで壊れる。
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'work' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
		$query->set( 'posts_per_page', 12 );
	}
}
