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

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<div class="container" style="margin-top: 15px;">
					<div>
						<strong><big><?php echo esc_html( date_i18n( 'l j F Y', (int) $ev['data'] ) ); ?></big></strong>
						<?php if ( ! empty( $ev['ora'] ) ) : ?>
							<?php printf( esc_html__( 'alle %s', 'cartellone' ), esc_html( $ev['ora'] ) ); ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="container">
					<div style="float:right; margin-top: 25px; margin-left: 1em;">
						<?php if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) : ?>
							<?php foreach ( $terms as $term ) : ?>
								<span style="background-color: red; color: white; padding: 8px;"><?php echo esc_html( $term->name ); ?></span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<?php if ( ! empty( $ev['produzione'] ) ) : ?>
						<em><?php echo esc_html( $ev['produzione'] ); ?></em>
					<?php endif; ?>
					<h2><?php echo esc_html( $ev['protagonisti'] ?? '' ); ?></h2>
					<h1 class="page-title"><?php the_title(); ?></h1>
				</div>

				<div class="container">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="cartellone-single-event__thumbnail">
							<?php the_post_thumbnail( 'large' ); ?>
						</div>
					<?php endif; ?>
				    <?php if ( ! empty( $ev['credits'] ?? '' ) ) : ?>
						<h3 class="lineup"><?php echo wp_kses_post( nl2br( $ev['credits'] ?? '' ) ); ?></h3>
					<?php endif; ?>

					<div class="entry-content">
						<?php the_content(); ?>
					</div>

					<?php require CARTELLONE_PATH . 'public/partials/cartellone-public-event-ticket.php'; ?>

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
				</div>

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
