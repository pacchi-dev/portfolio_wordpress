<?php
/**
 * シードスクリプトの共通処理。
 *
 * seed-front-page.php と seed-works.php の両方から読み込む。
 * wp eval-file はファイルごとに独立して実行されるため、
 * 片方で定義した関数はもう片方からは見えない。共通処理はここに置く。
 *
 *   使い方: require_once '/scripts/seed-lib.php';
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/*
 * メディア関連の関数はフロント・管理画面のどちらでも自動では読み込まれない。
 * WP-CLI から使う場合は明示的に require する必要がある。
 * media_handle_sideload() を使う移行スクリプトで最頻出のつまずきどころ。
 */
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

const PC_SEED_IMAGE_DIR = '/scripts/seed/images/';

/**
 * 画像をメディアライブラリへ取り込み、添付ファイル ID を返す。
 *
 * 同じファイル名の添付が既にあればそれを再利用する。
 * ローカルファイルからの取り込みなので media_sideload_image() ではなく
 * wp_upload_bits() + wp_insert_attachment() を使う。
 *
 * @param string $file_name seed/images/ 配下のファイル名。
 * @param string $alt       代替テキスト。
 * @param int    $post_id   紐付ける投稿 ID。
 * @return int 添付ファイル ID。失敗時は 0。
 */
function pc_seed_import_image( string $file_name, string $alt, int $post_id ): int {
	$path = PC_SEED_IMAGE_DIR . $file_name;

	if ( ! file_exists( $path ) ) {
		WP_CLI::warning( "画像が見つかりません: {$path}" );
		return 0;
	}

	// 既に取り込み済みならそれを使う（冪等性）。
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pc_seed_source',        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file_name,               // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
	}

	$upload = wp_upload_bits( $file_name, null, (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( ! empty( $upload['error'] ) ) {
		WP_CLI::warning( "アップロードに失敗しました: {$upload['error']}" );
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => (string) wp_check_filetype( $upload['file'] )['type'],
			'post_title'     => sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$post_id
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		WP_CLI::warning( "添付ファイルの作成に失敗しました: {$file_name}" );
		return 0;
	}

	/*
	 * サムネイル各サイズの生成。
	 * これを呼ばないと add_image_size() で定義した 'mp-work-card' が作られず、
	 * the_post_thumbnail( 'mp-work-card' ) がフルサイズ画像を返してしまう。
	 */
	wp_update_attachment_metadata(
		$attachment_id,
		wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
	);

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $attachment_id, '_pc_seed_source', $file_name );

	return (int) $attachment_id;
}
