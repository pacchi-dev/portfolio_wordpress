<?php
/**
 * 技術タームのアーカイブ（/tech/{slug}/）。
 *
 * テンプレート階層では
 *   taxonomy-technology-{term}.php → taxonomy-technology.php
 *   → taxonomy.php → archive.php → index.php
 * の順で探索される。
 *
 * このアーカイブは register_taxonomy() を呼んだだけで自動生成されており、
 * ルーティングもクエリも自分では書いていない。
 * 使用技術を ACF のテキストではなくタクソノミーにした利点がここに出る。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pp_term = get_queried_object();
?>

<div class="page-head">
	<div class="container">
		<p class="section__eyebrow">// technology</p>
		<h1 class="page-head__title"><?php echo esc_html( $pp_term instanceof WP_Term ? $pp_term->name : '' ); ?></h1>
		<p class="page-head__lead">
			<?php
			printf(
				/* translators: %s: 技術名 */
				esc_html__( '%s を使用した実務経験・制作実績です。', 'pacchi-portfolio' ),
				esc_html( $pp_term instanceof WP_Term ? $pp_term->name : '' )
			);
			?>
		</p>
	</div>
</div>

<div class="container section__body">
	<?php if ( have_posts() ) : ?>
		<ul class="term-list">
			<?php
			while ( have_posts() ) :
				the_post();
				$pp_type = get_post_type();
				?>
				<li>
					<a class="term-item" href="<?php the_permalink(); ?>">
						<span class="term-item__type">
							<?php echo esc_html( 'work' === $pp_type ? __( '制作実績', 'pacchi-portfolio' ) : __( '実務経験', 'pacchi-portfolio' ) ); ?>
						</span>
						<span class="term-item__title text-balance-ja"><?php the_title(); ?></span>
						<?php $pp_period = (string) get_field( 'period' ); ?>
						<?php if ( '' !== $pp_period ) : ?>
							<span class="term-item__period"><?php echo esc_html( $pp_period ); ?></span>
						<?php endif; ?>
					</a>
				</li>
				<?php
			endwhile;
			?>
		</ul>

		<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
	<?php else : ?>
		<p class="notice-inline"><?php esc_html_e( '該当する項目がありません。', 'pacchi-portfolio' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
