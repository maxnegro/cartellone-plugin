<?php
// import-cartellone-xml.php
$xml_file = dirname( __FILE__ ) . '/cartellone-spettacoli.xml';

if ( ! file_exists( $xml_file ) ) {
    die( "XML file not found: $xml_file\n" );
}

$xml = simplexml_load_file( $xml_file );

if ( ! $xml ) {
    die( "Failed to load XML.\n" );
}

$count = 0;

foreach ( $xml->channel->item as $item ) {
    $wp       = $item->children( 'wp', true );
    $dc       = $item->children( 'dc', true );
    $content  = $item->children( 'content', true );
    $excerpt  = $item->children( 'excerpt', true );

    $post_id   = (int) $wp->post_id;
    $post_title = (string) $item->title;
    $post_content = (string) $content->encoded;
    $post_excerpt = (string) $excerpt->encoded;
    $post_status = (string) $wp->status;
    $post_name  = (string) $wp->post_name;
    $post_type  = (string) $wp->post_type;
    $post_date  = (string) $wp->post_date;
    $post_date_gmt = (string) $wp->post_date_gmt;
    $post_modified = (string) $wp->post_modified;
    $post_modified_gmt = (string) $wp->post_modified_gmt;
    $post_author = 1;

    $creator = (string) $dc->creator;
    if ( $creator ) {
        $user = get_user_by( 'login', $creator );
        if ( $user ) {
            $post_author = $user->ID;
        }
    }

    $post_data = array(
        'ID' => $post_id,
        'post_title' => $post_title,
        'post_content' => $post_content,
        'post_excerpt' => $post_excerpt,
        'post_status' => $post_status ?: 'publish',
        'post_name' => $post_name,
        'post_type' => $post_type ?: 'spettacoli',
        'post_date' => $post_date,
        'post_date_gmt' => $post_date_gmt,
        'post_modified' => $post_modified,
        'post_modified_gmt' => $post_modified_gmt,
        'post_author' => $post_author,
    );

    $post_id = wp_insert_post( $post_data );

    if ( is_wp_error( $post_id ) ) {
        echo "ERROR inserting post: " . $post_id->get_error_message() . "\n";
        continue;
    }

    $tipo_terms = array();
    $stagione_terms = array();

    foreach ( $item->category as $cat ) {
        $domain = (string) $cat->attributes()->domain;
        $slug = (string) $cat->attributes()->nicename;
        if ( $domain === 'tipo' ) {
            $tipo_terms[] = $slug;
        } elseif ( $domain === 'stagione' ) {
            $stagione_terms[] = $slug;
        }
    }

    if ( ! empty( $tipo_terms ) ) {
        wp_set_object_terms( $post_id, $tipo_terms, 'tipo', false );
    }

    if ( ! empty( $stagione_terms ) ) {
        wp_set_object_terms( $post_id, $stagione_terms, 'stagione', false );
    }

    foreach ( $wp->postmeta as $meta ) {
        $key = (string) $meta->meta_key;
        $value = (string) $meta->meta_value;
        update_post_meta( $post_id, $key, $value );
    }

    $count++;
}

echo "Imported $count posts from XML.\n";
