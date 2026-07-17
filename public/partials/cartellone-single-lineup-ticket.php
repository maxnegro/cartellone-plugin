<?php

if ( empty( $event['credits'] ) && empty( $event['vivaticket'] ) ) {
	return;
}
?>

<div class="lineup-ticket-wrapper">
	<div class="lineup">
		<?php if ( ! empty( $event['credits'] ) ) : ?>
			<?php echo wp_kses_post( nl2br( $event['credits'] ) ); ?>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $event['vivaticket'] ) && ! empty( $event['data'] ) 
		// && (int) $event['data'] >= time() && $evdata->season_open() 
	) : ?>
		<div class="cartellone-event-ticket">
			<?php
			if ( false !== strpos( $event['vivaticket'], 'eventbrite.it' ) ) {
				$label = 'Registrati gratuitamente';
				printf(
					'%s: <a href="%s" target="_blank"><img src="%s" alt="%s"></a>',
					$label,
					esc_url( $event['vivaticket'] ),
					esc_url( CARTELLONE_URL . 'public/img/eventbrite.png' ),
					esc_attr( $label )
				);
			} else {
				$label = 'Acquista online';
				printf(
					'%s: <a href="%s" target="_blank"><img src="%s" alt="%s"></a>',
					$label,
					esc_url( $event['vivaticket'] ),
					esc_url( CARTELLONE_URL . 'public/img/vivaticket.png' ),
					esc_attr( $label )
				);
			}
			?>
		</div>
	<?php endif; ?>
</div>
