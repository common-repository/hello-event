<?php
/*
* Define shortcodes for showing lists of event
*
*/

namespace Tekomatik\HelloEvent;

class Hello_Event_List {
  public function __construct() {
    add_action('wp_enqueue_scripts', array($this, 'register_frontend_scripts'));
    add_shortcode( 'hello-event-list', array($this, 'handle_shortcode_event_list'));
    add_shortcode( 'hello-event-tabs', array($this, 'handle_shortcode_event_tabs'));
    add_image_size('hello-event-widget-image', 300, 300, false);
  }
  
  function register_frontend_scripts() {
    wp_register_style('fontawesome', '//use.fontawesome.com/releases/v5.0.10/css/all.css', array(), '5.0.10' );
    // wp_register_style( 'jquery-ui-base-theme', plugins_url('css/jquery-ui_1.8_themes_base.css', __FILE__) );
    wp_register_script('hello-event-list', plugins_url('js/hello_event_list.js', __FILE__), ['jquery'], '', true );
    wp_register_style( 'hello_event_frontend-list', plugins_url('css/frontend-list.css', __FILE__) );
    wp_enqueue_style( 'dashicons' );
    $this->enqueue_frontend_scripts();
  }
  
  function enqueue_frontend_scripts() {
    wp_enqueue_style('fontawesome');
    // wp_enqueue_style('jquery-ui-base-theme');
    wp_enqueue_style('hello-event-list');
    wp_enqueue_script('hello-event-list');
  }
  
  
  function handle_shortcode_event_list($args0) {
    $args = shortcode_atts( array(
           'limit' => -1,
           'select' => 'future', // Can be 'past' or 'future'
           'show' => 'default',   // Can be 'minimal', 'default', 'full', 'widget', 'compact', 'gallery'
           // 'width' => false, // Optionally Used in widget show
           'link_to_book' => false, // Do we want to display a "Book Now" button?
           // 'language' => false, // Can be used in date presentations
					 'thumbnail' => "", // can be 'square', 'round' or both (sepaated by space)
           'event_page' => false, // can be set to the slug of a custom page to show the events
             
       ), $args0 );
    global $hello_event_object; // Will give us access to its methods
    global $wp_locale;
    wp_enqueue_style('fontawesome');
    //wp_enqueue_script('hello-event-list');
    wp_enqueue_style('hello_event_frontend-list');
    $today = date('Y-m-d');
    //$today = date('Y/m/d');
    if ($args['select'] == 'past') {
      $q_args=array(
        'post_type' => HELLO_EVENT::EVENT_SLUG,
        'post_status' => 'publish',
        'posts_per_page' => $args['limit'],
        'meta_key' => 'end_date',
        'meta_query' => array(
            array(
                'key' => 'end_date',
                'value' => $today,
                'compare' => '<'
            )
        ),
        'orderby' => 'meta_value',
        'order' => 'DESC'
      );
    }
    else {
      $q_args=array(
        'post_type' => HELLO_EVENT::EVENT_SLUG,
        'post_status' => 'publish',
        'posts_per_page' => $args['limit'],
        'meta_key' => 'start_date',
        'meta_query' => array(
          'relation' => 'OR',
          'start_clause' => array(
                'key' => 'start_date',
                'value' => $today,
                'compare' => '>='
            ),
            'end_clause' => array(
                  'key' => 'end_date',
                  'value' => $today,
                  'compare' => '>='
              ),
        ),
        'orderby' => array(
            'start_date' => 'ASC',
        ),
      );  
    }
    $q_args = apply_filters('hello_event_retrieve_events', $q_args, $args0, $args);
    $my_query = new \WP_Query($q_args);
    // debug_log(print_r($q_args, true));
    // debug_log(print_r($my_query->request, true));
    if( $my_query->have_posts() ) {
      $html = "";
      $html .= '<div class="hello-event-list '.$args['show'].'">';
      while ($my_query->have_posts()) :
        $my_query->the_post();
        $id = get_the_ID();
        $start_date_db = get_post_meta( $id, 'start_date', true );
        $start_date = $hello_event_object->transform_iso_date($start_date_db);
        $end_date_db = get_post_meta( $id, 'end_date', true );
        $end_date = $hello_event_object->transform_iso_date($end_date_db);
        // Break out of the while loop if there are date errors
        if (! $start_date) {break;}
        list($start_year, $start_month, $start_day) = explode("-", $start_date_db);
        $start_month_long = $wp_locale->get_month_abbrev($wp_locale->get_month($start_month));
        if ($end_date_db)
          list($end_year, $end_month, $end_day) = explode("-", $end_date_db);
        else
          list($end_year, $end_month, $end_day) = explode("-", $start_date_db);
        $end_month_long = $wp_locale->get_month_abbrev($wp_locale->get_month($end_month));
        $start_time = get_post_meta( $id, 'start_time', true );
        $end_time = get_post_meta( $id, 'end_time', true );
        if ( ($start_time == "00:00") && ($end_time == "00:00") ){
          if ($start_date_db != $end_date_db)
            // $start_time = ' ' . __("Several full days", 'hello-event');
            $start_time = false;
          else
            $start_time = __("Full day", 'hello-event');
        }
        else
          $start_time = $hello_event_object->transform_iso_time($start_time);
        $location = get_post_meta( $id, 'location', true );
        $location_name = get_post_meta( $id, 'location_name', true );
        if ($location_name) {
          $location = "<b>$location_name</b>, " . $location;
        }
        $excerpt = $hello_event_object->real_or_computed_excerpt($id);
        $excerpt = wpautop($excerpt);
        $start_and_end_time_string = "...";
        
        $start_date_string = "
          <div class='start-date'>
            <div class='day'>$start_day</div>
            <div class='month'>$start_month_long</div>
          </div>
        ";
        
        if ($start_date_db == $end_date_db) {
          $date_string = "$start_date_string";            
          $event_type_class = "single-day";
        }
        else {
          $end_date_string = "
            <div class='end-date'>
              <div class='day'>$end_day</div>
              <div class='month'>$end_month_long</div>
            </div>
          ";
          $date_string = "
            $start_date_string
            <div class='to'>-</div>
            $end_date_string
          ";            
          
          $event_type_class = "multi-day";
        }
        
        
        if ($start_time) {
          $start_time_string = "
            <div class='time'>
              $start_time
            </div>
          ";
          if ($end_time && $end_time != "00:00") {
            $start_and_end_time_string = "
              <div class='time'>
                $start_time" . ' - ' ." $end_time
              </div>
              ";
          }
          else {
            $start_and_end_time_string = "
              <div class='time'>
                $start_time
              </div>
              ";
            
          }
        }
        else
          $start_time_string = "";
        
        $start_date_string = "
          <div class='date'>
            $start_date
          </div>
        ";

        switch ($args['show']) {
          case 'minimal' :
            $html .= '<div class="hello-event style1 min '.$event_type_class. '">';
            $html .= '  <div class="start-date">'.$start_date.'</div>';
            $html .= '  <div class="event-title">';
            $html .= '     <a href="'.$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page']).'" rel="bookmark" title="Permanent Link to '.get_the_title().'">';
            $html .=       get_the_title().'</a>';
            $html .= '  </div>';
            if ($args['link_to_book']) {
              list($link, $rc) = $hello_event_object->get_link_to_product(get_the_ID(), true);
              if ($link) {
                $ticket_link = '<a href="'. $link . '">' . __("Book now", 'hello-event') . '</a>';
                $ticket_link = apply_filters('hello_event_display_link_to_ticket', $html, get_the_ID(), $link);
                $html .= '<div class="ticket-link">' . $ticket_link . '</div>';
              }
            }
            
            $html .= '</div>';
            break;

          case 'gallery' : // xxx
            $g_start_date = $start_date;
            // if (false && $args['language'] == "french") {
            //   $g_start_date = new \DateTime($start_date);
            //   $g_start_date = $g_start_date->format("d/m Y");
            // }
            $g_dates = '<i class="far fa-calendar"></i> ' . $g_start_date .'<br/>';
            // Show end time
            // $g_times = '<i class="far fa-clock"></i> ' . $start_time .'<br/>';
            $g_times =  $start_and_end_time_string .'<br/>';
            // $g_place = '<div class="location"><i class="fab fa-fort-awesome"></i> ' . $location . '</div>';
            $g_place = '<i class="fab fa-fort-awesome"></i> ' . $location . '<br/>';
            $g_excerpt = '<div class="excerpt">' . $excerpt . '</div>';
            $g_ticket_link = "";
            if ($args['link_to_book']) {
              list($link, $rc) = $hello_event_object->get_link_to_product(get_the_ID(), true);
              if ($link) {
                $ticket_link = '<a href="'. $link . '">' . __("Tickets", 'hello-event') . '</a>';
                $ticket_link = apply_filters('hello_event_display_link_to_ticket', $ticket_link, get_the_ID(), $link);
                $g_ticket_link = '<i class="far fa-credit-card"></i> ' . $ticket_link .'<br/>';
                
              }
            }
            
            $g_title = "
              <div class='title'>
                <a href='".$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page'])."'
                    rel='bookmark'
                    title='Permalink to ".get_the_title()."'>".get_the_title()."
                </a>
              </div>";
              
              
              
            $html .= "
            <div class='hello-event style1 list gallery " . $event_type_class ."'>
              <div class='thumbnail ".$args['thumbnail']."'>" . get_the_post_thumbnail() . "</div>
              <div class='description'>
                $g_title
                $g_dates
                $g_times
                $g_place
                $g_ticket_link
                $g_excerpt
              </div>
            </div>";
            break;
            
            
          case 'full' :
            $html .= "
            <div class='hello-event style1 list full " . $event_type_class ."'>
              <div class='intro'>
                <div class='event-date'>
                  $date_string
                </div>
              </div>
              <div class='description'>
                <div class='title'>
                  <a href='".$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page'])."'
                      rel='bookmark'
                      title='Permalink to ".get_the_title()."'>".get_the_title()."
                  </a>
                </div>
                <div class='thumbnail ".$args['thumbnail']."'>" . get_the_post_thumbnail() . "</div>
                $start_and_end_time_string
                <div class='location'>
                  $location
                </div>
                <div class='excerpt'>
                $excerpt
                </div>
              </div>
            </div>
            ";
            break;

            case 'widget' : // Same info as full but organised so that the full description is full width
              $html .= "
              <div class='hello-event style1 widget " . $event_type_class ." flexFont'>
                <div class='intro'>
                  <div class='event-date'>
                    $date_string
                  </div>
                  <div class='title'>
                    <a href='".$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page'])."'
                        rel='bookmark'
                        title='Permalink to ".get_the_title()."'>".get_the_title()."
                    </a>
                  </div>
                  <div class='thumbnail ".$args['thumbnail']."'>" . get_the_post_thumbnail($id, 'hello-event-widget-image') . "</div>
                </div>
                <div class='description'>
                  $start_and_end_time_string
                  <div class='location'>
                    $location
                  </div>
                  <div class='excerpt'>
                  $excerpt
                  </div>
                </div>
              </div>
              ";
              break;

              case 'compact' : // Same info as full but organised so that the full description is full width
                $html .= "
                <div class='hello-event style1 list compact " . $event_type_class . "'>
                <div class='event-date'>
                  $date_string
                </div>
                <div class='intro'>
                  <div class='title'>
                    <a href='".$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page'])."'
                        rel='bookmark'
                        title='Permalink to ".get_the_title()."'>".get_the_title()."
                    </a>
                  </div>
                  
                  <div class='thumbnail ".$args['thumbnail']."'>" . get_the_post_thumbnail($id, 'hello-event-widget-image') . "</div>
                </div>
                <div class='description'>
                  $start_and_end_time_string
                  $start_date_string
                  <div class='location'>
                    $location
                  </div>
                  <div class='excerpt'>
                    $excerpt
                  </div>
                </div>
              </div>
              ";
              break;

              case 'short' : // same as default but without any text
                $html .= "
                <div class='hello-event style1 list default " . $event_type_class ."'>
                  <div class='event-date'>
                    $date_string
                  </div>
                  <div class='title'>
                    <a href='".$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page'])."'
                        rel='bookmark'
                        title='Permalink to ".get_the_title()."'>".get_the_title()."
                    </a>
                  </div>
                  <div class='description'>
                    <table style='width:100%'>
                      <tr>
                        <td>
                          $start_and_end_time_string
                          <div class='location'>
                            $location
                          </div>
                        </td>
                        <td>
                          <div class='readmore'>
                            <a href='".get_the_permalink()."'
                              rel='bookmark'
                              title='Permalink to ".get_the_title()."'>".__('Goto the event', 'hello-event')."
                            </a>
                          </div>";
                
                    if ($args['link_to_book']) {
                      list($link, $rc) = $hello_event_object->get_link_to_product(get_the_ID(), true);
                      if ($link) {
                        // $ticket_link = '<a href="'. $link . '">' . "Book" . '</a>';
                        $ticket_link = apply_filters('hello_event_display_link_to_ticket', $html, get_the_ID(), $link);
                        $html .= '<div class="ticket-link">' . $ticket_link . '</div>';
                      }
                    }
                    $html .= "</td></tr></table>";
                
                  $html .= "  
                  </div>
                </div>
                ";
                break;
              
          
              default :
                $html .= "
                <div class='hello-event style1 list default " . $event_type_class ."'>
                  <div class='event-date'>
                    $date_string
                  </div>
                  <div class='title'>
                    <a href='".$hello_event_object->link_to_event_page(get_the_permalink(), $id, $args['event_page'])."'
                        rel='bookmark'
                        title='Permalink to ".get_the_title()."'>".get_the_title()."
                    </a>
                  </div>
                  <div class='description'>
                    $start_and_end_time_string
                    <div class='location'>
                      $location
                    </div>
                    <div class='excerpt'>
                      $excerpt
                    </div>
                  <div class='readmore'>
                    <a href='".get_the_permalink()."'
                        rel='bookmark'
                        title='Permalink to ".get_the_title()."'>".__('Goto the event', 'hello-event')."
                    </a>
                  </div>";
                
                    if ($args['link_to_book']) {
                      list($link, $rc) = $hello_event_object->get_link_to_product(get_the_ID(), true);
                      if ($link) {
                        $ticket_link = '<a href="'. $link . '">' . "Book" . '</a>';
                        $ticket_link = apply_filters('hello_event_display_link_to_ticket', $html, get_the_ID(), $link);
                        $html .= '<div class="ticket-link">' . $ticket_link . '</div>';
                      }
                    }
                
                  $html .= "  
                  </div>
                </div>
                ";
                break;
              }
        
      endwhile;
      $html .= '</div>';
    }
    else {
      $html = apply_filters('hello-event-text-empty-list',__("There is no event to show", 'hello-event')) ."<br/>";
    }
    
    wp_reset_query();  // Restore global post data stomped by the_post().
    $html = apply_filters('hello_event_display_event_list', $html);
    return $html;
  }
  
  function handle_shortcode_event_tabs($args) {
    $args = shortcode_atts( array(
           'limit' => -1,
           'show' => 'default',
           'link_to_book' => false,
           'event_page' => false,
       ), $args );
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_script('hello-event-list');
    wp_enqueue_style('jquery-ui-base-theme');
    wp_enqueue_style('hello_event_frontend-list');
    $html = '';
    $past = $this->handle_shortcode_event_list(array(
      'select' => 'past',
      'limit' => $args['limit'],
      'show' => $args['show'],
      'event_page' => $args['event_page'],
    ));
    $future = $this->handle_shortcode_event_list(array(
      'select' => 'future',
        'limit' => $args['limit'],
        'show' => $args['show'],
        'link_to_book' => $args['link_to_book'],
        'event_page' => $args['event_page'],    ));
    $past_txt = __('Earlier events', 'hello-event');
    $future_txt = __('Upcoming events', 'hello-event');
    $html .= <<< EOF
      <div class="event-tabs">
        <ul>
          <li><a href="#fragment-future">$future_txt</a></li>
          <li><a href="#fragment-past">$past_txt</a></li>
        </ul>
        <div id="fragment-future">
          $future
        </div>
        <div id="fragment-past">
          $past
        </div>
      </div>
      
EOF;
    return $html;
  }
} // End of class
$hello_event_list_object = new Hello_Event_List;
?>