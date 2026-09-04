<?php
/**
 * テンプレートから使う出力ヘルパ。
 *
 * WordPress では「出力する直前にエスケープする」のが原則
 * （Late Escaping）。テンプレートに esc_html() が散らばるのは正しい状態だが、
 * 繰り返し現れるパターンだけをここに集約している。
 *
 * エスケープ関数の使い分け:
 *   esc_html()     テキストノード
 *   esc_attr()     属性値
 *   esc_url()      href / src
 *   wp_kses_post() 投稿本文相当の HTML を許可したい場合
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * 改行区切りのテキストを配列に変換する。
 *
 * ACF 無料版には Repeater が無いため、箇条書きは textarea に
 * 改行区切りで入力する運用にしている（docs/decisions.md D-6）。
 * 空行と前後の空白を除去して返す。
 *
 * @param string|null $text 改行区切りのテキスト。
 * @return string[]
 */
function mp_lines_to_array( ?string $text ): array {
	if ( null === $text || '' === trim( $text ) ) {
		return array();
	}

	/*
	 * /u（UTF-8 モード）は必須。
	 * \R は改行のほかに \x85（NEL）にもマッチするが、/u が無いと PCRE は
	 * バイト単位で動作するため、日本語の UTF-8 継続バイト 0x85 を改行と誤認し、
	 * マルチバイト文字の途中で分割してしまう。
	 * PHP で日本語を正規表現処理する際の典型的な不具合。
	 */
	$lines = preg_split( '/\R/u', $text );

	if ( false === $lines ) {
		return array();
	}

	$lines = array_map( 'trim', $lines );

	return array_values( array_filter( $lines, static fn( string $line ): bool => '' !== $line ) );
}

/**
 * 箇条書きを <ul> として出力する。
 *
 * @param string[] $items      項目。
 * @param string   $list_class ul に付けるクラス。
 * @param string   $item_class li に付けるクラス。
 */
function mp_render_list( array $items, string $list_class = '', string $item_class = '' ): void {
	if ( empty( $items ) ) {
		return;
	}

	printf( '<ul class="%s">', esc_attr( $list_class ) );

	foreach ( $items as $item ) {
		printf(
			'<li class="%1$s">%2$s</li>',
			esc_attr( $item_class ),
			esc_html( $item )
		);
	}

	echo '</ul>';
}

/**
 * タグ（技術名など）の一覧を出力する。
 *
 * @param string[] $items 表示する文字列。
 * @param string   $tone  'default' | 'accent' | 'muted'。
 */
function mp_render_tags( array $items, string $tone = 'default' ): void {
	if ( empty( $items ) ) {
		return;
	}

	echo '<ul class="tag-list">';

	foreach ( $items as $item ) {
		printf(
			'<li><span class="tag tag--%1$s">%2$s</span></li>',
			esc_attr( $tone ),
			esc_html( $item )
		);
	}

	echo '</ul>';
}

/**
 * 開閉できるセクションの開始タグを出力する。
 *
 * 移植元（Next.js 版）と同じく details / summary を使い、
 * JavaScript なしで開閉できるようにしている。
 *
 * @param array{
 *     id: string,
 *     title: string,
 *     eyebrow?: string,
 *     lead?: string,
 *     surface?: bool,
 *     open?: bool,
 * } $args セクションの設定。
 */
function mp_section_open( array $args ): void {
	$id      = (string) ( $args['id'] ?? '' );
	$title   = (string) ( $args['title'] ?? '' );
	$eyebrow = (string) ( $args['eyebrow'] ?? '' );
	$lead    = (string) ( $args['lead'] ?? '' );
	$surface = ! empty( $args['surface'] );
	$open    = ! isset( $args['open'] ) || (bool) $args['open'];

	printf(
		'<section id="%1$s" class="section%2$s" aria-labelledby="%1$s-heading">',
		esc_attr( $id ),
		$surface ? ' section--surface' : ''
	);

	printf(
		'<details class="section__details" data-section%s>',
		$open ? ' open' : ''
	);

	echo '<summary class="section__summary"><span class="section__summary-inner"><span>';

	if ( '' !== $eyebrow ) {
		printf(
			'<span class="section__eyebrow">// %s</span>',
			esc_html( $eyebrow )
		);
	}

	printf(
		'<h2 id="%1$s-heading" class="section__title">%2$s</h2>',
		esc_attr( $id ),
		esc_html( $title )
	);

	echo '</span>';
	mp_icon_chevron( 'section__chevron' );
	echo '</span></summary>';

	echo '<div class="section__body">';

	if ( '' !== $lead ) {
		printf(
			'<p class="section__lead text-balance-ja">%s</p>',
			esc_html( $lead )
		);
	}
}

/**
 * 開閉セクションの終了タグを出力する。
 */
function mp_section_close(): void {
	echo '</div></details></section>';
}

/**
 * 下向きシェブロンのアイコンを出力する。
 *
 * @param string $class SVG に付けるクラス。
 */
function mp_icon_chevron( string $class = '' ): void {
	printf(
		'<svg class="%s" width="20" height="20" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 6l4.5 4.5L12.5 6" /></svg>',
		esc_attr( $class )
	);
}

/**
 * ヘッダーのナビゲーションを出力する。
 *
 * 「外観 > メニュー」で primary に割り当てがあればそれを使い、
 * 未設定ならセクション一覧のフォールバックを出す。
 *
 * wp_nav_menu() が出力する ul/li の構造をそのまま使えるよう、
 * items_wrap でクラスを差し替えている。Walker を自作するほどの
 * カスタマイズは不要なため、まずは items_wrap で足りるか検討するのが定石。
 *
 * @param string $list_class ul に付けるクラス。
 */
function mp_primary_menu( string $list_class ): void {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'items_wrap'     => '<ul class="' . esc_attr( $list_class ) . '">%3$s</ul>',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);

		return;
	}

	// メニュー未設定時のフォールバック。
	$items = array(
		'about'      => __( 'About', 'pacchi-portfolio' ),
		'services'   => __( 'What I Do', 'pacchi-portfolio' ),
		'skills'     => __( 'Skills', 'pacchi-portfolio' ),
		'experience' => __( 'Experience', 'pacchi-portfolio' ),
		'ai'         => __( 'AI Development', 'pacchi-portfolio' ),
		'work'       => __( 'Work With Me', 'pacchi-portfolio' ),
		'contact'    => __( 'Contact', 'pacchi-portfolio' ),
	);

	printf( '<ul class="%s">', esc_attr( $list_class ) );

	foreach ( $items as $anchor => $label ) {
		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( home_url( '/#' . $anchor ) ),
			esc_html( $label )
		);
	}

	echo '</ul>';
}

/**
 * What I Do カードのアイコンを出力する。
 *
 * 外部アイコンライブラリを読み込まず、インライン SVG で描画する。
 * リクエスト数を増やさず、currentColor で配色にも追従できる。
 *
 * @param string $key アイコンの種類。
 */
function mp_service_icon( string $key ): void {
	$paths = array(
		'backend' => '<rect x="3" y="4" width="18" height="7" rx="1.5" /><rect x="3" y="13" width="18" height="7" rx="1.5" /><path d="M6.5 7.5h.01M6.5 16.5h.01M10 7.5h4M10 16.5h4" />',
		'cloud'   => '<path d="M17.5 18.5h-11a4 4 0 0 1-.6-7.96 5.5 5.5 0 0 1 10.73-1.4A4.5 4.5 0 0 1 17.5 18.5Z" />',
		'design'  => '<circle cx="12" cy="12" r="8.5" /><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z" />',
		'ai'      => '<path d="M12 3.5 13.8 9l5.7 1.8-5.7 1.8L12 18.5l-1.8-5.9-5.7-1.8L10.2 9 12 3.5Z" /><path d="M18.5 15.5l.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9.9-2.6Z" />',
	);

	$path = $paths[ $key ] ?? $paths['backend'];

	printf(
		'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
		// SVG の path は固定文字列のため wp_kses でタグを許可して出力する。
		wp_kses(
			$path,
			array(
				'path'   => array( 'd' => array() ),
				'rect'   => array( 'x' => array(), 'y' => array(), 'width' => array(), 'height' => array(), 'rx' => array() ),
				'circle' => array( 'cx' => array(), 'cy' => array(), 'r' => array() ),
			)
		)
	);
}
