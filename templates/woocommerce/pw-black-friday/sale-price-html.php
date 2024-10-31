<?php

	if ( !defined( 'ABSPATH' ) ) { exit; }

	global $pw_black_friday;
	global $pwbf_deal;
	global $pwbf_price;

	$event = $pw_black_friday->get_event( $pwbf_deal->post_parent );
	$title = sprintf( __( '%s Sale Price', 'pw-black-friday' ), $event->post_title );

	if ( 'bogo' == $pwbf_deal->deal_type ) {
	    $title .= '<br>' . __( 'Buy One, Get One Free', 'pw-black-friday' );
	}
?>
</p>
<style>
    .pwbf-promo-text {
        color: black;
        font-weight: 600;
        margin-top: 0;
    }

    .pwbf-expires-text {
        color: black;
        font-size: 75%;
    }
</style>
<div class="pwbf-promo-text"><?php echo $title; ?></div>
<div class="pwbf-expires-text"><?php _e( 'Expires', 'pw-black-friday' ); ?> <?php echo $pw_black_friday->local_date_and_time( $event->end_datetime ); ?></div>
<p class="price"><?php echo $pwbf_price; ?>
