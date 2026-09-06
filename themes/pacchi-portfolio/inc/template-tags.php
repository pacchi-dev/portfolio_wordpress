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

	/*
	 * メニュー未設定時のフォールバック。
	 *
	 * トップページ内のアンカーだけでなく、投稿タイプアーカイブ（/works/）も
	 * 並べる必要があるため、「スラッグ => ラベル」ではなく
	 * URL を組み立て済みの配列として持つ。
	 */
	$items = array();

	/*
	 * 英語ラベルの下に日本語を小さく併記する。
	 *
	 * 発注者に非エンジニアが多いことを踏まえた対応。
	 * 日本語だけに置き換えるとラベルが横に伸びてヘッダーに収まらないため、
	 * 英語を主・日本語を副として 2 段で出す（日本の企業サイトで一般的な形）。
	 * 日本語ラベルは各セクションの見出しと揃えている。
	 */
	$anchors_before = array(
		'about'      => array( __( 'About', 'pacchi-portfolio' ), __( '私について', 'pacchi-portfolio' ) ),
		'services'   => array( __( 'What I Do', 'pacchi-portfolio' ), __( '対応領域', 'pacchi-portfolio' ) ),
		'skills'     => array( __( 'Skills', 'pacchi-portfolio' ), __( '使用技術', 'pacchi-portfolio' ) ),
		'experience' => array( __( 'Experience', 'pacchi-portfolio' ), __( '実務経験', 'pacchi-portfolio' ) ),
	);

	foreach ( $anchors_before as $anchor => $labels ) {
		$items[] = array(
			'url'   => home_url( '/#' . $anchor ),
			'label' => $labels[0],
			'sub'   => $labels[1],
		);
	}

	/*
	 * 制作実績は CPT `work` のアーカイブで、トップページ内のセクションではない。
	 * URL を '/works/' と直書きせず get_post_type_archive_link() を使うことで、
	 * register_post_type() の rewrite スラッグやパーマリンク設定を変えても
	 * リンクが壊れなくなる。CPT が未登録なら false を返すため、
	 * プラグイン無効時に空リンクを出さない防御にもなる。
	 */
	$works_url = get_post_type_archive_link( 'work' );

	if ( $works_url ) {
		$items[] = array(
			'url'   => $works_url,
			'label' => __( 'Works', 'pacchi-portfolio' ),
			'sub'   => __( '制作実績', 'pacchi-portfolio' ),
		);
	}

	$anchors_after = array(
		'github'  => array( __( 'GitHub', 'pacchi-portfolio' ), __( 'リポジトリ', 'pacchi-portfolio' ) ),
		'ai'      => array( __( 'AI Development', 'pacchi-portfolio' ), __( '生成AI活用', 'pacchi-portfolio' ) ),
		'work'    => array( __( 'Work With Me', 'pacchi-portfolio' ), __( 'ご依頼について', 'pacchi-portfolio' ) ),
		'contact' => array( __( 'Contact', 'pacchi-portfolio' ), __( 'お問い合わせ', 'pacchi-portfolio' ) ),
	);

	foreach ( $anchors_after as $anchor => $labels ) {
		$items[] = array(
			'url'   => home_url( '/#' . $anchor ),
			'label' => $labels[0],
			'sub'   => $labels[1],
		);
	}

	printf( '<ul class="%s">', esc_attr( $list_class ) );

	foreach ( $items as $item ) {
		printf(
			'<li><a href="%1$s"><span class="nav-label">%2$s</span><span class="nav-label__ja">%3$s</span></a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] ),
			esc_html( (string) ( $item['sub'] ?? '' ) )
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

/**
 * アーキテクチャ図を SVG で出力する。
 *
 * ACF のテキストエリアに書いた簡易記法をパースして描画する。
 *
 *   Client -> Load Balancer -> *Go API (Echo)* -> PostgreSQL, Redis
 *
 *   ->  列（処理の流れ）の区切り。矢印でつなぐ
 *   ,   同じ列に並ぶノード（分岐）
 *   *   前後を囲むとアクセント色になる
 *
 * ASCII アートを <pre> で出す方式は、全角と半角が混在すると桁が揃わず、
 * 実際に本サイトでも図が崩れていた。SVG にすることで
 *   - 桁揃えの問題が消える
 *   - 画像ファイルを増やさない（リクエストが増えない）
 *   - currentColor でテーマの配色に追従する
 *   - 拡大しても劣化しない
 * を同時に満たす。
 *
 * 記法として解釈できない文字列は、従来どおり <pre> にフォールバックする。
 * 既存の入力を壊さないための保険で、移行を伴う実装では定石。
 *
 * @param string $source ACF に入力された図の定義。
 */
function mp_render_architecture( string $source ): void {
	$source = trim( $source );

	if ( '' === $source ) {
		return;
	}

	$columns = mp_parse_architecture( $source );

	if ( empty( $columns ) ) {
		// 記法として解釈できないので、そのまま整形済みテキストとして出す。
		printf( '<pre class="arch-diagram">%s</pre>', esc_html( $source ) );
		return;
	}

	mp_output_architecture_svg( $columns );
}

/**
 * 簡易記法を列の配列へパースする。
 *
 * @param string $source 入力文字列。
 * @return array<int, array<int, array{label: string, accent: bool}>> 解釈できない場合は空配列。
 */
function mp_parse_architecture( string $source ): array {
	// 改行を含む従来の ASCII アートは対象外。1 行の記法だけを扱う。
	if ( preg_match( '/\R/', $source ) ) {
		return array();
	}

	// 全角矢印も受け付ける。
	$normalized = str_replace( array( '→', '＞' ), array( '->', '>' ), $source );

	if ( ! str_contains( $normalized, '->' ) ) {
		return array();
	}

	$columns = array();

	foreach ( explode( '->', $normalized ) as $column_source ) {
		$nodes = array();

		foreach ( preg_split( '/[,、]/', $column_source ) as $node_source ) {
			$label = trim( (string) $node_source );

			if ( '' === $label ) {
				continue;
			}

			// *…* で囲まれていればアクセント扱い。
			$accent = (bool) preg_match( '/^\*(.+)\*$/u', $label, $matches );

			if ( $accent ) {
				$label = trim( $matches[1] );
			}

			$nodes[] = array(
				'label'  => $label,
				'accent' => $accent,
			);
		}

		if ( empty( $nodes ) ) {
			return array();
		}

		$columns[] = $nodes;
	}

	return count( $columns ) >= 2 ? $columns : array();
}

/**
 * 文字列の表示幅を返す（全角を 2、半角を 1 として数える）。
 *
 * SVG はテキストの実寸を自動で測ってくれないため、
 * ボックスの幅を自前で見積もる必要がある。
 * mb_strwidth() は全角/半角の判定を標準で行う。
 *
 * @param string $text 対象文字列。
 * @return int 表示幅。
 */
function mp_text_width( string $text ): int {
	return mb_strwidth( $text, 'UTF-8' );
}

/**
 * パース済みの列から SVG を出力する。
 *
 * @param array<int, array<int, array{label: string, accent: bool}>> $columns 列の配列。
 */
function mp_output_architecture_svg( array $columns ): void {
	// レイアウト定数（px）。等幅フォント 13px を前提に見積もる。
	$char_w     = 7.9;   // 半角 1 文字ぶんの横幅
	$pad_x      = 16.0;  // ボックス左右の内側余白
	$box_h      = 44.0;  // ボックスの高さ
	$row_gap    = 14.0;  // 同じ列に積むノードの間隔
	$col_gap    = 46.0;  // 列と列の間隔（矢印を描く幅）
	$margin     = 2.0;   // 枠線が切れないための余白

	// 各列の幅と高さを先に計算する。
	$col_widths  = array();
	$col_heights = array();

	foreach ( $columns as $index => $nodes ) {
		$width = 0.0;

		foreach ( $nodes as $node ) {
			$width = max( $width, mp_text_width( $node['label'] ) * $char_w + $pad_x * 2 );
		}

		$col_widths[ $index ]  = round( $width, 1 );
		$col_heights[ $index ] = count( $nodes ) * $box_h + ( count( $nodes ) - 1 ) * $row_gap;
	}

	$total_w = array_sum( $col_widths ) + $col_gap * ( count( $columns ) - 1 ) + $margin * 2;
	$total_h = max( $col_heights ) + $margin * 2;

	printf(
		'<div class="arch-svg"><svg viewBox="0 0 %1$s %2$s" width="%1$s" height="%2$s" role="img" aria-label="%3$s" xmlns="http://www.w3.org/2000/svg">',
		esc_attr( (string) round( $total_w ) ),
		esc_attr( (string) round( $total_h ) ),
		esc_attr__( 'システム構成図', 'pacchi-portfolio' )
	);

	// 矢印の先端。SVG では marker を defs に定義して参照する。
	echo '<defs><marker id="arch-arrow" viewBox="0 0 8 8" refX="7" refY="4" markerWidth="6" markerHeight="6" orient="auto-start-reverse">'
		. '<path d="M0 0 L8 4 L0 8 z" fill="currentColor" /></marker></defs>';

	$x = $margin;

	foreach ( $columns as $index => $nodes ) {
		$col_w = $col_widths[ $index ];
		$y     = $margin + ( $total_h - $margin * 2 - $col_heights[ $index ] ) / 2;

		foreach ( $nodes as $node ) {
			$class = $node['accent'] ? 'arch-svg__box arch-svg__box--accent' : 'arch-svg__box';

			printf(
				'<rect class="%1$s" x="%2$s" y="%3$s" width="%4$s" height="%5$s" rx="8" />',
				esc_attr( $class ),
				esc_attr( (string) round( $x, 1 ) ),
				esc_attr( (string) round( $y, 1 ) ),
				esc_attr( (string) $col_w ),
				esc_attr( (string) $box_h )
			);

			printf(
				'<text class="%1$s" x="%2$s" y="%3$s" text-anchor="middle" dominant-baseline="central">%4$s</text>',
				esc_attr( $node['accent'] ? 'arch-svg__label arch-svg__label--accent' : 'arch-svg__label' ),
				esc_attr( (string) round( $x + $col_w / 2, 1 ) ),
				esc_attr( (string) round( $y + $box_h / 2, 1 ) ),
				esc_html( $node['label'] )
			);

			$y += $box_h + $row_gap;
		}

		// 次の列があれば矢印でつなぐ。
		if ( isset( $columns[ $index + 1 ] ) ) {
			$arrow_y = round( $total_h / 2, 1 );

			printf(
				'<line class="arch-svg__arrow" x1="%1$s" y1="%2$s" x2="%3$s" y2="%2$s" marker-end="url(#arch-arrow)" />',
				esc_attr( (string) round( $x + $col_w + 12, 1 ) ),
				esc_attr( (string) $arrow_y ),
				esc_attr( (string) round( $x + $col_w + $col_gap - 12, 1 ) )
			);
		}

		$x += $col_w + $col_gap;
	}

	echo '</svg></div>';
}

/**
 * 技術スタックのグリッドを出力する。
 *
 * Main のみのグリッドと、折りたたみ内の残りのグリッドで
 * 同じマークアップを使うため関数に切り出している。
 *
 * @param array<string, string>                                                        $categories カテゴリのキーと表示名。
 * @param array<string, array<int, array{name: string, slug: string, level: string}>>   $grouped    カテゴリごとの技術。
 * @param array<string, string>                                                        $level_tone レベルとタグの見た目の対応。
 */
function mp_render_skill_grid( array $categories, array $grouped, array $level_tone ): void {
	$has_any = false;

	foreach ( $categories as $key => $label ) {
		if ( ! empty( $grouped[ $key ] ) ) {
			$has_any = true;
			break;
		}
	}

	if ( ! $has_any ) {
		return;
	}

	echo '<div class="skill-grid">';

	foreach ( $categories as $key => $label ) {
		if ( empty( $grouped[ $key ] ) ) {
			continue;
		}

		printf(
			'<div class="skill-group"><h3 class="skill-group__title">%s</h3><ul class="tag-list">',
			esc_html( $label )
		);

		foreach ( $grouped[ $key ] as $item ) {
			$tone = $level_tone[ $item['level'] ] ?? 'default';
			$link = get_term_link( $item['slug'], 'technology' );

			printf(
				'<li><a class="tag tag--%1$s" href="%2$s">%3$s</a></li>',
				esc_attr( $tone ),
				esc_url( is_wp_error( $link ) ? '' : (string) $link ),
				esc_html( $item['name'] )
			);
		}

		echo '</ul></div>';
	}

	echo '</div>';
}
