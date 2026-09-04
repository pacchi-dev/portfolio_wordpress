<?php
/**
 * 制作実績のアーカイブ（/works/）。
 *
 * テンプレート階層では archive-work.php → archive.php → index.php の順で
 * 探索され、このファイルが最優先される。
 *
 * ここではメインループを使う。URL に対応するクエリは WordPress が
 * すでに実行済みで、並び順は pre_get_posts（プラグイン側）で調整している。
 * テンプレート内で WP_Query を作り直すと、ページネーションが壊れる。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="page-head">
	<div class="container">
		<p class="section__eyebrow">// works</p>
		<h1 class="page-head__title"><?php post_type_archive_title(); ?></h1>
		<p class="page-head__lead text-balance-ja">
			<?php esc_html_e( '実運用を想定して設計・実装した制作物です。実務経験とは別に掲載しています。', 'pacchi-portfolio' ); ?>
		</p>
	</div>
</div>

<div class="container section__body">
	<?php if ( have_posts() ) : ?>
		<div class="work-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/card-work' );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => __( '前へ', 'pacchi-portfolio' ),
				'next_text' => __( '次へ', 'pacchi-portfolio' ),
			)
		);
		?>
	<?php else : ?>
		<p class="notice-inline">
			<?php esc_html_e( '制作実績は準備中です。', 'pacchi-portfolio' ); ?>
		</p>
	<?php endif; ?>
</div>

<?php
get_footer();
