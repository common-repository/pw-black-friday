<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

global $pw_black_friday;

if ( isset( $_POST['pwbf-edit-promo-editor'] ) ) {
    $promo_content = apply_filters('the_content', stripslashes( $_POST['pwbf-edit-promo-editor'] ) );
} else {
    foreach ( $pw_black_friday->get_active_events() as $event ) {
        if ( !empty( $event->post_content ) ) {
            $promo_content = $event->post_content;
            break;
        }
    }
}

if ( empty( $promo_content ) ) {
    return;
}

?>
<style>
    .pwbf-promo-background {
        position: fixed;
        height: 100%;
        width: 100%;
        top: 0;
        left: 0;
        background-color: #000000;
        opacity: 0.8;
        z-index: 2147483646;
    }

    .pwbf-promo-content-container {
        position: fixed;
        padding: 25px;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #FFFFFF;
        z-index: 2147483647;
        border-radius: 8px;
    }

    .pwbf-promo-close-button {
        position: absolute;
        top: -15px;
        right: -15px;
        width: 45px;
        height: 45px;
        -webkit-border-radius: 45px;
        -moz-border-radius: 45px;
        border-radius: 45px;
        background-color: #FFFFFF;
        -webkit-box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.34);
        -moz-box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.34);
        box-shadow: 0px 2px 4px 0px rgba(0, 0, 0, 0.34);
        cursor: pointer;
    }

    .pwbf-promo-close-button:hover {
        background-color: #EFEFEF;
    }

    .pwbf-promo-close-button-text {
        position: absolute;
        font-family: sans-serif;
        font-weight: 600;
        font-size: 20pt;
        line-height: 1.0em;
        top: 48%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
</style>
<div class="pwbf-promo">
    <div class="pwbf-promo-background"></div>
    <div class="pwbf-promo-content-container">
        <span class="pwbf-promo-close-button"><span class="pwbf-promo-close-button-text">x</span></span>
        <?php
            echo $promo_content;
        ?>
    </div>
</div>
<script>
    jQuery('.pwbf-promo-background, .pwbf-promo-close-button').on('click', function() {
        jQuery('.pwbf-promo').hide();
    });
</script>
