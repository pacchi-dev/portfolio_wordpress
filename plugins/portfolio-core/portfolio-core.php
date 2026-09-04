<?php
/**
 * Plugin Name:       Portfolio Core
 * Plugin URI:        https://github.com/pacchi-dev/portfolio_wordpress
 * Description:       ポートフォリオサイトのデータ構造と機能を提供する。カスタム投稿タイプ、タクソノミー、外部 API 連携、お問い合わせ処理を担当し、表示はテーマ側に委ねる。
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            pacchi
 * Author URI:        https://github.com/pacchi-dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       portfolio-core
 *
 * -----------------------------------------------------------------------------
 * なぜテーマではなくプラグインなのか
 *
 * カスタム投稿タイプをテーマの functions.php に書くと、テーマを変更した
 * 瞬間に投稿タイプが未登録になり、DB に残った記事が管理画面から
 * 見えなくなる（データ自体は消えないが到達できない）。
 *
 * WordPress では「表示 = テーマ / データ構造・機能 = プラグイン」で
 * 責務を分けるのが原則。実案件のコードレビューでも指摘される点。
 * -----------------------------------------------------------------------------
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

define( 'PC_VERSION', '1.0.0' );
define( 'PC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PC_PLUGIN_DIR . 'inc/post-types.php';
require_once PC_PLUGIN_DIR . 'inc/fields.php';

/**
 * 有効化時の処理。
 *
 * CPT の登録直後はリライトルールが未生成のため、
 * /works/{slug}/ のような URL が 404 になる。
 * 有効化のタイミングで一度だけルールを再生成しておく。
 *
 * flush_rewrite_rules() は重い処理なので、init などで毎回呼んではいけない。
 * 「CPT を作ったのに個別ページが 404」は WordPress 案件で最頻出のトラブルで、
 * 原因のほとんどがこのフラッシュ漏れ。
 */
register_activation_hook( __FILE__, 'pc_activate' );
function pc_activate(): void {
	pc_register_post_types();
	pc_register_taxonomies();
	flush_rewrite_rules();
}

/**
 * 無効化時の処理。
 *
 * 登録が外れた CPT のリライトルールを残さないよう再生成する。
 */
register_deactivation_hook( __FILE__, 'pc_deactivate' );
function pc_deactivate(): void {
	flush_rewrite_rules();
}
