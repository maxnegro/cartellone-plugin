<?php

/**
 * Event card partial for list views.
 *
 * @var int $post_id Post ID.
 * @var \Cartellone\Data $evdata Data instance.
 * @var array $event Event data.
 */

$evdata = new \Cartellone\Data( get_the_ID() );
$event  = $evdata->get_data();
$terms  = get_the_terms( get_the_ID(), CARTELLONE_TAX_TIPO );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'cartellone-event-card' ); ?>>
	<header class="cartellone-event-card__header">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="cartellone-event-card__image">
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
					<?php the_post_thumbnail( 'large' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<div class="cartellone-event-card__meta">
			<?php require CARTELLONE_PATH . 'public/partials/cartellone-public-event-meta.php'; ?>
		</div>
	</header>

	<div class="cartellone-event-card__content">
		<h3 class="cartellone-event-card__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h3>
		<div class="cartellone-event-card__cast">
			<?php echo esc_html( $event['protagonisti'] ?? '' ); ?>
		</div>
		<div class="cartellone-event-card__credits">
			<?php echo wp_kses_post( nl2br( $event['credits'] ?? '' ) ); ?>
		</div>
		<div class="cartellone-event-card__excerpt">
			<?php
			$ismore = strpos( get_the_content(), '<!--more-->' );
			if ( $ismore ) {
				the_content( '' );
			} else {
				the_excerpt();
			}
			?>
		</div>
		<?php require CARTELLONE_PATH . 'public/partials/cartellone-public-event-ticket.php'; ?>
	</div>
</article>
