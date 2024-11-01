jQuery(document).ready(function($){
    var wbcAjaxBlockCalled = false;
    var wbcBtnDayClick = false;

    var wbcMountCalendar = function() {
        var btnText = {};
        var viewStyle = $('#wbc-calendar').attr('data-default-view') ? $('#wbc-calendar').attr('data-default-view') : WBCVARS.fcDefaultView;

        // if ( WBCVARS.locale == 'en' ) {
            btnText = {
                day: WBCVARS.dayLabel,
                week: WBCVARS.weekLabel,
                month: WBCVARS.monthLabel,
                listDay: WBCVARS.listDayLabel,
                listWeek: WBCVARS.listWeekLabel,
                listMonth: WBCVARS.listMonthLabel
            };
        // }

        var options = {
            defaultView: viewStyle,
            editable: false,
            timeFormat: WBCVARS.timeFormat,
            slotLabelFormat: WBCVARS.timeFormat,
            locale: WBCVARS.locale,
            firstDay: WBCVARS.firstWeekDay,
            header: {
                left: 'prev,next',
                center: 'title',
                right: WBCVARS.fcHeaderRight
            },
            buttonText: btnText,
            events: function(start, end, timezone, callback) {
                var postData = {
                    action: 'wbc_events_load',
                    start: start.unix(),
                    end: end.unix(),
                    q_event: $('.wbc-search-field').val(),
                    client_timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
                };

                if ( 'url_params' in WBCVARS ) {
                    for ( key in WBCVARS.url_params ) {
                        postData[key] = WBCVARS.url_params[key];
                    }
                }
                

                if ( 'singleProductId' in WBCVARS ) {
                    postData.single_product_id = WBCVARS.singleProductId;
                }

                // filter by specific categories
                if ( $('#wbc-calendar').attr('data-category') ) {
                    postData.category_slugs = $('#wbc-calendar').data('category');
                }
                
                // filter by specific product/s
                if ( $('#wbc-calendar').attr('data-product') ) {
                    postData.product_ids = $('#wbc-calendar').data('product');
                }

                // reverse calendar logic
                if ( $('#wbc-calendar').attr('data-reverse-logic') ) {
                    postData.reverse_logic = 1;
                }

                $.ajax({
                    url: WBCVARS.ajaxUrl,
                    type: 'post',
                    dataType: 'json',
                    data: postData,
                    beforeSend: function() {
                        wbcShowLoading();
                    },
                    success: function(events) {
                        wbcHideLoading();
                        callback(events);
                    }
                });
            },
            viewRender: function( view, element ) {
                if ( WBCVARS.disableBackPast == 'yes' ) {
                    if( moment().isAfter(view.intervalStart, 'day') ) {
                        $('.fc-prev-button').addClass('fc-state-disabled');
                    }
                    else {
                        $('.fc-prev-button').removeClass('fc-state-disabled');
                    }
                }
            },
            eventClick: function(info, event) {
                $(document).trigger('wbc_event_click', [ info, event ]);
            },
            eventRender: function(event, element) {
                // add full title hint on mouseover
                element.prop('title', event.title);

                // trigger for 3rd plugins
                $(document.body).trigger('wbc_event_render', [ event, element ]);

                var time = $(element).find('.fc-time').html();

                if ( time == '00:00' || time == '<span>00:00</span>' || time == '<span>00:00 - 00:00</span>' || event.type == 'accommodation-booking' ) {
                    $(element).find(".fc-time").remove();
                    $(element).find(".fc-list-item-time").remove();
                }
            }
        };
        
        if ( ( 'forceStartDate' in WBCVARS ) && ( WBCVARS.forceStartDate.length > 0 ) ) {
            options.defaultDate = WBCVARS.forceStartDate;
        }

        if ( ( 'displayEventEnd' in WBCVARS ) && ( WBCVARS.displayEventEnd == 'yes' ) ) {
            options.displayEventEnd = true;
        }

        for ( key in WBCVARS.fcCustomOptions ) {
            options[ key ] = WBCVARS.fcCustomOptions[key];
        }

        $('#wbc-calendar').fullCalendar(options);
        $(document).trigger('wbc_calendar_loaded', [ $('#wbc-calendar') ]);
    };

    wbcShowLoading = function() {
        try { 
            if ( WBCVARS.displayLoading == 'yes' ) {
                $.blockUI({message: ''});
            }
        }
        catch(e){
        }
    };

    wbcHideLoading = function() {
        try { 
            if ( WBCVARS.displayLoading == 'yes' ) {
                $.unblockUI();
            }
        }
        catch(e){
        }
    };

    var wbcSingleBookingProductPage = function() {
        $(document.body).trigger('wbc_single_booking_page');

        // code executed when viewing single booking product page
        $('.booking_date_day').val( WBCVARS.bookingDateDay );
        $('.booking_date_month').val( WBCVARS.bookingDateMonth );
        $('.booking_date_year').val( WBCVARS.bookingDateYear );

        setTimeout(function(){
            $('.booking_date_year').trigger('click');
        }, 200);

        var cldExistCondition = setInterval(function() {
            if ( $('.wc-bookings-date-picker').length && !$('.booking_to_date_day').length ) {

                if($('.bookable').length ){
                     clearInterval(cldExistCondition);

                    // loop on days of calendar
                    $('.bookable,.bookable-range').each(function(){
                        var calendarDay = parseInt($(this).find('a').html());

                        // when find the right day, then click to open the time slots, if it's the case
                        if ( calendarDay == parseInt(WBCVARS.bookingDateDay) ) {
                            var btnDay = $(this);
                            
                            var clickFunction = function(){
                                if ( !wbcBtnDayClick ) {
                                    setTimeout(function(){
                                        var btnDay = $('.bookable-range');

                                        if ( !btnDay.length ) {
                                            btnDay = $('.bookable').find('.ui-state-active').parent();
                                        }

                                        if ( btnDay.length ) {
                                            wbcBtnDayClick = true;
                                            btnDay.trigger('click');
                                        }
                                    }, 200);
                                }
                            };

                            setTimeout(function(){ clickFunction(); }, 300);
                            setTimeout(function(){ clickFunction(); }, 800);
                            setTimeout(function(){ clickFunction(); }, 1000);
                            setTimeout(function(){ clickFunction(); }, 2000);
                        }
                    });

                    wbcListenClickTimeBlock();
                }
            }
               
        }, 100);
    };

    var wbcListenClickTimeBlock = function() {
        // watch until time slots are rendered
        $( document.body ).ajaxSuccess(function(e) {
            
            $('.block').ready(function(){
                $('.block').not('.wbc-block').each(function(){
                    $(this).addClass('wbc-block');

                    var slotLink = $(this).find('a');
                    var slotDate = slotLink.data('value');
                    if ( !slotDate.match(/(.*)-(.*)-(.*)/) ) {
                        slotDate = WBCVARS.bookingDateYear + '-' + WBCVARS.bookingDateMonth + '-' + WBCVARS.bookingDateDay + ' ' + slotDate;
                    }

                    if ( 'timezoneStr' in WBCVARS ) {
                        var date = moment.tz( slotDate, WBCVARS.timezoneStr );
                    }
                    else {
                        var date = moment( slotDate );
                    }
                    
                    var time = date.format('HH:mm');

                    if ( WBCVARS.bookingDateTime == time ) {
                        var clickFunction = function(){
                            if ( !slotLink.hasClass('clicked') ) {
                                slotLink.addClass('clicked').trigger('click');
                            }
                        };

                        setTimeout(function(){ clickFunction(); }, 200);
                        setTimeout(function(){ clickFunction(); }, 800);
                        setTimeout(function(){ clickFunction(); }, 1000);
                        setTimeout(function(){ clickFunction(); }, 2000);
                    }
                });
            });
        });
    };

    if ( $('#wbc-calendar').length && !$('#wbc-calendar').html().length ) {
        wbcMountCalendar();

        // fix Tabs widget display 
        $("a.w-tabs-item").click(function(){
            $(window).trigger('resize');
        });
    }

    if ( typeof WBCVARS.isSingleProduct != 'undefined' ) {
        setTimeout(function(){
            wbcSingleBookingProductPage();
        }, 300);
    }
});
