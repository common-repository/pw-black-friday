<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php

    pwbfWizardTitle( __( 'Restrictions', 'pw-black-friday' ) );

    if ( isset( $pwbf_deal ) ) {
        $order_limit = $pwbf_deal->order_limit;
        $coupon_code = $pwbf_deal->coupon_code;
        $individual_use = $pwbf_deal->individual_use;
        $individual_use_message = !empty( $pwbf_deal->individual_use_message ) ? $pwbf_deal->individual_use_message : __( 'Coupon is not valid.', 'pw-black-friday' );
    } else {
        $order_limit = '';
        $coupon_code = '';
        $individual_use = false;
        $individual_use_message = __( 'Coupon is not valid.', 'pw-black-friday' );
    }

?>
<div class="pwbf-bordered-content">
    <?php
        if ( wc_coupons_enabled() ) {
            ?>
            <div>
                <label for="pwbf-coupon-code" class="pwbf-input-title" style="margin-top: 0;">
                    <?php _e( 'Coupon Code', 'pw-black-friday' ); ?>
                </label><br>
                <input id="pwbf-coupon-code" class="pwbf-input" name="coupon_code" type="text" value="<?php echo esc_html( $coupon_code ); ?>">
                <div class="pwbf-input-subtitle" style="max-width: 600px;">
                    <?php _e( 'If you would like a coupon code to be required to activate this deal, enter it here. You can use the same coupon code for multiple deals in this event. You don\'t have to create the coupon in WooCommerce ahead of time, just input the code you would like to use and we\'ll take care of the rest!', 'pw-black-friday' ); ?>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label for="pwbf-individual-use" class="pwbf-input-title">
                    <input type="checkbox" id="pwbf-individual-use" name="individual_use" value="yes" <?php checked( $individual_use ); ?>>
                    <?php _e( 'Individual Use', 'pw-black-friday' ); ?>
                </label>
                <div class="pwbf-input-subtitle">
                    <?php _e( 'Check this box if other coupons are not allowed for items in this deal.', 'pw-black-friday' ); ?>
                </div>

                <div id="pwbf-individual-use-message-container" style="margin-left: 40px; <?php echo $individual_use ? '' : 'display: none;'; ?>">
                    <label for="pwbf-individual-use-message" class="pwbf-input-title" style="margin-top: 10px;">
                        <?php _e( 'Individual Use Error Message', 'pw-black-friday' ); ?>
                    </label><br>
                    <input id="pwbf-individual-use-message" class="pwbf-input" style="width: 100%;" name="individual_use_message" type="text" value="<?php echo esc_html( $individual_use_message ); ?>">
                    <div class="pwbf-input-subtitle">
                        <?php _e( 'Shown if customer tries to use another coupon during the event.', 'pw-black-friday' ); ?>
                    </div>
                </div>
            </div>
            <?php
        }
    ?>
    <label for="pwbf-order-limit" class="pwbf-input-title" style="margin-top: 0;">
        <?php _e( 'Quantity limit per order', 'pw-black-friday' ); ?>
    </label><br>
    <input id="pwbf-order-limit" name="order_limit" type="number" step="1" min="0" value="<?php echo $order_limit; ?>">
    <div class="pwbf-input-subtitle">
        <?php _e( 'The maximum number of discounted products allowed in the cart at the same time. Leave blank if there is no limit.', 'pw-black-friday' ); ?>
    </div>
</div>
<script>
    jQuery(function() {
        jQuery('#pwbf-individual-use').on('change', function() {
            jQuery('#pwbf-individual-use-message-container').toggle(jQuery(this).is(':checked'));
        });
    });

    function pwbfWizardLoadStep<?php echo $pwbf_step; ?>() {
        if (jQuery('#pwbf-deal-type').val() == 'bogo') {
            var buy = parseInt(jQuery('#pwbf-bogo-buy').val());
            var get = parseInt(jQuery('#pwbf-bogo-get').val());
            jQuery('#pwbf-order-limit').attr('min', buy + get);
        } else {
            jQuery('#pwbf-order-limit').attr('min', 0);
        }
    }

    function pwbfWizardValidateStep<?php echo $pwbf_step; ?>() {
        return true;
    }
</script>
