<?php
/**
 * 制作実績（CPT work）のシードデータ投入スクリプト。
 *
 *   実行: docker exec portfolio_wp_cli wp --path=/var/www/html eval-file /scripts/seed-works.php
 *
 * seed.php と同じく wp eval-file で実行する。WordPress を読み込んだ状態で
 * 動くため、wp_insert_post / update_field / メディア API がそのまま使える。
 *
 * アイキャッチ画像もここで投入する。work 投稿だけ手作業で作ると
 * bin/setup.sh からの環境再現が途切れるため、画像を含めてコード化しておく。
 *
 * 冪等性: スラッグで既存投稿を探し、あれば更新・なければ作成する。
 * 画像も添付ファイルのファイル名で重複を判定し、二重登録しない。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

require_once '/scripts/seed-lib.php';

// ---------------------------------------------------------------------------
// 制作実績の投入
// ---------------------------------------------------------------------------
WP_CLI::log( '==> 制作実績を登録します' );

$pc_seed_path = '/scripts/seed/works.json';

if ( ! file_exists( $pc_seed_path ) ) {
	WP_CLI::error( "シードファイルが見つかりません: {$pc_seed_path}" );
}

$pc_works = json_decode( (string) file_get_contents( $pc_seed_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

if ( ! is_array( $pc_works ) ) {
	WP_CLI::error( "JSON の解析に失敗しました: {$pc_seed_path}" );
}

$pc_count      = 0;
$pc_menu_order = 0;

foreach ( $pc_works as $pc_item ) {
	++$pc_menu_order;

	$pc_slug  = (string) ( $pc_item['slug'] ?? '' );
	$pc_title = (string) ( $pc_item['title'] ?? '' );

	if ( '' === $pc_slug || '' === $pc_title ) {
		continue;
	}

	// スラッグで既存投稿を探す。get_page_by_path() は CPT にも使える。
	$pc_existing = get_page_by_path( $pc_slug, OBJECT, 'work' );

	$pc_postarr = array(
		'post_type'   => 'work',
		'post_status' => 'publish',
		'post_title'  => $pc_title,
		'post_name'   => $pc_slug,
		'menu_order'  => $pc_menu_order,
	);

	if ( $pc_existing ) {
		$pc_postarr['ID'] = $pc_existing->ID;
	}

	$pc_post_id = wp_insert_post( $pc_postarr, true );

	if ( is_wp_error( $pc_post_id ) ) {
		WP_CLI::warning( "投稿の作成に失敗しました: {$pc_slug} / " . $pc_post_id->get_error_message() );
		continue;
	}

	$pc_post_id = (int) $pc_post_id;

	update_field( 'period', (string) ( $pc_item['period'] ?? '' ), $pc_post_id );
	update_field( 'status', (string) ( $pc_item['status'] ?? '' ), $pc_post_id );
	update_field( 'github_url', (string) ( $pc_item['githubUrl'] ?? '' ), $pc_post_id );
	update_field( 'site_url', (string) ( $pc_item['siteUrl'] ?? '' ), $pc_post_id );
	update_field( 'summary', (string) ( $pc_item['summary'] ?? '' ), $pc_post_id );
	update_field( 'architecture', (string) ( $pc_item['architecture'] ?? '' ), $pc_post_id );
	update_field(
		'responsibilities',
		implode( "\n", (array) ( $pc_item['responsibilities'] ?? array() ) ),
		$pc_post_id
	);

	// 使用技術。append を false にして毎回同期する。
	$pc_tech = array_filter( array_map( 'strval', (array) ( $pc_item['tech'] ?? array() ) ) );

	if ( ! empty( $pc_tech ) ) {
		wp_set_object_terms( $pc_post_id, $pc_tech, 'technology', false );
	}

	// アイキャッチ画像。
	$pc_image = (string) ( $pc_item['image'] ?? '' );

	if ( '' !== $pc_image ) {
		$pc_attachment_id = pc_seed_import_image(
			$pc_image,
			(string) ( $pc_item['imageAlt'] ?? $pc_title ),
			$pc_post_id
		);

		if ( $pc_attachment_id ) {
			set_post_thumbnail( $pc_post_id, $pc_attachment_id );
		}
	}

	++$pc_count;
	WP_CLI::log( "    {$pc_title}" );
}

WP_CLI::log( "    {$pc_count} 件の制作実績を登録しました" );
