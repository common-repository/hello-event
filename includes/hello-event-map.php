<?php
/*
* Provides a generifc "get_map" method that will use the map service selected in the settings
*
*/

namespace Tekomatik\HelloEvent;

class Hello_Event_Map {
  
  function __construct() {
    //add_action('init', array($this, 'plugin_init'), 1);
    //debug_log("In construct");
    $this->plugin_init();
    add_shortcode( 'hello-event-map', array($this, 'get_map'));
  }
  
  function plugin_init() { 
		if ( ! defined( 'MY_DIR' ) ) {
			define( 'MY_DIR', trailingslashit( dirname( __FILE__ ) ) );
		}
		// Include external classes.
	  require_once MY_DIR . 'hello-event-google-map.php';
	  require_once MY_DIR . 'hello-event-openstreet-map.php';
  }
  
  
  function get_map($args) {
    global $post;
    $args = shortcode_atts( array(
        'id' => $post->ID,
        'show_map_links' => false,
        'show_map' => true,
    ), $args );
    
    $id = $args['id'];
    $location = get_post_meta($id, 'location', true);
    
    $coordinates = get_post_meta($id, 'coordinates');
    // global $hello_event_again;
    // debug_log("MAP LOCATION for id $id : " . print_r($location, true));
    // $mycat = $hello_event_again->get_category_of_event_id($id);
    // debug_log("Category ". print_r($mycat, true));
    // debug_log("Coordinates ". print_r($coordinates, true));
    
    $html = '';
    $html = '<div class="event-location">';
    $html .= '<b>' . __( 'Location', 'hello-event' ) . ':</b> ';
    if ($location) {
      $html .= $location;
      if ($args['show_map_links']) {
        $escaped_location = urlencode($location);
        $google_url = "https://www.google.com/maps/search/?api=1&zoom=14&query=".$escaped_location;
        $google_url = "https://www.google.com/maps/search/?api=1&zoom=14&q=".$coordinates['lat'].",".$coordinates['lng'];
        $open_street_url = "http://www.openstreetmap.org/search?query=".$escaped_location;
        // $html .= '<br/><a href="'.$google_url.'" target="_blank">' . __( 'See in Google Maps', 'hello-event' ) .'</a>';
        $html .= '<br/><a href="'.$open_street_url.'" target="_blank">' . __( 'See in Open Street Map', 'hello-event' ) .'</a>';
      }
    }
    else
      $html .= __('No location given', 'hello-event');
    $html .='</div>';
    
    if ($args['show_map']) {
      $mapservice = isset(get_option( 'hello_event')['hello_field_select_map_api']) ? get_option( 'hello_event')['hello_field_select_map_api'] : 'undefined';
      switch ($mapservice) {
        case 'openstreetmap':
          $html .= $this->get_openstreet_map($id);
          break;
      
        case 'googlemap' :
          $html .= $this->get_google_map($id);
          break;
      
        case 'undefined' :
          $html .= __("Undefined map services", 'hello-event');
          break;
      
      }
    }
    return $html; 
  }
  
  // ----- Return Google map and optionally do geoencode and update the location coordinates via Ajax
  function get_google_map($post_id) {
    if (isset(get_option( 'hello_event')['hello_field_google_maps']) &&
        get_option( 'hello_event')['hello_field_google_maps']) {
      $key = get_option( 'hello_event')['hello_field_google_maps'];
      $location = get_post_meta($post_id, 'location', true);
      $lat = get_post_meta($post_id, 'location_lat', true) ?  get_post_meta($post_id, 'location_lat', true) : 53;
      $lng = get_post_meta($post_id, 'location_lng', true) ? get_post_meta($post_id, 'location_lng', true) : 10;
      if (!$location) { return "Location not set"; }
      $html = do_shortcode('[hello-event-google-map event_id="'.$post_id.'"]');
      return $html;
    }
    else { return __('You need to register your Google Maps API Key before the map can be shown', 'hello-event'); }
  }

  // ----- Return OpenStreet map and optionally do geoencode and update the location coordinates via Ajax
  function get_openstreet_map($post_id) {
    $location = get_post_meta($post_id, 'location', true);
    $lat = get_post_meta($post_id, 'location_lat', true) ?  get_post_meta($post_id, 'location_lat', true) : 53;
    $lng = get_post_meta($post_id, 'location_lng', true) ? get_post_meta($post_id, 'location_lng', true) : 10;
    if (!$location) { return "Location not set"; }
    $html = do_shortcode('[hello-event-openstreet-map event_id="'.$post_id.'"]');
    return $html;
  }
} // End of class
global $hello_event_map_object;
$hello_event_map_object = new Hello_Event_Map;


?>