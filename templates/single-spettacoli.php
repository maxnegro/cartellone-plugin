<?php
/**
 * The template for displaying all single spettacoli
 *
 * @package Cartellone
 */

get_header();

if ( ! have_posts() ) {
	get_template_part( 'template-parts/content', 'none' );
	return;
}
?>

<div id="primary" class="content-area cartellone-single">
	<main id="main" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			$evdata = new \Cartellone\Data( get_the_ID() );
			$event = $evdata->get_data();
			$terms  = get_the_terms( get_the_ID(), CARTELLONE_TAX_TIPO );
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'border-bottom-hover' ); ?>>
				<header class="entry-header">
					<?php if ( ! empty( $event['produzione'] ) ) : ?>
						<em><?php echo esc_html( $event['produzione'] ); ?></em>
					<?php endif; ?>
					<div class="entry-header-row">
						<div class="entry-header-main">
							<h1><?php the_title(); ?></h1>
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
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
								<?php
								$full_src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'cartellone-thumbnail' );
								$full_src = $full_src ? $full_src[0] : '';
								$thumb_src = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' );
								$thumb_src = $thumb_src ? $thumb_src[0] : '';
								if ( $full_src && $thumb_src ) : ?>
									<picture>
										<source media="(max-width: 600px)" srcset="<?php echo esc_url( $thumb_src ); ?>">
										<img decoding="async" style="width: 100%;" src="<?php echo esc_url( $full_src ); ?>" alt="<?php the_title_attribute(); ?>">
									</picture>
								<?php else : ?>
									<?php the_post_thumbnail( 'cartellone-thumbnail', array( 'style' => 'width: 100%;' ) ); ?>
								<?php endif; ?>
							</a>
						<?php else : ?>
							<div class="post-img-placeholder"></div>
						<?php endif; ?>
						<?php if ( ! empty( $event['data'] ) ) : ?>
							<div class="post-date">
								<span class="post-date-day"><?php echo esc_html( date_i18n( 'd', (int) $event['data'] ) ); ?></span>
								<span class="post-date-month"><?php echo esc_html( date_i18n( 'M', (int) $event['data'] ) ); ?></span>
								<span class="post-date-year"><?php echo esc_html( date_i18n( 'Y', (int) $event['data'] ) ); ?></span>
							</div>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $event['credits'] ) ) : ?>
						<div class="lineup">
							<?php echo wp_kses_post( nl2br( $event['credits'] ) ); ?>
						</div>
					<?php endif; ?>

					<div class="clearfix"></div>
				</header><!-- .entry-header -->

				<div class="entry-content">
					<?php the_content(); ?>

					<?php require CARTELLONE_PATH . 'public/partials/cartellone-public-event-ticket.php'; ?>
				</div><!-- .entry-content -->
			</article>

			<?php
			$json_data = $evdata->get_microdata_json();

			if ( $json_data ) {
				echo '<script type="application/ld+json">';
				echo $json_data;
				echo "</script>\n";
			}
			?>

			<?php
			if ( is_singular( 'spettacoli' ) ) {
				the_post_navigation(
					array(
						'next_text' => '<span class="meta-nav" aria-hidden="true">&nbsp;&raquo;</span> ' .
							'<span class="screen-reader-text">' . esc_html__( 'Next post:', 'cartellone' ) . '</span> ' .
							'<span class="post-title">%title</span>',
						'prev_text' => '<span class="meta-nav" aria-hidden="true">&nbsp;&laquo;</span> ' .
							'<span class="screen-reader-text">' . esc_html__( 'Previous post:', 'cartellone' ) . '</span> ' .
							'<span class="post-title">%title</span>',
						'screen_reader_text' => ' ',
					)
				);
			}
		?>

		<?php endwhile; ?>

	</main>
</div>

<?php
get_footer();
