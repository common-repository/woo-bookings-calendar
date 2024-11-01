<?php
class WBC_Event_Builder {
    private $products = array();
    private $start;
    private $end;
    private $eventAvailError = null;
    private $dateRanges = array();
    private $excludeRanges = array();

    public function __construct($products, $start, $end) {
        $this->products = $products;
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return array
     */
    public function createEventsList() {
        $products = $this->products;
        $events = array();
        $isPremium = apply_filters('mh_wbc_is_premium', false);

        foreach ( $products as $position => $product ) {
            $bookProd = wc_get_product( $product );

            // other advanced types of bookings like Accommodations only supported in premium version
            if ( !$isPremium && !$bookProd->is_type('booking') ) {
                continue;
            }

            $dateRanges = $this->buildDateRanges($bookProd);
            $productId = $product->ID;

            foreach ( $dateRanges as $ranges ) {
                $event = $this->buildEventRow($product, $bookProd, $ranges, $position);
                $uniqueId = crc32($productId . '_' . $event['start'] . '_' . $event['end']);

                $events[$uniqueId] = $event;
            }
        }

        $events = array_filter($events);
        sort($events);
        
        return apply_filters('wbc_filter_events', $events);
    }

    /**
     * Build date ranges blocks to create event sequences
     * 
     * @return array
     */
    private function buildDateRanges($bookProd) {
        $isAccommodation = $bookProd->is_type('accommodation-booking');
        $start = strtotime($this->start);
        $end = strtotime($this->end);
        $blocks = $bookProd->get_blocks_in_range($start, $end);

        // foreach ($bookProd->get_resource_ids() as $resourceId ) {
            // var_dump( $bookProd->get_blocks_in_range($start, $end, [], $resourceId) );
            // $blocks[] = $bookProd->get_blocks_in_range($start, $end, [], $resourceId);
        // }

        $blocksInRange = apply_filters('wbc_get_blocks_in_range', $blocks, $bookProd, $start, $end);

        if ( !$isAccommodation ) {
            $availableBlocks = wc_bookings_get_time_slots($bookProd, $blocksInRange);
        }

        $availRules = $bookProd->get_availability_rules();

        // foreach( $bookProd->get_resources() as $res ) {
        //     $availRules[] = $res->get_availability();
        // }

        $this->dateRanges = array();
        $this->excludeRanges = array();

        // When Availability Rules has defined, consider it to create availability blocks
        // TODO This needs to be reviewed. In many cases will not work correct
        if ( !empty($availRules) && !$bookProd->is_type('accommodation-booking') ) {
            foreach ( $availRules as $rule ) {
                switch ( $rule['type'] ) {
                    case 'custom:daterange':
                        $this->parseCustomDateRange($rule);
                    break;

                    // week days (monday, tuesday, ...)
                    case 'time:1':
                    case 'time:2':
                    case 'time:3':
                    case 'time:4':
                    case 'time:5':
                    case 'time:6':
                    case 'time:7':
                        // $this->parseWeekDayRange($bookProd, $rule, $start, $end);
                    break;
                }
            }
        }
        else if ( !empty($availableBlocks) ) {
            foreach ( $availableBlocks as $bStart => $info ) {
                $bStart = $this->getStartRange($bookProd, $bStart);
                $bEnd = $this->getEndRange($bookProd, $bStart);
                $this->addDateRanges($bStart, $bEnd);
            }
        }
        else if ( !empty($blocksInRange) ) {
            foreach ( $blocksInRange as $bStart ) {
                $bStart = $this->getStartRange($bookProd, $bStart);
                $bEnd = $this->getEndRange($bookProd, $bStart);
                $this->addDateRanges($bStart, $bEnd);
            }
        }

        // make works with time/hour blocks
        if ( empty($dateRanges) ) {
            foreach ( $blocksInRange as $key => $time ) {
                $isLast = $this->checkIsLastBlockDay($blocksInRange, $time);
                $bStart = $this->getStartRange($bookProd, $time);
                $bEnd = $this->getEndRange($bookProd, $time, $isLast);

                // ensure the block is available
                if ( !count($bookProd->get_blocks_in_range($bStart, $bEnd)) ) {
                    continue;
                }

                $this->addDateRanges($bStart, $bEnd);
            }
        }

        $dateRanges = $this->dateRanges;
        
        if ( !empty($excludeRanges) ) {
            $dateRanges = $this->applyExcludeRanges($dateRanges, $excludeRanges);
        }

        return apply_filters('wbc_filter_date_ranges', $dateRanges, $bookProd);
    }

    private function checkIsLastBlockDay($blocksInRange, $currentTime)
    {
        $blocksByDay = [];

        foreach ( $blocksInRange as $time ) {
            if ( $time > $currentTime ) {
                $blocksByDay[ date('Ymd', $time) ][] = $time;
            }
        }

        $currentDay = date('Ymd', $currentTime);
        $isLastDayBlock = empty($blocksByDay[$currentDay]);

        return $isLastDayBlock;
    }

    private function addDateRanges($blockStart, $blockEnd)
    {
        $this->dateRanges[] = array($blockStart, $blockEnd);
    }

    private function addProcessedDateRanges($bookProd, $blockStart)
    {
        $bStart = $this->getStartRange($bookProd, $blockStart);
        $bEnd = $this->getEndRange($bookProd, $bStart);

        $this->addDateRanges($bStart, $bEnd);
    }

    private function addExcludeRanges($blockStart, $blockEnd)
    {
        $this->excludeRanges[] = array($blockStart, $blockEnd);
    }

    private function addProcessedExcludeRanges($bookProd, $blockStart)
    {
        $bStart = $this->getStartRange($bookProd, $blockStart);
        $bEnd = $this->getEndRange($bookProd, $bStart);

        $this->addExcludeRanges($bStart, $bEnd);
    }

    private function parseCustomDateRange($rule)
    {
        $rule['range'] = $this->separateDateRangeRule($rule['range']);

        foreach ( $rule['range'] as $year => $info ) {
            foreach ( $info as $month => $subInfo ) {
                foreach ( $subInfo as $day => $subSubInfo ) {        
                    $day = str_pad($day, 2, '0', STR_PAD_LEFT);
                    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
                    $from = $subSubInfo['from'];
                    $to = $subSubInfo['to'];

                    $isBookable = ( $subSubInfo['rule'] === true );
                    $bStart = strtotime("{$year}-{$month}-{$day} {$from}");
                    $bEnd = strtotime("{$year}-{$month}-{$day} {$to}");

                    if ( $isBookable ) {
                        $this->addDateRanges($bStart, $bEnd);
                    }
                    else {
                        // when is NOT bookable, the range interval needs to be excluded
                        $this->addExcludeRanges($bStart, $bEnd);
                    }
                }
            }
        }
    }

    private function parseWeekDayRange($bookProd, $rule, $start, $end)
    {
        $isBookable = ( $rule['range']['rule'] === true );
        $ruleDay = ( ( $rule['range']['day'] == 7 ) ? 0 : $rule['range']['day'] );
        $oneDaySecs = ( 24 * 60 * 60 );

        while ( $start < $end )
        {
            $loopDay = date('w', $start);

            if ( $isBookable ) {
                if ( $loopDay == $ruleDay ) {
                    $this->addProcessedDateRanges($bookProd, $start);
                }
            }
            else {
                if ( $loopDay != $ruleDay ) {
                    $this->addProcessedExcludeRanges($bookProd, $start);
                }
            }

            // increments +1 day
            $start += $oneDaySecs;
        }
    }

    /**
     * Build the event row that will be sent to frontend and popuplate the Calendar
     * 
     * @return array
     */
    private function buildEventRow($product, $bookProd, $ranges, $position) {
        list($start, $end) = $ranges;
        $restrictedDays = $bookProd->has_restricted_days() ? $bookProd->get_restricted_days() : array();

        if ( !empty($restrictedDays) && !in_array(date('w', $start), $restrictedDays) ) {
            return array();
        }

        $availables = $this->getEventAvailability($product, $bookProd, $start, $end);
        $availError = $this->eventAvailError;
        $args = array('wbc_cal_start' => $start, 'wbc_cal_end' => $end);
        $url = add_query_arg($args, get_permalink($product));
        $color = empty($availError) ? $this->getAvailableColor($bookProd, $position) : wbc_unavailable_color($bookProd);

        $eventTitle = $product->post_title;
        if ( !empty($availError) ) {
            $eventTitle .= ' (' . $availError . ')';
        }

        $event = array();
        $event['title'] = $eventTitle;
        $event['start'] = date('Y-m-d H:i', $start);
        $event['end'] = date('Y-m-d H:i', $end);
        $event['post_id'] = $product->ID;
        $event['color'] = $color;
        $event['availables'] = $availables;
        $event['type'] = $bookProd->get_type();

        if ( empty($availError) ) {
            $event['url'] = $url;
        }

        return apply_filters('wbc_event_row', $event, $product, $bookProd, $availables);
    }

    /**
     * Check if the item has at least 1 quantity available or any other error that not allow to book in the date
     * 
     * @return WP_Error|int Number of available items of the event, or WP_Error in case of not available
     */
    private function getEventAvailability($product, $bookProd, $start, $end) {
        static $whenUnavailable = null;
        static $alwaysFetchAvailables = null;

        if ( is_null($whenUnavailable) ) {
            $whenUnavailable = wbc_option('when_unavailable');
        }

        if ( is_null($alwaysFetchAvailables) ) {
            $alwaysFetchAvailables = apply_filters('wbc_always_get_availables', false);
        }

        $availables = null;
        $this->eventAvailError = null;

        if ( $whenUnavailable != 'show_normal' || $alwaysFetchAvailables ) {
            $resource = null;

            if ( $bookProd->has_resources() ) {
                $resources = $bookProd->get_resources($product->ID);

                if (!empty($resources[0])) {
                    $resourceId = $resources[0]->ID;
                    $resource = new WC_Product_Booking_Resource( $resourceId );
                }
            }

            $preAailables = $bookProd->get_blocks_availability($start, $end, 1, $resource);
            $availables = apply_filters('wbc_loop_blocks_availability', $preAailables, $bookProd, $start, $end, $resource);

            // first check
            $isNotAvailable = is_wp_error( $availables ) || ( is_bool($availables) && $availables === false );

            // 2nd check
            if ( !$isNotAvailable ) {
                $start += 60;
                $end -= 60;
                $currentBookings = $bookProd->get_bookings_in_date_range($start, $end);

                if ( count($currentBookings) >= $bookProd->get_qty() ) { 
                    $isNotAvailable = true;
                }
            }

            if ( $isNotAvailable ) {
                // not include this range when the configuration tell to hide item when unavailable (hide_display)
                if ( $whenUnavailable == 'hide_display' ) {
                    return array();
                }

                // sets the error message when configuration tell to show item as readonly when unavailable (show_readonly)
                // $availError = $availables->get_error_message();
                $this->eventAvailError = __('BOOKED', 'woo-bookings-calendar');

                $availables = 0;
            }
        }

        return $availables;
    }

    /**
     * @return string Unix timestamp
     */
    private function getStartRange($bookProd, $time) {
        $unit = $bookProd->get_duration_unit();
        $timeFiltered = apply_filters('wbc_filter_start_range', $time, $unit, $bookProd);
        
        if ( $timeFiltered != $time ) {
            $time = $timeFiltered;
        }
        else {
            switch($unit) {
                case 'hour':
                    $time = strtotime(date('Y-m-d H:i', $time));
                    break;
    
                case 'day':
                    $time = strtotime(date('Y-m-d H:i', $time));
                    break;
    
                case 'month':
                    $time = strtotime(date('Y-m-d 00:00', $time));
                    break;
            }
        }

        $min_date   = $bookProd->get_min_date();
        $min_time = strtotime( "+{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
    
        if ( $time < $min_time ) {
            $time = $min_time;
        }

        return $time;
    }

    /**
     * @return string Unix timestamp
     */
    private function getEndRange($bookProd, $start, $isLastBlockDay = false) {
        $endFiltered = apply_filters('wbc_filter_end_range', $start, $bookProd);

        if ( $endFiltered != $start ) {
            $end = $endFiltered;
        }
        else {
            $duration = $bookProd->get_duration();
            $unit = $bookProd->get_duration_unit();
            $minDuration = $bookProd->get_min_duration();

            if ( $minDuration > 0 && !$isLastBlockDay ) {
                $duration = ( $duration * $minDuration );
            }

            $end = strtotime("+{$duration} {$unit}", $start);
        }

        $max_date   = $bookProd->get_max_date();
        $max_time = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time( 'timestamp' ) );
    
        if ( $end > $max_time ) {
            $end = $max_time;
        }

        return $end;
    }

    /**
     * This function check all date ranges to find the intersections that need to be excluded accordingly
     * from Availability configuration when defined as Bookable = No
     */
    private function applyExcludeRanges($dateRanges, $excludeRanges) {
        $newRanges = array();
        
        foreach ($dateRanges as $dRange) {
            list($rStart, $rEnd) = $dRange;
            $foundIntersection = false;

            foreach ( $excludeRanges as $eRange ) {
                list($eStart, $eEnd) = $eRange;

                if ( $eStart > $rStart && $eStart < $rEnd ) {
                    $newRanges[] = array($rStart, $eStart-60);
                    $newRanges[] = array($eEnd+60, $rEnd-60);
                    $foundIntersection = true;
                }
            }

            if ( !$foundIntersection ) {
                $newRanges[] = $dRange;
            }
        }

        return $newRanges;
    }

    /**
     * In many use cases it desired to show the start and to time separately in calendar
     * When configured "Range type = Date range with time" rule
     * So this function define all start time and end time as same
     * 
     * @return array
     */
    private function separateDateRangeRule($rule) {
        $firstFrom = current(current(current($rule)));
        $firstFrom = $firstFrom['from'];

        $end1 = end($rule);
        $end2 = end($end1);
        $end3 = end($end2);
        $lastTo = $end3['to'];

        foreach ( $rule as $year => $info ) {
            foreach ( $info as $month => $subInfo ) {
                foreach ( $subInfo as $day => $subSubInfo ) {
                    if ( !$subSubInfo['rule'] ) {
                        continue;
                    }

                    $rule[$year][$month][$day]['from'] = $firstFrom;
                    $rule[$year][$month][$day]['to'] = $lastTo;
                }
            }
        }
        return $rule;
    }

    /**
     * @return string
     */
    function getAvailableColor($bookProd, $position) {
        $color = apply_filters('wbc_color_available', $bookProd);

        if ( is_string($color) ) {
            return $color;
        }

        if ( wbc_option('customize_colors') == 'yes' ) {
            return wbc_option('color_available');
        }

        // randomize colors
        $colors = array(
            'royalblue',
            'olive',
            'royalblue',
            'darkcyan',
            'green',
            'orange',
        );

        if ( isset($colors[$position]) ) {
            return $colors[$position];
        }

        return 'blue';
    }
}
