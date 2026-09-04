<?php
/**
 * 制作実績 1 件分のカード（アーカイブ・一覧用）。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_id      = get_the_ID();
$mp_summary = (string) get_field( 'summary', $mp_id );
$mp_period  = (string) get_field( 'period', $mp_id );
$mp_status  = (string) get_field( 'status', $mp_id );

$mp_terms = get_the_terms( $mp_id, 'technology' );
$mp_tech  = is_array( $mp_terms ) ? wp_list_pluck( $mp_terms, 'name' ) : array();

$mp_status_labels = array(
	'released'    => __( '公開中', 'pacchi-portfolio' ),
	'development' => __( '開発中', 'pacchi-portfolio' ),
	'archived'    => __( '公開終了', 'pacchi-portfolio' ),
);
?>

<article <?php post_class( 'work-card' ); ?>>
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
				<?php if ( '' !== $mp_period ) : ?>
					<span class="work-card__period"><?php echo esc_html( $mp_period ); ?></span>
				<?php endif; ?>
				<?php if ( isset( $mp_status_labels[ $mp_status ] ) ) : ?>
					<span class="badge badge--<?php echo esc_attr( $mp_status ); ?>">
						<?php echo esc_html( $mp_status_labels[ $mp_status ] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h3 class="work-card__title text-balance-ja"><?php the_title(); ?></h3>

			<?php if ( '' !== $mp_summary ) : ?>
				<p class="work-card__summary text-balance-ja"><?php echo esc_html( $mp_summary ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $mp_tech ) ) : ?>
				<?php mp_render_tags( array_slice( $mp_tech, 0, 6 ), 'muted' ); ?>
			<?php endif; ?>
		</div>
	</a>
</article>
