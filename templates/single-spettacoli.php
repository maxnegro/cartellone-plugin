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

			global $post;
			$evdata = new \Cartellone\Data( $post->ID );
			$ev     = $evdata->get_data();
			$terms  = get_the_terms( $post->ID, CARTELLONE_TAX_TIPO );
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'cartellone-single-event' ); ?>>

				<header class="cartellone-single-event__header">
					<div class="cartellone-single-event__date">
						<time datetime="<?php echo esc_attr( date_i18n( 'c', (int) $ev['data'] ) ); ?>">
							<?php echo esc_html( date_i18n( 'l j F Y', (int) $ev['data'] ) ); ?>
						</time>
						<?php if ( ! empty( $ev['ora'] ) ) : ?>
							<span class="cartellone-single-event__time">
								<?php esc_html_e( 'at', 'cartellone' ); ?>
								<?php echo esc_html( $ev['ora'] ); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="cartellone-single-event__types">
						<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
							<?php foreach ( $terms as $term ) : ?>
								<span class="cartellone-single-event__type cartellone-single-event__type--<?php echo esc_attr( sanitize_html_class( $term->slug ) ); ?>">
									<?php echo esc_html( $term->name ); ?>
								</span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<div class="cartellone-single-event__production">
						<?php if ( ! empty( $ev['produzione'] ) ) : ?>
							<em><?php echo esc_html( $ev['produzione'] ); ?></em>
						<?php endif; ?>
					</div>

					<h1 class="cartellone-single-event__title">
						<?php echo esc_html( $ev['protagonisti'] ?? '' ); ?>
					</h1>
					<h2 class="cartellone-single-event__subtitle">
						<?php the_title(); ?>
					</h2>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="cartellone-single-event__thumbnail">
						<?php the_post_thumbnail( 'large' ); ?>
					</div>
				<?php endif; ?>

				<div class="cartellone-single-event__content">
					<div class="cartellone-single-event__credits">
						<?php echo wp_kses_post( nl2br( $ev['credits'] ?? '' ) ); ?>
					</div>

					<div class="cartellone-single-event__body">
						<?php the_content(); ?>
					</div>

					<?php require CARTELLONE_PATH . 'public/partials/cartellone-public-event-ticket.php'; ?>
				</div>

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

			</article>

			<?php
			$json_data = $evdata->get_microdata_json();

			if ( $json_data ) {
				echo '<script type="application/ld+json">';
				echo $json_data;
				echo "</script>\n";
			}
			?>

		<?php endwhile; ?>

	</main>
</div>

<?php
get_footer();
