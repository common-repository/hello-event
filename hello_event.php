<?php
/**
 *
 * @category     WordPress_Plugin
 * @author       Tekomatik
 * @license      GPL-2.0+
 * @link         https://www.tekomatik.com
 *
 * Plugin Name:  Hello Event
 * Plugin URI:   https://www.tekomatik.com/plugins/hello-event
 * Description:  Manage events and sell tickets with WooCommerce as easy as Hello World
 * Author:       Christer Fernstrom
 * Author URI:   https://www.tekomatik.com/about
 *
 * Version:      1.3.17
 *
 * Text Domain:  hello-event
 * Domain Path:  /languages
 *
 *
 * Released under the GPL license
 * https://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * https://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */


namespace Tekomatik\HelloEvent;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! function_exists('debug_log')) {
  function debug_log ( $log )  {
    if ( is_array( $log ) || is_object( $log ) ) {
       error_log( print_r( $log, true ) );
    } else {
       error_log( $log );
    }
 }
}


class Hello_Event {
  const PLUGIN_NAME = "Hello Event";
  const EVENT_SLUG = "hello_event";
  const PRODUCT_SLUG = "hello_event_ticket";
  const LOCALES = ['en', 'fr', 'sv'];

  public $namings = Array();
  // The following is used to temporarily disable error messages during event_save when a ticket is created first time
  private $enable_product_save_error_messages = true;
  private $default_event_id = false;
  

  public function install() {
    $this->register_event_type();
    flush_rewrite_rules();
  }
    
  public function uninstall() {
    // 
  }
  
  public function __construct() {
    $this->namings = Array(
      'event_singular_name'     => 'Event',
      'event_plural_name'        => 'Events', // Can be changed through the settings
    );
    
    if (isset(get_option( 'hello_event')['hello_field_eventname_singular']) &&
        get_option( 'hello_event')['hello_field_eventname_singular']) {
      $this->namings['event_singular_name'] = get_option( 'hello_event')['hello_field_eventname_singular'];
    }
    if (isset(get_option( 'hello_event')['hello_field_eventname_plural']) &&
       get_option( 'hello_event')['hello_field_eventname_plural'] ) {
      $this->namings['event_plural_name'] = get_option( 'hello_event')['hello_field_eventname_plural'];
    }
    $this->hello_event_debug = isset(get_option( 'hello_event')['hello_field_debug']) ? get_option( 'hello_event')['hello_field_debug']=="on" : false;
    
    add_action('init', array($this, 'plugin_init'), 1);
    
    // Create and set up the Event custom type
    add_action( 'init', array($this, 'register_event_type'), 2);
    add_action( 'add_meta_boxes', array($this, 'register_event_type_metaboxes'));
    add_action( 'save_post', array($this, 'event_save' ), 10, 2);
    
    // Prepare translations
    add_action( 'plugins_loaded', array($this, 'load_textdomain' ));
    
    // Load our scripts and styles
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    
    // Define shortcodes
    add_shortcode('hello-link-to-ticket', array($this, 'link_to_ticket'));
    add_shortcode('hello-id-of-event', array($this, 'id_of_event'));
    add_shortcode('hello-link-to-event', array($this, 'link_to_event'));
    add_shortcode('hello-title', array($this, 'event_title'));
    add_shortcode('hello-thumbnail', array($this, 'event_thumbnail'));
    add_shortcode('hello-thumbnail-url', array($this, 'event_thumbnail_url'));
    add_shortcode('hello-start-date', array($this, 'event_start_date'));
    add_shortcode('hello-start-time', array($this, 'event_start_time'));
    add_shortcode('hello-start-date-and-time', array($this, 'event_start_date_and_time'));
    add_shortcode('hello-end-date', array($this, 'event_end_date'));
    add_shortcode('hello-end-time', array($this, 'event_end_time'));
    add_shortcode('hello-end-date-and-time', array($this, 'event_end_date_and_time'));
    add_shortcode('hello-location-name', array($this, 'event_location_name'));
    add_shortcode('hello-location', array($this, 'event_location'));
    add_shortcode('hello-location-address', array($this, 'event_location'));
    add_shortcode('hello-advice', array($this, 'event_advice'));
    add_shortcode('hello-content', array($this, 'event_content'));
    add_shortcode('hello-excerpt', array($this, 'event_excerpt'));
    add_shortcode('hello-map', array($this, 'event_map'));
    add_shortcode('hello-default-event', array($this, 'default_event'));
    add_shortcode('hello-event-ics', array($this, 'event_ics'));
    
    
    // Make sure we have the necessary product category for WooCommerce
    add_action( 'init', array($this, 'declare_hello_event_product_category'), 20); // must be done AFTER 'register_event_type'
    
    // Add start end end date columns to admin panel list and make start sortable
    add_filter( 'manage_hello_event_posts_columns', array($this, 'set_custom_edit_hello_event_columns' ));
    add_action( 'manage_hello_event_posts_custom_column' , array($this, 'custom_hello_event_column'), 10, 2 );
    add_filter( 'manage_edit-hello_event_sortable_columns', array($this, 'make_sortable_hello_event_column'));
    add_action( 'pre_get_posts', array($this, 'event_column_orderby') );  
    
    // Configure Woocommerce
    add_action( 'woocommerce_payment_complete', array($this, 'woocommerce_payment_complete'), 10, 1 );
    add_action('save_post', array($this, 'product_save'),100,2);
    add_action( 'trashed_post', array($this, 'trashed_post'), 10, 1 );
    add_action( 'admin_notices', array($this, 'admin_notice' ));
    //add_filter( 'woocommerce_short_description', array($this, 'add_backlink_to_event_0') );
    add_action( 'woocommerce_before_add_to_cart_form', array($this, 'add_backlink_to_event'), 10, 1 );
    
    // ICS
    add_action('template_redirect', array($this, 'get_event_ics'));
    
    // Add hooks so that we can use standard templates for archive and single
    // add_filter('post_thumbnail_html', array($this, 'filter_post_thumbnail_html'));
    add_filter('the_content', array($this, 'filter_post_content'));
    
    // add filter to allow shortcodes in the Custo, HTML widget
    add_filter( 'widget_text', 'do_shortcode' );
    
    // Ajax Hooks
    add_action('wp_ajax_set_geocode', array($this, 'set_geocode'));
    add_action('wp_ajax_nopriv_set_geocode', array($this, 'set_geocode'));
    
    // Intercept query for generation of previous and next links on the event page
    add_filter( 'get_next_post_join', array($this, 'get_both_post_join'), 10, 5 );
    add_filter( 'get_previous_post_join', array($this, 'get_both_post_join'), 10, 5 );
    add_filter( 'get_next_post_where', array($this, 'get_next_post_where'), 10, 5 );
    add_filter( 'get_previous_post_where', array($this, 'get_previous_post_where'), 10, 5 );
    add_filter( 'get_next_post_sort', array($this, 'get_next_post_sort'), 10, 3 );
    add_filter( 'get_previous_post_sort', array($this, 'get_previous_post_sort'), 10, 3 );
    do_action('hello_event_ready');
    
    // Add pages in the backend for ticket sales reports
    add_action('admin_menu', array($this, 'add_backend_sales_report'));
    
    // Before showing tickets in the shop: set tickets to past event so not visible
    add_action('woocommerce_before_shop_loop', array($this, 'hide_tickets_to_past_events'));
    
    // Make sure Gutenberg is disabled for the events if we enable the REST API for our post type
    // add_filter('use_block_editor_for_post', array($this, 'disable_gutenberg'), 10, 2);
  }
  

  
  //
  // ================= Enquueues and Plugin initialisation ========================================================
  //
  function enqueue_admin_scripts() {
    wp_enqueue_script('hello_event_datetimepicker_js',
            plugins_url('js/datetimepicker/build/jquery.datetimepicker.full.min.js', __FILE__), ['jquery'], '', true);
    wp_enqueue_style('hello_event_datetimepicker_css',
            plugins_url('js/datetimepicker/jquery.datetimepicker.css', __FILE__));
    wp_enqueue_script('hello_event_timepicker_js',
            plugins_url('js/jquery-timepicker/jquery.timepicker.min.js', __FILE__), ['jquery'], '', true);
    wp_enqueue_style('hello_event_timepicker_css',
            plugins_url('js/jquery-timepicker/jquery.timepicker.min.css', __FILE__));
    wp_enqueue_script('hello_event_admin_js', plugins_url('js/admin.js', __FILE__), ['jquery'], '', true);
    wp_enqueue_style('hello_event_admin_css', plugins_url('css/admin.css', __FILE__));
    // Prepare for Ajax
    wp_localize_script('hello_event_admin_js', 'adminAjax', [admin_url('admin-ajax.php')]);
  }
  
  function enqueue_frontend_scripts() {
    wp_enqueue_script('hello_event_frontend_js', plugins_url('js/frontend.js', __FILE__), ['jquery'], '', true);
    wp_enqueue_style('hello_event_frontend', plugins_url('css/frontend.css', __FILE__));
    wp_enqueue_style('hello_event_jquery_ui', plugins_url('includes/css/jquery-ui.min.css', __FILE__));
    wp_enqueue_style('hello_event_jquery_ui_theme', plugins_url('includes/css/jquery-ui.theme.min.css', __FILE__));
    // Prepare for Ajax
    wp_localize_script('hello_event_frontend_js', 'adminAjax', [admin_url('admin-ajax.php')]);
  }
  
  function plugin_init() { 
		if ( ! defined( 'HELLO_DIR' ) ) {
			define( 'HELLO_DIR', trailingslashit( dirname( __FILE__ ) ) );
		}
		// Include external classes.
		require_once HELLO_DIR . 'includes/hello-event-settings.php';
		require_once HELLO_DIR . 'includes/hello-event-calendar.php';
		require_once HELLO_DIR . 'includes/hello-event-list-events.php';
    
		require_once HELLO_DIR . 'includes/hello-event-map.php';
		require_once HELLO_DIR . 'includes/ics.php';
  }
  
  function load_textdomain(){
    load_plugin_textdomain('hello-event', false, basename( dirname( __FILE__ ) ) . '/languages');    
  }
  
  
  //
  // ================== Setting up the custom event type ===========================================
  //
  // ----------- Declare the custom post type
  function register_event_type() {
  	$labels = array(
  		'name'               => $this->namings['event_plural_name'],
  		'singular_name'      => $this->namings['event_singular_name'],
  		'menu_name'          => $this->namings['event_plural_name'],
  		'name_admin_bar'     => $this->namings['event_singular_name'],
  		'add_new'            => __('Add New', 'hello-event'),
  		'add_new_item'       => __('Add New', 'hello-event') . ' ' . $this->namings['event_singular_name'],
  		'new_item'           => __('New', 'hello-event') . ' ' . $this->namings['event_singular_name'],
  		'edit_item'          => __('Edit', 'hello-event') . ' ' . $this->namings['event_singular_name'],
  		'view_item'          => __('View', 'hello-event') . ' ' . $this->namings['event_singular_name'],
  		'all_items'          => __('All', 'hello-event') . ' ' . $this->namings['event_plural_name'],
  		'search_items'       => __('Search', 'hello-event') . ' ' . $this->namings['event_plural_name'],
  		'parent_item_colon'  => 'PPPParent ' . $this->namings['event_plural_name'] .':',
  		'not_found'          => __('Nono', 'hello-event') . ' ' . $this->namings['event_plural_name'] . ' found.',
  		'not_found_in_trash' => __('Nono', 'hello-event') . ' ' . $this->namings['event_plural_name'] . ' found in Trash.',
  	);

  	$args = array( 
  		'labels'		=> $labels,
  		'public'		=> true,
  		'rewrite'		=> array( 'slug' => Hello_Event::EVENT_SLUG ),
  		'has_archive'   => true,
  		'menu_position' => 20,
  		'menu_icon'     => 'dashicons-carrot',
      'show_in_rest' => false,
  		//'taxonomies'		=> array( 'post_tag', 'category' ), // Will add tags and category check boxes to the admin page
  		'taxonomies'		=> array('post_tag'),
  		//'supports'      => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments' ),
    	'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt')
  	);
    $args = apply_filters('hello_event_registration', $args); // Can be modified in add-ons
  	register_post_type( Hello_Event::EVENT_SLUG, $args );
  }
  

  
  // ----- Register the metaboxes for the event admin pages
  function register_event_type_metaboxes() {
    
    do_action('hello_event_register_event_metaboxes_start'); // Allow add-ons to register metaboxes at the beginning
    
  	add_meta_box(
      'dates_meta_box',
      __( 'Dates and time', 'hello-event' ),
      array($this, 'render_meta_box'),
      Hello_Event::EVENT_SLUG,
      'advanced',
      'high'
    );
  	add_meta_box(
      'location_meta_box',
      __( 'Location', 'hello-event' ),
      array($this, 'render_meta_box'),
      Hello_Event::EVENT_SLUG,
      'advanced',
      'high'
    );
  	add_meta_box(
      'advice_meta_box',
      __( 'Advice', 'hello-event' ),
      array($this, 'render_meta_box'),
      Hello_Event::EVENT_SLUG,
      'advanced',
      'high'
    );
  	add_meta_box(
      'tickets_meta_box',
      __( 'Tickets', 'hello-event' ),
      array($this, 'render_meta_box'),
      Hello_Event::EVENT_SLUG,
      'advanced',
      'high'
    );
    
    do_action('hello_event_register_event_metaboxes_end'); // Allow add-ons to register metaboxes at the end
  }
  
  
  // ---- Hook to save the meta info of events when the post is saved.
  // When enabling/disabling the selling of tickets we need to take specific actions
  public function event_save( $post_id, $post ) {
    $this->errors = false;
    $this->error_codes = [];
    // We're only interested to track saves of our events !
    if ( ! (get_post_type($post_id) == Hello_Event::EVENT_SLUG) ) { return; }
    /*
     * We need to verify this came from the our screen and with proper authorization,
     * because save_post can be triggered at other times.
     */
    // Check if our nonce is set.
    if ( ! isset( $_POST['hello_event_custom_box_nonce'] ) ) {
        return $post_id;
    }
    $nonce = $_POST['hello_event_custom_box_nonce'];
    // Verify that the nonce is valid.
    if ( ! wp_verify_nonce( $nonce, 'hello_event_custom_box' ) ) {
        return $post_id;
    }
    /*
     * If this is an autosave, our form has not been submitted,
     * so we don't want to do anything.
     */
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return $post_id;
    }
    // Check the user's permissions.
    if ( 'page' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return $post_id;
        }
    } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
    }
    // OK to save the data now.
    //
    // sanitize the user input and Update the meta fields
    $start_date = $this->force_iso_date( $_POST['start_date_meta_box_field'] );
    if (!$start_date) {
      $this->errors = true;
      array_push($this->error_codes, 'no-start-date');
      $start_date = date("Y-m-d"); // Set to today
    }
    update_post_meta( $post_id, 'start_date', $start_date );
    
    $start_time = $this->force_iso_time( $_POST['start_time_meta_box_field'] );
    $start_time = $start_time ? $start_time : "00:00";
    update_post_meta( $post_id, 'start_time', $start_time );
    // We also add a start_date_time since this will help us in sorting the events if there are more than on on a day
    update_post_meta( $post_id, 'start_date_time', $start_date.' '.$start_time);
    
    $end_date = $this->force_iso_date( $_POST['end_date_meta_box_field'] );
    if (!$end_date) {
      $this->errors = true;
      array_push($this->error_codes, 'no-end-date');
    }
    if ($end_date && $start_date && ($end_date < $start_date)) {
      $this->errors = true;
      array_push($this->error_codes, 'end-date-earlier-than-start-date');
      $end_date = date("Y-m-d"); // Set to today
    }
    update_post_meta( $post_id, 'end_date', $end_date );
    $end_time = $this->force_iso_time( $_POST['end_time_meta_box_field'] );
    $end_time = $end_time ? $end_time : "00:00";
    update_post_meta( $post_id, 'end_time', $end_time );

    $mydata = sanitize_text_field( $_POST['location_name_meta_box_field'] );
    update_post_meta( $post_id, 'location_name', $mydata );
    
    $mydata = sanitize_text_field( $_POST['location_meta_box_field'] );
    update_post_meta( $post_id, 'location', $mydata );
    delete_post_meta($post_id, 'location_lat');
    delete_post_meta($post_id, 'location_lng');
          
    $mydata = sanitize_text_field( $_POST['advice_meta_box_field'] );
    update_post_meta( $post_id, 'advice', $mydata );


    $sell_tickets_cb = isset($_POST['sell_tickets_meta_box_field']) && $_POST['sell_tickets_meta_box_field'];
    
    update_post_meta( $post_id, '_sell_tickets', $sell_tickets_cb );
    
        
    if ( $sell_tickets_cb ) {
      $product_id = $this->get_id_of_product($post_id, false, false)[0];
      if (! $product_id ) {
        // Need to create ticket product
        // Every time a product is saved there is a verification that the corresponding event exists
        // This will NOT be the case here, since the '_tickets' meta has not yet been set to the ticket product ID
        // To avoid confusing error messages we will therefore temporarily disable the error messages from product save
        $this->enable_product_save_error_messages = false;
        $product_id = $this->product_create_for_event($post_id);
        $this->enable_product_save_error_messages = true;
        update_post_meta( $post_id, '_tickets', $product_id);
        do_action('hello_event_product_created', $product_id, $post_id);
      }
      else {
        // Ticket product OK. But not necessarily in status published !!
        // Update the ticket title if this is enabled in the settings
        $options = get_option( 'hello_event' );
        if (isset($options['hello_field_sync_ticket_title']) && $options['hello_field_sync_ticket_title']=='on') {
          $event_title = isset( $post->post_title ) ? $post->post_title : false;
          if ($event_title) {
            wp_update_post(array(
              'ID'    =>  $product_id,
              'post_title'   =>  $event_title
            ));
          }
        }
        // If the event is saved as anything else as published then change the ticket too to draft
        if (get_post_status($post) != 'publish') {
          wp_update_post(array(
            'ID'    =>  $product_id,
            'post_status'   =>  'draft'
          ));
        }
      }
    }
    else {
      if (! get_post_meta( $post_id, '_tickets', true ) ) {
        // Nothing needs to be done. There are no and there should be no tickets
      }
      else {
        // There are tickets, but we want to disable them. Set the product status to draft
        $prod_id = $this->get_id_of_product($post_id, false);
        $prod_id = get_post_meta( $post_id, '_tickets', true);
        wp_update_post(array(
          'ID'    =>  $prod_id,
          'post_status'   =>  'draft'
        ));
        
      }
    }
    
    do_action('hello_event_save_event', $post_id); // Allow add-ons to save other metadata
      
    if ($this->errors) {
      global $wpdb;
      // Allow add-ons to manipulate the generated errors
      $error_codes = apply_filters('hello_event_error_codes', $this->error_codes);
      //$error_codes = $this->error_codes;
      // filter the query URL to change the published message
      add_filter('redirect_post_location', function($location) use ($error_codes){
        return add_query_arg( 'hello-event-errors', $error_codes, $location );
      });
    }
  }
  
  // Disable Gutenberg for the event type
  // function disable_gutenberg($is_enabled, $post) {
  //   debug_log("Guten? " . $is_enabled .print_r($post,true));
  //   if ($post->post_type === Hello_Event::EVENT_SLUG) return false;
  //   return $is_enabled;
  // }

  
  // ----------------- Render Meta Box content
  public function render_meta_box( $post, $more ) {
    $field = $more['id'];
    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'hello_event_custom_box', 'hello_event_custom_box_nonce' );

    // Display the form, using the current value.
    $html = '';
    switch ($field) {
      case 'dates_meta_box':
      // We need the locale in javascript tp initiate the datepicker
        $locale = "'".explode('_', get_locale())[0]."'";
        $html = "<script> var locale; locale = $locale; </script>";
        $timeformat =  isset(get_option( 'hello_event')['hello_field_timeformat']) ? get_option( 'hello_event')['hello_field_timeformat'] : '';
        $dateformat =  isset(get_option( 'hello_event')['hello_field_dateformat']) ? get_option( 'hello_event')['hello_field_dateformat'] : '';
        $value = $this->transform_iso_date(get_post_meta( $post->ID, 'start_date', true ), true);
        
        $html .= '<table>';
        $html .= '<tr><td>';
        $html .= '<label for="start_date_meta_box_field">';
        $html .= __('Start date', 'hello-event');
        $html .= '</label>';
        $html .= '</td><td>';
        $html .= '<input type="text" class="datepicker" id="start_date_meta_box_field" name="start_date_meta_box_field" value="'. esc_attr( $value ) . '" data-dateformat="'.$dateformat.'" />';
        $html .= '</td>';
        
        $value = $this->transform_iso_time(get_post_meta( $post->ID, 'start_time', true ));
        $html .= '<td>';
        $html .= '<label for="start_time_meta_box_field">';
        $html .= __('Start time', 'hello-event');
        $html .= '</label>';
        $html .= '</td><td>';
        $html .= '<input type="text" class="timepicker" id="start_time_meta_box_field" name="start_time_meta_box_field" value="'. esc_attr( $value ) . '" data-timeformat="'.$timeformat.'" />';
        $html .= '</td></tr>';
      
        $html .= '<tr><td>';
        $value = $this->transform_iso_date(get_post_meta( $post->ID, 'end_date', true ), true);
        $html .= '<label for="end_date_meta_box_field">';
        $html .= __('End date', 'hello-event');
        $html .= '</label>';
        $html .= '</td><td>';
        $html .= '<input type="text" class="datepicker" id="end_date_meta_box_field" name="end_date_meta_box_field" value="'. esc_attr( $value ) . '
" />';
        $html .= '</td>';
        
        $value = $this->transform_iso_time(get_post_meta( $post->ID, 'end_time', true ));
        $html .= '<td>';
        $html .= '<label for="end_time_meta_box_field">';
        $html .= __('End time', 'hello-event');
        $html .= '</label>';
        $html .= '</td><td>';
        $html .= '<input type="text" class="timepicker" id="end_time_meta_box_field" name="end_time_meta_box_field" value="'. esc_attr( $value ) . '" data-timeformat="'.$timeformat.'" />';
        $html .= '</td>';
        $html .= '</tr></table>';
        $html .= '<div class="hint">' . __("For full day events: leave times empty or set them to 00:00", "hello_event") . '</div>';
         break;
        
      case 'location_meta_box':
        $html = '<table>';
        $html .= '<tr>';
        $value = get_post_meta( $post->ID, 'location_name', true );
        $html .= '<td><label for="location_meta_box_field">';
        $html .= __('Location Name', 'hello-event');
        $html .= '</label></td>';
        $html .= '<td><input type="text" id="location_name_meta_box_field" name="location_name_meta_box_field" value="'. esc_attr( $value ) . '
" size="25" /></td>';
        $html .= '</tr><tr>';
        $html .= '<td colspan=2><div class="hint">' . __("The (optional) location name can for example be the name of a concert hall", "hello-event") . '.</div></td>';
        
        
        $html .= '</tr><tr>';
        $value = get_post_meta( $post->ID, 'location', true );
        $html .= '<td><label for="location_meta_box_field">';
        $html .= __('Location address', 'hello-event');
        $html .= '</label></td>';
        $html .= '<td><input type="text" id="location_meta_box_field" name="location_meta_box_field" value="'. esc_attr( $value ) . '
" size="25" /></td>';
        $html .= '</tr><tr>';
        $html .= '<td colspan=2><div class="hint">' . __("The location address should be detailed enough to geo-localize the place", "hello-event") . '.</div></td>';
        $html .= '</tr></table>';
        break;
        
      case 'advice_meta_box':
        $html = '';
        $html .= '<table><tr>';
        $value = get_post_meta( $post->ID, 'advice', true );
        $html .= '<td style="vertical-align: top; padding-right:10px;"><label for="advice_meta_box_field">';
        $html .= __('Advice', 'hello-event').' ';
        $html .= '</label></td>';
        $html .= '<td><textarea id="advice_meta_box_field" name="advice_meta_box_field" rows="4" cols="30">';
        $html .= esc_attr( $value );
        $html .= '</textarea></td>';
        $html .= '</tr></table>';
        $html .= '<div class="hint">' . __("Advice to participants, for example related to parking or public transportation", "hello-event") . '.</div>';
        break;
        
      case 'tickets_meta_box':
        $html = '';
        if ( class_exists( 'WooCommerce' ) ) {
          $value = get_post_meta( $post->ID, '_sell_tickets', true );
          $checked = $value ? 'checked' : '';
          $html .= '<label for="sell_tickets_meta_box_field">';
          $html .= __('Sell tickets in the shop', 'hello-event').' ';
          $html .= '</label>';
          $html .= '<input type="checkbox" id="sell_tickets_meta_box_field" name="sell_tickets_meta_box_field"' . $checked . ' /><br/>';
          
          $value = get_post_meta( $post->ID, '_tickets', true );
          if ( $value ) {
            $edit_url = admin_url( "post.php" );
            $edit_url = add_query_arg( 'post', $value, $edit_url );
            $edit_url = add_query_arg( 'action', 'edit', $edit_url );
            
            $html .= '<label for="tickets_meta_box_field">';
            $html .= __('Shop link', 'hello-event').' ';
            $html .= '</label>';
            $html .= '<a href="' . $edit_url . '">' . __('Edit ticket', 'hello-event') . '</a>';
          }
          else {
            $html .= '<br/><br/>' . __('Tickets are not yet available in the shop.', 'hello-event');
            $html .= ' ' . __('Fill in the checkbox above to start sell tickets', 'hello-event').', ';
            $html .= __("then after saving the event a new link will appear to allow you to set the price and quantity of tickets", 'hello-event');
          }
        }
        else {
          $html .= __('To sell tickets Woocommerce needs to be installed and actived', 'hello-event');
        }
        break;

    };
    $html = apply_filters('hello_event_render_meta_box', $html, $post, $field); // Allow add-ons to display more metaboxes
    echo $html;
  }
  
  // -- Add start, end and ticket columns to admin lists for the event type
  // Header info
  function set_custom_edit_hello_event_columns($columns) {
    $columns['start_date_time'] = __( 'Start', 'hello-event' );
    $columns['end_date_time'] = __( 'End', 'hello-event' );
    $columns['tickets'] = __( 'Tickets', 'hello-event' );
    $columns['sales'] = __( 'Sales', 'hello-event' );
    
    return $columns;
  }

  // Content info: Add the data to the custom columns 
  function custom_hello_event_column( $column, $post_id ) {
    switch ( $column ) {
      case 'start_date_time' :
        echo get_post_meta( $post_id , 'start_date_time' , true ); 
        break;
      // case 'start_time' :
      //   echo get_post_meta( $post_id , 'start_time' , true );
      //   break;
      case 'end_date_time' :
        echo get_post_meta( $post_id , 'end_date' , true ). " ". get_post_meta( $post_id , 'end_time' , true );
        break;
      // case 'end_time' :
      //   echo get_post_meta( $post_id , 'end_time' , true );
      //   break;
      case 'tickets' :
        $value = get_post_meta( $post_id, '_tickets', true );
        if ( $value ) {
          $edit_url = admin_url( "post.php" );
          $edit_url = add_query_arg( 'post', $value, $edit_url );
          $edit_url = add_query_arg( 'action', 'edit', $edit_url );
          echo '<a href="' . $edit_url . '">' .  __('Edit ticket', 'hello-event') . '</a>';
        }
        break;
        
        case 'sales' :
        $value = get_post_meta( $post_id, '_tickets', true );
        if ( $value ) {
          $url = admin_url( "edit.php" );
          $url = add_query_arg('post_type', Hello_Event::EVENT_SLUG, $url);
          $url = add_query_arg('page', 'hello-bsr', $url);
          $url = add_query_arg('event-id', $post_id, $url);
          echo '<a href="' . $url . '">' .  __('Tickets sold', 'hello-event') . '</a>';
        }
    }
  }
  
  function make_sortable_hello_event_column( $columns ) {
    $columns['start_date_time'] = 'start_date_time';
    $columns['end_date_time'] = 'end_date_time';
    //To make a column 'un-sortable' remove it from the array
    //unset($columns['date']);
    return $columns;
  }

  function event_column_orderby( $query ) { 
    if( ! is_admin() )  
      return;  
    $orderby = $query->get( 'orderby');  
    if( 'start_date_time' == $orderby ) {  
      $query->set('meta_key','start_date_time');  
      $query->set('orderby','meta_value');  
    }  
  }
  
  // Set up the ticket sales report pages
  function add_backend_sales_report() {
    add_submenu_page(
      '', // Put empty string here to make it disappear from the backend menu
      'Test Settings', // Will not show up
      'Test Settings', // Will not show up
      'edit_posts',    // Allow contributors to see
      'hello-bsr',     // Just a unique slug for this (invisible) menu
       array($this, 'backend_sales_report_page')
     );
  }
  
  function backend_sales_report_page() {
    if ( array_key_exists( 'event-id', $_GET) ) {
      $post_id = $_GET['event-id'];
      $post = get_post($post_id);
      if ($post) {
        $html = '';
        $html .= '<div class="admin-sales-report">';
        $html .= '<h1>'.__('Event:', 'hello-event').' '.get_the_title($post_id).'</h1>';
        $html .= $this->get_purchases_of_event($post_id);
        $html .= '</div>';
        echo $html;
      }
      else {
        _e('No event corresponds to the event id in the URL', 'hello-event');
      }
    }
    else {
      _e('No event id in the URL', 'hello-event');
    }
  }
  
  // =========================== Helpers ================================
  function permalink_by_slug($slug) {
    $args = array(
        'post_type'       => 'page',
        'name'            => $slug,
        'posts_per_page'  => 1
    );
    $pages = get_posts($args);
    if ($pages)
      return get_permalink($pages[0]);
    else
      return false;
  }
  
  function event_id_by_slug($slug) {
    $args = array(
        'post_type'       => Hello_Event::EVENT_SLUG,
        'name'            => $slug,
        'posts_per_page'  => 1
    );
    $events = get_posts($args);
    if ($events and isset($events[0]))
      return $events[0]->ID;
    else
      return false;
  }

  
  function link_to_event_page($permalink, $event_id, $custom_page_slug) {
    // A custom page slug can be given as parameter.
    // If not defined or empty we will look in the settings for a custom page slug
    // If a custom page slug is found and the page exists the link returned = custom page URL with event id as parameter
    // Otherwise the $permalink is returned
    if (isset(get_option( 'hello_event')['hello_field_custom_event_page'])) {
        $custom_page_in_settings = get_option( 'hello_event')['hello_field_custom_event_page'];
        $custom_page_slug = $custom_page_slug ? $custom_page_slug : $custom_page_in_settings;
    }
    else
      return $permalink;
    if (!$custom_page_slug)
      return $permalink;
    $url = $this->permalink_by_slug($custom_page_slug);
    if ($url)
      return add_query_arg('event_id',$event_id, $url);
    else
      return $permalink;
  }
  
  
  //
  // ====================== WooCommerce hooks to make products behave the way we want ===================
  //
  // ----- Test if Woocommerce is active
  function wc_active() {
    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
  }
  
  // ------ Declare the product category for event tickets
  function declare_hello_event_product_category() {
    // Changing the user-readable name of the Woocommerce product category for event tickets
    $existing_old_name = get_term_by('name', $this->namings['event_plural_name'], 'product_cat');
    if ($existing_old_name) {
      // debug_log("Exisiting category = " . print_r($existing_old_name, true));
      wp_update_term($existing_old_name->term_id, 'product_cat',
        array(
          'name' => __('Event ticket', 'hello-event'),
      ));
    }
    else {
      // debug_log("Product category with the old name does not exist");
      $existing = get_term_by('slug', Hello_Event::PRODUCT_SLUG, 'product_cat');
      if (!$existing) {
        // debug_log("Product category with the new name does not exist");
        $result=wp_insert_term(
          __('Event ticket', 'hello-event'),
          'product_cat', // the taxonomy
          array(
            'description'=> __('Tickets to Events', 'hello-event'),
            'slug' => Hello_Event::PRODUCT_SLUG
          )
        );
      }
    }
    // $result=wp_insert_term(
    //   $this->namings['event_plural_name'], // the term. We use the same name is what has been set for the Events
    //   'product_cat', // the taxonomy
    //   array(
    //     'description'=> __('Tickets to Events', 'hello-event'),
    //     'slug' => Hello_Event::PRODUCT_SLUG
    //   )
    // );
  }
  
  // --- Saving products: verify that category set and for tickets verify link
  // If there are errors, the product is still saved, but we output error messages
  function product_save($post_id,$post) {
    global $wpdb;
    $errors = false;
    $error_codes = [];
       // verify this is not an auto save routine. 
       if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

       if ( ! ($post->post_type == 'product') ) return;
       if ( $post->post_status == 'auto-draft' ) return;
      if (has_term(Hello_Event::PRODUCT_SLUG, 'product_cat', $post_id)) {
         $event_ids = $this->get_events_of_this_ticket($post_id);
         $event_id = $event_ids ? $event_ids[0] : false;
         if ( $event_id) {
           // Check that the event does not already have another associated ticket
           $shop_link = get_post_meta($event_id,'link_to_the_shop',true);
           if ( $shop_link && $shop_link != "null" && $shop_link != $post_id ) {
             $errors = true;
             array_push($error_codes, 'ticket-exists');
           }
           else { }
         }
         else {
           $errors = true;
           array_push($error_codes,'no-event');         
         }
       }
       // Before showing any error messages, we verify that error messaging is enabled. Otherwise we finishe here
       if (! $this->enable_product_save_error_messages)
         return;
       // on attempting to publish - check for completion and intervene if necessary
       if ( ( isset( $_POST['publish'] ) || isset( $_POST['save'] ) ) && $_POST['post_status'] == 'publish' ) {
         if ($errors) {
           global $wpdb;
           // Previously we forced the status to pending if there were errors. That was not a good idea!
           // But for cases that go beyond the documented possibilities such as for example: 
           //   There can be situations where there is not a single event for a product
           //   (when you want an event to have several occurencies it is allowed to create several products of a category)
           // $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $post_id ) );
           
           // filter the query URL to change the published message
           add_filter('redirect_post_location', function($location) use ($error_codes){
             return add_query_arg( 'hello-event-errors', $error_codes, $location );
           });
         }
       }
  }
  
  // ------------- Order completion 
  function woocommerce_payment_complete( $order_id ) {
    global $woocommerce;
    if ( !$order_id )
      return;
    $order_can_be_completed = true;
    $order = wc_get_order( $order_id );
    //debug_log("... user_id = ".$order->get_user_id());
    $order_items = $order->get_items();
    foreach( $order_items as $order_item) {
      // $order_item is an array with keys: keys: name,type,item_meta,item_meta_array,qty,tax_class,product_id,variation_id,line_subtotal,line_total,line_subtotal_tax,line_tax,line_tax_data
      $product_cat = wp_get_post_terms($order_item['product_id'], 'product_cat' )[0];
      if ($product_cat->slug == Hello_Event::PRODUCT_SLUG) {
          $this->process_event_ticket_order($order, $order_item);
      }
      else { $order_can_be_completed = false; }
    }
    // Change order status unless it contains other stuff that is to be delivered separately
    if ($order_can_be_completed) {
      $order->update_status('completed');
    }
  }

  function process_event_ticket_order($order, $order_item) {
    $order_id = $order->get_id();
    $product_id = $order_item['product_id'];
    // Here we could log ticket purchase to dedicated database table
    // $first_name = $order->get_billing_first_name();
    // $last_name =  $order->get_billing_last_name();
    // $event_id = get_post_meta($product_id,'event',true);
    // $order_number = $order->get_order_number();
    // $quantity = $order_item['qty'];
    // $price = $order_item['line_total'];
    // $this->log_purchase($first_name, $last_name, $event_id, $order_number, $quantity, $price);
  }

  // --- Trashing tickets: remove link(s) from event(s)
  function trashed_post($post_id) {
    //debug_log("Trashed post handler. Post id = ".$post_id);
    if ( (get_post_type($post_id) == 'product') && $this->product_has_category($post_id, Hello_Event::PRODUCT_SLUG) ) {
      $old_event_ids = $this->get_events_of_this_ticket($post_id);
      //debug_log("This was a ticket to the events: ". print_r($old_event_ids, true));
      foreach ($old_event_ids as $old_event_id) {
        do_action('hello_event_link_to_product_deleted', $old_event_id);
        delete_post_meta($old_event_id, '_tickets');
      }
    }
  }

  // --- Get the slug of a product category from its ID
  function get_product_category_by_id($cat_id) {
    $category = get_term_by('id', $cat_id, 'product_cat', 'ARRAY_A');
    return $category['slug']; // can be 'name' if we want that
  }

  // When showing ticket products show backlink to event page if it has been defined
  // This function is currently disabled. We use a shortcode instead for the function
  // function add_backlink_for_tickets_OLD() {
  //   global $wpdb;
  //   $prod_id = get_the_ID();
  //   if ($this->product_is_category($prod_id, Hello_Event::PRODUCT_SLUG)) {
  //     $table_name = $wpdb->prefix . 'postmeta';
  //     $sql = "SELECT post_id FROM {$table_name}
  //              WHERE  meta_key = '_tickets' AND meta_value = $prod_id";
  //     $result = $wpdb->get_results($sql);
  //     $event_id = $result ? $result[0]->post_id : false;
  //     if ($event_id) {
  //       $backlink = get_permalink( $event_id );
  //       echo("<a href='".$backlink."'>Go to the event page</a><br/>");
  //     }
  //   }
  // }
  
  
  // Two alternatives to generate backlinks on the product page
  // The first one is a filter function. It works but for variable products the link appears twice
  //
  // public function add_backlink_to_event_0($html) {
  //   // Filter function to return the html with a backlink from a ticket in the shop to the corresponding event
  //   // For the link to be added, the option must be set and we should be on a single product page.
  //   if ( is_single() &&
  //        isset(get_option( 'hello_event')['hello_field_autolink_to_event']) &&
  //        get_option( 'hello_event')['hello_field_autolink_to_event']=="on" ) {
  //     global $wpdb;
  //     $prod_id = get_the_ID();
  //     list($event_id, $rc) = $this->get_id_of_event($prod_id);
  //     if ($event_id) {
  //       $backlink = get_permalink( $event_id );
  //       $html .= '<br/><a href="' . $backlink .'">' . __("Goto the event", 'hello-event') . '</a><br/>';
  //       $html = apply_filters('hello_event_display_link_to_event', $html, $prod_id, $event_id);
  //     }
  //   }
  //   return $html;
  // }
  
  public function add_backlink_to_event($html) {
    // Action to echo a backlink from a ticket in the shop to the corresponding event
    // For the link to be added, the option must be set and we should be on a single product page.
    if ( is_single() &&
         isset(get_option( 'hello_event')['hello_field_autolink_to_event']) &&
         get_option( 'hello_event')['hello_field_autolink_to_event']=="on" ) {
      global $wpdb;
      $prod_id = get_the_ID();
      list($event_id, $rc) = $this->get_id_of_event($prod_id);
      if ($event_id) {
        $backlink = $this->link_to_event_page(get_permalink($event_id), $event_id, false);
        $html = '<a href="' . $backlink .'">' . __("Goto the event", 'hello-event') . '</a><br/><br/>';
        $html = apply_filters('hello_event_display_link_to_event', $html, $prod_id, $event_id);
      }
    }
    echo $html;
  }
  
  public function hide_tickets_to_past_events() {
    $options = get_option( 'hello_event' );
    if (isset($options['hello_field_ticket_visibility']) && $options['hello_field_ticket_visibility']=='on') {
      $args = array(
          'category' => array( Hello_Event::PRODUCT_SLUG ),
          'visibility' => 'catalog',
      );
      $tickets = wc_get_products( $args );
      // debug_log("TICKETS: " . print_r($tickets, true));
      // debug_log("NUM TICKETS: " . count($tickets));
      foreach($tickets as $ticket) {
        list($event_id, $rc) = $this->get_id_of_event($ticket->get_id());
        if ($event_id) {
          $event_end_date = get_post_meta($event_id, 'end_date', true);
          if($event_end_date && $event_end_date < date("Y-m-d")) {
            // Past event
            $ticket->set_catalog_visibility('hidden');
            // Save and sync the product visibility
            $ticket->save();
          }
        }
        else {
          // Event ticket but there is no corresponding event
          $ticket->set_catalog_visibility('hidden');
          // Save and sync the product visibility
          $ticket->save();
        }
      }
    }
    else {
      // 
    }
    
  }
  

  
  //
  // ================= Private support functions =============================================================
  //
  
  function simple_excerpt($html) {
    return substr(strip_tags($html), 0, 150) . '...';
  }
  
  function real_or_computed_excerpt($event_id) {
    // The fancy stuff is currently disabled
    // Will return:
    //  - excerpt in current language if this exists
    //  - excerpt in primary language if this exists
    //  - computed excerpt in current language if the content in the current language exists
    //  - computed excerpt from the primary language
    $post = get_post($event_id);
    //return get_the_excerpt($post);
    global $wp;
    //debug_log("POST=".print_r($post,true));
    $locale = explode('_', get_locale())[0]; // Get first part of locale, like fr from fr_FR
    // 1) Get directly from the event
    $excerpt = $post->post_excerpt;
    // debug_log("FIRST TRY Event: $event_id ; Excerpt: $excerpt");
    // 2) Get from category hierarchy if the event excerpt is empty
		//    This filter is defined when there are event categories, for example using the"Hello Event Again" plugin
    $excerpt = apply_filters('hello_event_excerpt_from_category', $excerpt, $post);
    // debug_log("SECOND TRY Event: $event_id ; Excerpt: $excerpt");
    $excerpt = apply_filters('translate_text', $excerpt , $locale) ;  
    // 3) If still empty then create one from the content
    if ($excerpt == '') {
      $content = $post->post_content;
      $content = apply_filters('hello_event_content_from_category', $content);
      $content = apply_shortcodes($content);
	    $content = apply_filters('translate_text', $content , $locale) ;  
      $content = str_replace(']]>', ']]&gt;', $content);
      $excerpt = $this->simple_excerpt($content);
    }
    return $excerpt;
  }
  
  function product_create_for_event($event_id) {
    // Create a WC product for the event
    // Return the correspondig ID and the URL to editing the product
    global $wp;
    $post = get_post($event_id);
    // Notice: In what follows  we can't use get_the_title($event_id) since it would apply content filtering
    // which would apply translations! Instead we want to keep the multilingual content.
    $title = isset( $post->post_title ) ? $post->post_title : '';
    $excerpt = isset( $post->post_excerpt ) ? $post->post_excerpt : '';
    $prod = array(
          'post_title' => $title,
          'post_type' => 'product',
    );
    $prod_id = wp_insert_post( $prod );
    $real_post = get_post($prod_id);
    set_post_thumbnail( $real_post, get_post_thumbnail_id($event_id) );
    // Make the ticket a virtual product
    update_post_meta( $prod_id, '_virtual', 'yes' );
    // Set the event category for our product and its status to draft
    wp_set_object_terms($prod_id, Hello_Event::PRODUCT_SLUG, 'product_cat');
    wp_update_post(array(
      'ID'    =>  $prod_id,
      'post_status'   =>  'draft',
      'post_excerpt' => $excerpt
    ));
    return $prod_id;
  }
  
  public function get_id_of_product($event_id, $must_be_published, $must_have_price=true) {
    // Return id to corresponding product in the shop + return code if:
    // - the _sell_tickets is set
    // - _tickets is defined
    // - _tickets points to an existing post
    // - this post is a product and of the correct category
    // - if $must_be_published then the product must also have status set to published
    // - if $must_have_price then the product must also have a price set
    // if a condition not met the return code indicates one reason
    if ($this->wc_active()) {
      if ( get_post_meta( $event_id, '_sell_tickets', true)) {
        if ( get_post_meta( $event_id, '_tickets', true) ) {
          $prod_id = get_post_meta( $event_id, '_tickets', true);
          if (is_string( get_post_status( $prod_id )) && 
              (get_post_type($prod_id) == 'product') &&
              $this->product_has_category($prod_id, Hello_Event::PRODUCT_SLUG)) {
            if ( ! ($must_be_published && (get_post_status($prod_id) != 'publish')) ) {
              if ( !$must_have_price || get_post_meta($prod_id, '_price', true) || get_post_meta($prod_id, '_regular_price', true) ) {
                return array($prod_id, 0); // Evereything is fine
              }
              else return array(false, 6); // No price has been set
            }
            else return array(false, 5); // Ticket is not publsihed
          }
          else return array(false, 4); // The link does not point to a product of the right category
        }
        else return array(false, 3); // _tickets link not set
      }
      else return array(false, 2); // _sell_tickets check box not set
    }
    else return array(false, 1); // Woocommerce not active
  }
  
  function get_link_to_product($event_id, $must_be_published, $must_have_price=true) {
    // Return a link to the shop if:
    // - the _sell_tickets is set
    // - _tickets is defined
    // - _tickets points to an existing post
    // - this post is a product and of the correct category
    // - if $must_be_published then the product must also have status set to published
    list($prod_id, $rc) = $this->get_id_of_product($event_id, $must_be_published, $must_have_price);
    if ($prod_id) {return array(get_permalink($prod_id), 0);}
    else {return array(false, $rc);}
  }
  
  function test_get_link_to_product($event_id, $must_be_published, $must_have_price=true) {
    // Return debug info:
    // - the _sell_tickets is set
    // - _tickets is defined
    // - _tickets points to an existing post
    // - this post is a product and of the correct category
    // - if $must_be_published then the product must also have status set to published
    $html = "Getting link to ticket for event $event_id. Must be published: $must_be_published Must have price: $must_have_price <br/>";
    list($prod_id, $rc) = $this->get_id_of_product($event_id, $must_be_published, $must_have_price);
    $html .= "Ticket product_id = $prod_id | Return code: $rc <br/>";
    if ($prod_id) {
      $html .= "Getting the URL of the ticket: ";
      $html .= get_permalink($prod_id);
    }
    else {
      $html .= "Product id not set. Can't get an URL";
    }
    return $html;
  }
  
  
  
  function get_orders_items_by_product_id( $product_id, $order_status = array( 'wc-completed', 'wc-processing' ) ) {
      global $wpdb;
      $sql = "
          SELECT *
          FROM {$wpdb->prefix}woocommerce_order_items as order_items
          LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
          LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
          ON order_items.order_item_id =   order_item_meta.order_item_id
          WHERE posts.post_type = 'shop_order'
          AND posts.post_status IN ( '" . implode( "','", $order_status ) . "' )
          AND order_items.order_item_type = 'line_item'
          AND order_item_meta.meta_key = '_product_id'
          AND order_item_meta.meta_value = '".$product_id."'
      ";
      $results = $wpdb->get_results($sql);
      return $results;
  }

  
  function get_orders_by_product_id( $product_id ) {
    // This version from https://www.rfmeier.net/get-all-orders-for-a-product-in-woocommerce/
      global $wpdb;

      $raw = "
          SELECT
            `items`.`order_id`,
            MAX(CASE WHEN `itemmeta`.`meta_key` = '_product_id' THEN `itemmeta`.`meta_value` END) AS `product_id`
          FROM
            `{$wpdb->prefix}woocommerce_order_items` AS `items`
          INNER JOIN
            `{$wpdb->prefix}woocommerce_order_itemmeta` AS `itemmeta`
          ON
            `items`.`order_item_id` = `itemmeta`.`order_item_id`
          WHERE
            `items`.`order_item_type` IN('line_item')
          AND
            `itemmeta`.`meta_key` IN('_product_id')
          GROUP BY
            `items`.`order_item_id`
          HAVING
            `product_id` = %d";

      $sql = $wpdb->prepare( $raw, $product_id );

      return array_map(function ( $data ) {
          return wc_get_order( $data->order_id );
      }, $wpdb->get_results( $sql ) );

  }
  
  
  function get_purchases_of_event($post_id) {
    $post = get_post($post_id);
    global $wbdb;
    if ($this->wc_active()) {
      if ( $post->post_type == Hello_Event::EVENT_SLUG ) {
        list($product_id, $rc) = $this->get_id_of_product($post->ID, true);
        if ($product_id) {
          $html = "";
          $order_items = $this->get_orders_items_by_product_id($product_id);
          $html .= "<div class='hello_purchase_of_event'>";
          $html .= "<h3>" . __("Ticket sales status", 'hello-event')."</h3>";
          $html .= "<table>";
          $html .= "<thead>";
          $html .= "<tr><th>".__("Date", 'hello-event')."</th>";
          $html .= "<th>".__("Order", 'hello-event')."</th>";
          $html .= "<th>".__("Last name", 'hello-event')."</th><th>".__("First name", 'hello-event')."</th>";
          $html .= "<th>".__("Quantity", 'hello-event')."</th><th>".__("Paid", 'hello-event')."</th></tr>";
          $html .= "</thead><tbody>";
          $total_paid = 0;
          $total_qty = 0;
          foreach($order_items as $order_item) {
            $html .= "<tr>";
            $order_item_id = $order_item->order_item_id;
            $order_id = $order_item->order_id;
            $order = get_post($order_id);
            $order_url = get_edit_post_link($order_id);
            $qty = wc_get_order_item_meta($order_item_id, '_qty', true);
            $paid = wc_get_order_item_meta($order_item_id, '_line_subtotal', true);
            $html .= "<td>".$order->post_date."</td>";
            $html .= "<td>".'<a href="'.$order_url.'">'.__("Order", 'hello-event').'</a>'."</td>";
            $html .= "<td>".get_post_meta($order_id, '_billing_last_name', true)."</td>";
            $html .= "<td>".get_post_meta($order_id, '_billing_first_name', true)."</td>";
            $html .= "<td>".$qty."</td>";
            $html .= "<td>".$paid."</td>";
            $html .= "</tr>";
            $total_paid += $paid;
            $total_qty += $qty;
          }
          $html .= "<tr class='total'><td> </td><td> </td><td> </td><th>".__("Total", 'hello-event')."</th>";
          $html .= "<td>".$total_qty."</td>";
          $html .= "<td>".$total_paid."</td>";
          $html .= "</tr>";
          $html .= "</tbody>";
          $html .= "</table>";
          $html .= "</div>";
          return $html;
        }
        else {
          return $this->debug_msg(__("Tickets not fully configured", 'hello-event') . ". ".
                               $this->ticket_config_error($rc));
        }
      }
      else { return $this->debug_msg(__("Shortcode can only be used when displaying events",
                                                        'hello-event')); }
    }
    else { return $this->debug_msg(__("The Woocommerce plugin is not activated", 'hello-event')); }
  }
  
  function debug_msg($text) {
    if ($this->hello_event_debug && current_user_can('edit_posts') ) {
      return "<div class='hello_event_debug'>" . $text . "</div>";
    }
    else { return is_admin(); }
  }
  
  function force_iso_date($date) {
    // Sanitize and return a date in ISO format
    $date = sanitize_text_field($date);
    $dateformat =  isset(get_option( 'hello_event')['hello_field_dateformat']) ?
      get_option( 'hello_event')['hello_field_dateformat'] : 'iso';
    switch ($dateformat) {
      case 'iso':
      return $date;
      break;
      case 'us':
      $d = explode("/", $date);
      return $d[2].'-'.$d[0].'-'.$d[1];
      break;
      case 'fr':
      $d = explode("/", $date);
      return $d[2].'-'.$d[1].'-'.$d[0];
      break;
    }
  }

  public function transform_iso_date($date, $hide_weekday = false) {
    // In: date in ISO format
    // Return: date in current format or false the date is not on iso-format
    // The date will be preceeded by the weekday name if this setting is positioned AND $hide_weekday is false
    $dayNames = array(
      __('Sunday', 'hello-event'),
      __('Monday', 'hello-event'),
      __('Tuesday', 'hello-event'),
      __('Wednesday', 'hello-event'),
      __('Thursday', 'hello-event'),
      __('Friday', 'hello-event'),
      __('Saturday', 'hello-event'),
     );
    
    $d = explode("-", $date);
    if (count($d) != 3) { return false;}
    $dateformat =  isset(get_option( 'hello_event')['hello_field_dateformat']) ?
      get_option( 'hello_event')['hello_field_dateformat'] : 'iso';
    $show_weekday = isset(get_option( 'hello_event')['hello_field_weekday']) ?
      get_option( 'hello_event')['hello_field_weekday'] : 'no';
    $weekday = "";
    if ($show_weekday == 'yes') {
      $t = mktime(0,0,0,(int)$d[1], (int)$d[2], (int)$d[0]);
      $weekday_number = date("w", $t);
      $weekday = $dayNames[$weekday_number]. " ";
    }
    if ($hide_weekday)
      $weekday = "";
    switch ($dateformat) {
      case 'iso':
      return $weekday . $date;
      break;
      case 'us':
      return $weekday . $d[1].'/'.$d[2].'/'.$d[0];
      break;
      case 'fr':
      return $weekday . $d[2].'/'.$d[1].'/'.$d[0];
      break;
    }
  }
  
  function force_iso_time($time) {
    // Sanitize and return a date in ISO format (24h)
    $time = trim(sanitize_text_field($time));
    $timeformat =  isset(get_option( 'hello_event')['hello_field_timeformat']) ?
      get_option( 'hello_event')['hello_field_timeformat'] : '24h';
    switch ($timeformat) {
      case '24h':
      $iso_time = $time;
      break;
      case 'am-pm':
      $t = explode(" ", $time);
      $time_array = explode(":", $t[0]);
      if (count($time_array) == 2) {
        $h = $time_array[0];
        $m = $time_array[1];
        $h = isset($t[1]) && $t[1]=="PM" ? $h+12 : $h;
        $iso_time = $h.":".$m;
      }
      else {
        $iso_time = "00:00"; // Set to 0 if illegal format
      }
      break;
    }
    // pad with 0 as needed
    $time_array = explode(":", $iso_time);
    if (count($time_array) == 2) {
      $iso_time = strlen($time_array[0]) == 1 ? '0'.$iso_time : $iso_time;
    }
    else {
      $iso_time = "00:00"; // Set to 0 if illegal format
    }
    return $iso_time;
  }

  function transform_iso_time($time) {
    // In: time in ISO format (24h)
    // Return: time in current format
    $timeformat =  isset(get_option( 'hello_event')['hello_field_timeformat']) ?
      get_option( 'hello_event')['hello_field_timeformat'] : '24h';
    switch ($timeformat) {
      case '24h':
      return $time;
      break;
      case 'am-pm':
      $t = explode(":", $time);
      if (count($t) == 2) {
        if ($t[0] > 12) {
          return $t[0]-12 . ':'. $t[1] . ' PM';
        }
        else {
          return $t[0] . ':'. $t[1] . ' AM';
        }
      }
      else { // Time is empty or of illegal format
        return $time;
      }
      break;
    }
  }
  
  // function product_is_category_Obsolete($product_id, $category_slug){
    // This version only checks the first category of a product, which is not enough for example
    // if the product has subcategories
  //   if ( empty(wp_get_post_terms($product_id, 'product_cat' )) )
  //     return false;
  //   $product_category = wp_get_post_terms($product_id, 'product_cat' );
  //   $it_is = $product_category ? ($product_category->slug) == $category_slug : false ;
  //   return $it_is;
  // }
  
  function product_is_category($product_id, $category_slug) {
    $product_cats_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
    $product_cats = array_map(
        function($cat_id){$term = get_term_by( 'id', $cat_id, 'product_cat' ); return $term->slug;},
        $product_cats_ids
    );
    return (in_array('hello_event_ticket', $product_cats));    
  }

  function product_has_category($product_id, $category_slug){
    $product_categories = wp_get_post_terms($product_id, 'product_cat' );
    $it_has = false;
    foreach($product_categories as $cat) {
      //debug_log($cat->slug);
      if ($cat->slug == $category_slug) {
        $it_has = true;
        break;
      }
    }
    return $it_has;
  }
  
  function ticket_config_error($rc) {
    // Return the appropriate error message for an unfinished/misconfigured ticket
    $errors = array(
      1=> __("The Woocommerce plugin is not activated", 'hello-event'),
      2=> __("You need to set the check box to sell tickets in the event!", 'hello-event'),
      3=> __("There is no link from the event to a ticket. Save the event again!", 'hello-event'),
      4=> __("The link from the event does not point to a ticket in Woocommerce. Save the event again!", 'hello-event'),
      5=> __("The ticket exists in Woocommerce, but you need to set it as published", 'hello-event'),
      6=> __("The ticket exists in Woocommerce, and is published, but you need to set its price", 'hello-event'),
    );
    return $errors[$rc];
  }
  
  //
  // ================== Shortcode handlers =========================================================
  //
  public function link_to_ticket_OLD($args) {
    // If the current post is an event and there are slots for sale, then return a link
    // Process shortcode hello_event_link_to_ticket

    global $post;
    $html = '';
    if ( $post->post_type == Hello_Event::EVENT_SLUG ) {
      $a = shortcode_atts( array(
             'text' => __("Tickets to participate are available in our shop", 'hello-event'),
         ), $args );
      list($link, $rc) = $this->get_link_to_product(get_the_ID(), true);
      if ($link) {
        $html .= '<a href="'. $link . '">' . $a['text'] . '</a>';
        $html = apply_filters('hello_event_display_link_to_ticket', $html, get_the_ID(), $link);
        // Don't know why at some point we had a fourth parameter here
        // $html = apply_filters('hello_event_display_link_to_ticket', $html, get_the_ID(), $link, 'shortcode');
        return $html . '</div>';
      }
      else { return $html . $this->debug_msg(__("Tickets not fully configured", 'hello-event') . ". ".
                     $this->ticket_config_error($rc)); }      
    }
    else { return $html . $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function link_to_ticket($args) {
    // If the current post is an event and there are slots for sale, then return a link
    // Process shortcode hello_event_link_to_ticket
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
           'text' => __("Tickets to participate are available in our shop", 'hello-event'),    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    $html = '';
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      list($link, $rc) = $this->get_link_to_product($id, true);
      if ($link) {
        $html .= '<a href="'. $link . '">' . $args['text'] . '</a>';
        $html = apply_filters('hello_event_display_link_to_ticket', $html, $id, $link);
        // Don't know why at some point we had a fourth parameter here
        // $html = apply_filters('hello_event_display_link_to_ticket', $html, get_the_ID(), $link, 'shortcode');
        // Why a closing DIV???
        // return $html . '</div>';
        return $html;
      }
      else { return $html . $this->debug_msg(__("Tickets not fully configured", 'hello-event') . ". ".
                     $this->ticket_config_error($rc)); }      
    }
    else { return $html . $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  function get_events_of_this_ticket($ticket_post_id) {
    // Helper function
    // return an array of event ids for this ticket (should be 0 or 1, but you never know...)
    global $wpdb;
    $table_name = $wpdb->prefix . 'postmeta';
    $sql = "SELECT post_id FROM {$table_name} 
             WHERE meta_key = '_tickets'
             AND   meta_value = $ticket_post_id";
    return array_map(function($elem){return $elem->post_id;},$wpdb->get_results($sql));
  }


  function get_id_of_event($prod_id = false) {
    // Internal function
    // Return id to corresponding event + return code if:
    // - post is a product and of the correct category
    // - event exists
    global $wpdb;
    $prod_id = $prod_id ? $prod_id : get_the_ID();
    if ($this->product_is_category($prod_id, Hello_Event::PRODUCT_SLUG)) {
      // debug_log("Is slug");
      $table_name = $wpdb->prefix . 'postmeta';
      $sql = "SELECT post_id FROM {$table_name} 
               WHERE  meta_key = '_tickets' AND meta_value = $prod_id";
      $result = $wpdb->get_results($sql);
      // debug_log("RESULT=". print_r($result, true));
      $event_id = $result ? $result[0]->post_id : false;
      // debug_log("Event id = $event_id");
      if ($event_id) {
        return array($event_id, 0);
      }
      else { return array(false, 2); }
    }
    else { return array(false, 1); }
  }
  
  public function id_of_event($args) {
    // Return id to corresponding event + return code if:
    // - post is a product and of the correct category
    // - event exists  
    $prod_id = get_the_ID();
    list($event_id, $rc) = $this->get_id_of_event($prod_id);
    if ($event_id) {
      return $event_id;
    }
    else {
      if ($rc == 1) {
        return $this->debug_msg(__("Shortcode can only be used for products of the Hello Event category", 'hello-event'));
      }
      elseif ($rc == 2) {
        return $this->debug_msg(__("Product is orphaned", 'hello-event'));
      }
    }
  }

  public function link_to_event($args) {
    // Return a backlink from a ticket in the shop to the corrsponding event
    $a = shortcode_atts( array(
           'text' => __("Goto the event", 'hello-event'),
    ), $args );
    global $wpdb;
    $prod_id = get_the_ID();
    list($event_id, $rc) = $this->get_id_of_event($prod_id);
    if ($event_id) {
      $backlink = $this->link_to_event_page(get_permalink($event_id), $event_id, false);
      $html = '<a href="' . $backlink .'">' . $a['text'] . '</a><br/>';
      $html = apply_filters('hello_event_display_link_to_event', $html, $prod_id, $event_id);
      return $html;
    }
    else {
      if ($rc == 1) {
        return $this->debug_msg(__("Shortcode can only be used for products of the Hello Event category", 'hello-event'));
      }
      elseif ($rc == 2) {
        return $this->debug_msg(__("Product is orphaned", 'hello-event'));
      }
    }
  }
  
  public function event_start_date($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
           'format' => false,
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      if ($args['format'] == 'iso')
        $html = get_post_meta($id, 'start_date', true);
      else
        $html = $this->transform_iso_date(get_post_meta($id, 'start_date', true));
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function event_title($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      return get_the_title($id);
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function event_thumbnail($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
           'width' => false,
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      if ($args['width']) {
        $html = '<div style="display:inline-block; width:'.$args['width'].'px">';
        $html .= get_the_post_thumbnail($id);
        $html .= '</div>';
        return $html;
      }
      else
          return get_the_post_thumbnail($id);
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }

  public function event_thumbnail_url($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
           'width' => false,
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      if ($args['width']) {
        $html = '<div style="display:inline-block; width:'.$args['width'].'px">';
        $html .= get_the_post_thumbnail_url($id);
        $html .= '</div>';
        return $html;
      }
      else
          return get_the_post_thumbnail_url($id);
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }


  public function event_content($args){
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = get_post_field('post_content', $id);
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function event_excerpt($args){
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = $this->real_or_computed_excerpt($id);
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
    
  
  public function event_start_time($args){
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = $this->transform_iso_time(get_post_meta($id, 'start_time', true));
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  function fullday_event($id) {
    $start = get_post_meta($id, 'start_time', true);
    $end = get_post_meta($id, 'end_time', true);
    // debug_log("start=".$start."<");
    // debug_log("end=".$end."<");
    return ( ($start == "00:00") || $start == '' ) && ( ($end == "00:00") || $end == '' );
  }

  public function event_start_date_and_time($args){
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html_date = $this->event_start_date($args);
      $html_date = apply_filters('tekomatik_adapt_date', $html_date); // possibility t adapt the date format
      $html_time = $this->event_start_time($args);
      if ($this->fullday_event($id))
        return $html_date;
      else {
        return $html_date . ' ' . __('at', 'hello-event') . ' ' . $html_time;
      }
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }

  public function event_end_date_and_time($args){
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html_date = $this->event_end_date($args);
      $html_date = apply_filters('tekomatik_adapt_date', $html_date); // possibility t adapt the date format
      $html_time = $this->event_end_time($args);
      if ($this->fullday_event($id))
        return $html_date;
      else {
        return $html_date . ' ' . __('at', 'hello-event') . ' ' . $html_time;
      }
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }

  
  public function event_end_date($args) { 
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
           'format' => false,
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      if ($args['format'] == 'iso')
        $html = get_post_meta($id, 'end_date', true);
      else
        $html = $this->transform_iso_date(get_post_meta($id, 'end_date', true));
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function event_end_time($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = $this->transform_iso_time(get_post_meta($id, 'end_time', true));
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }

  public function event_location_name($args) { 
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = get_post_meta($id, 'location_name', true);
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function event_location($args) { 
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = get_post_meta($id, 'location', true);
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }

  public function event_advice($args) { 
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $html = get_post_meta($id, 'advice', true);
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  
  function event_ics($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
           'text' => __('Add to Calendar', 'hello-event'),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      $btn_txt = $args['text'];
      $html = "<form method='post' action=#>";
      $html .=  "<input type='hidden' name='get_ics' value='" . $id  . "'/>";
      $html .= '<input type="submit" value="' . $btn_txt . '">';
      $html .= '</form>';
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  function event_map($args) {
    $args = shortcode_atts( array(
           'id' => get_the_ID(),
    ), $args );
    $args['id'] = isset($_GET['event_id']) ? $_GET['event_id'] : $args['id'];
    $id = $args['id'];
    if (get_post_type($id) != Hello_Event::EVENT_SLUG)
      $id = $this->default_event_id;
    if (get_post_type($id) == Hello_Event::EVENT_SLUG) {
      global $hello_event_map_object;
      $html= $hello_event_map_object->get_map(array('id' => $id));
      return $html;
    }
    else { return $this->debug_msg(__("Shortcode can only be used when displaying events", 'hello-event')); }
  }
  
  public function default_event($args) {
    // Set a default event id that can be used in the various shortcodes if no other id is available
    // This is in particular useful when editing and testing the custom event page
    // Add slog
    // Possibly : set as meta on the page ??? (as with elementor plugin)
    $args = shortcode_atts( array(
           'id' => false,
           'slug' =>false,
    ), $args );
    if ($args['id'])
      $this->default_event_id = $args['id'];
    if ($args['slug']) {
      $this->default_event_id = $this->event_id_by_slug($args['slug']);
    }
  }

  
  // ============== End Shortcode Handlers ==============================================================
  
  // ============== ICS Downloader ======================================================================
  function get_event_ics() {    
    // If we have the get_ics attribute in the URL then we answer by sending back an ical event
    if (isset($_POST['get_ics'])) {
      $id = $_POST['get_ics'];
      $post = get_post($id);
      $permalink = get_permalink($id);
      $start = get_post_meta( $id, 'start_date', true ) . ' ' . get_post_meta( $id, 'start_time', true );
      $dstart = get_post_meta( $id, 'start_time', true ) ?
          date('Ymd\THis', strtotime($start)) :
          date('Ymd', strtotime(get_post_meta( $id, 'start_date', true )));
      //$dstart = date('Ymd\THis\Z', strtotime($start)); // Compensate for UTC+x
      $end = get_post_meta( $id, 'end_date', true ) . ' ' . get_post_meta( $id, 'end_time', true );
      //$dend = date('Ymd\THis\Z', strtotime($end));
      $dend = get_post_meta( $id, 'end_time', true ) ?
          date('Ymd\THis', strtotime($end)) :
          date('Ymd', strtotime(get_post_meta( $id, 'end_date', true )));
      $args = array (
      'location_name' => get_post_meta($id, 'location_name', true),
      'location' => get_post_meta($id, 'location', true),
      'summary' => get_the_title($id),
      'dstart' => $dstart,
      'dend' => $dend,
      'description' => apply_filters('the_excerpt', get_post_field('post_excerpt', $id)),
      );
      $ics = new ICS($args);
      // Previously we applied htmlentities to summary, location and description
      header('Content-Type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename=invite.ics');
      echo 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:councilsites
METHOD:PUBLISH
BEGIN:VEVENT
URL:'. htmlentities( $permalink ) .'
UID:'. $id .'
SUMMARY:'. $args['summary'] .'
DTSTART:'. $args['dstart'] .'
DTEND:'. $args['dend'] .'
LOCATION:'. $args['location_name'] . ": ". $args['location'] .' 
DESCRIPTION:'. strip_tags( $args['description'] ) .' 
END:VEVENT
END:VCALENDAR';
exit();
  }
}

  // // ----- Return Google map and optionally do geoencode and update the location coordinates via Ajax
  // function get_google_map($post_id) {
  //   if (isset(get_option( 'hello_event')['hello_field_google_maps']) &&
  //       get_option( 'hello_event')['hello_field_google_maps']) {
  //     $key = get_option( 'hello_event')['hello_field_google_maps'];
  //     $location = get_post_meta($post_id, 'location', true);
  //     $lat = get_post_meta($post_id, 'location_lat', true) ?  get_post_meta($post_id, 'location_lat', true) : 53;
  //     $lng = get_post_meta($post_id, 'location_lng', true) ? get_post_meta($post_id, 'location_lng', true) : 10;
  //     if (!$location) { return "Location not set"; }
  //     $html = do_shortcode('[hello-event-google-map]');
  //     return $html;
  //   }
  //   else { return __('You need to register your Google Maps API Key before the map can be shown', 'hello-event'); }
  // }
  //
  // // ----- Return OpenStreet map and optionally do geoencode and update the location coordinates via Ajax
  // function get_openstreet_map($post_id) {
  //   $location = get_post_meta($post_id, 'location', true);
  //   $lat = get_post_meta($post_id, 'location_lat', true) ?  get_post_meta($post_id, 'location_lat', true) : 53;
  //   $lng = get_post_meta($post_id, 'location_lng', true) ? get_post_meta($post_id, 'location_lng', true) : 10;
  //   if (!$location) { return "Location not set"; }
  //   $html = do_shortcode('[hello-event-openstreet-map]');
  //   return $html;
  // }
  
  // ----- Return structured data for an event
  function get_structured_data($id) {
    $title = get_the_title($id); // Will be localized
    $start_date_time = get_post_meta($id, 'start_date', true)."T".get_post_meta($id, 'start_time', true);
    $end_date_time = get_post_meta($id, 'end_date', true)."T".get_post_meta($id, 'end_time', true);
    $location_name = get_post_meta($id, 'location_name', true);
    $location = get_post_meta($id, 'location', true);
    $content_post = get_post($id);
    $content = $content_post->post_content;
    //$content = apply_filters('the_content', $content);
    //$content = str_replace(']]>', ']]&gt;', $content);
    $excerpt = $this->simple_excerpt($content);
    
    $html = <<< EOF
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Event",
    "name": "$title",
    "startDate": "$start_date_time",
    "location": {
      "@type": "Place",
      "name": "$location_name",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "$location"
      }
    },
    "description": "$excerpt",
    "endDate": "$end_date_time"
  }
  </script>
EOF;
return $html;
}
  
  // ============== Adapt display using standard templates ==============================================
  
  // Could be used to filter the thumbnail. Currently not used
  function filter_post_thumbnail_html($html) {
    global $post;
    $post_id = $post->ID;
    if (get_post_type($post_id) == Hello_Event::EVENT_SLUG) {
      if (is_archive()) {
      }
      else {
      }
      return $html;
    }
  }
  
    
  function filter_post_content($content) {
    global $post;
    $post_id = $post->ID;
    if (get_post_type($post_id) == Hello_Event::EVENT_SLUG) {
      if (is_archive()) {
        // No good since this evolves any filters when getting the excerpt
        // $excerpt = $post->post_excerpt;
        $excerpt = get_the_excerpt($post);
        if (! $excerpt) {
          $excerpt = substr(strip_tags($content), 0, 150) . '...';
        }
        $location_name = $this->event_location_name([]) ? '<b>'.$this->event_location_name([]).'</b>, ' : "";
        $before = "<table class='hello-event-venue'>";
        $before .= "<tr><th>" . __("Start", 'hello-event') . ": </th><td>" . $this->event_start_date_and_time([]) . "</td></tr>";
        $before .= "<tr><th>" . __("Location", 'hello-event') . ": </th><td>" . $location_name . $this->event_location([]) . "</td></tr>";
        $before .= "<tr><td></td></tr>";
        $before .= "</table>";
        $after = "";
        $content = $before . $excerpt . $after;
        
      }
      else {
        $before = "<table class='hello-event-venue'>";
        // Include Start date and time of event if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_autostart_event']) &&
             get_option( 'hello_event')['hello_field_autostart_event']=="on" ) {
          $before .= "<tr><th>" . __("Start", 'hello-event') . ": </th><td>" . $this->event_start_date_and_time([]) . "</td></tr>";
        }
        // Include End date and time of event if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_autoend_event']) &&
             get_option( 'hello_event')['hello_field_autoend_event']=="on" ) {
               $before .= "<tr><th>" . __("End", 'hello-event') . ": </th><td>" . $this->event_end_date_and_time([]) . "</td></tr>";
        }
        // Include location of event if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_autolocation_event']) &&
             get_option( 'hello_event')['hello_field_autolocation_event']=="on" ) {
          $location_name = $this->event_location_name([]) ? '<b>'.$this->event_location_name([]).'</b>, ' : "";
          $before .= "<tr><th>" . __("Location", 'hello-event') . ": </th><td>";
          $before .=  $location_name . $this->event_location([]) . "</td></tr>";
        }
        
        // Include link to tickets if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_autolink_to_product']) &&
             get_option( 'hello_event')['hello_field_autolink_to_product']=="on" ) {
          list($link, $rc) = $this->get_link_to_product($post_id, true);
          if ($rc > 0) {
            $before .= $this->debug_msg(__("Tickets not fully configured", 'hello-event') . ". ".
                                           $this->ticket_config_error($rc));
          }
          if ($link) {
            if ( isset(get_option( 'hello_event')['hello_field_ticket_visibility']) &&
                  get_option( 'hello_event')['hello_field_ticket_visibility']=="on" &&
                  $this->event_end_date(['format' => 'iso']) < date("Y-m-d")) {
              // Don't show any link to the ticket for past event
            }
            else {
              $before .= "<tr><th>" . __("Participate", 'hello-event') . ": </th><td>" . $this->link_to_ticket([]). "</td></tr>";
            }
          }
        }
        
        // Include add-to-calendar button if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_autoical_event']) &&
             get_option( 'hello_event')['hello_field_autoical_event']=="on" ) {
          $addto_calendar = "<tr class='ics'><td></td><td>" . $this->event_ics([]) . "</td></tr>";
          $before .= apply_filters('hello_event_autoinsert_addto_calendar', $addto_calendar);
        }
        $before .= "</table>";
        $before .= $this->get_structured_data($post_id); // Give food for thought to Google
        $before = apply_filters('hello_event_autoinsert_before_content', $before);
        $after = "";
        // Include advice if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_autoadvice_event']) &&
                get_option( 'hello_event')['hello_field_autoadvice_event']=="on" ) {
          $advice = $this->event_advice($post_id);
          if($advice)
            $after .= "<p><i>$advice</i></p>";
        }        
        // Include map if appropriate
        if ( isset(get_option( 'hello_event')['hello_field_automap_event']) &&
                get_option( 'hello_event')['hello_field_automap_event']=="on" ) {
          global $hello_event_map_object;
          $after .= $hello_event_map_object->get_map($post_id);
        }
        $after = apply_filters('hello_event_autoinsert_after', $after);
        $content = $before . $content . $after;
      }
    }
    return $content;
  }
  
  // ================== Filters to make navigation between events follow the real start date ======================
  //
  // Notice: If there are several full-day events on the same day they will currently not show up
  public function get_both_post_join($join, $insame, $excluded, $post) {
    global $wpdb;
    $join = "INNER JOIN $wpdb->postmeta as pm ON pm.post_id = p.ID";
    return $join;
  }
  
  public function get_next_post_where($where, $insame, $excl, $tax, $post) {
    //debug_log("WHERE:".$where);
    //debug_log("TAX:".$tax);
    $post_id = $post->ID;
    $start_date_time = get_post_meta($post_id, 'start_date_time', true);
    $where = "WHERE pm.meta_key = 'start_date_time' AND pm.meta_value > '".$start_date_time."' AND p.post_type = 'hello_event'  AND ( p.post_status = 'publish' OR p.post_status = 'private' )";
    return $where;
  }

  public function get_previous_post_where($where, $insame, $excl, $tax, $post) {
    $post_id = $post->ID;
    $start_date_time = get_post_meta($post_id, 'start_date_time', true);
    $where = "WHERE pm.meta_key = 'start_date_time' AND pm.meta_value < '".$start_date_time."' AND p.post_type = 'hello_event'  AND ( p.post_status = 'publish' OR p.post_status = 'private' )";
    return $where;
  }
  
  public function get_next_post_sort($query, $post, $order) {
    $query = 'ORDER BY pm.meta_value ASC LIMIT 1';
    return $query;
  }

  public function get_previous_post_sort($query, $post, $order) {
    $query = 'ORDER BY pm.meta_value DESC LIMIT 1';
    return $query;
  }
  
  

  // ========================= AJAX ROUTINES ================================================
  public function set_geocode() {
    $post_id = isset($_REQUEST['post_id']) ?  $_REQUEST['post_id'] : false;
    $lat = isset($_REQUEST['lat']) ? $_REQUEST['lat'] : false;
    $lng = isset($_REQUEST['lng']) ? $_REQUEST['lng'] : false;
    // Check validity of values
    if ( get_post_type($post_id) == Hello_Event::EVENT_SLUG ) {
      update_post_meta( $post_id, 'location_lat', $lat );
      update_post_meta( $post_id, 'location_lng', $lng );
    }
    echo "OK";
    wp_die();
  }

  // ------ Display error notice on admin page
  function admin_notice() {
    if ( array_key_exists( 'hello-event-errors', $_GET) ) {
      $msg = '';
      foreach ($_GET['hello-event-errors'] as $error_code) {
        // debug_log("en erreur code=".$error_code);
        switch($error_code) {
          case 'no-event':
            $target = 'product';
            $msg .= "<li>" . __("No event points to the ticket", 'hello-event') . "</li>";
            break;
          case 'ticket-exists':
            $target = 'product';
            $msg .= "<li>" . __("There is already a ticket for the event in the shop", 'hello-event') . '</li>';
            break;
          case 'no-start-date':
            $target = 'event';
            $msg .= "<li>" . __("No start date given. The date has been set to today.", 'hello-event') . '</li>';
            break;
          case 'no-end-date':
            $target = 'event';
            $msg .= "<li>" . __("No end date given. The date has been set to today.", 'hello-event') . '</li>';
            break;
          case 'end-date-earlier-than-start-date':
            $target = 'event';
            $msg .= "<li>" . __("End date is earlier than start date", 'hello-event') . '</li>';
            break;
          default:
            $target = 'event';
            $the_msg = "<li>" . __('Unknown error', 'hello-event') . "</li>";
            // Add hook to allow an add-on to generate the error message
            $the_msg = apply_filters('hello_event_error',  $the_msg, $error_code); 
            $msg .= $the_msg;
            break;
        }
      }
      ?>
        <div class="error">
          <p>
            <?php
            if ($target == 'product')
                echo __("The product was saved but there are error(s)", 'hello-event') . ":<ul>".$msg."</ul>";
            else
              echo __("The event was saved but there are error(s)", 'hello-event') . ":<ul>".$msg."</ul>";
            ?>
          </p>
        </div><?php
    }
  }


  // ---------------------------------------------------------------------------------------------------
  

  
  

} // End of class
global $hello_event_object;
$hello_event_object = new Hello_Event();
register_activation_hook( __FILE__, array($hello_event_object, 'install' ));


?>
