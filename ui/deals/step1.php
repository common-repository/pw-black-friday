<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php

    pwbfWizardTitle( __( 'Products included in the deal', 'pw-black-friday' ) );

?>
<div class="pwbf-bordered-content">
    <div>
        <label for="pwbf-product-categories-included" style="float: left;"><?php _e( 'Categories', 'pw-black-friday' ); ?></label>
        <div style="float: right;">
            <?php _e( 'Select', 'pw-black-friday' ); ?>
            <a href="#" id="pwbf-product-categories-included-select-all"><?php _e( 'All', 'pw-black-friday' ); ?></a>
            <a href="#" id="pwbf-product-categories-included-select-none" style="margin-left: 6px;"><?php _e( 'None', 'pw-black-friday' ); ?></a>
        </div>
    </div>
    <select id="pwbf-product-categories-included" name="product_categories_included[]" class="pwbf-product-categories" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'pw-black-friday' ); ?>">
        <?php
            $categories = get_terms(array('taxonomy' => 'product_cat', 'orderby' => 'name', 'hide_empty' => false ));

            $category_ids = array();
            if ( isset( $pwbf_deal ) ) {
                $category_ids = $pwbf_deal->product_categories_included;
            }

            if ( $categories ) {
                $sorted = array();
                $pw_black_friday->sort_terms_hierarchicaly( $categories, $sorted );
                $pw_black_friday->hierarchical_select( $sorted, $category_ids );
            }
        ?>
    </select>

    <div style="margin-top: 48px;">
        <label for="pwbf-include-product-ids"><?php _e( 'Include specific products in this deal', 'pw-black-friday' ); ?></label><br>
        <?php
            $include_product_ids = array();
            if ( isset( $pwbf_deal ) ) {
                $include_product_ids = $pwbf_deal->include_product_ids;
            }

            if ( $pw_black_friday->wc_min_version( '2.7' ) ) {
                ?>
                <select id="pwbf-include-product-ids" class="wc-product-search" multiple="multiple" style="width: 50%;" name="include_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'pw-black-friday' ); ?>" data-action="woocommerce_json_search_products_and_variations">
                    <?php
                        foreach ( $include_product_ids as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '" ' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                    ?>
                </select>
                <?php
            } else {
                ?>
                <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="include_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'pw-black-friday' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                    $json_ids = array();

                    foreach ( $include_product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                        }
                    }

                    echo esc_attr( json_encode( $json_ids ) );
                ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
                <?php
            }
        ?>
    </div>
    <div style="margin-top: 48px;">
        <label for="pwbf-exclude-product-ids"><?php _e( 'Exclude specific products from this deal', 'pw-black-friday' ); ?></label><br>
        <?php
            $exclude_product_ids = array();
            if ( isset( $pwbf_deal ) ) {
                $exclude_product_ids = $pwbf_deal->exclude_product_ids;
            }

            if ( $pw_black_friday->wc_min_version( '2.7' ) ) {
                ?>
                <select id="pwbf-exclude-product-ids" class="wc-product-search" multiple="multiple" style="width: 50%;" name="exclude_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'pw-black-friday' ); ?>" data-action="woocommerce_json_search_products_and_variations">
                    <?php
                        foreach ( $exclude_product_ids as $product_id ) {
                            $product = wc_get_product( $product_id );
                            if ( is_object( $product ) ) {
                                echo '<option value="' . esc_attr( $product_id ) . '" ' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
                            }
                        }
                    ?>
                </select>
                <?php
            } else {
                ?>
                <input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="exclude_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'pw-black-friday' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
                    $json_ids = array();

                    foreach ( $exclude_product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) {
                            $json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
                        }
                    }

                    echo esc_attr( json_encode( $json_ids ) );
                ?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
                <?php
            }
        ?>
    </div>
</div>
<script>

    function pwbfWizardLoadStep<?php echo $pwbf_step; ?>() {

    }

    function pwbfWizardValidateStep<?php echo $pwbf_step; ?>() {
        if (!jQuery('#pwbf-product-categories-included').val() || jQuery('#pwbf-product-categories-included').val() == []) {
            if (!jQuery('#pwbf-include-product-ids').val() || jQuery('#pwbf-include-product-ids').val() == []) {
                alert('<?php esc_attr_e( 'Please select at least one Product Category or one Included Product.', 'pw-black-friday' ); ?>');
                jQuery('#pwbf-product-categories-included').focus();
                return false;
            }
        }

        return true;
    }
</script>
