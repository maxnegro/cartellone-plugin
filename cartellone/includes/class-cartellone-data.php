<?php

/**
* Used for event data handling
*
* @link       http://photomarketing.it
* @since      1.0.0
*
* @package    Cartellone
* @subpackage Cartellone/includes
*/

/**
* Used for event data handling.
*
* This class defines all code necessary to treat event related data.
*
* @since      1.0.0
* @package    Cartellone
* @subpackage Cartellone/includes
* @author     Massimiliano Masserelli <info@photomarketing.it>
*/
class Cartellone_Data {

  private $event;

  private $post_id;

  /**
  * Inits event attribute.
  *
  * @since 1.0.0
  */
  public function __construct( $post_id = NULL) {
    $this->event = array(
      'data' => NULL,
      'ora' => NULL,
      'produzione' => NULL,
      'protagonisti' => NULL,
      'credits' => NULL,
      'vivaticket' => NULL
    );
    $this->post_id = NULL;

    if ( ! empty ( $post_id )) {
      $this->post_id = $post_id;
      $this->load_data();
    }
  }

  /**
  * Loads event data from wordpress DB.
  *
  * @since 1.0.0
  */
  public function load_data() {
    if (empty($this->post_id)) {
      return false;
    }
    $data = get_post_meta ( $this->post_id, 'cartellone_data', 'true');
    if (!empty($data)) {
      $this->event = $data;
    }
    // $this->tipo = wp_get_object_terms($this->post_id, 'tipo');
    return true;
  }

  /**
  * Saves event data to wordpress DB.
  *
  * @since 1.0.0
  */
  public function save_data() {
    if (!empty($this->post_id)) {
      $retcode = update_post_meta( $this->post_id, 'cartellone_data', $this->event );
      update_post_meta($this->post_id, 'cartellone_data_sort', $this->event['data']);

      // wp_set_object_terms($this->post_id, $this->tipo ,'tipo');
    } else {
      return false;
    }
    return $retcode;
  }

  public function load_form_fields() {
    if (empty($this->post_id)) {
      return false;
    }
    // Check for commen conditions where saving should not occur
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    return;
    if (!isset($_POST['cartellone_nonce']) || !wp_verify_nonce($_POST['cartellone_nonce'], '_cartellone_nonce'))
    return;
    if (!current_user_can('edit_post', $this->post_id))
    return;

    if (isset($_POST['cartellone_data'])) {
      $datetimestamp = DateTime::createFromFormat('!d/m/Y', $_POST['cartellone_data']);
      if (is_object($datetimestamp)) {
        $this->event['data'] = $datetimestamp->getTimeStamp();
      }
    }
    if (isset($_POST['cartellone_ora'])) {
      $this->event['ora'] = esc_attr($_POST['cartellone_ora']);
    }
    if (isset($_POST['cartellone_produzione'])) {
      $this->event['produzione'] = esc_attr($_POST['cartellone_produzione']);
    }
    if (isset($_POST['cartellone_protagonisti'])) {
      $this->event['protagonisti'] = esc_attr($_POST['cartellone_protagonisti']);
    }
    if (isset($_POST['cartellone_credits'])) {
      $this->event['credits'] = esc_attr($_POST['cartellone_credits']);
    }
    if (isset($_POST['cartellone_vivaticket'])) {
      $this->event['vivaticket'] = esc_attr($_POST['cartellone_vivaticket']);
    }
  }

  public function getData() {
    if (empty($this->post_id)) { return NULL; }
    return $this->event;
  }

  // Check per verificare la possibilità di acquistare biglietti online sul
	// circuito VivaTicket. Al momento un controllo statico, da aggiornare anno
	// per anno, TODO: si potrebbe creare apposita pagina di configurazione.
	public function season_open() {
		return (time() > mktime(0,0,0,10,26,2017));
	}

  // Generate schema.org markup for current event
  public function get_microdata() {
    $payload = array(
      "@context" => "http://schema.org",
      "@type" => "Event",
      "location" => array(
        "@type" => "Place",
        "name" => "Teatro Comunale Bibiena",
        "address" => array(
          "@type" => "PostalAddress",
          "streetAddress" => "Via 2 Agosto 1980 n.114",
          "addressLocality" => "S.Agata Bolognese",
          "postalCode" => "40019",
          "addressRegion" => "BO",
          "addressCountry" => "IT"
        ),
      ),
      "startDate" => date("c", $this->event['data']),
      "performer" => array(
        "@Type" => "PerforminGroup",
        "name" => $this->event["protagonisti"]
      )
    );
    $evPostObj = get_post($this->post_id, OBJECT, "display");
    if (is_object($evPostObj)) {
      if (!empty($evPostObj->post_title)) {
        $payload["name"] = $evPostObj->post_title;
      }
      if (!empty($evPostObj->post_content)) {
        $payload["description"] = apply_filters('the_content', $evPostObj->post_content);
      }
    }
    if ($evlink = get_permalink($this->post_id)) {
      $payload["url"] = $evlink;
    }
    if (has_post_thumbnail($this->post_id)) {
      $payload["image"] = get_the_post_thumbnail_url($this->post_id, "full");
    }
    if (!empty($this->event["vivaticket"])) {
      $payload["offers"] = array (
        "@type" => "Offer",
        "url" => $this->event["vivaticket"],
        "validFrom" => date("c", mktime(0,0,0,10,26,2017))
      );
    }
  }
}
