<?php
/**
 * ヘッダー。
 *
 * get_header() から読み込まれる。
 * wp_head() / wp_body_open() は WordPress とプラグインが
 * <head> と <body> 直後に出力を差し込むための hook 呼び出しで、
 * これを消すとプラグインが軒並み動かなくなる。必ず残すこと。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( '本文へスキップ', 'pacchi-portfolio' ); ?></a>

<header class="site-header">
	<div class="site-header__inner">
		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="site-logo__name">pacchi</span><span class="site-logo__slash">/backend</span>
		</a>

		<nav class="site-nav" aria-label="<?php esc_attr_e( 'セクション', 'pacchi-portfolio' ); ?>">
			<?php mp_primary_menu( 'site-nav__list' ); ?>
		</nav>

		<details class="mobile-nav" data-mobile-nav>
			<summary class="mobile-nav__toggle">
				<span class="screen-reader-text"><?php esc_html_e( 'メニュー', 'pacchi-portfolio' ); ?></span>
				<svg class="mobile-nav__icon mobile-nav__icon--open" width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
					<path d="M2.5 5h13" /><path d="M2.5 9h13" /><path d="M2.5 13h13" />
				</svg>
				<svg class="mobile-nav__icon mobile-nav__icon--close" width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
					<path d="M4 4l10 10" /><path d="M14 4L4 14" />
				</svg>
			</summary>

			<nav class="mobile-nav__panel" aria-label="<?php esc_attr_e( 'セクション', 'pacchi-portfolio' ); ?>">
				<?php mp_primary_menu( 'mobile-nav__list' ); ?>
			</nav>
		</details>
	</div>
</header>

<main id="main" class="site-main">
