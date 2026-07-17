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

			add_filter( 'cartellone_skip_single_event_filter', '__return_true' );

			$evdata = new \Cartellone\Data( get_the_ID() );
			$event = $evdata->get_data();
			$terms  = get_the_terms( get_the_ID(), CARTELLONE_TAX_TIPO );
			?>

			<?php require CARTELLONE_PATH . 'public/partials/cartellone-single-header.php'; ?>

			<div class="entry-content">
				<?php
				$content = get_post_field( 'post_content', get_the_ID() );
				echo apply_filters( 'the_content', $content );
				?>
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
