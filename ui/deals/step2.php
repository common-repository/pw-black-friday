<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php

    pwbfWizardTitle( __( 'Promotion type for this deal', 'pw-black-friday' ) );

    if ( isset( $pwbf_deal ) ) {
        $deal_type = $pwbf_deal->deal_type;
        $use_regular_price = !isset( $pwbf_deal->use_regular_price ) ? true : $pwbf_deal->use_regular_price;
        $bogo_identical_products = $pwbf_deal->bogo_identical_products;
        $bogo_identical_variations = $pwbf_deal->bogo_identical_variations;
        $free_shipping = $pwbf_deal->free_shipping;
        $free_shipping_min_amount = max( 0, $pwbf_deal->free_shipping_min_amount );

    } else {
        $deal_type = 'percentage';
        $use_regular_price = false;
        $bogo_identical_products = false;
        $bogo_identical_variations = false;
        $free_shipping = false;
        $free_shipping_min_amount = 0;
    }

?>
<div class="pwbf-bordered-content">
    <div style="margin-top: 30px;">

        <div id="pwbf-percentage-discount-container-pro" class="pwbf-inline-help-text pwbf-deal-container-pro <?php echo $deal_type == 'percentage' ? '' : 'pwbf-hidden'; ?>">
            <?php printf( __( 'To set a custom discount percentage (plus a whole lot more!)<br>upgrade to %s', 'pw-black-friday' ), '<a href="https://www.pimwick.com/black-friday/" target="_blank">Black Friday Pro</a>.' ); ?>
        </div>

        <div id="pwbf-fixed-discount-container-pro" class="pwbf-inline-help-text pwbf-deal-container-pro <?php echo $deal_type == 'fixed' ? '' : 'pwbf-hidden'; ?>">
            <?php printf( __( 'To set a custom amount (plus a whole lot more!)<br>upgrade to %s', 'pw-black-friday' ), '<a href="https://www.pimwick.com/black-friday/" target="_blank">Black Friday Pro</a>.' ); ?>
        </div>

        <div id="pwbf-bogo-container-pro" class="pwbf-inline-help-text pwbf-deal-container-pro <?php echo $deal_type == 'bogo' ? '' : 'pwbf-hidden'; ?>">
            <?php printf( __( '%s will let you specify Buy X, Get X<br>for a percentage off (for example, Buy 3, Get 1 for 50%% off).', 'pw-black-friday' ), '<a href="https://www.pimwick.com/black-friday/" target="_blank">Black Friday Pro</a>' ); ?>
        </div>

        <div id="pwbf-deal-type-container">
            <select id="pwbf-deal-type" name="deal_type" class="pwbf-input">
                <option value="percentage" <?php selected( $deal_type, 'percentage' ); ?>>10% <?php _e( 'off regular price', 'pw-black-friday' ); ?></option>
                <option value="fixed" <?php selected( $deal_type, 'fixed' ); ?>><?php echo wc_price( 10 ); ?> <?php _e( 'off regular price', 'pw-black-friday' ); ?></option>
                <option value="bogo" <?php selected( $deal_type, 'bogo' ); ?>><?php _e( 'Buy One, Get One Free', 'pw-black-friday' ); ?></option>
            </select>
        </div>

        <div id="pwbf-use-regular-price-container" class="pwbf-deal-container <?php echo ( $deal_type == 'percentage' || $deal_type == 'fixed' ) ? '' : 'pwbf-hidden'; ?>">
            <label for="pwbf-use-regular-price">
                <input type="checkbox" id="pwbf-use-regular-price" name="use_regular_price" value="yes" <?php checked( $use_regular_price ); ?>>
                <?php _e( 'Discount is based on Regular Price', 'pw-black-friday' ); ?>
            </label>
            <div class="pwbf-input-subtitle"><?php _e( 'Ignores any existing sale price.', 'pw-black-friday' ); ?></div>
        </div>

        <div id="pwbf-bogo-container" class="pwbf-deal-container <?php echo $deal_type == 'bogo' ? '' : 'pwbf-hidden'; ?>">

            <div id="pwbf-bogo-identical-products-container" style="margin-top: 12px;">
                <label for="pwbf-bogo-identical-products">
                    <input type="checkbox" id="pwbf-bogo-identical-products" name="bogo_identical_products" value="yes" <?php checked( $bogo_identical_products ); ?>>
                    <?php _e( 'Only discount identical products.', 'pw-black-friday' ); ?> <a href="#" id="pwbf-bogo-identical-products-help-link"><?php _e( "What's this?", 'pw-black-friday' ); ?></a>
                </label>
            </div>

            <div id="pwbf-bogo-identical-variations-container" <?php echo $bogo_identical_products ? '' : 'style="display: none;"'; ?>>
                <label for="pwbf-bogo-identical-variations">
                    <input type="checkbox" id="pwbf-bogo-identical-variations" name="bogo_identical_variations" value="yes" <?php checked( $bogo_identical_variations ); ?>>
                    <?php _e( 'Only discount identical variations.', 'pw-black-friday' ); ?>
                </label>
            </div>

            <div id="pwbf-bogo-identical-products-help" class="pwbf-inline-help-text" style="display: none;">
                <div>
                    <?php _e( 'Instead of allowing a mix-and-match of products included in this deal, only discount identical products. For example:', 'pw-black-friday' ); ?>
                </div>
                <div style="margin: 6px 24px;">
                    <?php _e( 'Promotion:', 'pw-black-friday' ); ?> <b><?php _e( 'Buy 1, Get 1 Free', 'pw-black-friday' ); ?></b><br>
                    <?php _e( 'Category:', 'pw-black-friday' ); ?> <b><?php _e( 'Hats', 'pw-black-friday' ); ?></b><br>
                    <?php _e( 'Cart:', 'pw-black-friday' ); ?><br>
                    <div style="margin-left: 24px;">
                        <?php _e( '$100 Large Hat (x 2)', 'pw-black-friday' ); ?><br>
                        <?php _e( '$50 Small Hat (x 2)', 'pw-black-friday' ); ?>
                    </div>
                </div>
                <div>
                    <?php _e( 'With this option <b>unchecked</b>, the discount will be <b>$100</b>. (2 Large Hats purchased, 2 Small Hats for free.)', 'pw-black-friday' ); ?>
                    <br>
                    <?php _e( 'With this option <b>checked</b>, the discount will be <b>$150</b>. (1 free Large Hat, 1 free Small Hat.)', 'pw-black-friday' ); ?>
                </div>
            </div>
        </div>

        <div style="margin-top: 48px;">
            <label for="pwbf-free-shipping" class="pwbf-input-title">
                <input type="checkbox" id="pwbf-free-shipping" name="free_shipping" value="yes" <?php checked( $free_shipping ); ?>>
                <?php _e( 'Offer Free Shipping', 'pw-black-friday' ); ?>
            </label>

            <div id="pwbf-free-shipping-min-amount-container" style="margin-top: 6px; margin-left: 24px; <?php echo ( $free_shipping ) ? '' : 'display: none;'; ?>">
                <label for="pwbf-free-shipping-min-amount"><?php _e( 'Minimum order amount', 'pw-black-friday' ); ?> (<?php echo get_woocommerce_currency_symbol(); ?>)</label><br>
                <input id="pwbf-free-shipping-min-amount" class="pwbf-input pwbf-medium-number-field" name="free_shipping_min_amount" type="number" min="0" value="<?php echo $free_shipping_min_amount; ?>">
            </div>
        </div>
    </div>
</div>
<script>
    jQuery(function() {
        jQuery('#pwbf-deal-type').on('change', function() {
            jQuery('.pwbf-deal-container, .pwbf-deal-container-pro').addClass('pwbf-hidden');

            switch (jQuery(this).val()) {
                case 'percentage':
                    jQuery('#pwbf-percentage-discount-container-pro, #pwbf-use-regular-price-container').removeClass('pwbf-hidden');
                break;

                case 'fixed':
                    jQuery('#pwbf-fixed-discount-container-pro, #pwbf-use-regular-price-container').removeClass('pwbf-hidden');
                break;

                case 'bogo':
                    jQuery('#pwbf-bogo-container-pro').removeClass('pwbf-hidden');
                    jQuery('#pwbf-bogo-container').removeClass('pwbf-hidden');
                break;
            }
        });

        jQuery('#pwbf-free-shipping').on('change', function(e) {
            jQuery('#pwbf-free-shipping-min-amount-container').toggle(this.checked);
        });

        jQuery('#pwbf-bogo-identical-products-help-link').on('click', function(e) {
            jQuery('#pwbf-bogo-identical-products-help').toggle();
            jQuery(this).blur();
            e.preventDefault();
            return false;
        });

        jQuery('#pwbf-bogo-identical-products').on('change', function() {
            jQuery('#pwbf-bogo-identical-products-help').hide();
            jQuery('#pwbf-bogo-identical-variations-container').toggle(jQuery(this).is(':checked'));
        });
    });

    function pwbfWizardLoadStep<?php echo $pwbf_step; ?>() {

    }

    function pwbfWizardValidateStep<?php echo $pwbf_step; ?>() {
        return true;
    }
</script>
