<?php
/*
Plugin Name: WooCommerce Bookings Calendar
Plugin URI: https://wordpress.org/plugins/woo-bookings-calendar
Description: Availability Calendar Shortcode for WooCommerce Bookings Extension
Version: 1.0.36
Author: Moises Heberle
Author URI: https://pluggablesoft.com/contact/
Text Domain: woo-bookings-calendar
Domain Path: /i18n/languages/
WC requires at least: 3.2
WC tested up to: 5.9.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

defined('WBC_BASE_FILE') || define('WBC_BASE_FILE', __FILE__);

add_action('init', 'wbc_init');
add_action('plugins_loaded', 'wbc_plugins_loaded', 10 );
add_filter('mh_wbc_settings', 'wbc_settings');
add_filter('mh_wbc_premium_url', 'wbc_premium_url');
add_action('wp_enqueue_scripts', 'wbc_enqueue_assets');
add_action('woocommerce_after_single_product_summary', 'wbc_after_single_product_summary');

add_shortcode('wbc-calendar', 'wbc_shortcode_calendar');

add_action('wp_ajax_nopriv_wbc_events_load', 'wbc_events_load');
add_action('wp_ajax_wbc_events_load', 'wbc_events_load');

if ( !function_exists('wbc_init') ) {
    add_action( 'before_woocommerce_init', function() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );

    function wbc_init() {
        // load Common library
        include_once( 'common/MHCommon.php' );
        MHCommon::initializeV2(
            'woo-bookings-calendar',
            'wbc',
            WBC_BASE_FILE,
            __('WooCommerce Bookings Calendar', 'woo-bookings-calendar')
        );

        /*
        if ( !class_exists('WC_Bookings') ) {
            $bookingsUrl = 'https://woocommerce.com/products/woocommerce-bookings/';
            $message = sprintf('Apparently you miss to enable this required extension: <a href="%s" target="_blank">WooCommerce Bookings</a>. Please fix the issue.', $bookingsUrl);
            $common = MHCommon::getInstance();
            $notice = $common->getNotice();
            $notice->addTempNotice('error', $message);
        }

        if ( !class_exists('WooCommerce') ) {
            $wooUrl = 'https://wordpress.org/plugins/woocommerce/';
            $message = sprintf('Apparently you miss to enable this required plugin: <a href="%s" target="_blank">WooCommerce</a>. Please fix the issue.', $wooUrl);
            $common = MHCommon::getInstance();
            $notice = $common->getNotice();
            $notice->addTempNotice('error', $message);
        }
        */
        
        // debug for developers only
        if ( !empty($_GET['debug']) ) {
            wbc_debug();
        }

        // allow to use the shortcodes in widgets, in this case [wbc-calendar]
        if ( !has_filter('widget_text', 'do_shortcode') ) {
            add_filter('widget_text', 'do_shortcode');
        }
    }

    function wbc_plugins_loaded() {
        load_plugin_textdomain( 'woo-bookings-calendar', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
    }
    
    function wbc_premium_url() {
        return 'http://gum.co/woocalendar';
    }

    function wbc_settings($options) {

        if ( !class_exists('WBC_Settings') ) {
            require_once __DIR__ . '/includes/WBC_Settings.php';
        }
        
        $settings = new WBC_Settings();
        $options += $settings->buildSettingsArray();

        return $options;
    }

    function wbc_option($name) {
        return apply_filters('mh_wbc_setting_value', $name);
    }

    function wbc_check_shortcode_inuse() {
        global $post;

        if ( !empty($post->post_content) && has_shortcode($post->post_content, 'wbc-calendar') ) {
            return true;
        }

        return false;
    }

    function wbc_enqueue_assets() {
        global $post;

        // when viewing page with pre selected calendar event
        if ( !empty($_GET['wbc_cal_start']) && !empty($_GET['wbc_cal_end']) ) {
            $start = sanitize_text_field($_GET['wbc_cal_start']);
            $end = sanitize_text_field($_GET['wbc_cal_end']);

            $vars = array(
                'productId' => $post->ID,
                'isSingleProduct' => true,
                'bookingDateTime' => date('H:i', $start),
                'bookingDateDay' => date('d', $start),
                'bookingDateMonth' => date('m', $start),
                'bookingDateYear' => date('Y', $start),
                'bookingEndDay' => date('d', $end),
                'bookingEndMonth' => date('m', $end),
                'bookingEndYear' => date('Y', $end),
            );

            wbc_enqueue_fullcalendar();
            wbc_enqueue_scripts($vars);
        }
    }

    function wbc_enqueue_fullcalendar() {
        $fcAssetAlias = wbc_fullcalendar_asset_alias();

        wp_enqueue_style($fcAssetAlias, plugins_url('assets/fullcalendar/fullcalendar.min.css', plugin_basename(WBC_BASE_FILE)));
        wp_enqueue_script($fcAssetAlias, plugins_url('assets/fullcalendar/fullcalendar.min.js', plugin_basename(WBC_BASE_FILE)), array('moment', 'jquery'));

        if ( wbc_option('locale') != 'en' ) {
            wp_enqueue_script('fc-locales', plugins_url('assets/fullcalendar/locale-all.js', plugin_basename(WBC_BASE_FILE)), array($fcAssetAlias));
        }
    }

    function wbc_fullcalendar_asset_alias() {
        // special conditions to compatibilize with Private Google Calendars plugin avoiding asset collision
        if ( defined('PGC_PLUGIN_VERSION') ) {
            $fcAssetAlias = 'fullcalendar_wbc';
        }
        else {
            $fcAssetAlias = 'fullcalendar';
        }

        return $fcAssetAlias;
    }

    function wbc_enqueue_scripts($frontendVars = array()) {
        wp_enqueue_style('woo-bookings-calendar', plugins_url('assets/wbclite.css', plugin_basename(WBC_BASE_FILE)));
        wp_enqueue_script('woo-bookings-calendar', plugins_url('assets/wbclite.js', plugin_basename(WBC_BASE_FILE)), array('jquery'));

        if ( wbc_option('show_events_loading') ) {
            wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI.min.js', array( 'jquery' ));
        }

        $displays = wbc_calendar_mode_displays();
        $viewConf = wbc_option('calendar_view');

        $initialStartDate = !empty($frontendVars['initialStartDate']) ? $frontendVars['initialStartDate'] : wbc_option('calendar_start_date');
        $startDate = apply_filters('wbc_calendar_start_date', $initialStartDate);

        $frontendVars += array(
            'ajaxUrl' => get_admin_url() . 'admin-ajax.php',
            'displayLoading' => wbc_option('show_events_loading'),
            'disableBackPast' => wbc_option('disable_back_past'),
            'firstWeekDay' => wbc_option('first_weekday'),
            'fcDefaultView' => current($displays),
            'fcHeaderRight' => implode(',', $displays),
            'dayLabel' => __('day', 'woo-bookings-calendar'),
            'weekLabel' => __('week', 'woo-bookings-calendar'),
            'monthLabel' => __('month', 'woo-bookings-calendar'),
            'listDayLabel' => in_array('day', $viewConf) ? __('list day', 'woo-bookings-calendar') : __('day', 'woo-bookings-calendar'),
            'listWeekLabel' => in_array('week', $viewConf) ? __('list week', 'woo-bookings-calendar') : __('week', 'woo-bookings-calendar'),
            'listMonthLabel' => in_array('month', $viewConf) ? __('list month', 'woo-bookings-calendar') : __('month', 'woo-bookings-calendar'),
            'timeFormat' => wbc_option('time_format'),
            'locale' => wbc_option('locale'),
            'forceStartDate' => $startDate,
            'fcCustomOptions' => apply_filters('wbc_fullcalendar_options', array()),
        );

        $timeZoneString = get_option('timezone_string');
        if ( strlen($timeZoneString) > 0 ) {
            $frontendVars['timezoneStr'] = $timeZoneString;
        }

        $frontendVars = apply_filters('wbc_frontend_vars', $frontendVars);

        wp_localize_script('woo-bookings-calendar', 'WBCVARS', $frontendVars );
    }

    function wbc_calendar_mode_displays() {
        $display = array();
        $confs = wbc_option('calendar_view');

        foreach ( $confs as $view ) {
            $display[$view] = wbc_translate_view_display($view);
        }

        if ( empty($display) ) {
            $display['day'] = 'agendaDay';
        }

        return apply_filters('wbc_calendar_display', $display);
    }

    function wbc_translate_view_display($view) {
        $view = str_replace('-', '_', $view);

        return str_replace(
            array('list_month', 'list_week', 'list_day', 'week', 'day'),
            array('listMonth', 'listWeek', 'listDay', 'agendaWeek', 'agendaDay'),
            $view
        );
    }

    function wbc_shortcode_calendar( $atts ){
        $startDate = !empty($atts['startdate']) ? $atts['startdate'] : null;

        wbc_enqueue_fullcalendar();
        wbc_enqueue_scripts(array('initialStartDate' => $startDate));

        do_action('wbc_after_shortcode_script_queue');

        $html = '';

        if ( wbc_option('add_event_filter') == 'yes' ) {
            $query = sanitize_text_field(filter_input(INPUT_GET, 'q_event'));
            $placeholder = __('Search events ...', 'woo-bookings-calendar');

            $html .= '<div class=\'wbc-search\'>';
            $html .= '<form role="search" method="get" class="wbc-search-form">';
            $html .= '<input type="search" class="search-field wbc-search-field" placeholder="' . $placeholder . '" value="'. $query .'" name="q_event">';
            $html .= '</form>';
            $html .= '</div>';
        }

        $extra = ''; // PRO version related variable

        if ( !empty($atts['cat']) ) {
            $extra .= ' data-category="'.$atts['cat'].'"';
        }

        if ( !empty($atts['product']) ) {
            $extra .= ' data-product="'.$atts['product'].'"';
        }

        if ( !empty($atts['default-view']) ) {
            $extra .= ' data-default-view="'.wbc_translate_view_display($atts['default-view']).'"';
        }

        if ( !empty($atts['reverse-logic']) ) {
            $extra .= ' data-reverse-logic="1"';
        }

        $classes = apply_filters('wbc_calendar_class', array());

        $html .= '<div id="wbc-calendar"'.$extra.' class="'.implode(' ', $classes).'"></div>';

        return apply_filters('wbc_shortcode_html', $html);
    }

    function wbc_events_load() {
        $start = sanitize_text_field(filter_input(INPUT_POST, 'start'));
        $end = sanitize_text_field(filter_input(INPUT_POST, 'end'));
        $filter = sanitize_text_field(filter_input(INPUT_POST, 'q_event'));

        // Set the client browser timezone when WooCommerce Bookings has this config checked: Display visitor's local time
        // This code needs to be reviewed work with  wbc_build_events_array() function
        // $clientTimeZone = null;
        // if ( wc_should_convert_timezone() ) {
        //     $clientTimeZone = sanitize_text_field(filter_input(INPUT_POST, 'client_timezone'));
        //     if ( !empty($clientTimeZone) ) {
        //         WC_Bookings_Cache::clear_cache();
        //         date_default_timezone_set($clientTimeZone);
        //         wp_cache_set( 'wc_bookings_timezone_string', $clientTimeZone);
        //     }
        // }

        if ( date( 'Ymd', $start ) < date( 'Ymd', current_time( 'timestamp' ) ) ) {
            $start = time();
        }

        $dtStart = date('Ymd', $start);
        $dtEnd = date('Ymd', $end);
        $products = wbc_get_bookable_products($dtStart, $dtEnd, $filter);

        $preEvents = wbc_build_events_array($products, $dtStart, $dtEnd);
        $events = apply_filters('wbc_build_events_array', $preEvents, $products, $dtStart, $dtEnd);

        wp_send_json($events);

        exit;
    }

    function wbc_build_events_array($products, $start, $end, $timeZone = null) {

        if ( !class_exists('WBC_Event_Builder') ) {
            require_once __DIR__ . '/includes/WBC_Event_Builder.php';
        }
        
        $eventBuilder = new WBC_Event_Builder($products, $start, $end);
        return $eventBuilder->createEventsList();
    }

    function wbc_get_bookable_products($start = null, $end = null, $filter = null){
        $args = apply_filters( 'get_booking_products_args', array(
            'post_status'    => 'publish',
            'post_type'      => 'product',
            's'              => $filter,
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => array('booking'),
                ),
            ),
            'suppress_filters' => true,
        ) );

        $productIds = !empty($_POST['product_ids']) ? explode(',', $_POST['product_ids']) : null;

        if ( !empty($productIds) ) {
            $args['post__in'] = $productIds;
        }

        $args = apply_filters( 'wbc_booking_products_args', $args);
        $results = get_posts($args);

        return $results;
    }

    function wbc_debug() {
        $start = '20190401';
        $end = '20190420';
        $products = wbc_get_bookable_products($start, $end);
        $events = wbc_build_events_array($products, $start, $end);

        // var_dump($events); exit;

        var_dump(date('H:i'));
        var_dump( wc_booking_get_timezone_string() );
        var_dump( wc_should_convert_timezone() );
    }

    function wbc_debug_dateranges($dateRanges) {
        foreach ( $dateRanges as $dates ) {
            var_dump(date('d/m/Y H:i', $dates[0]).' to '.date('d/m/Y H:i', $dates[1]));
        }

        exit;
    }

    function wbc_after_single_product_summary() {
        global $product;

        if ( !$product->is_type(array('booking', 'accommodation-booking')) || ( wbc_option('attach_in_single_page') != 'yes' ) ) {
            return;
        }

        $atts = array('product' => $product->get_id());
        echo wbc_shortcode_calendar($atts);
    }

    // warning: function used in dependent plugin
    function wbc_unavailable_color($bookProd) {
        $color = apply_filters('wbc_color_unavailable', $bookProd);

        if ( is_string($color) ) {
            return $color;
        }

        if ( wbc_option('customize_colors') == 'yes' ) {
            return wbc_option('color_not_bookable');
        }

        return 'red';
    }
}

