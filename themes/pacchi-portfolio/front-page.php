<?php
/**
 * トップページ。
 *
 * front-page.php はテンプレート階層で最優先される特別なテンプレート。
 * 各セクションは template-parts/ に分割し、
 * Next.js 版のコンポーネント構成と 1:1 で対応させている。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/section-hero' );
get_template_part( 'template-parts/section-about' );
get_template_part( 'template-parts/section-services' );
get_template_part( 'template-parts/section-skills' );
get_template_part( 'template-parts/section-experience' );
get_template_part( 'template-parts/section-github' );
get_template_part( 'template-parts/section-ai' );
get_template_part( 'template-parts/section-work-with-me' );
get_template_part( 'template-parts/section-contact' );

get_footer();
