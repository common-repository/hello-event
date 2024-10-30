<?php
/*
* Define shortcodes for event calendar
* Uses the jQuery Fullcalendar
*
*/

namespace Tekomatik\HelloEvent;
  
class Hello_Event_Calendar {
  
  public $cf_calendar_add_my_resources = false;
  
  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'calendar_register_my_resources'));
    add_action('wp_footer', array($this, 'calendar_insert_my_resources_if_necessary'));
    add_action('wp_ajax_hello_event_get_events', array($this, 'get_events'));
    add_action('wp_ajax_nopriv_hello_event_get_events', array($this, 'get_events'));
    add_shortcode( 'hello-event-calendar', array($this, 'handle_shortcode_calendar'));
  }


// ================== Function responding to Ajax returning the events for a period ============================
// To make this work:
// 1) Define the function
// 2) add action to connect wp_ajax with the function
// 3) make a call to wp_localize_script to define adminAjax as the URL to be called
// 4) Set up the ajax call in js with url = adminAjax and action = name of the function to call

function get_events() {
  // Called via ajax
  global $hello_event_object; // Will give us access to its methods
  remove_filter( 'the_title', 'wptexturize' ); // Stop WP from converting quotes into html quotes
  $start = $_REQUEST['start'] ;
  $end = $_REQUEST['end'] ;
  $event_page = isset($_REQUEST['event_page']) ? $_REQUEST['event_page'] : false;
  // Fullcalendar needs 1 day to be added to the end date
  // Event reguests from fullcalendar has an additional parameter: 'fix_end_date
  $fix_end_date = isset($_REQUEST['fix_end_date']) ? $_REQUEST['fix_end_date'] : false;
  $cf_query = array(
    'post_type' => HELLO_EVENT::EVENT_SLUG,
    'post_status' => 'publish',
    'meta_query' => array(
      array(
        'key' => 'start_date',
        'value' => $start,
        'compare' => '>='
      ),
      array(
        'key' => 'start_date',
        'value' => $end,
        'compare' => '<='
      )
    ),
    'nopaging' => true
  );
  // debug_log("Query 0 = " . print_r($cf_query,true));
  $cf_query = apply_filters('hello_event_calendar_get_events', $cf_query); // Allow add-ons to intercept
  // debug_log("Query 1 = " . print_r($cf_query,true));
  global $post;
  $the_posts = new \WP_Query($cf_query);
  // debug_log("Result: " . print_r($the_posts, true));
  $events = array();
  while($the_posts->have_posts()) {
    $the_posts->the_post();
    $ev = array();
    $id = get_the_ID();
    // debug_log("On with ID = " . $id);
    // Take into account custom event page when linking
    // $url = get_permalink($id);
    global $hello_event_object;
    $url = $hello_event_object->link_to_event_page(get_the_permalink(), $id, $event_page);
    $start_date = $hello_event_object->transform_iso_date(get_post_meta( $id, 'start_date', true ));
    $end_date = $hello_event_object->transform_iso_date(get_post_meta( $id, 'end_date', true ));
    $start_time = get_post_meta( $id, 'start_time', true );
    $end_time = get_post_meta( $id, 'end_time', true );
    if ( ($start_time == "00:00") && ($end_time == "00:00") )
      $start_time = "Full day";
    else
      $start_time = $hello_event_object->transform_iso_time($start_time);
    $location = get_post_meta( $id, 'location', true );
    $dates_html = "<div class='date'>$start_date - $end_date</div>";
    $start_html = "<div class='time'>$start_time</div>";
    $location_html = "<div class='location'>$location</div>";
    $see_more = '<div class="clearfix"><a href="'.$url.'" class="btn btn-success pull-right">'.__("See more", "hello-event").'</a>'; 
    $ev['title_long'] = get_the_title()." - ".$hello_event_object->transform_iso_date(get_post_meta( $id, 'start_date', true ));
    $ev['title'] = get_the_title();
    $ev['start'] = get_post_meta( $id, 'start_date', true );
    if ( get_post_meta( $id, 'heure', true) ) { $ev['start'] .= "T".get_post_meta( $id, 'heure', true); };
    $ev['end'] = get_post_meta( $id, 'end_date', true );
    if ( !$ev['end'] ) { $ev['end'] = get_post_meta( $id, 'start_date', true ); }
    // For the end date to display correctly in fullcalendar we need to add 1 day to the end date!
    if ($fix_end_date) {
      $end_fixed_date = new \DateTime($ev['end']);
      $end_fixed_date->modify('+1 day');
      $ev['end'] = $end_fixed_date->format('Y-m-d');
    }
    
    //$excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $post->ID));
    //$ev['description'] = $excerpt . $see_more ;
    $ev['description'] = "<div class='event-modal-description'>" . $dates_html . $start_html . $location_html . $hello_event_object->real_or_computed_excerpt($id) . $see_more ."</div>";
    $ev['url'] = $url;
    $ev = apply_filters('event-information', $ev, $id); // Allow other plugin to complement the event information
    array_push($events, $ev);
  };
  wp_reset_postdata();
  $events_to_test = array(array(
    "title_long" =>"Stage cuisine 2023 - 2023-05-05",
    "title"=>"Stage cuisine 2023",
    "start"=>"2023-05-05",
    "end"=>"2023-05-06",
    "description"=>"Villard de Lans, France."
  ));
	// cf_l("Events to test", $events_to_test);
	// cf_l("Events", $events);
  $events = json_encode($events);
  //$events = json_encode($events_to_test);
  echo $events;
  wp_die();
}



  function calendar_register_my_resources() {
    wp_register_script( 'moment',
        plugins_url('moment.min.js', __FILE__), ['jquery'], '', true );
    wp_register_script( 'fullcalendar',
        plugins_url('fullcalendar/dist/index.global.js', __FILE__), ['jquery', 'moment'], '', true );
     wp_register_script( 'hello_event_fullcalendar_french',
          plugins_url('fullcalendar/packages/core/locales/fr.global.js', __FILE__), ['jquery', 'moment'], '', true );
     wp_register_script( 'hello_event_fullcalendar_swedish',
         plugins_url('fullcalendar/packages/core/locales/sv.global.min.js', __FILE__), ['jquery', 'moment'], '', true );
    wp_register_script( 'hello_event_bootbox',
        plugins_url('js/bootbox.all.js', __FILE__), ['jquery'], '', true );
    wp_register_style( 'jquery_dialog', plugins_url('js/jquery-ui-1.12.1-2.custom-dialog/jquery-ui.min.css', __FILE__));
    wp_register_style( 'hello_event_frontend-calendar', plugins_url('css/frontend-calendar.css', __FILE__));
    // wp_register_style( 'fullcalendar', plugins_url('fullcalendar/fullcalendar.css', __FILE__));
    // Prepare for Ajax
    wp_localize_script('fullcalendar', 'adminAjax', [admin_url('admin-ajax.php')]);
    $this->cf_calendar_add_my_resources = true;
		$this->calendar_insert_my_resources_if_necessary();
  }

  function calendar_insert_my_resources_if_necessary() {
    // The resources are only included if the shortcode is used on the page   
    // debug_log("Do we need to insert resources for the calendar?"); 
    if ( ! $this->cf_calendar_add_my_resources )
      return;
    // debug_log("Yes we do");
    $modals = isset(get_option( 'hello_event' )['hello_field_modal']) ? get_option( 'hello_event' )['hello_field_modal'] : 'none';
    // wp_enqueue_style( 'fullcalendar');
    wp_enqueue_style( 'hello_event_frontend-calendar');
    wp_enqueue_script( 'moment');
    wp_enqueue_script( 'fullcalendar');
    wp_enqueue_script( 'hello_event_fullcalendar_french');
    wp_enqueue_script( 'hello_event_fullcalendar_swedish');
    if ($modals == 'boot') {
      wp_enqueue_script( 'hello_event_bootbox');
    }
    elseif ($modals == "jquery") {
      wp_enqueue_style( 'jquery_dialog');
      wp_enqueue_script( 'jquery-ui-dialog');
    }
  }
	

  public function handle_shortcode_calendar( $args ){
    // debug_log("HANDLE SC CALENDAR. args=".print_r($args, true));
    $args = shortcode_atts( array(
      'header' => "{}", // Not used since july 2023
      'view' => 'month', // can be month, week or day
      'view_selector' => false,
      'event_page' => false, // can be set to the slug of a custom page to show the events
    ), $args );
    
    global $hello_event_object; // Will give us access to its methods
    $modals = isset(get_option( 'hello_event' )['hello_field_modal']) ? get_option( 'hello_event' )['hello_field_modal'] : 'none';
    $locale = explode("_", get_locale())[0];
    if (!in_array($locale, Hello_Event::LOCALES)) {$locale='en';} // Fallback for esoteric locales
    $this->cf_calendar_add_my_resources = true;
    if ($modals == 'boot') {
      $evclick = "
      function (info) {
				var event = info.event;
				var jsEvent = info.jsEvent;
				jsEvent.preventDefault();
				var title_long = event._def.extendedProps.title_long;
				var description = event._def.extendedProps.description;
        //alert('one');
        //bootbox.alert('Ready for bootbox with description = ' );
        bootbox.alert({
          message: ''+description,
          title: title_long,
        });
      }";
      // debug_log("With boostratp: ". $evclick);
      $evclickX = "
        function(info) {alert('top');}
        ";
    }
    elseif ($modals == 'jquery') {
      $evclick = "
      function (info) {
				var event = info.event;
				var jsEvent = info.jsEvent;
				jsEvent.preventDefault();
				var title_long = event._def.extendedProps.title_long;
				var description = event._def.extendedProps.description;
          
        $('#dialog').dialog('option', 'title', title_long);
        $('#dialog').html(''+description);
        $('#dialog').dialog('open');
          

      }";
    }
    elseif ($modals == 'none') {
      $evclick = "
      function (info) {
				event = info.event;
				element = info.el;
        element.attr('href', event.url);
        }";   
    }
    $html = '';
    if ($modals == 'jquery') {
      $html .= '<div id="dialog" title="Event">Body</div>';
    }
    
    switch ($args['view']) {
      case 'week':
      $initialView = 'timeGridWeek';
      break;
      case 'day':
      $initialView = 'timeGridDay';
      break;
      default:
      $initialView = 'dayGridMonth';
      break;      
    }
    
    if ($args['view_selector'])
      $headerSwitch = 'dayGridMonth timeGridWeek timeGridDay';
    else
      $headerSwitch = '';
    
    // debug_log("Initial view = $initialView");
    // debug_log("Header switch = $headerSwitch");
    $html .=  '<div id="cf_calendar"></div>';
    $html .= '<script>';
    $html .= '(function($){';
    $html .= '$(document).ready(function () {';
    if ($modals == "jquery") {
       $html .= 'jQuery("#dialog").dialog({ autoOpen: false });';
       $html .= "$('#dialog').dialog('option', 'width', 330);";
       $html .= "$('#dialog').dialog('option', 'show', true);";
     }
     $header = $args['header'];
     // debug_log("header=".$header);
     // $html .= '    alert("adminajax="+adminAjax);';
    $html .= '
      var eventPage = "'.$args['event_page'].'";
			var calendarEl = document.getElementById("cf_calendar");
			var calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: "'.$initialView.'",
        headerToolbar: {
          left: "prev,next today",
          center: "title",
          right: "'.$headerSwitch.'"
        },
				slotMinTime: "07:00:00",
				slotMaxTime: "22:00:00",
				slotDuration: "01:00:00",
	      // timeFormat: " ",
	      firstDay:1,
	      height: "auto",
	      locale:"' . $locale .'",
	      // header:' . $header .',
	      eventClick: '. $evclick .',
	      events: function(info, successCallback, failureCallback) {
					var start = FullCalendar.formatDate(info.start, {year:"numeric"})+"-"+FullCalendar.formatDate(info.start, {month:"2-digit"})+"-"+FullCalendar.formatDate(info.start, {day:"2-digit"});
					var end = FullCalendar.formatDate(info.end, {year:"numeric"})+"-"+FullCalendar.formatDate(info.end, {month:"2-digit"})+"-"+FullCalendar.formatDate(info.end, {day:"2-digit"});

	        jQuery.ajax({
	              url: adminAjax,
	              type: "POST",
	              dataType: "json",
	              data: {
	                action: "hello_event_get_events",
	                start: start,
	                end: end,
                  fix_end_date: true,
                  event_page: eventPage,
	              },
	              error: function(obj, status, err) {
	                alert("AJAX ERROR. status:" + status + " error: " + err);
	              },
	              success: function(doc) {
	                successCallback(doc);
	              }
	          });
	      }
      });
			calendar.render();';
    $html .= '});';
    $html .= ' })(jQuery); ';
    $html .= '</script>';
    return $html;
  }
} // End of class
$hello_event_calendar_object = new Hello_Event_Calendar;
?>
