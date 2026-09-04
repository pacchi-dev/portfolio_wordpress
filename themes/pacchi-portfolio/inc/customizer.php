<?php
/**
 * カスタマイザー設定。
 *
 * ACF とどう使い分けるか:
 *   ACF        … そのページのコンテンツ（Hero の文言、実績の詳細など）
 *   Customizer … サイト全体に効く表示設定（受付状況、フッターの表記など）
 *
 * 受付状況は Hero 以外の場所にも影響しうるサイト全体の状態のため、
 * ページのコンテンツではなく Customizer に置いている。
 *
 * Customizer の値は get_theme_mod() で取得する。
 * ACF の get_field() と取得元が異なる点に注意（theme_mods_{theme} オプション）。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * カスタマイザーに設定項目を追加する。
 *
 * @param WP_Customize_Manager $wp_customize カスタマイザーのインスタンス。
 */
add_action( 'customize_register', 'mp_customize_register' );
function mp_customize_register( WP_Customize_Manager $wp_customize ): void {

	$wp_customize->add_section(
		'mp_site_options',
		array(
			'title'       => __( 'サイト設定', 'pacchi-portfolio' ),
			'description' => __( '受付状況やフッターなど、サイト全体に関わる表示設定。', 'pacchi-portfolio' ),
			'priority'    => 30,
		)
	);

	/*
	 * 受付中かどうか。
	 *
	 * sanitize_callback は必須。省略すると値が検証されないまま保存され、
	 * 権限のあるユーザーが任意の値を入れられる状態になる。
	 * WordPress のレビューでも指摘される項目。
	 */
	$wp_customize->add_setting(
		'mp_accepting_work',
		array(
			'default'           => true,
			'sanitize_callback' => 'mp_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'mp_accepting_work',
		array(
			'section' => 'mp_site_options',
			'label'   => __( '副業・業務委託の相談を受け付ける', 'pacchi-portfolio' ),
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'mp_availability_label',
		array(
			'default'           => '副業・業務委託のご相談を受付中',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'mp_availability_label',
		array(
			'section'         => 'mp_site_options',
			'label'           => __( '受付中の表示文言', 'pacchi-portfolio' ),
			'type'            => 'text',
			// 受付中がオフのときは入力欄を隠す。
			'active_callback'  => static fn(): bool => (bool) get_theme_mod( 'mp_accepting_work', true ),
		)
	);

	$wp_customize->add_setting(
		'mp_footer_tagline',
		array(
			'default'           => '// Backend Engineer — Go · PHP / Laravel · AWS',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'mp_footer_tagline',
		array(
			'section' => 'mp_site_options',
			'label'   => __( 'フッターの表記', 'pacchi-portfolio' ),
			'type'    => 'text',
		)
	);
}

/**
 * チェックボックス用のサニタイズ関数。
 *
 * WordPress にはチェックボックス用の標準サニタイズ関数が無いため自作する。
 *
 * @param mixed $value 入力値。
 * @return bool
 */
function mp_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

/**
 * 受付中かどうかを返す。
 *
 * @return bool
 */
function mp_is_accepting_work(): bool {
	return (bool) get_theme_mod( 'mp_accepting_work', true );
}

/**
 * 受付中の表示文言を返す。
 *
 * @return string
 */
function mp_availability_label(): string {
	return (string) get_theme_mod( 'mp_availability_label', '副業・業務委託のご相談を受付中' );
}
