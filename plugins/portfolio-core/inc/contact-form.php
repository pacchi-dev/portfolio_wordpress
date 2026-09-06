<?php
/**
 * お問い合わせフォームの処理。
 *
 * Contact Form 7 を使わず自作している理由:
 *   nonce / サニタイズ / wp_mail を自分で実装する経験を優先したため。
 *   実案件では CF7 が標準的に使われるので、README に使い分けを記載する。
 *
 * 処理の流れ（POST-Redirect-Get）:
 *   1. admin_post_nopriv_* / admin_post_* で POST を受ける
 *   2. nonce を検証し、入力をサニタイズ・バリデーション
 *   3. wp_mail() で送信
 *   4. 結果をクエリパラメータに載せてリダイレクト
 *
 * 4 のリダイレクトが重要。POST の結果をそのまま出力すると
 * ブラウザの再読み込みで二重送信が起きる。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/** フォームの nonce アクション名。 */
const PC_CONTACT_NONCE_ACTION = 'pc_contact_submit';

/** admin-post.php で受け取るアクション名。 */
const PC_CONTACT_ACTION = 'pc_contact';

/**
 * POST を受け取るハンドラを登録する。
 *
 * WordPress のフォーム送信先には admin-post.php を使うのが定石。
 * テンプレートに直接 POST すると、WordPress の初期化前に
 * リダイレクトを返せず「ヘッダー送信済み」エラーになりやすい。
 *
 * admin_post_{action}        … ログイン済みユーザーからの POST
 * admin_post_nopriv_{action} … 未ログインユーザーからの POST
 * 両方を登録しないと、片方のユーザーだけ 400 になる。よくある実装漏れ。
 */
add_action( 'admin_post_' . PC_CONTACT_ACTION, 'pc_handle_contact_form' );
add_action( 'admin_post_nopriv_' . PC_CONTACT_ACTION, 'pc_handle_contact_form' );

/**
 * フォーム送信を処理する。
 */
function pc_handle_contact_form(): void {
	$redirect = wp_get_referer();

	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}

	// ---------------------------------------------------------------
	// 1. nonce 検証（CSRF 対策）
	//
	// wp_verify_nonce は「アクション名 + ユーザーID + セッショントークン + 時刻」
	// から算出したハッシュを照合する。サーバー側に値を保存しないため
	// 有効期限（既定 24 時間）がある点が Laravel の CSRF トークンと異なる。
	// ---------------------------------------------------------------
	$nonce = isset( $_POST['pc_contact_nonce'] )
		? sanitize_text_field( wp_unslash( (string) $_POST['pc_contact_nonce'] ) )
		: '';

	if ( ! wp_verify_nonce( $nonce, PC_CONTACT_NONCE_ACTION ) ) {
		pc_contact_redirect( $redirect, 'invalid_nonce' );
	}

	// ---------------------------------------------------------------
	// 2. ハニーポット（簡易スパム対策）
	//
	// CSS で隠したフィールドに入力があれば bot と判断する。
	// 成功したように見せて捨てることで、bot に検知させない。
	// ---------------------------------------------------------------
	$honeypot = isset( $_POST['pc_website'] )
		? trim( (string) wp_unslash( $_POST['pc_website'] ) )
		: '';

	if ( '' !== $honeypot ) {
		pc_contact_redirect( $redirect, 'sent' );
	}

	// ---------------------------------------------------------------
	// 3. サニタイズ
	//
	// wp_unslash は必須。WordPress は歴史的経緯から $_POST に
	// スラッシュを付与しており、外さないと 'It\'s' のように残る。
	// サニタイズ関数の適用より先に実行すること。
	// ---------------------------------------------------------------
	$name = isset( $_POST['pc_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['pc_name'] ) ) : '';

	/*
	 * sanitize_email は不正な文字列に対して空文字を返す。
	 * サニタイズ後だけを見ると「未入力」と区別できないため、
	 * 入力の有無は生の値で判定してからサニタイズ結果を検証する。
	 */
	$email_raw = isset( $_POST['pc_email'] ) ? trim( (string) wp_unslash( $_POST['pc_email'] ) ) : '';
	$email     = sanitize_email( $email_raw );
	$subject = isset( $_POST['pc_subject'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['pc_subject'] ) ) : '';
	// 本文は改行を保持したいので textarea 用のサニタイズを使う。
	$message = isset( $_POST['pc_message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['pc_message'] ) ) : '';

	// ---------------------------------------------------------------
	// 4. バリデーション
	// ---------------------------------------------------------------
	if ( '' === $name || '' === $email_raw || '' === $message ) {
		pc_contact_redirect( $redirect, 'missing_required' );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		pc_contact_redirect( $redirect, 'invalid_email' );
	}

	if ( mb_strlen( $message ) > 5000 ) {
		pc_contact_redirect( $redirect, 'too_long' );
	}

	// ---------------------------------------------------------------
	// 5. メール送信
	// ---------------------------------------------------------------
	$to = pc_contact_recipient();

	if ( '' === $to ) {
		pc_contact_redirect( $redirect, 'no_recipient' );
	}

	$site_name = wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );

	$mail_subject = sprintf(
		/* translators: 1: サイト名, 2: 件名 */
		__( '[%1$s] お問い合わせ: %2$s', 'portfolio-core' ),
		$site_name,
		'' !== $subject ? $subject : __( '（件名なし）', 'portfolio-core' )
	);

	$body = implode(
		"\n",
		array(
			__( 'サイトのお問い合わせフォームから送信されました。', 'portfolio-core' ),
			'',
			__( 'お名前:', 'portfolio-core' ) . ' ' . $name,
			__( '返信先メールアドレス:', 'portfolio-core' ) . ' ' . $email,
			__( '件名:', 'portfolio-core' ) . ' ' . $subject,
			'',
			__( '本文:', 'portfolio-core' ),
			$message,
			'',
			str_repeat( '-', 40 ),
			__( '送信日時:', 'portfolio-core' ) . ' ' . wp_date( 'Y-m-d H:i:s' ),
			__( '送信元 IP:', 'portfolio-core' ) . ' ' . pc_contact_client_ip(),
		)
	);

	/*
	 * From はサイトのドメインにする。
	 * 送信者のアドレスを From にすると SPF / DKIM の検証に失敗し
	 * 迷惑メール判定されるため、Reply-To に入れるのが正しい。
	 * 実案件で「問い合わせメールが届かない」原因の多くがこれ。
	 */
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		sprintf( 'From: %s <%s>', $site_name, pc_contact_from_address() ),
		sprintf( 'Reply-To: %s <%s>', $name, $email ),
	);

	$sent = wp_mail( $to, $mail_subject, $body, $headers );

	pc_contact_redirect( $redirect, $sent ? 'sent' : 'send_failed' );
}

/**
 * 結果をクエリに載せてリダイレクトし、処理を終了する。
 *
 * @param string $url    リダイレクト先。
 * @param string $status 結果コード。
 */
function pc_contact_redirect( string $url, string $status ): void {
	$url = add_query_arg( 'contact', rawurlencode( $status ), remove_query_arg( 'contact', $url ) );

	/*
	 * wp_safe_redirect は許可されたホストにしかリダイレクトしない。
	 * wp_redirect だと外部サイトへ飛ばせてしまい、
	 * オープンリダイレクトの脆弱性になる。
	 */
	wp_safe_redirect( $url . '#contact' );
	exit;
}

/**
 * 送信先アドレスを返す。
 *
 * @return string
 */
function pc_contact_recipient(): string {
	$page_id = (int) get_option( 'page_on_front' );

	if ( $page_id > 0 && function_exists( 'get_field' ) ) {
		$email = (string) get_field( 'contact_email', $page_id );

		if ( is_email( $email ) ) {
			return $email;
		}
	}

	return (string) get_option( 'admin_email' );
}

/**
 * From に使うアドレスを返す。
 *
 * サイトのドメインに合わせることで送信ドメイン認証に通しやすくする。
 *
 * @return string
 */
function pc_contact_from_address(): string {
	$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$host = preg_replace( '/^www\./i', '', (string) $host );

	if ( '' === $host ) {
		$host = 'localhost';
	}

	/*
	 * PHPMailer は TLD を持たないドメイン（localhost 等）を
	 * 無効なアドレスとして拒否する。ローカル開発では From が
	 * no-reply@localhost になり送信が失敗するため、
	 * ドットを含まない場合はテスト用 TLD を補う。
	 * .test は RFC 6761 で予約された、実在しないことが保証された TLD。
	 */
	if ( ! str_contains( $host, '.' ) ) {
		$host .= '.test';
	}

	return 'no-reply@' . $host;
}

/**
 * 送信元 IP を返す。
 *
 * $_SERVER の値は改ざん可能な入力として扱い、必ず検証する。
 *
 * @return string
 */
function pc_contact_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
		: '';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '-';
}

/**
 * 結果コードに対応するメッセージを返す。
 *
 * @param string $status 結果コード。
 * @return array{type: string, message: string}|null
 */
function pc_contact_message( string $status ): ?array {
	$messages = array(
		'sent'             => array( 'success', __( 'お問い合わせを送信しました。ご連絡ありがとうございます。', 'portfolio-core' ) ),
		'invalid_nonce'    => array( 'error', __( '送信内容を確認できませんでした。ページを再読み込みしてからもう一度お試しください。', 'portfolio-core' ) ),
		'missing_required' => array( 'error', __( '必須項目が入力されていません。', 'portfolio-core' ) ),
		'invalid_email'    => array( 'error', __( 'メールアドレスの形式が正しくありません。', 'portfolio-core' ) ),
		'too_long'         => array( 'error', __( '本文が長すぎます。5000 文字以内で入力してください。', 'portfolio-core' ) ),
		'no_recipient'     => array( 'error', __( '送信先が設定されていません。サイト管理者にご連絡ください。', 'portfolio-core' ) ),
		'send_failed'      => array( 'error', __( '送信に失敗しました。時間をおいてお試しください。', 'portfolio-core' ) ),
	);

	if ( ! isset( $messages[ $status ] ) ) {
		return null;
	}

	return array(
		'type'    => $messages[ $status ][0],
		'message' => $messages[ $status ][1],
	);
}
