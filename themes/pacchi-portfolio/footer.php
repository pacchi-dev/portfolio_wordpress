<?php
/**
 * フッター。
 *
 * wp_footer() は </body> 直前に出力を差し込むための hook 呼び出し。
 * enqueue した JS もここで出力されるため、削除すると
 * スクリプトが一切読み込まれなくなる。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;
?>
</main><!-- .site-main -->

<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__logo">
			<span class="site-footer__name">pacchi</span><span class="site-footer__slash">/backend</span>
		</p>
		<p class="site-footer__tagline">
			<?php
			/*
			 * 肩書きと技術スタックは Phase 5 で ACF に移す。
			 * この時点ではテーマ内の定数として持つ。
			 */
			echo esc_html( '// Backend Engineer — Go · PHP / Laravel · AWS' );
			?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
