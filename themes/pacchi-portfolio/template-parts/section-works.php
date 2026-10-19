<?php
/**
 * 制作実績セクション（トップページ）。
 *
 * ナビの項目のうち、制作実績だけがトップに現れない状態だった。
 * 発注者が最初に確認したいのは «何を作れるか» であり、
 * 流し読みしたときに実績が一切目に入らないのは構成として弱い。
 *
 * 全件は /works/ に置き、ここでは新しい 3 件だけを出す。
 * トップが長くなりすぎるのを避けつつ、アーカイブへ誘導する。
 *
 * サブループなので wp_reset_postdata() で必ず後始末する。
 * 忘れるとグローバルの $post が書き換わったままになり、
 * 以降のセクションが誤った投稿を表示する。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$pp_works_per_view = 3;

$pp_works_query = new WP_Query(
	array(
		'post_type'              => 'work',
		'post_status'            => 'publish',
		'posts_per_page'         => $pp_works_per_view,
		'orderby'                => 'menu_order',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_term_cache' => true,
	)
);

if ( ! $pp_works_query->have_posts() ) {
	return;
}

/*
 * アーカイブに続きがあるときだけ「すべて見る」を出す。
 *
 * wp_count_posts() は投稿タイプが未登録のとき publish を持たない
 * stdClass を返す。プラグイン無効時に Warning を出さないよう既定値を置く。
 */
$pp_works_counts = wp_count_posts( 'work' );
$pp_works_total  = (int) ( $pp_works_counts->publish ?? 0 );

pp_section_open(
	array(
		'id'      => 'works',
		'eyebrow' => 'works',
		'title'   => __( '制作実績', 'pacchi-portfolio' ),
		'lead'    => __( '実運用を想定して設計・実装した制作物です。実務経験とは別に掲載しています。', 'pacchi-portfolio' ),
		'surface' => false,
		'open'    => true,
	)
);
?>

<div class="work-grid">
	<?php
	while ( $pp_works_query->have_posts() ) :
		$pp_works_query->the_post();
		get_template_part( 'template-parts/card-work' );
	endwhile;
	?>
</div>

<?php if ( $pp_works_total > $pp_works_per_view ) : ?>
	<p class="works-more">
		<a class="button button--secondary" href="<?php echo esc_url( (string) get_post_type_archive_link( 'work' ) ); ?>">
			<?php
			printf(
				/* translators: %d: 制作実績の総件数 */
				esc_html__( '制作実績をすべて見る（%d 件）', 'pacchi-portfolio' ),
				(int) $pp_works_total
			);
			?>
		</a>
	</p>
<?php endif; ?>

<?php
pp_section_close();

wp_reset_postdata();
