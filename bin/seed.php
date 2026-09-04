<?php
/**
 * シードデータ投入スクリプト。
 *
 * Next.js 版の src/data/*.ts から書き出した JSON を読み、
 * カスタム投稿タイプとタクソノミーへ登録する。
 *
 *   実行: docker exec portfolio_wp_cli wp --path=/var/www/html eval-file /scripts/seed.php
 *
 * wp eval-file は WordPress を読み込んだ状態で PHP を実行する。
 * WP_Query / wp_insert_post など全ての API が使えるため、
 * 実案件のデータ移行でも定番の手段。
 *
 * 冪等性: スラッグで既存投稿を探し、あれば更新・なければ作成する。
 * 移行スクリプトは何度も流し直すことになるため、常に冪等に書く。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * JSON ファイルを読み込む。
 *
 * @param string $name ファイル名。
 * @return array<mixed>
 */
function pc_seed_load( string $name ): array {
	$path = '/scripts/seed/' . $name;

	if ( ! file_exists( $path ) ) {
		WP_CLI::error( "シードファイルが見つかりません: {$path}" );
	}

	$data = json_decode( (string) file_get_contents( $path ), true );

	if ( ! is_array( $data ) ) {
		WP_CLI::error( "JSON の解析に失敗しました: {$path}" );
	}

	return $data;
}

/**
 * スラッグから既存投稿の ID を返す。無ければ 0。
 *
 * @param string $post_type 投稿タイプ。
 * @param string $slug      スラッグ。
 * @return int
 */
function pc_seed_find( string $post_type, string $slug ): int {
	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'name'             => $slug,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => false,
		)
	);

	return empty( $posts ) ? 0 : (int) $posts[0];
}

// ---------------------------------------------------------------------------
// 1. 使用技術（タクソノミー technology）
// ---------------------------------------------------------------------------
WP_CLI::log( '==> 使用技術を登録します' );

$skill_categories = pc_seed_load( 'skills.json' );
$tech_count       = 0;
$order            = 0;

foreach ( $skill_categories as $category ) {
	$category_key = (string) ( $category['id'] ?? 'backend' );

	foreach ( (array) ( $category['skills'] ?? array() ) as $skill ) {
		$name  = (string) ( $skill['name'] ?? '' );
		$level = (string) ( $skill['level'] ?? 'experienced' );

		if ( '' === $name ) {
			continue;
		}

		$order += 10;

		$term = term_exists( $name, 'technology' );

		if ( ! $term ) {
			$term = wp_insert_term( $name, 'technology' );

			if ( is_wp_error( $term ) ) {
				WP_CLI::warning( "技術の登録に失敗: {$name} — " . $term->get_error_message() );
				continue;
			}
		}

		$term_id = (int) $term['term_id'];

		// ターム メタは "technology_{term_id}" という形式で指定する（ACF の仕様）。
		update_field( 'level', $level, 'technology_' . $term_id );
		update_field( 'category', $category_key, 'technology_' . $term_id );
		update_field( 'display_order', $order, 'technology_' . $term_id );

		++$tech_count;
	}
}

WP_CLI::log( "    {$tech_count} 件の技術を登録しました" );

// ---------------------------------------------------------------------------
// 2. 実務経験（CPT experience）
// ---------------------------------------------------------------------------
WP_CLI::log( '==> 実務経験を登録します' );

$experiences = pc_seed_load( 'experience.json' );
$exp_count   = 0;
$menu_order  = 0;

foreach ( $experiences as $item ) {
	$slug  = (string) ( $item['id'] ?? '' );
	$title = (string) ( $item['title'] ?? '' );

	if ( '' === $slug || '' === $title ) {
		continue;
	}

	++$menu_order;

	$postarr = array(
		'post_type'   => 'experience',
		'post_title'  => $title,
		'post_name'   => $slug,
		'post_status' => 'publish',
		'menu_order'  => $menu_order,
	);

	$existing = pc_seed_find( 'experience', $slug );

	if ( $existing > 0 ) {
		$postarr['ID'] = $existing;
		$post_id       = wp_update_post( $postarr, true );
	} else {
		$post_id = wp_insert_post( $postarr, true );
	}

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "実務経験の登録に失敗: {$title} — " . $post_id->get_error_message() );
		continue;
	}

	$post_id = (int) $post_id;
	$period  = (string) ( $item['period'] ?? '' );

	update_field( 'period', $period, $post_id );
	update_field( 'duration', (string) ( $item['duration'] ?? '' ), $post_id );
	update_field( 'is_current', str_contains( $period, '現在' ), $post_id );
	update_field( 'industry', (string) ( $item['industry'] ?? '' ), $post_id );
	update_field( 'role', (string) ( $item['role'] ?? '' ), $post_id );
	update_field( 'team_size', (string) ( $item['teamSize'] ?? '' ), $post_id );
	update_field( 'summary', (string) ( $item['summary'] ?? '' ), $post_id );
	update_field( 'responsibilities', implode( "\n", (array) ( $item['responsibilities'] ?? array() ) ), $post_id );
	update_field( 'showcase', implode( "\n", (array) ( $item['showcase'] ?? array() ) ), $post_id );
	update_field( 'note', (string) ( $item['note'] ?? '' ), $post_id );

	// 使用技術をタクソノミーとして関連付ける。
	$tech = array_filter( array_map( 'strval', (array) ( $item['tech'] ?? array() ) ) );

	if ( ! empty( $tech ) ) {
		// 存在しない技術はここで作られる。append を false にして毎回同期する。
		wp_set_object_terms( $post_id, $tech, 'technology', false );
	}

	++$exp_count;
	WP_CLI::log( "    [{$post_id}] {$title}" );
}

WP_CLI::log( "    {$exp_count} 件の実務経験を登録しました" );

WP_CLI::success( 'シードデータの投入が完了しました。' );
