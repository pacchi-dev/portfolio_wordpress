<?php
/**
 * Hero セクション。
 *
 * ACF はフロントページの固定ページに紐づくため、
 * get_field() の第 2 引数へ明示的にページ ID を渡す。
 * front-page.php では The Loop を回していないので、
 * 省略すると「現在の投稿」が特定できず値を取得できない。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id = (int) get_option( 'page_on_front' );

if ( $mp_page_id <= 0 ) {
	return;
}

$role_1 = (string) get_field( 'hero_role_1', $mp_page_id );
$role_2 = (string) get_field( 'hero_role_2', $mp_page_id );
$name   = (string) get_field( 'hero_name', $mp_page_id );
$lead   = (string) get_field( 'hero_lead', $mp_page_id );
$stack  = mp_lines_to_array( (string) get_field( 'hero_stack', $mp_page_id ) );

$highlights_raw = (array) get_field( 'hero_highlights', $mp_page_id );
$highlights     = array();

foreach ( array( 'item_1', 'item_2', 'item_3' ) as $key ) {
	$item = $highlights_raw[ $key ] ?? null;

	if ( is_array( $item ) && '' !== trim( (string) ( $item['value'] ?? '' ) ) ) {
		$highlights[] = $item;
	}
}

$github_url = (string) get_field( 'contact_github', $mp_page_id );
?>

<section id="top" class="hero">
	<div class="hero-backdrop" aria-hidden="true"></div>

	<div class="hero__inner">
		<div class="hero__main">
			<?php if ( mp_is_accepting_work() ) : ?>
				<p class="status-pill">
					<span class="status-pill__dot status-dot" aria-hidden="true"></span>
					<?php echo esc_html( mp_availability_label() ); ?>
				</p>
			<?php endif; ?>

			<h1 class="hero__title">
				<?php echo esc_html( $role_1 ); ?><br>
				<span class="hero-gradient-text"><?php echo esc_html( $role_2 ); ?></span>
			</h1>

			<?php if ( '' !== $name ) : ?>
				<p class="hero__name"><?php echo esc_html( $name ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $stack ) ) : ?>
				<ul class="hero__stack">
					<?php foreach ( $stack as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( '' !== $lead ) : ?>
				<p class="hero__lead text-balance-ja"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>

			<div class="hero__actions">
				<a class="button button--primary" href="#experience">
					<?php esc_html_e( 'View Experience', 'pacchi-portfolio' ); ?>
				</a>
				<?php if ( '' !== $github_url ) : ?>
					<a class="button button--secondary" href="<?php echo esc_url( $github_url ); ?>" target="_blank" rel="noreferrer noopener">
						<?php esc_html_e( 'GitHub', 'pacchi-portfolio' ); ?>
					</a>
				<?php else : ?>
					<a class="button button--secondary" href="#work">
						<?php esc_html_e( 'Work With Me', 'pacchi-portfolio' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $highlights ) ) : ?>
				<dl class="hero__highlights">
					<?php foreach ( $highlights as $item ) : ?>
						<div>
							<dt><?php echo esc_html( (string) $item['label'] ); ?></dt>
							<dd class="text-balance-ja"><?php echo esc_html( (string) $item['value'] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>

		<div class="hero__aside">
			<?php get_template_part( 'template-parts/code-card' ); ?>
		</div>
	</div>
</section>
