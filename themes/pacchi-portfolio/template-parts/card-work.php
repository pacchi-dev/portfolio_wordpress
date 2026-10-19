<?php
/**
 * 制作実績 1 件分のカード（アーカイブ・一覧用）。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/*
 * ACF が無効・更新失敗の状態でも致命的エラーにせず、
 * このセクションを出力しないで済ませる。
 * README が謳う「ACF が無効でも致命的エラーにならない」の実装。
 */
if ( ! pp_acf_active() ) {
	return;
}

$pp_id      = get_the_ID();
$pp_summary = (string) get_field( 'summary', $pp_id );
$pp_period  = (string) get_field( 'period', $pp_id );
$pp_status  = (string) get_field( 'status', $pp_id );
$pp_site    = (string) get_field( 'site_url', $pp_id );

$pp_terms = get_the_terms( $pp_id, 'technology' );
$pp_tech  = is_array( $pp_terms ) ? wp_list_pluck( $pp_terms, 'name' ) : array();

$pp_status_labels = array(
	'released'    => __( '公開中', 'pacchi-portfolio' ),
	'development' => __( '開発中', 'pacchi-portfolio' ),
	'archived'    => __( '公開終了', 'pacchi-portfolio' ),
);
?>

<article <?php post_class( 'work-card' ); ?>>
	<?php
	/*
	 * カード全体を 1 つのリンクにしている。
	 * 公開中のサイトがある場合はその導線も出したいが、
	 * a 要素の入れ子は HTML として不正で、ブラウザが勝手に分解する。
	 * そのため «リンクの外側» に別のリンクを並べる。
	 */
	?>
	<a class="work-card__link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="work-card__thumb">
				<?php
				/*
				 * the_post_thumbnail() は srcset / sizes / loading="lazy" を
				 * 自動で付与する。img タグを手書きするとこれらを失う。
				 */
				the_post_thumbnail( 'mp-work-card', array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) );
				?>
			</div>
		<?php endif; ?>

		<div class="work-card__body">
			<div class="work-card__meta">
				<?php if ( '' !== $pp_period ) : ?>
					<span class="work-card__period"><?php echo esc_html( $pp_period ); ?></span>
				<?php endif; ?>
				<?php if ( isset( $pp_status_labels[ $pp_status ] ) ) : ?>
					<span class="badge badge--<?php echo esc_attr( $pp_status ); ?>">
						<?php echo esc_html( $pp_status_labels[ $pp_status ] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h3 class="work-card__title text-balance-ja"><?php the_title(); ?></h3>

			<?php if ( '' !== $pp_summary ) : ?>
				<p class="work-card__summary text-balance-ja"><?php echo esc_html( $pp_summary ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $pp_tech ) ) : ?>
				<?php pp_render_tags( array_slice( $pp_tech, 0, 6 ), 'muted' ); ?>
			<?php endif; ?>
		</div>
	</a>

	<?php if ( '' !== $pp_site ) : ?>
		<div class="work-card__actions">
			<a class="work-card__site" href="<?php echo esc_url( $pp_site ); ?>" target="_blank" rel="noreferrer noopener">
				<?php esc_html_e( 'サイトを見る', 'pacchi-portfolio' ); ?>
				<span class="work-card__site-icon" aria-hidden="true">&#8599;</span>
				<span class="screen-reader-text"><?php esc_html_e( '別のタブで開きます', 'pacchi-portfolio' ); ?></span>
			</a>
		</div>
	<?php endif; ?>
</article>
