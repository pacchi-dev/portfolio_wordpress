<?php
/**
 * トップページ。
 *
 * front-page.php はテンプレート階層で最優先される特別なテンプレート。
 * 「静的な固定ページを表示」「最新の投稿を表示」のどちらの設定でも、
 * サイトのトップにはこのファイルが使われる。
 *
 * 各セクションは template-parts/ に分割し、
 * Next.js 版のコンポーネント構成と 1:1 で対応させている。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/section-skills' );
get_template_part( 'template-parts/section-experience' );

get_footer();
