<?php
/**
 * Plugin Name: Local SMTP (development only)
 * Description: ローカル開発時に wp_mail() の送信先を Mailpit へ向ける。本番環境では読み込まれない。
 *
 * mu-plugins（must-use plugins）は管理画面から無効化できず、
 * 自動で読み込まれる特殊なプラグイン。
 * 「環境固有の設定で、絶対に外れてほしくないもの」を置く場所として使う。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * PHPMailer を SMTP モードに切り替える。
 *
 * wp_mail() は内部で PHPMailer を使っており、
 * phpmailer_init はその設定を差し替えるための action hook。
 * WP Mail SMTP などのプラグインも同じ仕組みで動いている。
 *
 * 本番では SES や SendGrid に向けることになるが、
 * アプリケーション側のコードは wp_mail() のままで変わらない。
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer インスタンス。
 */
add_action( 'phpmailer_init', 'mp_local_smtp' );
function mp_local_smtp( $phpmailer ): void {
	// ローカル環境以外では何もしない。
	if ( 'local' !== wp_get_environment_type() ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = 'mailpit';
	$phpmailer->Port       = 1025;
	$phpmailer->SMTPAuth   = false;
	$phpmailer->SMTPSecure = '';
	$phpmailer->SMTPAutoTLS = false;
}

/**
 * ローカルでの送信元アドレスを妥当なドメインにする。
 *
 * WordPress の既定は wordpress@{ホスト名} で、ローカルでは
 * wordpress@localhost になる。PHPMailer は TLD を持たない
 * ドメインを拒否するため、パスワードリセット等も含めて送信できない。
 *
 * @param string $from 既定の送信元アドレス。
 * @return string
 */
add_filter( 'wp_mail_from', 'mp_local_mail_from' );
function mp_local_mail_from( string $from ): string {
	if ( 'local' !== wp_get_environment_type() ) {
		return $from;
	}

	if ( str_ends_with( $from, '@localhost' ) ) {
		return 'wordpress@localhost.test';
	}

	return $from;
}
