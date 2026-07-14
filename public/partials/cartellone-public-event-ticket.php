<?php

/**
 * Event ticket link partial.
 *
 * @var array $event Event data.
 * @var \Cartellone\Data $evdata Data instance.
 */

if ( empty( $event['vivaticket'] ) || empty( $event['data'] ) || (int) $event['data'] < time() || ! $evdata->season_open() ) {
	return;
}
?>

<div class="cartellone-event-ticket">
	<?php
	if ( false !== strpos( $event['vivaticket'], 'eventbrite.it' ) ) {
		printf(
			'<a href="%s" target="_blank"><img src="%s" alt="%s"></a>',
			esc_url( $event['vivaticket'] ),
			esc_url( CARTELLONE_URL . 'public/img/eventbrite.png' ),
			esc_attr__( 'Free registration', 'cartellone' )
		);
	} else {
		printf(
			'<a href="%s" target="_blank"><img src="%s" alt="%s"></a>',
			esc_url( $event['vivaticket'] ),
			esc_url( CARTELLONE_URL . 'public/img/vivaticket.png' ),
			esc_attr__( 'Buy tickets', 'cartellone' )
		);
	}
	?>
</div>
