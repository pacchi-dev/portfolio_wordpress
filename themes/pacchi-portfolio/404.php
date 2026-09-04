<?php
/**
 * 404 ページ。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container container--narrow section__body error-404">
	<p class="section__eyebrow">// 404</p>
	<h1 class="section__title"><?php esc_html_e( 'ページが見つかりません', 'pacchi-portfolio' ); ?></h1>
	<p class="section__lead">
		<?php esc_html_e( 'お探しのページは移動または削除された可能性があります。', 'pacchi-portfolio' ); ?>
	</p>
	<p>
		<a class="button button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'トップへ戻る', 'pacchi-portfolio' ); ?>
		</a>
	</p>
</div>

<?php
get_footer();
