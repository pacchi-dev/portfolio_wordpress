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
add_action( 'customize_register', 'pp_customize_register' );
function pp_customize_register( WP_Customize_Manager $wp_customize ): void {

	$wp_customize->add_section(
		'pp_site_options',
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
		'pp_accepting_work',
		array(
			'default'           => true,
			'sanitize_callback' => 'pp_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pp_accepting_work',
		array(
			'section' => 'pp_site_options',
			'label'   => __( '副業・業務委託の相談を受け付ける', 'pacchi-portfolio' ),
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'pp_availability_label',
		array(
			'default'           => '副業・業務委託のご相談を受付中',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pp_availability_label',
		array(
			'section'         => 'pp_site_options',
			'label'           => __( '受付中の表示文言', 'pacchi-portfolio' ),
			'type'            => 'text',
			// 受付中がオフのときは入力欄を隠す。
			'active_callback'  => static fn(): bool => (bool) get_theme_mod( 'pp_accepting_work', true ),
		)
	);

	/*
	 * お問い合わせフォームの表示切り替え。
	 *
	 * サーバーによっては PHP からのメール送信が制限され、wp_mail() が
	 * 失敗することがある（無料プランや、契約前のお試し期間など）。
	 * その環境では「送信できたように見えて届かない」より、
	 * フォームを出さずメールアドレスだけ案内するほうが親切。
	 *
	 * コードを消さずスイッチで切り替えられるようにしているのは、
	 * 環境ごとに動く／動かないが変わるため。移設先で送信できるなら戻せる。
	 */
	$wp_customize->add_setting(
		'pp_show_contact_form',
		array(
			'default'           => true,
			'sanitize_callback' => 'pp_sanitize_checkbox',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pp_show_contact_form',
		array(
			'section'     => 'pp_site_options',
			'label'       => __( 'お問い合わせフォームを表示する', 'pacchi-portfolio' ),
			'description' => __( 'メール送信ができないサーバーではオフにします。オフのときはメールアドレスの案内のみ表示されます。', 'pacchi-portfolio' ),
			'type'        => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'pp_footer_tagline',
		array(
			'default'           => '// Backend Engineer — Go · PHP / Laravel · AWS',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pp_footer_tagline',
		array(
			'section' => 'pp_site_options',
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
function pp_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

/**
 * 受付中かどうかを返す。
 *
 * @return bool
 */
function pp_is_accepting_work(): bool {
	return (bool) get_theme_mod( 'pp_accepting_work', true );
}

/**
 * 受付中の表示文言を返す。
 *
 * @return string
 */
function pp_availability_label(): string {
	return (string) get_theme_mod( 'pp_availability_label', '副業・業務委託のご相談を受付中' );
}
