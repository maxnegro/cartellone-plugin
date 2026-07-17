<?php

if ( empty( $event['data'] ) ) {
	return;
}
?>

<div class="entry-meta list-post-entry-meta">
	<time datetime="<?php echo esc_attr( date_i18n( 'c', (int) $event['data'] ) ); ?>" class="cartellone-event-meta__date">
		<span class="post-date-day"><?php echo esc_html( date_i18n( 'd', (int) $event['data'] ) ); ?></span>
		<span class="post-date-month"><?php echo esc_html( date_i18n( 'M', (int) $event['data'] ) ); ?></span>
		<span class="post-date-year"><?php echo esc_html( date_i18n( 'Y', (int) $event['data'] ) ); ?></span>
	</time>

	<?php if ( ! empty( $event['ora'] ) ) : ?>
		<span class="cartellone-event-meta__time"><?php echo esc_html( $event['ora'] ); ?></span>
	<?php endif; ?>

	<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
		<?php foreach ( $terms as $term ) : ?>
			<span class="post-comments"><?php echo esc_html( $term->name ); ?></span>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
