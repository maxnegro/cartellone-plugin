<?php

/**
 * Event meta partial.
 *
 * @var array $event Event data.
 */

if ( empty( $event['data'] ) ) {
	return;
}
?>

<div class="cartellone-event-meta">
	<time datetime="<?php echo esc_attr( date_i18n( 'c', (int) $event['data'] ) ); ?>" class="cartellone-event-meta__date">
		<span class="cartellone-event-meta__day"><?php echo esc_html( date_i18n( 'd', (int) $event['data'] ) ); ?></span>
		<span class="cartellone-event-meta__month"><?php echo esc_html( date_i18n( 'M', (int) $event['data'] ) ); ?></span>
		<span class="cartellone-event-meta__year"><?php echo esc_html( date_i18n( 'Y', (int) $event['data'] ) ); ?></span>
	</time>

	<?php if ( ! empty( $event['ora'] ) ) : ?>
		<span class="cartellone-event-meta__time"><?php echo esc_html( $event['ora'] ); ?></span>
	<?php endif; ?>

	<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
		<span class="cartellone-event-meta__types">
			<?php foreach ( $terms as $term ) : ?>
				<span class="cartellone-event-meta__type"><?php echo esc_html( $term->name ); ?></span>
			<?php endforeach; ?>
		</span>
	<?php endif; ?>
</div>
