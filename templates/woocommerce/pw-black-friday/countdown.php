<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

global $pw_black_friday;

$event = null;

if ( isset( $_POST['countdown_field'] ) && isset( $_POST[ $_POST['countdown_field'] ] ) ) {
    $event = $pw_black_friday->get_event( absint( $_POST['event_id'] ) );
    $event->countdown_content = trim( stripslashes( $_POST[ $_POST['countdown_field'] ] ) );
    $event->countdown_start = ( $_POST['countdown_field'] == 'upcoming_countdown' ) ? $event->begin_datetime : $event->end_datetime;

} else {
    $all_events = $pw_black_friday->get_all_events();
    foreach ( $all_events as &$e ) {
        $e->upcoming_offset = absint( $e->upcoming_offset );
        if ( empty( $e->upcoming_offset ) ) {
            $e->upcoming_offset = 99999;
        }

        if ( !empty( $e->upcoming_countdown ) && time() <= $e->begin_datetime && pwbf_strtotime( "+{$e->upcoming_offset} days" ) >= $e->begin_datetime ) {
            $event = $e;
            $event->countdown_start = $event->begin_datetime;
            $event->countdown_content = $event->upcoming_countdown;
            break;

        } else if ( !empty( $e->ending_countdown ) && time() >= $e->begin_datetime && time() <= $e->end_datetime ) {
            $event = $e;
            $event->countdown_start = $event->end_datetime;
            $event->countdown_content = $event->ending_countdown;
            break;
        }
    }
}

if ( is_null( $event ) || empty( $event->countdown_content ) ) {
    return;
}

?>
<style>
    <?php
        $top_offset = is_admin_bar_showing() ? '32' : '0';
    ?>

    body {
        padding-top: 32px;
    }

    #pwbf-countdown {
        display: none;
        color: #FFFFFF;
        background: #BA2633;
        font-family: 'Roboto', Helvetica, Arial, sans-serif;
        font-size: 13px;
        line-height: 32px;
        height: 32px;
        position: fixed;
        top: <?php echo $top_offset; ?>px;
        left: 0;
        width: 100%;
        z-index: 999999;
        text-align: center;
    }
</style>
<div id="pwbf-countdown">
    <?php
        echo $event->countdown_content;
    ?>
</div>
<script>
    jQuery(function() {

        var countdownTime = new Date('<?php echo $pw_black_friday->local_date_and_time( $event->countdown_start, 'm/d/Y', 'G:i' ); ?>');
        var beginDateTime = new Date('<?php echo $pw_black_friday->local_date_and_time( $event->begin_datetime, 'm/d/Y', 'G:i' ); ?>');
        var endDateTime = new Date('<?php echo $pw_black_friday->local_date_and_time( $event->end_datetime, 'm/d/Y', 'G:i' ); ?>');

        var countdownHtml = jQuery('#pwbf-countdown').html();
        jQuery('#pwbf-countdown').html(jQuery('#pwbf-countdown').html()
            .replace(/{countdown}/gi, '<span class=\"pwbf-countdown-tags-countdown\"></span>')
            .replace(/{begin_date}/gi, beginDateTime.toLocaleDateString())
            .replace(/{begin_time}/gi, beginDateTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}))
            .replace(/{end_date}/gi, endDateTime.toLocaleDateString())
            .replace(/{end_time}/gi, endDateTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}))
        );

        <?php
            if ( false !== stripos( $event->countdown_content, '{countdown}' ) ) {
                ?>
                pwbfCountdownTick(countdownTime);
                var pwbfCountdownInterval = setInterval(function() { pwbfCountdownTick(countdownTime); }, 1000);
                <?php
            }
        ?>

        jQuery('#pwbf-countdown').show();
    });

function pwbfCountdownTick(countdownTime) {
    var now = new Date().getTime();
    var distance = countdownTime - now;
    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

    if (distance > 0) {
        var message = '';

        if (days > 0) {
            if (days == 1) {
                message += '<?php _e( '1 day', 'pw-black-friday' ); ?> ';
            } else {
                message += days + ' <?php _e( 'days', 'pw-black-friday' ); ?> ';
            }
        }

        if (hours > 0 || days > 0) {
            if (hours == 1) {
                message += '<?php _e( '1 hour', 'pw-black-friday' ); ?> ';
            } else {
                message += hours + ' <?php _e( 'hours', 'pw-black-friday' ); ?> ';
            }
        }

        if (minutes == 1) {
            message += '<?php _e( '1 minute', 'pw-black-friday' ); ?> ';
        } else {
            message += minutes + ' <?php _e( 'minutes', 'pw-black-friday' ); ?> ';
        }

        if (seconds == 1) {
            message += '<?php _e( '1 second', 'pw-black-friday' ); ?> ';
        } else {
            message += seconds + ' <?php _e( 'seconds', 'pw-black-friday' ); ?>';
        }


    } else {
        message = '<?php _e( '0 seconds', 'pw-black-friday' ); ?>';
        if (typeof pwbfCountdownInterval !== 'undefined') {
            clearInterval(pwbfCountdownInterval);
        }
    }

    jQuery('.pwbf-countdown-tags-countdown').text(message);
}

</script>
