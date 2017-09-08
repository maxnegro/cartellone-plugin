<?php
/**
 * Template per la lista spettacoli con shortcode stagione
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Cartellone
 * @subpackage Cartellone/public/partials
 */
?>

<?php
/**
 * The template part for displaying results in search pages.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package llorix-one-lite
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'border-bottom-hover' ); ?>>
	<header class="entry-header">
    <em><?php echo $ev['produzione']; ?>
    <h1 class="entry-title"><?php echo $ev['protagonisti']; ?></h1>
    <?php the_title( sprintf( '<h2><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
    <div class="colored-line-left"></div>
    <div class="clearfix"></div>

			<div class="post-img-wrap">
			 	<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" >

					<?php
						if ( has_post_thumbnail() ) { // check if the post has a Post Thumbnail assigned to it.
					?>
					<?php
					$image_id = get_post_thumbnail_id();
					$image_url_big = wp_get_attachment_image_src( $image_id,'llorix-one-lite-post-thumbnail-big', true );
					$image_url_mobile = wp_get_attachment_image_src( $image_id,'llorix-one-lite-post-thumbnail-mobile', true );
					?>
					<picture>
					<source media="(max-width: 600px)" srcset="<?php echo esc_url( $image_url_mobile[0] ); ?>">
					<img style="width: 100%;" src="<?php echo esc_url( $image_url_big[0] ); ?>" alt="<?php the_title_attribute(); ?>">
					</picture>
					<?php
						} else {
					?>
          <!--
					<picture>
					<source media="(max-width: 600px)" srcset=" <?php echo llorix_one_lite_get_file( '/images/no-thumbnail-mobile.jpg' ); ?> ">
					<img src="<?php echo llorix_one_lite_get_file( '/images/no-thumbnail.jpg' ); ?>" alt="<?php the_title_attribute(); ?>">
        </picture>-->
					<?php } ?>

				</a>
				<div class="post-date">
					<span class="post-date-day"><?php echo date_i18n('d', $ev['data']); ?></span>
					<span class="post-date-month"><?php echo date_i18n('M', $ev['data']); ?></span>
          <span class="post-date-year"><?php echo date_i18n('Y', $ev['data']); ?></span>
	      </div>
			</div>

			<div class="entry-meta list-post-entry-meta">
					<?php
          $terms = get_the_terms(get_the_ID(), 'tipo');
  				if ($terms) {
  					// var_dump($terms);
  					foreach ($terms as $term) {
  						printf('<span class="post-comments" style="background-color: red; color: white; padding: 0px 8px;">%s</span>', $term->name);
  					}
  				}
					?>
          <div class="post-author" style="width: 80%">
  					<?php echo preg_replace('/\n/', '<br>', $ev['credits']); ?>
  				</div>
			</div><!-- .entry-meta -->
      <div class="clearfix"></div>
	</header><!-- .entry-header -->
	<div class="entry-content">
		<?php
			$ismore = strpos( get_the_content(), '<!--more-->' );
			if ( $ismore ) : the_content( sprintf( esc_html__( 'Read more %s ...','llorix-one-lite' ), '<span class="screen-reader-text">' . esc_html__( 'about ', 'llorix-one-lite' ) . get_the_title() . '</span>' ) );
			else : the_excerpt();
			endif;

			// if ((!empty($ev['vivaticket'])) && ($ev['data'] >= time())) {
			if ((!empty($ev['vivaticket'])) && ($ev['data'] >= time()) && $evdata->season_open()) {
				printf('Acquista online: <a href="%s" target="_blank"><img src="%s" alt="Acquista online"></a>', $ev['vivaticket'], plugins_url( '../img/vivaticket.png', __FILE__ ) );
			}
		?>
	</div><!-- .entry-content -->
</article><!-- #post-## -->
<hr>
