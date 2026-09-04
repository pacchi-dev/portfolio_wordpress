<?php
/**
 * トップページのシードデータ投入。
 *
 *   実行: docker exec portfolio_wp_cli wp --path=/var/www/html eval-file /scripts/seed-front-page.php
 *
 * フロントページ用の固定ページを作成し、「設定 > 表示設定」で
 * それをフロントページに指定したうえで ACF の値を投入する。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

$path = '/scripts/seed/front-page.json';

if ( ! file_exists( $path ) ) {
	WP_CLI::error( "シードファイルが見つかりません: {$path}" );
}

$data = json_decode( (string) file_get_contents( $path ), true );

if ( ! is_array( $data ) ) {
	WP_CLI::error( 'JSON の解析に失敗しました。' );
}

// ---------------------------------------------------------------------------
// 1. フロントページ用の固定ページを用意する
// ---------------------------------------------------------------------------
WP_CLI::log( '==> フロントページを用意します' );

$front_pages = get_posts(
	array(
		'post_type'      => 'page',
		'name'           => 'home',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

if ( ! empty( $front_pages ) ) {
	$page_id = (int) $front_pages[0];
	WP_CLI::log( "    既存の固定ページを使用します (ID: {$page_id})" );
} else {
	$page_id = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'Home',
			'post_name'   => 'home',
			'post_status' => 'publish',
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( '固定ページの作成に失敗しました: ' . $page_id->get_error_message() );
	}

	$page_id = (int) $page_id;
	WP_CLI::log( "    固定ページを作成しました (ID: {$page_id})" );
}

// 「設定 > 表示設定」でフロントページに指定する。
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );

// ---------------------------------------------------------------------------
// 2. Hero
// ---------------------------------------------------------------------------
WP_CLI::log( '==> Hero を投入します' );

$profile = (array) ( $data['profile'] ?? array() );
$role    = (string) ( $profile['role'] ?? 'Backend Engineer' );
$parts   = explode( ' ', $role, 2 );

update_field( 'hero_role_1', $parts[0] ?? 'Backend', $page_id );
update_field( 'hero_role_2', $parts[1] ?? 'Engineer', $page_id );
update_field( 'hero_name', (string) ( $profile['name'] ?? '' ), $page_id );
update_field( 'hero_stack', implode( "\n", (array) ( $profile['stack'] ?? array() ) ), $page_id );
update_field( 'hero_lead', (string) ( $profile['heroLead'] ?? '' ), $page_id );

$highlights = array();

foreach ( array_values( (array) ( $profile['highlights'] ?? array() ) ) as $i => $item ) {
	$highlights[ 'item_' . ( $i + 1 ) ] = array(
		'label' => (string) ( $item['label'] ?? '' ),
		'value' => (string) ( $item['value'] ?? '' ),
	);
}

update_field( 'hero_highlights', $highlights, $page_id );

// 受付状況はカスタマイザー側の設定。
set_theme_mod( 'mp_accepting_work', true );
set_theme_mod( 'mp_availability_label', (string) ( $profile['availabilityLabel'] ?? '副業・業務委託のご相談を受付中' ) );

// ---------------------------------------------------------------------------
// 3. About
// ---------------------------------------------------------------------------
WP_CLI::log( '==> About を投入します' );

update_field( 'about_title', 'バックエンドを軸に、設計から運用まで', $page_id );
// 段落は空行区切りで保存する。
update_field( 'about_body', implode( "\n\n", (array) ( $profile['about'] ?? array() ) ), $page_id );

// ---------------------------------------------------------------------------
// 4. What I Do
// ---------------------------------------------------------------------------
WP_CLI::log( '==> What I Do を投入します' );

update_field( 'services_title', '対応できる領域', $page_id );
update_field( 'services_lead', '実装だけでなく、要件整理・設計・運用までを含めて対応します。', $page_id );

// 移植元の id とアイコンの対応。
$icon_map = array(
	'backend' => 'backend',
	'cloud'   => 'cloud',
	'design'  => 'design',
	'ai'      => 'ai',
);

foreach ( array_values( (array) ( $data['services'] ?? array() ) ) as $i => $service ) {
	$slot = $i + 1;

	if ( $slot > 4 ) {
		break;
	}

	update_field(
		"service_{$slot}",
		array(
			'title'       => (string) ( $service['title'] ?? '' ),
			'icon'        => $icon_map[ (string) ( $service['id'] ?? '' ) ] ?? 'backend',
			'description' => (string) ( $service['description'] ?? '' ),
			'tags'        => implode( "\n", (array) ( $service['tags'] ?? array() ) ),
		),
		$page_id
	);
}

// ---------------------------------------------------------------------------
// 5. AI Development
// ---------------------------------------------------------------------------
WP_CLI::log( '==> AI Development を投入します' );

$ai = (array) ( $data['ai'] ?? array() );

update_field( 'ai_title', '生成AIを開発プロセスへ組み込む', $page_id );
update_field( 'ai_lead', (string) ( $ai['lead'] ?? '' ), $page_id );
update_field( 'ai_body', implode( "\n\n", (array) ( $ai['body'] ?? array() ) ), $page_id );
update_field( 'ai_points', implode( "\n", (array) ( $ai['points'] ?? array() ) ), $page_id );
update_field( 'ai_flow', implode( "\n", (array) ( $ai['flow'] ?? array() ) ), $page_id );

// ---------------------------------------------------------------------------
// 6. Work With Me
// ---------------------------------------------------------------------------
WP_CLI::log( '==> Work With Me を投入します' );

$work = (array) ( $data['work'] ?? array() );

update_field( 'work_title', 'ご依頼いただけること', $page_id );
update_field( 'work_lead', (string) ( $work['lead'] ?? '' ), $page_id );
update_field( 'work_services', implode( "\n", (array) ( $work['services'] ?? array() ) ), $page_id );
update_field( 'work_note', (string) ( $work['note'] ?? '' ), $page_id );

$conditions = array();

foreach ( (array) ( $work['conditions'] ?? array() ) as $condition ) {
	$key = strtolower( (string) ( $condition['label'] ?? '' ) );

	if ( in_array( $key, array( 'remote', 'availability', 'hours', 'contract' ), true ) ) {
		$conditions[ $key ] = (string) ( $condition['value'] ?? '' );
	}
}

update_field( 'work_conditions', $conditions, $page_id );

// ---------------------------------------------------------------------------
// 7. Contact
// ---------------------------------------------------------------------------
WP_CLI::log( '==> Contact を投入します' );

update_field( 'contact_title', 'お問い合わせ', $page_id );
update_field( 'contact_lead', 'お仕事のご相談・ご質問は以下からお気軽にご連絡ください。', $page_id );
update_field( 'contact_email', 'contact@pacchi.net', $page_id );
update_field( 'contact_github', 'https://github.com/pacchi-dev', $page_id );

// ---------------------------------------------------------------------------
// 8. サイトアイコン（favicon）
// ---------------------------------------------------------------------------
WP_CLI::log( '==> サイトアイコンを設定します' );

if ( (int) get_option( 'site_icon' ) > 0 ) {
	WP_CLI::log( '    設定済みです（スキップ）' );
} else {
	$icon_source = '/var/www/html/wp-content/themes/pacchi-portfolio/assets/img/site-icon.png';

	if ( ! file_exists( $icon_source ) || 0 === (int) filesize( $icon_source ) ) {
		WP_CLI::warning( 'サイトアイコンの元画像が見つかりません: ' . $icon_source );
	} else {
		// media_handle_sideload に必要な WordPress 側のヘルパを読み込む。
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// 元ファイルを移動されないよう、一時ファイルへコピーしてから渡す。
		$tmp = wp_tempnam( 'site-icon.png' );
		copy( $icon_source, $tmp );

		$attachment_id = media_handle_sideload(
			array(
				'name'     => 'site-icon.png',
				'tmp_name' => $tmp,
			),
			0,
			'Site Icon'
		);

		if ( is_wp_error( $attachment_id ) ) {
			WP_CLI::warning( 'サイトアイコンの登録に失敗: ' . $attachment_id->get_error_message() );
			@unlink( $tmp );
		} else {
			update_option( 'site_icon', (int) $attachment_id );
			WP_CLI::log( "    登録しました (attachment ID: {$attachment_id})" );
		}
	}
}

WP_CLI::success( "トップページの内容を投入しました (page_on_front = {$page_id})" );
