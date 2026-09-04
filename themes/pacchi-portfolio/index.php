<?php
/**
 * テンプレート階層の最終フォールバック。
 *
 * WordPress は URL の種類ごとに決められた順序でテンプレートを探し、
 * 見つからなければ最後にこのファイルを使う。
 * 例: /works/foo/ なら
 *   single-work.php → single.php → singular.php → index.php
 *
 * index.php が存在しないテーマは WordPress にテーマとして認識されない。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="container container--narrow section__body">
	<?php
	if ( have_posts() ) :
		/*
		 * The Loop。
		 * have_posts() / the_post() は、現在の URL に対して WordPress が
		 * 事前に実行したメインクエリの結果を 1 件ずつ進める。
		 * the_post() が「現在の投稿」をグローバルに設定するため、
		 * 以降の the_title() 等がその投稿の情報を返すようになる。
		 */
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'entry' ); ?>>
				<h1 class="entry__title"><?php the_title(); ?></h1>
				<div class="entry__content">
					<?php the_content(); ?>
				</div>
			</article>
			<?php
		endwhile;

		the_posts_pagination(
			array(
				'mid_size'  => 1,
				'prev_text' => __( '前へ', 'pacchi-portfolio' ),
				'next_text' => __( '次へ', 'pacchi-portfolio' ),
			)
		);
	else :
		?>
		<p><?php esc_html_e( '表示できるコンテンツがありません。', 'pacchi-portfolio' ); ?></p>
		<?php
	endif;
	?>
</div>

<?php
get_footer();
