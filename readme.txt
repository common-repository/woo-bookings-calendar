=== WooCommerce Bookings Calendar ===
Contributors: moiseh
Tags: woocommerce, booking, reservation, calendar, availability
Requires at least: 4.8
Tested up to: 6.1.1
Stable tag: 1.0.36
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Provides nice availability calendar shortcode, making the proccess of booking more intuitive and easier to the end user. 

This plugin uses FullCalendar library to renderize the calendar, so it's have great customizability.

To add the availability calendar, just add the `[wbc-calendar]` shortcode in your page.

**Attention: This plugin is an addon for the official [WooCommerce Bookings](https://woocommerce.com/products/woocommerce-bookings/) extension. It just provides the frontend calendar view, not booking functionalities nor booking type related products.**

[youtube https://youtu.be/C2bk_vgCnuM ]

Premium version features:

* Allow to define product-specific colors [view demo](https://youtu.be/EVNYlV_1Cbk)
* Allow to add availability calendar as widget using drag and drop
* Allow to filter by specified product and categories in plugin settings and shortcode
* Open bookings directly in AJAX modal and display tooltip on event mouseover [view demo](https://youtu.be/EFUXdYNpZU8)
* Reverse calendar logic to display only taken slots
* Perform calendar search using AJAX without page reload [view demo](https://youtu.be/XRhR-YzI9UI)
* Support for WooCommerce Booking Accommodations

== Screenshots ==

1. The default calendar view using `[wbc-calendar]` shortcode in page

== Installation ==

1. Upload `woo-bookings-calendar.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. It's done. Now you can go to the Settings to configure what you want

== Changelog ==

= 1.0.36 =
* Fixed duplicated events when option "Reverse calendar logic to display only taken slots" enabled
* Added `High-Performance order storage` compatibility
* Added check to respect the minimum booking time when display events on calendar

= 1.0.35 =
* Fixed overlapping events display bug

= 1.0.34 =
* Added a quick prevention to avoid show duplicated events for edge cases
* Fixed plugin domain for translation of "Search events" string
* Added HTML5 close button to Search events field
* Fixed calendar week, month, day labels locale

= 1.0.33 =
* Added configuration to define the first day of the week
* Added new calendar shortcode attribute: startdate="Y-m-d"

= 1.0.32 =
* Adding support for new booking date ranges configuration (days of week)

= 1.0.31 =
* Fixed PHP code warnings and notices
* Tested with WooCommerce 6.1.1 and WordPress 5.9

= 1.0.30 =
* Tested with WooCommerce 5.6.0 and WordPress 5.8

= 1.0.29 =
* Updating WooCommerce tested up tag to 5.2.2
* Fixed bug on backend that not showing events on certain conditions
* Respecting the correct minimum and maximum future booking date limits
* Compatibilize with Private Google Calendars plugin to show calendar correctly

= 1.0.28 =
* Preventing JS error when blockUI loading library not available

= 1.0.27 =
* Tested with WordPress version 5.5.1
* Added wbc_shortcode_html filter when needed to append or transform shortcode HTML
* Fix calendar display for Tabs widget

= 1.0.26 =
* Refactoring code for better maintainability
* Removing firstDay parameter in FullCalendar to display correct in Week view mode

= 1.0.25 =
* Removing unwanted admin notices
* Updated WooCommerce tested tag

= 1.0.24 =
* Added new configuration: Disable back button to the past time
* Added wbc_fullcalendar_options hook to add extra FullCalendar settings
* Added JS hook wbc_calendar_loaded after FullCalendar was constructed

= 1.0.23 =
* Fixed translation textdomain loader

= 1.0.22 =
* Alert user on admin when some required extension does not enabled
* Tested with latest WordPress and WooCommerce versions
* Added wbc_calendar_start_date and wbc_calendar_class filter hooks

= 1.0.21 =
* Removing deprecated PHP short_open_tag blocks
* Fixed incorrect translation words
* Added default-view optional parameter in shortcode

= 1.0.20 =
* Reversed changelog ordering to make it more standard
* Changed plugin notices to respect the guidelines
* Added new config option: Fixed calendar start date

= 1.0.19 =
* Added compatibility with Woo Ultimate PRO
* Ignoring timezone conversion when opening booking page link

= 1.0.18 =
* Fixed bug that not checking availability when booking not have resources

= 1.0.17 =
* Added compatibility support with WooCommerce 4.0.1

= 1.0.16 =
* Fixed incorrect calls to get booking resources
* Added action wbc_after_shortcode_script_queue
* Added filter wbc_always_get_availables

= 1.0.15 =
* Added option to attach calendar in single booking page

= 1.0.14 =
* Added support for other calendar languages

= 1.0.13 =
* Simplified calendar view config in admin frontend
* Updated WC tested up to tag

= 1.0.12 =
* Allow to define display colors for available and not available items

= 1.0.11 =
* Added new config to define the behavior when item is not bookable

= 1.0.10 =
* Better loading style while update calendar
* Calendar events filter box option
* Time format configuration
* Display full event title on mouse over
* Removing redundant time information when event is full day slot

== Frequently Asked Questions ==

= Why my calendar events taking so much time to load? =

When it happens the most common issue is the user is viewing calendar in Month mode, and bookable products are divided by small blocks of days, hours or minutes. This cause heavy CPU load to calculate events availability, and the solution is to set higher Booking duration blocks, or/and limit the calendar to display only in Day or/and Week mode.

== Upgrade Notice ==
