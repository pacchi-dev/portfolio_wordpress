<?php
/**
 * 技術スタックセクション。
 *
 * ACF のテキストではなく technology タクソノミーから描画する。
 * 実績に技術を登録すればこのセクションにも反映される単一ソース構成。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$pp_terms = get_terms(
	array(
		'taxonomy'   => 'technology',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);

if ( is_wp_error( $pp_terms ) || empty( $pp_terms ) ) {
	return;
}

// 表示用のカテゴリ定義。ACF の choices と対応させる。
$pp_categories = array(
	'backend'  => __( 'Backend', 'pacchi-portfolio' ),
	'cms'      => __( 'CMS', 'pacchi-portfolio' ),
	'database' => __( 'Database / Cache', 'pacchi-portfolio' ),
	'cloud'    => __( 'Cloud / Infrastructure', 'pacchi-portfolio' ),
	'devops'   => __( 'Development / Operation', 'pacchi-portfolio' ),
	'frontend' => __( 'Frontend', 'pacchi-portfolio' ),
	'ai'       => __( 'AI', 'pacchi-portfolio' ),
);

$pp_level_order = array(
	'main'        => 0,
	'experienced' => 1,
	'familiar'    => 2,
);

$pp_level_tone = array(
	'main'        => 'accent',
	'experienced' => 'default',
	'familiar'    => 'muted',
);

// カテゴリごとに仕分ける。
$pp_grouped = array_fill_keys( array_keys( $pp_categories ), array() );

foreach ( $pp_terms as $pp_term ) {
	$category = (string) get_field( 'category', 'technology_' . $pp_term->term_id );
	$level    = (string) get_field( 'level', 'technology_' . $pp_term->term_id );
	$order    = (int) get_field( 'display_order', 'technology_' . $pp_term->term_id );

	if ( ! isset( $pp_grouped[ $category ] ) ) {
		continue;
	}

	$pp_grouped[ $category ][] = array(
		'name'  => $pp_term->name,
		'slug'  => $pp_term->slug,
		'level' => isset( $pp_level_order[ $level ] ) ? $level : 'experienced',
		'order' => $order,
	);
}

// Main → Experienced → Familiar の順に並べる。
foreach ( $pp_grouped as $key => $items ) {
	usort(
		$items,
		static function ( array $a, array $b ) use ( $pp_level_order ): int {
			$diff = $pp_level_order[ $a['level'] ] <=> $pp_level_order[ $b['level'] ];

			return 0 !== $diff ? $diff : ( $a['order'] <=> $b['order'] );
		}
	);

	$pp_grouped[ $key ] = $items;
}

pp_section_open(
	array(
		'id'      => 'skills',
		'eyebrow' => 'skills',
		'title'   => __( '技術スタック', 'pacchi-portfolio' ),
		'lead'    => __( '実務での関わり方に応じて 3 段階に分類しています。', 'pacchi-portfolio' ),
		'open'    => true,
	)
);
?>

<ul class="skill-legend">
	<li><span class="tag tag--accent">Main</span><span><?php esc_html_e( '実務で継続的に使用し、設計から対応できる', 'pacchi-portfolio' ); ?></span></li>
	<li><span class="tag tag--default">Experienced</span><span><?php esc_html_e( '実務での開発経験がある', 'pacchi-portfolio' ); ?></span></li>
	<li><span class="tag tag--muted">Familiar</span><span><?php esc_html_e( '業務で触れた経験がある', 'pacchi-portfolio' ); ?></span></li>
</ul>

<?php
/*
 * 既定では Main のみを表示する。
 *
 * 全 40 件超を一度に出すと、非エンジニアの閲覧者には
 * 「何ができる人か」が読み取れない情報量になる。
 * Experienced / Familiar は details に畳み、必要な人だけが開く形にした。
 *
 * JavaScript を使わず details/summary で実装しているのは、
 * 本テーマの他セクションと挙動を揃えるため。
 */
$pp_main  = array();
$pp_rest  = array();
$pp_rest_count = 0;

foreach ( $pp_grouped as $pp_key => $pp_items ) {
	$pp_main[ $pp_key ] = array_values(
		array_filter( $pp_items, static fn( array $i ): bool => 'main' === $i['level'] )
	);
	$pp_rest[ $pp_key ] = array_values(
		array_filter( $pp_items, static fn( array $i ): bool => 'main' !== $i['level'] )
	);
	$pp_rest_count += count( $pp_rest[ $pp_key ] );
}

pp_render_skill_grid( $pp_categories, $pp_main, $pp_level_tone );

if ( $pp_rest_count > 0 ) :
	?>
	<details class="skill-more">
		<summary class="skill-more__summary">
			<span><?php
				printf(
					/* translators: %d: 折りたたまれている技術の件数 */
					esc_html__( 'Experienced / Familiar もすべて表示（%d 件）', 'pacchi-portfolio' ),
					(int) $pp_rest_count
				);
			?></span>
			<?php pp_icon_chevron( 'skill-more__chevron' ); ?>
		</summary>
		<?php pp_render_skill_grid( $pp_categories, $pp_rest, $pp_level_tone ); ?>
	</details>
	<?php
endif;

pp_section_close();
