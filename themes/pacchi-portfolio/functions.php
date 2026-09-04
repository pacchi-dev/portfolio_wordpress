<?php
/**
 * テーマのエントリポイント。
 *
 * WordPress は有効なテーマの functions.php を毎リクエスト自動で読み込む
 * （フロント・管理画面の両方）。実質的に「hooks の登録簿」として機能する。
 *
 * このファイル自体には処理を書かず、inc/ に責務ごとに分割したファイルを
 * 読み込むだけにしている。functions.php は何でも書けてしまうため、
 * 実案件では容易に数千行へ肥大化する。分割は保守性のための基本方針。
 *
 * @package PacchiPortfolio
 */

// 直接アクセスされた場合に処理を止める。WordPress のテーマ・プラグインでは定石。
defined( 'ABSPATH' ) || exit;

define( 'MP_THEME_VERSION', '1.0.0' );
define( 'MP_THEME_DIR', get_template_directory() );
define( 'MP_THEME_URI', get_template_directory_uri() );

require_once MP_THEME_DIR . '/inc/setup.php';
require_once MP_THEME_DIR . '/inc/enqueue.php';
require_once MP_THEME_DIR . '/inc/template-tags.php';
require_once MP_THEME_DIR . '/inc/security.php';
