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

	$mp_id       = get_the_ID();
	$mp_summary  = (string) get_field( 'summary', $mp_id );
	$mp_period   = (string) get_field( 'period', $mp_id );
	$mp_status   = (string) get_field( 'status', $mp_id );
	$mp_github   = (string) get_field( 'github_url', $mp_id );
	$mp_site     = (string) get_field( 'site_url', $mp_id );
	$mp_arch     = (string) get_field( 'architecture', $mp_id );
	$mp_duties   = mp_lines_to_array( (string) get_field( 'responsibilities', $mp_id ) );

	$mp_terms = get_the_terms( $mp_id, 'technology' );
	$mp_tech  = is_array( $mp_terms ) ? $mp_terms : array();

	$mp_status_labels = array(
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
				<?php if ( '' !== $mp_period ) : ?>
					<span class="work-card__period"><?php echo esc_html( $mp_period ); ?></span>
				<?php endif; ?>
				<?php if ( isset( $mp_status_labels[ $mp_status ] ) ) : ?>
					<span class="badge badge--<?php echo esc_attr( $mp_status ); ?>">
						<?php echo esc_html( $mp_status_labels[ $mp_status ] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h1 class="page-head__title text-balance-ja"><?php the_title(); ?></h1>

			<?php if ( '' !== $mp_summary ) : ?>
				<p class="page-head__lead text-balance-ja"><?php echo esc_html( $mp_summary ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $mp_github || '' !== $mp_site ) : ?>
				<div class="work-single__actions">
					<?php if ( '' !== $mp_site ) : ?>
						<a class="button button--primary" href="<?php echo esc_url( $mp_site ); ?>" target="_blank" rel="noreferrer noopener">
							<?php esc_html_e( 'サイトを見る', 'pacchi-portfolio' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( '' !== $mp_github ) : ?>
						<a class="button button--secondary" href="<?php echo esc_url( $mp_github ); ?>" target="_blank" rel="noreferrer noopener">
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

		<?php if ( ! empty( $mp_duties ) ) : ?>
			<section class="work-single__block">
				<h2 class="work-single__heading"><?php esc_html_e( '担当内容', 'pacchi-portfolio' ); ?></h2>
				<ul class="bullet-list">
					<?php foreach ( $mp_duties as $mp_duty ) : ?>
						<li class="text-balance-ja"><?php echo esc_html( $mp_duty ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( '' !== trim( $mp_arch ) ) : ?>
			<section class="work-single__block">
				<h2 class="work-single__heading"><?php esc_html_e( 'アーキテクチャ', 'pacchi-portfolio' ); ?></h2>
				<pre class="arch-diagram"><?php echo esc_html( $mp_arch ); ?></pre>
			</section>
		<?php endif; ?>

		<?php
		// 投稿本文。ブロックエディタで書いた内容が入る。
		$mp_content = trim( (string) get_the_content() );

		if ( '' !== $mp_content ) :
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

		<?php if ( ! empty( $mp_tech ) ) : ?>
			<section class="work-single__block">
				<h2 class="work-single__heading"><?php esc_html_e( '使用技術', 'pacchi-portfolio' ); ?></h2>
				<ul class="tag-list">
					<?php foreach ( $mp_tech as $mp_term ) : ?>
						<li>
							<a class="tag tag--default" href="<?php echo esc_url( (string) get_term_link( $mp_term ) ); ?>">
								<?php echo esc_html( $mp_term->name ); ?>
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
