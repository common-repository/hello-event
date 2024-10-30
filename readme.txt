=== Hello Event ===
Contributors: christer_f
Tags: events, calendar, tickets, woocommerce, ical, openstreetmap, google map
Requires at least: 4.6
Tested up to: 6.6.1
Stable tag: master
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage events and sell tickets through WooCommerce in an extremely easy way. Just drop in the plugin and you're ready to go. Calendar, lists & iCal

== Description ==

Hello Event adds event management and ticket sales in an extremely easy way to any WordPress site!  Just drop in the plugin and you're ready to go. Welcome to the "Hello World" for events!

With this plugin you can easily create and manage events (like concerts, training camps or competitions). The events you create have fields for location, start and end dates (and optionally times for events that are not full-day events) and can be presented in different formats, both in lists and in calendars.

If you have installed WooCommerce you will be able to generate and sell tickets with a simple click. On the event page a link will point to the tickets in WooCommerce and in WooCommerce there will be a backlink from the ticket to the event. And in the backend dashboard you will immediately see how ticket sales progress for each event

Hello Event supports different date and time formats and is of course multi-lingual and currently comes with translations for English, French and Swedish. More languages will be added, and if you want to add other languages yourself, you can easily do that for example with the help of the Loco plugin.

Hello Event is iCal compatible and the event pages will display an "Add to calendar button" to allow users to insert the event into their calendars. The plugin can make use of external services to present the locations of events. For this purpose either Google Maps or OpenStreetMap may be used.

Although the plugin is perfect for use by the non-technical site creator, it is also developer-friendly with shortcodes, hooks and all styling provided by CSS that can be overridden. To get you going immediately the plugin will automatically show event-related information when a post of the "hello_event" custom type is presented. For more control, you can switch off the automatic insertion of this information and use the shortcodes provided wherever you want.

To enhance the organic SEO of your site, all pages that present events provide Structured Data descriptions of the event - see [Google documentation on Structured Data](https://developers.google.com/search/docs/guides/intro-structured-data).

With Hello Event you can now use WooCommerce and all its different payment gateways to handle the ticket sales! Easy peasy!

<a href="https://hello.tekomatik.com" target="_blank">SEE DEMO</a>.


== Installation ==

Nothing special. Just like any other plugin. If you want to sell tickets you also need to have Woocommerce installed.

== Frequently Asked Questions ==

= I already have another plugin to handle some events on my site but would like to try Hello Event. What will happen now? =

No problem.
Hello Event creates its own custom post type (called "hello_event"), which will not conflict with other custom post types for events. You can even change Hello Event's default naming of events in the backend, "Events",  to any other name so as not to confuse the backend users.

= Once I have created some events, how are they presented on my site? =

To see the events defined on the front-end of your site you can either use the standard WordPress archive facility by navigating to SITEADDRESS/hello_event, or - much better - create a dedicated page for this and use the shortcodes provided by the plugin to display calendars and/or lists of events. Please consult the plugin documentation for a detailed example of how to do this.

= Can I use other means than WooCommerce to sell tickets? =

No, this is currently not supported by the plugin.

= Can I sell tickets with different price categories to an event? =

Of course! Please consult the plugin documentation for a detailed example of how to do this.

= Where can I find more documentation? =

Documentation is available at the plugin page: (https://www.tekomatik.com/plugins/hello-event)

= Where can I find a demo site? =

A basic demo site that shows the plugin in action is available here: <a href="https://hello.tekomatik.com" target="_blank">https://hello.tekomatik.com"</a>.

== Screenshots ==

1. Editing an event in the backend. Tickets not yet on sale
2. Editing the same event after creating a ticket for sale in WooCommerce. Clicking the link takes you to the ticket in WooCommerce, where you can set the price, the number of available tickets, etc.
3. List of events in the backend. Tickets on sale for the first three events.
4. Calendar of events. When clicking an event in the calendar, a modal provides information about the event.
5. Tabbed list of events.


== Changelog ==
= 1.3.17
* Tested up to Wordpress version 6.6.1
* Minor bug fix
= 1.3.16
* New shortcode: hello-thumbnail-url, which does not include the IMG tag
* Added the property "Location Name" to the event
* Added shortcode hello-location-name to extract the Location Name
* Added shortcode hello-event-location-address as a synonym to hello-event-location
* Added the property "Advice" to the event
* Added shortcode hello-advice to extract the Advice
* Added a setting for automatical insertion of the Advice
* Modified the default presentation of events to include the Location Name and Advice
* Add end-time in the event list
* Accept slug in the shortcode hello-default-event
= 1.3.15
* Event custom page enabled also for calendar and tabbed display
* Minor fix in the width of thumbnails returned by the hello-thumbnail shortcode
* Text parameter to the shortcode hello-event-ics
* Text to book ticket in event list is localized
= 1.3.14
* Added possibility to display the event using a custom page. This also means there are several new shortcodes
* More debug info when debug setting is switched on in the settings
* Added Dutch language - courtesy of Wim De Kelver
* Removed language parameter from shortcodes. This parameter only impacted how dates are shown, but this is already handled in the plugin settings
* Bug correction: when settings are positioned to use other date format than ISO (yyyy-mm-dd) the test to show the link to an event ticket only for future events failed. Has been corrected
= 1.3.13
* Better info about Demo site on Wordpress.org
= 1.3.12
* Fixed deprecation warning from PHP 8.1.27
= 1.3.11
* Tickets to past events can automatically be set to not visible so they don't appear in the shop
* The calendar view of events can be set to show monthly, weekly or daily calendars as controlled by shotcode parameters
* Update of the Javascript Fullcalendar to version 6.1.5
= 1.3.10
* Added thumbnail parameter to the shortcode 'hello-event-list' making it possible to force the thumbails to be square or/and round
= 1.3.9
* Fixed the translation of excerpts generated from the description text.
= 1.3.8 =
* Better generation of excerpts, including the possibility to support event categories (these are not defined in this plugin)
* Made some functions public so they can be called from other plugins, in particular the new Hello Event Again plugin
* Minor bug fixes
= 1.3.7 =
* A new setting makes it possible to update the title of the ticket to the event's title every time the event is saved
* Bug correction: Unless a price has been set for a product linked to an event, a new product was created at every save of the event. This has been fixed by modifying the internal functions get_id_of_product and get_link_to_product with additional optional parameter $must_have_price

= 1.3.6 =
* Admins and event owners can see the ticket sales status when viewing the event on the front-end. However only orders with
status 'wc-completed' were included in the list. Now we also include orders with status 'wc-processing'

= 1.3.5 =
* The fact that a Woocommerce product is a ticket to an event which was not correctly detected in some situations is
now fixed.

= 1.3.4 =
* Added parameters to the hello-event-map shortcode to allow more control (show the address, show links to open Google Maps or Open Street Maps on the location, show or not the map)

= 1.3.3 =
* Both the start date and the end date should be defined for avants, but if end date is not defined we use the start date as a substitute to avoid PHP warnings (but such events will still show up in lists and tabs with past events)

= 1.3.2 =
* Added optional parameter to list events shortcode to allow "book now" button directly in the event list

= 1.3.1 =
* Fix: Woocommerce product category for event tickets were sometimes created in double
* Added filters to allow other plugins to modify presentations in the calendar
* Some functions have been made public 
* Added a 'min' style for presenting events in a list. Other styles tidied up

= 1.2.3 ==
* End date column in admin is also set to be sortable
* Excerpts no longer automatically generated from the content

= 1.2.2 =
* In the previous version we forced the product status to be "pending" when saving a product of type hello-event. This could cause issues in certain use cases, so this feature has now been removed
* We do not apply the htmlentities function to the title and the location of events before sending the to the calendar, since this will always transform all accented characters to htmlentities.

= 1.2.1 =
* Dates better displayed for multi-day events in hello-event-list shortcode

= 1.2 =
* hello-event-tabs shows upcoming events to the left and earlier events to the right. And the upcoming events tab is selected by default

= 1.1 =
* Fixed bug that sometimes generated a warning in the payment section on the Woocommerce checkout page
* Added 'widget' to the parameters of the hello-event-list shortcode for better display of full information in sidebar widget
* Inluded missing background images for JqueryUI
* When presenting events using the 'hello-event-list' shortcode, it should be the end-date not the start-date that determines when an event is in the future or the past
* The admin CSS file sometimes impacted other admin pages than those of this plugin
* Moved the get_ics handler from the pre_get_posts hook to the template_redirect hook, since depending on the installation, the former comes too early and creates errors
* Default number of event to show in list of events was 4 is now set to -1, menaing unlimited
* Image size in widgets set to  300 x 300 pixels (soft crop)
= 1.0 =
* First public version.

== Upgrade Notice ==

= 1.0 =
First version



