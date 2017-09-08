<?php
/**
* The template for displaying all single posts and attachments
*
* @package Cartellone
* @subpackage templates
* @since  1.0
*/
$user = wp_get_current_user();

/**
* Modify post navigation
*/
add_filter('get_next_post_join', 'spettacoli_post_join');
add_filter('get_previous_post_join', 'spettacoli_post_join');
function spettacoli_post_join($join) {
	global $wpdb;
	$sql  = sprintf("INNER JOIN wp_postmeta AS m ON p.ID = m.post_id ");
	$sql .= " INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
	return $sql;
}

add_filter('get_previous_post_sort', 'spettacoli_post_sort_date_previous', 10, 1);
add_filter('get_next_post_sort', 'spettacoli_post_sort_date_next', 10, 1);

add_filter('get_previous_post_where', 'spettacoli_post_where_previous', 10);
add_filter('get_next_post_where', 'spettacoli_post_where_next', 10);

function spettacoli_post_sort_date_previous($orderby) {
	return 'ORDER BY m.meta_value DESC, p.post_title DESC LIMIT 1';
}

function spettacoli_post_sort_date_next($orderby) {
	return 'ORDER BY m.meta_value ASC, p.post_title ASC LIMIT 1';
}

function spettacoli_post_where_previous($original) {
	global $wpdb; global $post;
	$where 		= '';
	$taxonomy  	= 'stagione';
	$where 		= $wpdb->prepare( "AND tt.taxonomy = %s", $taxonomy );
	if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) )
		return $original ;

	$term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

	$term_array = array_map( 'intval', $term_array );

	if ( ! $term_array || is_wp_error( $term_array ) )
		return $original ;

	$where 		= " AND tt.term_id IN (" . implode( ',', $term_array ) . ")";

	$sql = $wpdb->prepare("WHERE p.post_type = 'spettacoli' AND (p.post_status = 'publish' OR p.post_status = 'private') AND m.meta_key = 'cartellone_data_sort' AND m.meta_value < %d AND ". $where, get_post_meta($post->ID, 'cartellone_data_sort' ));
	return $sql;
}

function spettacoli_post_where_next($original) {
	global $wpdb; global $post;
	$where 		= '';
	$taxonomy  	= 'stagione';
	$where 		= $wpdb->prepare( "AND tt.taxonomy = %s", $taxonomy );
	if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) )
		return $original ;

	$term_array = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

	$term_array = array_map( 'intval', $term_array );

	if ( ! $term_array || is_wp_error( $term_array ) )
		return $original ;

	$where 		= " AND tt.term_id IN (" . implode( ',', $term_array ) . ")";
	$sql = $wpdb->prepare("WHERE p.post_type = 'spettacoli' AND (p.post_status = 'publish' OR p.post_status = 'private') AND m.meta_key = 'cartellone_data_sort' AND m.meta_value > %d " . $where, get_post_meta($post->ID, 'cartellone_data_sort'));
	return $sql;
}

get_header(); ?>
</div></header>
<div id="content" class="site-content"><!-- site main -->
		<?php
		// Start the loop.
		while ( have_posts() ) :
			the_post();

			$evdata = new Cartellone_Data($post->ID);
			$ev = $evdata->getData();
		?>
		<div class="container" style="margin-top: 15px;">
			<?php
				printf('<div>');
				printf('<strong><big>%s</big></strong> ', date_i18n('l j F Y', $ev['data']));
				if (!empty($ev['ora'])) { printf('alle %s', $ev['ora']); }
				printf('</div>');
			?>
		</div>
		<div class="container">
			<div style="float:right; margin-top: 25px;">
				<?php
				$terms = get_the_terms($post, 'tipo');
				if ($terms) {
					// var_dump($terms);
					foreach ($terms as $term) {
						printf('<span style="background-color: red; color: white; padding: 8px;">%s</span>', $term->name);
					}
				}
				?>

			</div>
			<div class="container">
				<?php if (!empty($ev['produzione'])) printf('<em>%s</em>', $ev['produzione']); ?>
				<h1><?php echo $ev['protagonisti']; ?></h1>
				<h2 class="page-title"><?php the_title(); ?></h2>
			</div>
		</header>

		<div class="container">
			<?php
			if (has_post_thumbnail()) {
				echo "<center>";
				the_post_thumbnail();
				echo "</center>\n";
			}
			?>
			<h3><?php echo preg_replace('/\n/','<br>',$ev['credits']); ?></h3>

			<?php the_content(); ?>

		<br />
		<?php
		  if ((!empty($ev['vivaticket'])) && ($ev['data'] >= time()) && $evdata->season_open()) {
				printf('Acquista online: <a href="%s" target="_blank"><img src="%s" alt="Acquista online"></a>', $ev['vivaticket'], plugins_url( '../public/img/vivaticket.png', __FILE__ ) );
			}
		?>
		<?php
		if ( is_singular( 'spettacoli' ) ) {
			// Previous/next post navigation.
			the_post_navigation( array(
				'next_text' => '<span class="meta-nav" aria-hidden="true">&nbsp;&raquo;</span> ' .
					'<span class="screen-reader-text">' . __( 'Next post:', 'twentysixteen' ) . '</span> ' .
					'<span class="post-title">%title</span>',
				'prev_text' => '<span class="meta-nav" aria-hidden="true">&nbsp;&laquo;</span> ' .
					'<span class="screen-reader-text">' . __( 'Previous post:', 'twentysixteen' ) . '</span> ' .
				'	<span class="post-title">%title</span>',
				'screen_reader_text' => ' '
				) );
			}
			// End of the loop.
		endwhile;
		?>
	</div><!-- .site-main -->

</div><!-- .content-area -->

<?php get_footer(); ?>
