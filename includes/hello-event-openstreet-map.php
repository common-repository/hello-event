<?php
/*
* Define shortcodes for geocoding and showing OpenStreet maps
*
*/

// Lagt till admin_enq scripts + alltid anropa enqu resources
// Saknas fortfarande div map i editors

namespace Tekomatik\HelloEvent;

class Hello_Event_OpenStreet_Map {
  public function __construct() {
    $this->need_resources = false;
    add_action('wp_enqueue_scripts', array($this, 'register_resources'));
    add_shortcode( 'hello-event-openstreet-map', array($this, 'openstreet_map'));
    add_action('admin_enqueue_scripts', array($this, 'register_resources'));
  }

  function register_resources() {
    
    if (isset(get_option( 'hello_event')['hello_field_select_map_api']) &&
          get_option( 'hello_event')['hello_field_select_map_api'] == 'openstreetmap') {
      wp_register_style('hello-openstreet_maps', plugins_url('leaflet/leaflet.css', __FILE__));
      wp_register_script('hello-openstreet_maps', plugins_url('leaflet/leaflet.js', __FILE__), ['jquery'], '', true);
      wp_register_script('hello-openmaps', plugins_url('js/hello_openstreet_maps.js', __FILE__), ['jquery', 'hello-openstreet_maps'], '', true );
      wp_register_style( 'hello_event_maps', plugins_url('css/frontend-maps.css', __FILE__) );
    }
    // $this->enqueue_resources_if_needed();
  }
  
  function enqueue_resources_if_needed(){
    wp_enqueue_style('hello-openstreet_maps');
    wp_enqueue_script('hello-openstreet_maps');
    wp_enqueue_script('hello-openmaps');
    wp_enqueue_style('hello_event_maps');
  }
  
  
  public function openstreet_map($args) {
    global $post;
    if (isset(get_option( 'hello_event')['hello_field_select_map_api']) &&
          get_option( 'hello_event')['hello_field_select_map_api'] == 'openstreetmap') {
      $this->enqueue_resources_if_needed();

      $post_id = $post->ID;
      $args = shortcode_atts( array(
           'event_id' => false,
           'location' => false,
           'lat' => false,
           'lng' => false,
      ), $args );
      $event_id = $args['event_id'] ? $args['event_id'] : $post_id;
      $lat = $args['lat'] ? $args['lat'] : get_post_meta($event_id, 'location_lat', true);
      $lat = $lat ? $lat : 53; //Dummy location
      $lng = $args['lng'] ? $args['lng'] : get_post_meta($event_id, 'location_lng', true);
      $lng = $lng ? $lng : 10; //Dummy location
      $location = $args['location'] ? $args['location'] : get_post_meta($event_id, 'location', true);
      $location = $location ? $location : "New York"; //Dummy location
    
      $html = '';
      // $html .= "LAT=$lat LNG=$lng LOCATION=$location";
      $html .= '<div id="open-map"></div>';
      $html .= "<script>var lat=".$lat.";" ;
      $html .= "        var lng=".$lng."; "; 
      $html .= '        var loc="'.$location.'"; ';
      $html .= '        var post_id="'.$event_id.'"; ';
      $html .= "</script>";
      return $html;
    }
    else return __('You have not selected OpenStreet Map for the maps', 'hello_event'); 
  }
} // End of class
$hello_event_openstreet_map_object = new Hello_Event_OpenStreet_Map;


?>