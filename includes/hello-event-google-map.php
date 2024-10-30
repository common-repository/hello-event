<?php
/*
* Define shortcodes for geocoding and showin Google maps
*
*/

namespace Tekomatik\HelloEvent;

class Hello_Event_Google_Map {
  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_resources'));
    add_shortcode( 'hello-event-google-map', array($this, 'google_map'));
  }
  
  function enqueue_resources() {
    wp_register_script('hello-gmaps', plugins_url('js/hello_google_maps.js', __FILE__), ['jquery'], '', true );
    if (isset(get_option( 'hello_event')['hello_field_google_maps']) &&
        get_option( 'hello_event')['hello_field_google_maps']) {
        $key = get_option( 'hello_event')['hello_field_google_maps'];
        wp_register_script('hello-google_maps',
                    'https://maps.googleapis.com/maps/api/js?key='.$key.'&callback=initMap', ['hello-gmaps'], '', true);
    }
    wp_register_style( 'hello_event_maps', plugins_url('css/frontend-maps.css', __FILE__) ); 
  }
  
  public function google_map($args) {
    global $post;
    if (isset(get_option( 'hello_event')['hello_field_google_maps']) &&
        get_option( 'hello_event')['hello_field_google_maps']) {
        $key = get_option( 'hello_event')['hello_field_google_maps'];
        wp_enqueue_script('hello-gmaps');
        wp_enqueue_script('hello-google_maps');
        wp_enqueue_style('hello_event_maps');
    }

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
    
    if (isset(get_option( 'hello_event')['hello_field_google_maps']) &&
        get_option( 'hello_event')['hello_field_google_maps']) {
      $key = get_option( 'hello_event')['hello_field_google_maps'];
      
      
      $html = '';
      $html .= '<div id="map"></div>';
      $html .= "<script>var lat=".$lat.";" ;
      $html .= "        var lng=".$lng."; "; 
      $html .= '        var loc="'.$location.'"; ';
      $html .= '        var post_id="'.$event_id.'"; ';
      $html .= "</script>";
      return $html;
    }
    else return __('You need to register your Google Maps API Key before the map can be shown', 'hello_event'); 
  }
} // End of class
$hello_event_google_map_object = new Hello_Event_Google_Map;


?>