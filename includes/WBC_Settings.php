<?php
class WBC_Settings {
    /**
     * @return array
     */
    public function buildSettingsArray() {
        $arr = array();

        $arr['show_events_loading'] = array(
            'label' => __('Display loading while updating calendar events', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'checkbox',
            'default' => 'yes',
        );
        $arr['add_event_filter'] = array(
            'label' => __('Enable event filter box in calendar', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'checkbox',
            'default' => 'no',
        );
        $arr['customize_colors'] = array(
            'label' => __('Customize bookable product colors', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'checkbox',
            'default' => 'no',
        );
        $arr['attach_in_single_page'] = array(
            'label' => __('Attach calendar in single booking page', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'checkbox',
            'default' => 'no',
        );
        $arr['disable_back_past'] = array(
            'label' => __('Disable back button to the past time', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'checkbox',
            'default' => 'no',
        );
        $arr['calendar_view'] = array(
            'label' => __('Calendar view', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'multicheckbox',
            'default' => array('day', 'week', 'month'),
            'options' => $this->getFCCalendarViews(),
        );            
        $arr['color_available'] = array(
            'label' => __('Bookable color', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'color',
            'default' => '#0000CD',
            'depends_on' => 'customize_colors',
        );
        $arr['color_not_bookable'] = array(
            'label' => __('Not bookable color', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'color',
            'default' => '#FF0000',
            'depends_on' => 'customize_colors',
        );
        $arr['when_unavailable'] = array(
            'label' => __('When item not bookable', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'select',
            'default' => 'show_readonly',
            'options' => array(
                'show_readonly' => __('Display as read-only in calendar', 'woo-bookings-calendar'),
                'hide_display' => __('Hide item from calendar', 'woo-bookings-calendar'),
                'show_normal' => __('Display as bookable item', 'woo-bookings-calendar'),
            ),
        );
        $arr['time_format'] = array(
            'label' => __('Time format', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'select',
            'default' => 'HH:mm',
            'options' => array(
                'HH:mm' => sprintf(__('%s - HH:mm'), date('H:m')),
                'h(:mm)a' => sprintf(__('%s - h(:mm)a'), date('ga')),
            ),
        );
        $arr['locale'] = array(
            'label' => __('Calendar language', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'select',
            'default' => 'en',
            'options' => $this->getFCLocales(),
        );
        $arr['first_weekday'] = array(
            'label' => __('Start day of week', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'type' => 'select',
            'default' => '0',
            'options' => $this->getWeekDays(),
        );
        $arr['calendar_start_date'] = array(
            'label' => __('Fixed calendar start date', 'woo-bookings-calendar'),
            'tab' => __('General', 'woo-bookings-calendar'),
            'default' => '',
            'type' => 'date',
        );

        return $arr;
    }

    private function getFCCalendarViews() {
        return array(
            'day' => __('Day', 'woo-bookings-calendar'),
            'week' => __('Week', 'woo-bookings-calendar'),
            'month' => __('Month', 'woo-bookings-calendar'),
            'list_day' => __('Day (list)', 'woo-bookings-calendar'),
            'list_week' => __('Week (list)', 'woo-bookings-calendar'),
            'list_month' => __('Month (list)', 'woo-bookings-calendar'),
        );
    }

    private function getWeekDays()
    {
        return array(
            '0' => __( 'Sunday', 'woo-bookings-calendar' ),
            '1' => __( 'Monday', 'woo-bookings-calendar' ),
            '2' => __( 'Tuesday', 'woo-bookings-calendar' ),
            '3' => __( 'Wednesday', 'woo-bookings-calendar' ),
            '4' => __( 'Thursday', 'woo-bookings-calendar' ),
            '5' => __( 'Friday', 'woo-bookings-calendar' ),
            '6' => __( 'Saturday', 'woo-bookings-calendar' ),
        );
    }

    private function getFCLocales() {
        return array(
            'af'=>'af',
            'ar'=>'ar',
            'ar-dz'=>'ar-dz',
            'ar-kw'=>'ar-kw',
            'ar-ly'=>'ar-ly',
            'ar-ma'=>'ar-ma',
            'ar-sa'=>'ar-sa',
            'ar-tn'=>'ar-tn',
            'bg'=>'bg',
            'bs'=>'bs',
            'ca'=>'ca',
            'cs'=>'cs',
            'da'=>'da',
            'de'=>'de',
            'el'=>'el',
            'en-au'=>'en-au',
            'en'=>'en',
            'en-gb'=>'en-gb',
            'en-nz'=>'en-nz',
            'es'=>'es',
            'et'=>'et',
            'eu'=>'eu',
            'fa'=>'fa',
            'fi'=>'fi',
            'fr-ch'=>'fr-ch',
            'fr'=>'fr',
            'gl'=>'gl',
            'he'=>'he',
            'hi'=>'hi',
            'hr'=>'hr',
            'hu'=>'hu',
            'id'=>'id',
            'is'=>'is',
            'it'=>'it',
            'ja'=>'ja',
            'ka'=>'ka',
            'kk'=>'kk',
            'ko'=>'ko',
            'lb'=>'lb',
            'lt'=>'lt',
            'lv'=>'lv',
            'mk'=>'mk',
            'ms'=>'ms',
            'nb'=>'nb',
            'nl'=>'nl',
            'nn'=>'nn',
            'pl'=>'pl',
            'pt-br'=>'pt-br',
            'pt'=>'pt',
            'ro'=>'ro',
            'ru'=>'ru',
            'sk'=>'sk',
            'sl'=>'sl',
            'sq'=>'sq',
            'sr-cyrl'=>'sr-cyrl',
            'sr'=>'sr',
            'sv'=>'sv',
            'th'=>'th',
            'tr'=>'tr',
            'uk'=>'uk',
            'vi'=>'vi',
            'zh-cn'=>'zh-cn',
            'zh-tw'=>'zh-tw'
        );
    }
}
