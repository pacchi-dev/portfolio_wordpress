<?php
/**
 * 制作実績の詳細（/works/{slug}/）。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$pp_id       = get_the_ID();
	$pp_summary  = (string) get_field( 'summary', $pp_id );
	$pp_period   = (string) get_field( 'period', $pp_id );
	$pp_status   = (string) get_field( 'status', $pp_id );
	$pp_github   = (string) get_field( 'github_url', $pp_id );
	$pp_site     = (string) get_field( 'site_url', $pp_id );
	$pp_arch     = (string) get_field( 'architecture', $pp_id );
	$pp_duties   = pp_lines_to_array( (string) get_field( 'responsibilities', $pp_id ) );

	$pp_terms = get_the_terms( $pp_id, 'technology' );
	$pp_tech  = is_array( $pp_terms ) ? $pp_terms : array();

	$pp_status_labels = array(
		'released'    => __( '公開中', 'pacchi-portfolio' ),
		'development' => __( '開発中', 'pacchi-portfolio' ),
		'archived'    => __( '公開終了', 'pacchi-portfolio' ),
	);
	?>

	<div class="page-head">
		<div class="container">
			<p class="section__eyebrow">
				<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'work' ) ); ?>">// works</a>
			</p>

			<div class="work-single__meta">
				<?php if ( '' !== $pp_period ) : ?>
					<span class="work-card__period"><?php echo esc_html( $pp_period ); ?></span>
				<?php endif; ?>
				<?php if ( isset( $pp_status_labels[ $pp_status ] ) ) : ?>
					<span class="badge badge--<?php echo esc_attr( $pp_status ); ?>">
						<?php echo esc_html( $pp_status_labels[ $pp_status ] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h1 class="page-head__title text-balance-ja"><?php the_title(); ?></h1>

			<?php if ( '' !== $pp_summary ) : ?>
				<p class="page-head__lead text-balance-ja"><?php echo esc_html( $pp_summary ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $pp_github || '' !== $pp_site ) : ?>
				<div class="work-single__actions">
					<?php if ( '' !== $pp_site ) : ?>
						<a class="button button--primary" href="<?php echo esc_url( $pp_site ); ?>" target="_blank" rel="noreferrer noopener">
							<?php esc_html_e( 'サイトを見る', 'pacchi-portfolio' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $pp_github ) : ?>
						<a class="button button--secondary" href="<?php echo esc_url( $pp_github ); ?>" target="_blank" rel="noreferrer noopener">
							<?php esc_html_e( 'GitHub', 'pacchi-portfolio' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<div class="container section__body work-single">
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="work-single__thumb">
				<?php the_post_thumbnail( 'large' ); ?>
			</figure>
		<?php endif; ?>

		<?php if ( ! empty( $pp_duties ) ) : ?>
			<section class="work-single__block">
				<h2 class="work-single__heading"><?php esc_html_e( '担当内容', 'pacchi-portfolio' ); ?></h2>
				<ul class="bullet-list">
					<?php foreach ( $pp_duties as $pp_duty ) : ?>
						<li class="text-balance-ja"><?php echo esc_html( $pp_duty ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( '' !== trim( $pp_arch ) ) : ?>
			<section class="work-single__block">
				<h2 class="work-single__heading"><?php esc_html_e( 'アーキテクチャ', 'pacchi-portfolio' ); ?></h2>
				<?php pp_render_architecture( $pp_arch ); ?>
			</section>
		<?php endif; ?>

		<?php
		// 投稿本文。ブロックエディタで書いた内容が入る。
		$pp_content = trim( (string) get_the_content() );

		if ( '' !== $pp_content ) :
			?>
			<section class="work-single__block entry__content">
				<?php
				/*
				 * the_content() は wp_kses を通さない。
				 * 投稿本文は編集権限を持つユーザーしか書けない前提のため、
				 * WordPress の設計として意図的にそうなっている。
				 * 逆に言えば、権限管理が甘いサイトでは XSS の経路になる。
				 */
				the_content();
				?>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $pp_tech ) ) : ?>
			<section class="work-single__block">
				<h2 class="work-single__heading"><?php esc_html_e( '使用技術', 'pacchi-portfolio' ); ?></h2>
				<ul class="tag-list">
					<?php foreach ( $pp_tech as $pp_term ) : ?>
						<li>
							<a class="tag tag--default" href="<?php echo esc_url( (string) get_term_link( $pp_term ) ); ?>">
								<?php echo esc_html( $pp_term->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<nav class="work-single__nav">
			<?php
			// 前後の投稿へのリンク。get_adjacent_post_link は自動でエスケープ済み。
			previous_post_link( '<span class="work-single__prev">%link</span>', '← %title' );
			next_post_link( '<span class="work-single__next">%link</span>', '%title →' );
			?>
		</nav>
	</div>

	<?php
endwhile;

get_footer();
