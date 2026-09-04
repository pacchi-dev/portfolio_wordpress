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

$mp_terms = get_terms(
	array(
		'taxonomy'   => 'technology',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);

if ( is_wp_error( $mp_terms ) || empty( $mp_terms ) ) {
	return;
}

// 表示用のカテゴリ定義。ACF の choices と対応させる。
$mp_categories = array(
	'backend'  => __( 'Backend', 'pacchi-portfolio' ),
	'database' => __( 'Database / Cache', 'pacchi-portfolio' ),
	'cloud'    => __( 'Cloud / Infrastructure', 'pacchi-portfolio' ),
	'devops'   => __( 'Development / Operation', 'pacchi-portfolio' ),
	'frontend' => __( 'Frontend', 'pacchi-portfolio' ),
	'ai'       => __( 'AI', 'pacchi-portfolio' ),
);

$mp_level_order = array(
	'main'        => 0,
	'experienced' => 1,
	'familiar'    => 2,
);

$mp_level_tone = array(
	'main'        => 'accent',
	'experienced' => 'default',
	'familiar'    => 'muted',
);

// カテゴリごとに仕分ける。
$mp_grouped = array_fill_keys( array_keys( $mp_categories ), array() );

foreach ( $mp_terms as $mp_term ) {
	$category = (string) get_field( 'category', 'technology_' . $mp_term->term_id );
	$level    = (string) get_field( 'level', 'technology_' . $mp_term->term_id );
	$order    = (int) get_field( 'display_order', 'technology_' . $mp_term->term_id );

	if ( ! isset( $mp_grouped[ $category ] ) ) {
		continue;
	}

	$mp_grouped[ $category ][] = array(
		'name'  => $mp_term->name,
		'slug'  => $mp_term->slug,
		'level' => isset( $mp_level_order[ $level ] ) ? $level : 'experienced',
		'order' => $order,
	);
}

// Main → Experienced → Familiar の順に並べる。
foreach ( $mp_grouped as $key => $items ) {
	usort(
		$items,
		static function ( array $a, array $b ) use ( $mp_level_order ): int {
			$diff = $mp_level_order[ $a['level'] ] <=> $mp_level_order[ $b['level'] ];

			return 0 !== $diff ? $diff : ( $a['order'] <=> $b['order'] );
		}
	);

	$mp_grouped[ $key ] = $items;
}

mp_section_open(
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

<div class="skill-grid">
	<?php foreach ( $mp_categories as $mp_key => $mp_label ) : ?>
		<?php if ( empty( $mp_grouped[ $mp_key ] ) ) { continue; } ?>
		<div class="skill-group">
			<h3 class="skill-group__title"><?php echo esc_html( $mp_label ); ?></h3>
			<ul class="tag-list">
				<?php foreach ( $mp_grouped[ $mp_key ] as $mp_item ) : ?>
					<li>
						<a class="tag tag--<?php echo esc_attr( $mp_level_tone[ $mp_item['level'] ] ); ?>"
						   href="<?php echo esc_url( get_term_link( $mp_item['slug'], 'technology' ) ); ?>">
							<?php echo esc_html( $mp_item['name'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>

<?php
mp_section_close();
