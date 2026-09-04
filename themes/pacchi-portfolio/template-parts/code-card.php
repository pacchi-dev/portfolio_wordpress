<?php
/**
 * Hero に表示する装飾用のコードカード。
 *
 * 実案件のコードではなく、作風を伝えるための一般的なサンプル。
 * aria-hidden で支援技術からは読み上げ対象外にしている。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/*
 * トークンの配列。[ クラス名, 文字列 ] の並びで 1 行を表す。
 * クラス名が空文字なら通常色。
 */
$mp_lines = array(
	array( array( 'com', '// booking/service.go' ) ),
	array(
		array( 'kw', 'func ' ),
		array( 'punct', '(' ),
		array( '', 's ' ),
		array( 'punct', '*' ),
		array( 'type', 'Service' ),
		array( 'punct', ') ' ),
		array( 'fn', 'CreateBooking' ),
		array( 'punct', '(' ),
	),
	array( array( '', '  ctx ' ), array( 'type', 'context.Context' ), array( 'punct', ',' ) ),
	array( array( '', '  req ' ), array( 'type', 'CreateRequest' ), array( 'punct', ',' ) ),
	array( array( 'punct', ') (*' ), array( 'type', 'Booking' ), array( 'punct', ', ' ), array( 'type', 'error' ), array( 'punct', ') {' ) ),
	array(
		array( 'kw', '  if ' ),
		array( '', 'err ' ),
		array( 'punct', ':= ' ),
		array( '', 'req.' ),
		array( 'fn', 'Validate' ),
		array( 'punct', '(); ' ),
		array( '', 'err ' ),
		array( 'punct', '!= ' ),
		array( 'kw', 'nil' ),
		array( 'punct', ' {' ),
	),
	array( array( 'kw', '    return ' ), array( 'kw', 'nil' ), array( 'punct', ', ' ), array( '', 'ErrInvalidRequest' ) ),
	array( array( 'punct', '  }' ) ),
	array(),
	array(
		array( '', '  booking, err ' ),
		array( 'punct', ':= ' ),
		array( '', 's.repo.' ),
		array( 'fn', 'Save' ),
		array( 'punct', '(ctx, ' ),
		array( 'fn', 'NewBooking' ),
		array( 'punct', '(req))' ),
	),
	array( array( 'kw', '  if ' ), array( '', 'err ' ), array( 'punct', '!= ' ), array( 'kw', 'nil' ), array( 'punct', ' {' ) ),
	array(
		array( 'kw', '    return ' ),
		array( 'kw', 'nil' ),
		array( 'punct', ', ' ),
		array( '', 'fmt.' ),
		array( 'fn', 'Errorf' ),
		array( 'punct', '(' ),
		array( 'str', '"save: %w"' ),
		array( 'punct', ', err)' ),
	),
	array( array( 'punct', '  }' ) ),
	array(),
	array( array( '', '  s.cache.' ), array( 'fn', 'Invalidate' ), array( 'punct', '(ctx, booking.ResourceID)' ) ),
	array( array( 'kw', '  return ' ), array( '', 'booking' ), array( 'punct', ', ' ), array( 'kw', 'nil' ) ),
	array( array( 'punct', '}' ) ),
);
?>

<div class="terminal-card glow-card" aria-hidden="true">
	<div class="terminal-card__bar">
		<span class="terminal-card__dot"></span>
		<span class="terminal-card__dot"></span>
		<span class="terminal-card__dot"></span>
		<span class="terminal-card__name">booking_service.go</span>
	</div>
	<pre class="code-card"><?php
	foreach ( $mp_lines as $mp_line ) {
		echo '<span class="code-card__line">';

		if ( empty( $mp_line ) ) {
			echo ' ';
		} else {
			foreach ( $mp_line as $mp_token ) {
				list( $mp_class, $mp_text ) = $mp_token;

				if ( '' === $mp_class ) {
					echo esc_html( $mp_text );
					continue;
				}

				printf( '<span class="tok-%1$s">%2$s</span>', esc_attr( $mp_class ), esc_html( $mp_text ) );
			}
		}

		/*
		 * <pre> 内では改行文字がそのまま行送りになる。
		 * .code-card__line が display:block で既に改行するため、
		 * ここで改行文字を出すと行間が二重になる。
		 */
		echo '</span>';
	}
	?></pre>
</div>
