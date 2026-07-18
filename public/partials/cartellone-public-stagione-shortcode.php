<?php

$post_id = get_the_ID();

$evdata = new \Cartellone\Data( $post_id );
$event  = $evdata->get_data();
$terms  = get_the_terms( $post_id, CARTELLONE_TAX_TIPO );
$type_classes = '';
if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
	$type_classes = implode( ' ', array_map( function( $term ) {
		return 'tipo-' . sanitize_html_class( $term->slug );
	}, $terms ) );
}
$season_terms = get_the_terms( $post_id, CARTELLONE_TAX_STAGIONE );
$season_class = '';
if ( ! empty( $season_terms ) && ! is_wp_error( $season_terms ) ) {
	$season_class = sanitize_html_class( 'stagione-' . $season_terms[0]->slug );
}
$post_classes = 'border-bottom-hover ' . trim( $season_class . ' ' . $type_classes );
?>

<article id="post-<?php echo esc_attr( $post_id ); ?>" class="post-<?php echo esc_attr( $post_id ); ?> <?php echo esc_attr( $post_classes ); ?>">
	<header class="entry-header">
		<?php if ( ! empty( $event['produzione'] ) ) : ?>
			<em><?php echo esc_html( $event['produzione'] ); ?></em>
		<?php endif; ?>
		<div class="entry-header-row">
			<div class="entry-header-main">
				<h1><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" rel="bookmark"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h1>
				<?php if ( ! empty( $event['protagonisti'] ) ) : ?>
					<h2 class="entry-header"><?php echo esc_html( $event['protagonisti'] ); ?></h2>
				<?php endif; ?>
			</div>
			<div class="entry-meta list-post-entry-meta">
				<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
					<?php foreach ( $terms as $term ) : ?>
						<span class="post-comments"><?php echo esc_html( $term->name ); ?></span>
					<?php endforeach; ?>
				<?php endif; ?>
			</div><!-- .entry-meta -->
		</div>

		<div class="colored-line-left"></div>
		<div class="clearfix"></div>

		<div class="post-img-wrap">
			<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
					<?php
					$full_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'cartellone-thumbnail' );
					$full_src = $full_src ? $full_src[0] : '';
					$thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );
					$thumb_src = $thumb_src ? $thumb_src[0] : '';
					if ( $full_src && $thumb_src ) : ?>
						<picture>
							<source media="(max-width: 600px)" srcset="<?php echo esc_url( $thumb_src ); ?>">
							<img decoding="async" style="width: 100%;" src="<?php echo esc_url( $full_src ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>">
						</picture>
					<?php else : ?>
						<?php echo get_the_post_thumbnail( $post_id, 'cartellone-thumbnail', array( 'style' => 'width: 100%;' ) ); ?>
					<?php endif; ?>
				</a>
			<?php else : ?>
				<img src="<?php echo esc_url( apply_filters( 'cartellone_placeholder_image_url', CARTELLONE_URL . 'public/img/cartellone-plugin-placeholder.png' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" style="width: 100%; display: block;">
			<?php endif; ?>
			<?php if ( ! empty( $event['data'] ) ) : ?>
				<div class="post-date">
					<span class="post-date-day"><?php echo esc_html( date_i18n( 'd', (int) $event['data'] ) ); ?></span>
					<span class="post-date-month"><?php echo esc_html( date_i18n( 'M', (int) $event['data'] ) ); ?></span>
					<span class="post-date-year"><?php echo esc_html( date_i18n( 'Y', (int) $event['data'] ) ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php require CARTELLONE_PATH . 'public/partials/cartellone-single-lineup-ticket.php'; ?>

		<div class="clearfix"></div>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<?php
		$content = get_post_field( 'post_content', $post_id );
		$extended = get_extended( $content );

		if ( ! empty( $extended['extended'] ) ) {
			echo apply_filters( 'the_content', $extended['main'] );
		} else {
			$excerpt = get_the_excerpt( $post_id );
			if ( empty( $excerpt ) ) {
				$excerpt = wp_trim_words( $content, 55 );
			}
			echo apply_filters( 'the_excerpt', $excerpt );
		}
		echo '<a class="moretag" href="' . esc_url( get_permalink( $post_id ) ) . '"><span class="screen-reader-text">' . sprintf( esc_html__( 'Leggi di piu a riguardo %s', 'cartellone' ), get_the_title( $post_id ) ) . '</span>Leggi di più</a>';
		?>
	</div><!-- .entry-content -->
</article>
