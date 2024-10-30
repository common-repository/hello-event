<?php
/*
* Settings admin panel for the Hello Event plugin
* 
*
*/

namespace Tekomatik\HelloEvent;

class Hello_Event_Settings {
  
  public function __construct() {
    add_action('admin_menu', array($this, 'options_page'));
    add_action( 'admin_init', array($this, 'settings_init'));
    add_action( 'init', array($this, 'set_defaults'));
    add_action( 'admin_init', array($this, 'set_defaults'));
  }


  function options_page() {
    global $hello_event_object;
    add_options_page(
      Hello_Event::PLUGIN_NAME,
      Hello_Event::PLUGIN_NAME,
      'manage_options',
      Hello_Event::PLUGIN_NAME.'_options',
      array($this, 'options_page_html')
    );
  }
  
  
  function settings_init(){
    // register a new setting for "hello_event" page
    register_setting( 'hello_event', 'hello_event' );
 
    // -------- Register Sections ---------------------------------------------------------------------------
    add_settings_section(
      'hello_section_1',                                // Section slug
      __( 'Date and Time formats', 'hello-event' ),     // Section title
      array($this, 'hello_section_1_intro'),            // Callback that will ECHO the section intro text
      'hello_event'                                     // page
    );
 
    add_settings_section(
      'hello_section_2',
      __( 'Naming events', 'hello-event' ),
      array($this, 'hello_section_2_intro'),
      'hello_event'
    );
 
    add_settings_section(
      'hello_section_3',
      __( 'Debugging', 'hello-event' ),
      array($this, 'hello_section_3_intro'),
      'hello_event'
    );
 
    add_settings_section(
      'hello_section_4',
      __( 'Calendar', 'hello-event' ),
      array($this, 'hello_section_4_intro'),
      'hello_event'
    );
 
    add_settings_section(
      'hello_section_5',
      __("Maps", 'hello-event'),
      array($this, 'hello_section_5_intro'),
      'hello_event'
    );
 
    // New section for custom event page
    add_settings_section(
      'hello_section_6a',
      __('Custom event page', 'hello-event'),
      array($this, 'hello_section_6a_intro'),
      'hello_event'
    );
 
    add_settings_section(
      'hello_section_6',
      __('Auto inserted content', 'hello-event'),
      array($this, 'hello_section_6_intro'),
      'hello_event'
    );
 
    add_settings_section(
      'hello_section_7',
      __('Keep tickets in sync with events', 'hello-event'),
      array($this, 'hello_section_7_intro'),
      'hello_event'
    );
 
    // -------- Register Fields in the Sections ----------------------------------------------------------------
    //
    // register field for setting the dateformat
    add_settings_field(
      'hello_field_dateformat',                       // Field slug
      __( 'Date format', 'hello-event' ),             // Title
      array($this, 'hello_field_dateformat_cb'),      // Callback to fill the field
      'hello_event',                                  // page
      'hello_section_1',                              // section
      [                                               // Arguments passed to the callback function
        'label_for' => 'hello_field_dateformat',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    // register field for setting the weekday 
    add_settings_field(
      'hello_field_weekday',                    // Field slug
      __( 'Weekday', 'hello-event' ),                // Title
      array($this, 'hello_field_weekday_cb'),      // Callback to fill the field
      'hello_event',                                  // page
      'hello_section_1',                              // section
      [                                               // Arguments passed to the callback function
        'label_for' => 'hello_field_weekday',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    // register field for setting the time
    add_settings_field(
      'hello_field_timeformat',
      __( 'Time format', 'hello-event' ),
      array($this, 'hello_field_timeformat_cb'),
      'hello_event',
      'hello_section_1',
      [
        'label_for' => 'hello_field_timeformat',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    // register field for defining the external singular name of the Events
    add_settings_field(
      'hello_field_eventname_singular',
      __( 'Name of event (singular)', 'hello-event' ),
      array($this, 'hello_field_eventname_cb'),
      'hello_event',
      'hello_section_2',
      [
        'label_for' => 'hello_field_eventname_singular',
        'class' => 'hello_row',
        'hello_custom_data' => 'singular',
      ]
    );

    // register field for defining the external plural name of the Events
    add_settings_field(
      'hello_field_eventname_plural',
      __( 'Name of event (plural)', 'hello-event' ),
      array($this, 'hello_field_eventname_cb'),
      'hello_event',
      'hello_section_2',
      [
        'label_for' => 'hello_field_eventname_plural',
        'class' => 'hello_row',
        'hello_custom_data' => 'plural',
      ]
    );

    // register field for switching on/off debug output from shortcodes
    add_settings_field(
      'hello_field_debug',
      __( 'Debug', 'hello-event' ),
      array($this, 'hello_field_debug_cb'),
      'hello_event',
      'hello_section_3',
      [
        'label_for' => 'hello_field_debug',
        'class' => 'hello_row',
        'hello_custom_data' => 'debug',
      ]
    );
    
    add_settings_field(
      'hello_field_modal',                            // Field slug
      __( 'Choose behaviour', 'hello-event' ),        // Title
      array($this, 'hello_field_modals_cb'),          // Callback to fill the field
      'hello_event',                                  // page
      'hello_section_4',                              // section
      [                                               // Arguments passed to the callback function
        'label_for' => 'hello_field_modal',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );
    
    add_settings_field(
      'hello_field_select_map_api',
      __( 'Select map service', 'hello-event' ),
      array($this, 'hello_field_select_map_api_cb'),
      'hello_event',
      'hello_section_5',
      [
        'label_for' => 'hello_field_select_map_api',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );
    add_settings_field(
      'hello_field_google_maps',
      __( 'Maps API Key', 'hello-event' ),
      array($this, 'hello_field_google_maps_cb'),
      'hello_event',
      'hello_section_5',
      [
        'label_for' => 'hello_field_google_maps',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    // New field for custom event page
    add_settings_field(
      'hello_field_select_map_api',
      __( 'Slug for custom event page', 'hello-event' ),
      array($this, 'hello_field_custom_event_page_cb'),
      'hello_event',
      'hello_section_6a',
      [
        'label_for' => 'hello_field_custom_event_page',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'hello_field_autostart_event',                // Field slug
      __( 'Start date and time of event', 'hello-event' ),// Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_autostart_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'hello_field_autoend_event',                // Field slug
      __( 'End date and time of event', 'hello-event' ),// Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_autoend_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'hello_field_autolocation_event',                 // Field slug
      __( 'Location of event', 'hello-event' ), // Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_autolocation_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    
    add_settings_field(
      'hello_field_autoical_event',                // Field slug
      __( 'Add-to-calendar button', 'hello-event' ),// Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_autoical_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'hello_field_automap_event',                      // Field slug
      __( 'Location map', 'hello-event' ),              // Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_automap_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'hello_field_autoadvice_event',                    // Field slug
      __( 'Advice', 'hello-event' ),                    // Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_autoadvice_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );

    add_settings_field(
      'hello_field_autolink_to_product',                // Field slug
      __( 'Link from event to ticket', 'hello-event' ),// Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                    // page
      'hello_section_6',                                // section
      [                                                 // Arguments passed to the callback function
        'label_for' => 'hello_field_autolink_to_product',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );
    add_settings_field(
      'hello_field_autolink_to_event',                    // Field slug
      __( 'Link from ticket to event', 'hello-event' ),  // Title
      array($this, 'hello_field_autocontent'),          // Callback to fill the field
      'hello_event',                                  // page
      'hello_section_6',                              // section
      [                                               // Arguments passed to the callback function
        'label_for' => 'hello_field_autolink_to_event',
        'class' => 'hello_row',
        'hello_custom_data' => 'custom',
      ]
    );
  
  // register field for switching on/off keeping tickets in sync
  add_settings_field(
    'hello_field_sync_ticket_title',                                                // Field slug
    __( 'Update the ticket title when the event title is changed', 'hello-event' ), // Title
    array($this, 'hello_field_sync_ticket_title_and_visibility'),                   // Callback to fill the field
    'hello_event',                                                                  // page
    'hello_section_7',                                                              // section
    [                                                                               // Arguments passed to the callback function
      'label_for' => 'hello_field_sync_ticket_title',
      'class' => 'hello_row',
      'hello_custom_data' => 'custom',
    ]
  );
  add_settings_field(
    'hello_field_ticket_visibility',                                                // Field slug
    __( 'Hide ticket from shop unless the event is in the future', 'hello-event' ), // Title
    array($this, 'hello_field_sync_ticket_title_and_visibility'),                   // Callback to fill the field
    'hello_event',                                                                  // page
    'hello_section_7',                                                              // section
    [                                                                               // Arguments passed to the callback function
      'label_for' => 'hello_field_ticket_visibility',
      'class' => 'hello_row',
      'hello_custom_data' => 'custom',
    ]
  );
  
}
  
  
  // -------- Set all settings to their defaults -------------------------------------------------------
  function set_defaults() {
    if (!get_option('hello_event')) {
      $options = array(
        'hello_field_dateformat' => 'iso',
        'hello_field_weekday' => 'no',
        'hello_field_timeformat' => '24h',
        'hello_field_debug' => 'on',
        'hello_field_modal' => 'jquery',
        'hello_field_select_map_api' => 'openstreetmap',
        'hello_field_autostart_event' => 'on',
        'hello_field_autoend_event' => 'on',
        'hello_field_autolocation_event' => 'on',
        'hello_field_autoical_event' => 'on',
        'hello_field_automap_event' => 'on',
        'hello_field_autoadvice_event' => 'on',
        'hello_field_autolink_to_product' => 'on',
        'hello_field_autolink_to_event' => 'on',
      );
      update_option('hello_event', $options);
    }
  }

  // -------- The options page -------------------------------------------------------------------------
  //
  function options_page_html() {
      if (!current_user_can('manage_options')) { return; }
      ?>
      <div class="wrap hello_event-wrap">
        <div class="intro-block">
          <h1><?= esc_html(get_admin_page_title()); ?></h1>
          <p>
            <?= __("A plugin for managing events and sell tickets with Woocommerce", 'hello-event') ?>.
          </p>
          <p>
            <?= __("Documentation: ", 'hello-event') ?>
            <a href="https://tekomatik.com/en/plugins/hello-event/" target="_blank">https://tekomatik.com/en/plugins/hello-event/</a>
          </p>
        </div>
          <?php
          // show error/update messages
          settings_errors( 'wporg_messages' );
          ?>
          <form action="options.php" method="post">
              <?php
              // output security fields for the registered setting "hello_event"
              settings_fields('hello_event');
              // output setting sections and their fields
              // (sections are registered for "hello_event"
              do_settings_sections('hello_event');
              // output save settings button
              submit_button(__('Save Settings', 'hello-event'));
              ?>
          </form>
      </div>
      <?php
  }
  
  // -------- Intro text for the sections  ----------------------------------------------------------------
  //
  function hello_section_1_intro( $args ) {
    // Here we can echo a section introduction text
  }
  
  function hello_section_2_intro( $args ) {
    _e( 'Here you can change the name for the event custom type that you see on the admin pages.', 'hello-event' );
    echo "<br/>";
    _e( 'Default is "Event" in singular and "Events" in plural.', 'hello-event' );
    echo(" ");
    _e( 'Leave blank to keep the defaults.', 'hello-event' );
  }

  function hello_section_3_intro( $args ) {
  }

  function hello_section_4_intro( $args ) {
    _e("Select what should happen when a user clicks on an event in the calendar", 'hello-event');
  }
  
  function hello_section_5_intro( $args ) {
    $html = '<p>';
    $html .= __("It is possible to use either Google Maps or OpenStreetMap to display the locations of events.", 'hello-event').' ';
    $html .= __("OpenStreetMap is a community-based free service, which is sufficient in most cases.", 'hello-event').' ';
    $html .= __("With Google Maps comes many additional features (street view, driving directions, ...), but in order to use Google maps you need to obtain a Google Maps API Key.", 'hello-event') . ' ';
    $html .= __("As of July 2018 you will be able to do 28500 requests per month free of charge,", 'hello-event') . ' ';
    $html .= __("but please notice that the pricing plans are quite complex and you should consult Google's information about the details.", 'hello-event').' ';
    $html .= __("If you choose to use Google Maps, no maps will be displayed on the event pages until you have obtained the key and registered it here.", 'hello-event') . '</p>';
    $html .= '<p>'.__("Please notice that the maps presented by either of the map services contain links with credits and contact information to the service provider.", 'hello-event').'</p>';
    echo $html;
  }
    
  function hello_section_6a_intro( $args ) {
    $html = "";
    $html .= "<p>" . __("It is possible to create a custom page used to display the events.", 'hello-event') . "<br/>";
    $html .= __("If no custom page is defined, events will be displayed using the standard page template of the theme, and in the settings below", 'hello-event') .", <i>". __("Auto inserted content", "hello-event") .",</i> " . __("you can decide what information should be automatically inserted", "hello-event") . "<br/>"; 
    $html .= __("When using a custom event page you must use shortcodes to show the event information.", 'hello-event') . "</p>";
    echo $html;
  }
  
  function hello_section_6_intro( $args ) {
    $html = "";
    $html .= "<p>" . __("Choose what should be AUTOMAICALLY inserted into the content of events and tickets.", 'hello-event') . "<br/>";
    $html .= __("Any items not selected can always be inserted into the content parts of events and tickets using shortcodes.", 'hello-event') . "</p>"; 
    $html .= __("Please notice that a link from an event to a ticket will only appear when the sale of tickets for the event is active.", 'hello-event') . "</p>";
    echo $html;
  }
  
  function hello_section_7_intro( $args ) {
    $html = "";
    $html .= __("Here you can decide", 'hello_event') . " :<ul>";
    $html .= "<li> - " . __("if the ticket title should be automatically updated to the event title when the event is saved.", 'hello-event' ) . "</li>";
    $html .= "<li> - " . __("if tickets to past events and tickets to events that have been deleted should be set to hidden to prevent them from being shown in the shop", 'hello-event' ) . "</li>";
    $html .= "</ul>";
    echo $html;
  }
  
  
  // -------- Controls in the sections  ----------------------------------------------------------------
  //
  function hello_field_dateformat_cb($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="iso" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'iso', false ) ) : ( '' ); ?>>
              YYYY-mm-dd
        </option>
        <option value="us" <?php echo isset( $options[ $args['label_for'] ] ) ?
            ( selected( $options[ $args['label_for'] ], 'us', false ) ) : ( '' ); ?>>
            mm/dd/YY
        </option>
        <option value="fr" <?php echo isset( $options[ $args['label_for'] ] ) ?
            ( selected( $options[ $args['label_for'] ], 'fr', false ) ) : ( '' ); ?>>
            dd/mm/YYYY
        </option>
    </select>
    <p class="description">
    <?php _e( 'Select the dateformat used in datepickers and displays.', 'hello-event' ); ?><br/>
    <?php _e( 'Internally all dates are stored in the ISO-format (YYYY-mm-dd).', 'hello-event' ); ?>
    </p>
    <?php
  }

  function hello_field_weekday_cb($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="no" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'no', false ) ) : ( '' ); ?>>
              <?php _e("don't show", 'hello-event'); ?>
        </option>
        <option value="yes" <?php echo isset( $options[ $args['label_for'] ] ) ?
            ( selected( $options[ $args['label_for'] ], 'yes', false ) ) : ( '' ); ?>>
            <?php _e("show", 'hello-event'); ?>
        </option>

    </select>
    <p class="description">
    <?php _e( 'Should the weekday name (eg. Monday) be shown in front of the date', 'hello-event' ); ?>.<br/>
    <?php _e( 'If shown it will be localized into the selected language of the site', 'hello-event' ); ?>.
    </p>
    <?php
  }

  function hello_field_timeformat_cb($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="24h" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], '24h', false ) ) : ( '' ); ?>>
              24h
        </option>
        <option value="am-pm" <?php echo isset( $options[ $args['label_for'] ] ) ?
            ( selected( $options[ $args['label_for'] ], 'am-pm', false ) ) : ( '' ); ?>>
            AM / PM
        </option>
    </select>
    <?php
  }

  function hello_field_eventname_cb($args) {
    $options = get_option( 'hello_event' );
    //print_r($options);
    ?>
    <input type="text"
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
        name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]"
        value="<?php echo isset($options[ $args['label_for'] ]) ? $options[ $args['label_for'] ] : '' ; ?>"/>    
    <?php
  }
  
  function hello_field_debug_cb($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="on" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'on', false ) ) : ( '' ); ?>>
              <?php _e('on', 'hello-event'); ?>
        </option>
        <option value="off" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for']], 'off', false ) ) : ( '' ); ?>>
              <?php _e('off', 'hello-event'); ?>
        </option>
    </select>
    <p class="description">
    <?php _e( 'When switched on admins and editors will see debug information', 'hello-event' ); ?>
    <?php _e( 'generated by the shortcodes in case of problems', 'hello-event' ); ?>
    </p>
    <?php
  }
  
  
  function hello_field_modals_cb($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="none" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'none', false ) ) : ( '' ); ?>>
              <?php _e("Go to the event page", 'hello-event'); ?>
        </option>
        <option value="boot" <?php echo isset( $options[ $args['label_for'] ] ) ?
            ( selected( $options[ $args['label_for'] ], 'boot', false ) ) : ( '' ); ?>>
            <?php _e("Show event summary in a Bootstrap modal", 'hello-event'); ?>
        </option>
        <option value="jquery" <?php echo isset( $options[ $args['label_for'] ] ) ?
            ( selected( $options[ $args['label_for'] ], 'jquery', false ) ) : ( '' ); ?>>
            <?php _e("Show event summary in a jQuery UI modal", 'hello-event'); ?>
        </option>
    </select>
    <p class="description">
    <?php _e( 'If you want to use Bootstrap modals your theme must be built with Bootstrap', 'hello-event' ); ?><br/>
    <?php _e( 'Please notice that modals may not work properly with older versions of Bootstrap', 'hello-event' ); ?><br/>
  </p>
    <?php
  }
  
  function hello_field_autocontent($args) {
    $options = get_option( 'hello_event' );
    //print_r($options);
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="on" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'on', false ) ) : ( '' ); ?>>
              <?php _e('on', 'hello-event'); ?>
        </option>
        <option value="off" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for']], 'off', false ) ) : ( '' ); ?>>
              <?php _e('off', 'hello-event'); ?>
        </option>
    </select>

    <?php
  }
  
  function hello_field_select_map_api_cb($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="openstreetmap" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'openstreetmap', false ) ) : ( '' ); ?>>
              OpenStreetMap
        </option>
        <option value="googlemap" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for']], 'googlemap', false ) ) : ( '' ); ?>>
              Google Map
        </option>
    </select>
    <?php
  }
  
  
  function hello_field_google_maps_cb($args) {
    $options = get_option( 'hello_event' );
    //print_r($options);
    ?>
    <input type="text"
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
        name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]"
        value="<?php echo isset($options[ $args['label_for'] ]) ? $options[ $args['label_for'] ] : '' ; ?>"/>
    <?php
    $html = '<p class="description">' . __("To obtain the API Key and read Google's explanation go to", 'hello-event') . ' ';
    $html .= '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">';
    $html .= __('this page', 'hello-event') . '</a></p>';
    echo $html;
  }
  
  function hello_field_custom_event_page_cb($args) {
    $options = get_option( 'hello_event' );
    //print_r($options);
    ?>
    <input type="text"
        id="<?php echo esc_attr( $args['label_for'] ); ?>"
        data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
        name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]"
        value="<?php echo isset($options[ $args['label_for'] ]) ? $options[ $args['label_for'] ] : '' ; ?>"/>
    <?php
    $html = '<p class="description">' . __("Fill in the slug of the custom event page, or leave blank for default.", 'hello-event') . '<br/>';
    $html .=  __("If the page does not exist the default will be used.", 'hello-event') . '</p>';
    echo $html;
    
  }
  
  function hello_field_sync_ticket_title_and_visibility($args) {
    $options = get_option( 'hello_event' );
    ?>
    <select id="<?php echo esc_attr( $args['label_for'] ); ?>"
      data-custom="<?php echo esc_attr( $args['hello_custom_data'] ); ?>"
      name="hello_event[<?php echo esc_attr( $args['label_for'] ); ?>]">
        <option value="on" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for'] ], 'on', false ) ) : ( '' ); ?>>
              <?php _e('on', 'hello-event'); ?>
        </option>
        <option value="off" <?php echo isset( $options[ $args['label_for'] ] ) ?
              ( selected( $options[ $args['label_for']], 'off', false ) ) : ( '' ); ?>>
              <?php _e('off', 'hello-event'); ?>
        </option>
    </select>
    <?php    
  }
  
  
  

} // End of class
global $hello_event_settings_object;
$hello_event_settings_object = new Hello_Event_Settings;
?>
