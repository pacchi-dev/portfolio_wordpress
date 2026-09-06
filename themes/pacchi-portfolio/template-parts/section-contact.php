<?php
/**
 * Contact セクション。
 *
 * 未入力の連絡先は表示しない。有効・無効のフラグを別に持たず、
 * 「空欄なら非表示」とすることで管理項目を減らしている。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id = (int) get_option( 'page_on_front' );

$mp_email = (string) get_field( 'contact_email', $mp_page_id );

$mp_links = array(
	array(
		'label' => __( 'Email', 'pacchi-portfolio' ),
		'value' => $mp_email,
		'href'  => '' !== $mp_email ? 'mailto:' . $mp_email : '',
		'ext'   => false,
	),
	array(
		'label' => __( 'GitHub', 'pacchi-portfolio' ),
		'value' => __( 'プロフィール', 'pacchi-portfolio' ),
		'href'  => (string) get_field( 'contact_github', $mp_page_id ),
		'ext'   => true,
	),
	array(
		'label' => __( 'CrowdWorks', 'pacchi-portfolio' ),
		'value' => __( 'プロフィール', 'pacchi-portfolio' ),
		'href'  => (string) get_field( 'contact_crowdworks', $mp_page_id ),
		'ext'   => true,
	),
	array(
		'label' => __( 'X', 'pacchi-portfolio' ),
		'value' => __( 'プロフィール', 'pacchi-portfolio' ),
		'href'  => (string) get_field( 'contact_x', $mp_page_id ),
		'ext'   => true,
	),
);

$mp_links = array_values(
	array_filter( $mp_links, static fn( array $l ): bool => '' !== trim( (string) $l['href'] ) )
);

mp_section_open(
	array(
		'id'      => 'contact',
		'eyebrow' => 'contact',
		'title'   => (string) get_field( 'contact_title', $mp_page_id ),
		'lead'    => (string) get_field( 'contact_lead', $mp_page_id ),
		'open'    => true,
	)
);
?>

<?php if ( ! empty( $mp_links ) ) : ?>
	<ul class="contact-list">
		<?php foreach ( $mp_links as $mp_link ) : ?>
			<li>
				<a class="contact-card"
					href="<?php echo esc_url( (string) $mp_link['href'] ); ?>"
					<?php if ( $mp_link['ext'] ) : ?>target="_blank" rel="noreferrer noopener"<?php endif; ?>>
					<span class="contact-card__label"><?php echo esc_html( (string) $mp_link['label'] ); ?></span>
					<span class="contact-card__value"><?php echo esc_html( (string) $mp_link['value'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p class="contact-empty"><?php esc_html_e( '連絡先は準備中です。', 'pacchi-portfolio' ); ?></p>
<?php endif; ?>

<?php
/*
 * フォームの表示可否。
 *
 * PHP からのメール送信が使えないサーバーではオフにする（カスタマイザー）。
 * オフのときは上のメールアドレス案内だけが残る。
 * 「送信できたように見えて届かない」状態を作らないための切り替え。
 */
if ( get_theme_mod( 'mp_show_contact_form', true ) ) :
	?>

	<h3 class="contact-form__heading"><?php esc_html_e( 'メッセージを送る', 'pacchi-portfolio' ); ?></h3>

	<?php
/*
 * 送信結果の表示。
 * $_GET の値は必ずサニタイズしてから使う。
 * nonce 検証は不要（読み取り専用で副作用が無いため）だが、
 * 未検証の入力をそのまま出力しないことは必須。
 */
$mp_status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( (string) $_GET['contact'] ) ) : '';
$mp_notice = ( '' !== $mp_status && function_exists( 'pc_contact_message' ) )
	? pc_contact_message( $mp_status )
	: null;

if ( $mp_notice ) :
	?>
	<p class="form-notice form-notice--<?php echo esc_attr( $mp_notice['type'] ); ?>" role="status">
		<?php echo esc_html( $mp_notice['message'] ); ?>
	</p>
	<?php
endif;
?>

<form class="contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php
	/*
	 * admin-post.php はこの action の値でハンドラを振り分ける。
	 * 値が無いと 400 になる。
	 */
	?>
	<input type="hidden" name="action" value="<?php echo esc_attr( PC_CONTACT_ACTION ); ?>">

	<?php
	/*
	 * nonce の隠しフィールドを出力する。
	 * 第 3 引数 false は referer フィールドを出さない指定。
	 */
	wp_nonce_field( PC_CONTACT_NONCE_ACTION, 'pc_contact_nonce', true, true );
	?>

	<?php /* ハニーポット。人間には見えず、bot だけが入力する。 */ ?>
	<div class="contact-form__honeypot" aria-hidden="true">
		<label for="pc_website"><?php esc_html_e( 'この項目は入力しないでください', 'pacchi-portfolio' ); ?></label>
		<input type="text" id="pc_website" name="pc_website" tabindex="-1" autocomplete="off">
	</div>

	<div class="contact-form__row">
		<p class="field">
			<label for="pc_name"><?php esc_html_e( 'お名前', 'pacchi-portfolio' ); ?> <span class="field__required">*</span></label>
			<input type="text" id="pc_name" name="pc_name" required maxlength="100" autocomplete="name">
		</p>
		<p class="field">
			<label for="pc_email"><?php esc_html_e( '返信先メールアドレス', 'pacchi-portfolio' ); ?> <span class="field__required">*</span></label>
			<input type="email" id="pc_email" name="pc_email" required maxlength="200" autocomplete="email">
		</p>
	</div>

	<p class="field">
		<label for="pc_subject"><?php esc_html_e( '件名', 'pacchi-portfolio' ); ?></label>
		<input type="text" id="pc_subject" name="pc_subject" maxlength="200">
	</p>

	<p class="field">
		<label for="pc_message"><?php esc_html_e( 'お問い合わせ内容', 'pacchi-portfolio' ); ?> <span class="field__required">*</span></label>
		<textarea id="pc_message" name="pc_message" rows="7" required maxlength="5000"></textarea>
	</p>

	<p class="contact-form__submit">
		<button type="submit" class="button button--primary">
			<?php esc_html_e( '送信する', 'pacchi-portfolio' ); ?>
		</button>
	</p>
</form>

	<?php
endif;

mp_section_close();
