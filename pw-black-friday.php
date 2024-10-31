<?php
/**
 * Plugin Name: Black Friday and Cyber Monday for WooCommerce
 * Plugin URI: https://www.pimwick.com/black-friday/
 * Description: Offer Black Friday and Cyber Monday deals via WooCommerce.
 * Version: 2.1
 * Author: Pimwick, LLC
 * Author URI: https://www.pimwick.com
 * Text Domain: pw-black-friday
 * Domain Path: /languages
 * WC requires at least: 4.0
 * WC tested up to: 9.4
 * Requires Plugins: woocommerce
 */
define( 'PW_BLACK_FRIDAY_VERSION', '2.1' );

/*
Copyright (C) Pimwick, LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or exit;

if ( !function_exists( 'pimwick_define' ) ) :
function pimwick_define( $constant_name, $default_value ) {
    defined( $constant_name ) or define( $constant_name, $default_value );
}
endif;

pimwick_define( 'PW_BLACK_FRIDAY_REQUIRES_PRIVILEGE', 'manage_woocommerce' );
pimwick_define( 'PW_BLACK_FRIDAY_EVENT_POST_TYPE', 'pwbf_event' );
pimwick_define( 'PW_BLACK_FRIDAY_DEAL_POST_TYPE', 'pwbf_deal' );
pimwick_define( 'PWBF_USE_DATE_I18N', true );
pimwick_define( 'PW_BLACK_FRIDAY_BOGO_DISCOUNT_PRICE_INCLUDES_TAX', false );
pimwick_define( 'PW_BLACK_FRIDAY_ALLOW_MULTIPLE_DEALS', false );
pimwick_define( 'PW_BLACK_FRIDAY_PRICE_OVERRIDE_HOOK_PRIORITY', 9 );

// Verify this isn't called directly.
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'PWBF_PLUGIN_FILE', __FILE__ );
define( 'PWBF_PLUGIN_ROOT', plugin_dir_path( PWBF_PLUGIN_FILE ) );

if ( !class_exists( 'PW_Black_Friday' ) ) :

final class PW_Black_Friday {

    public $holidays = array();
    public $countdown_tags = array();
    private $all_events_cache = null;
    private $active_events_cache = null;
    private $active_deals_cache = null;
    private $active_bogo_deals_cache = null;
    private $product_deals_cache = array();
    private $bogo_discounts_cache = null;
    private $use_coupons = true;
    private $price_override_hooks = array();

    function __construct() {
        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );

        // WooCommerce High Performance Order Storage (HPOS) compatibility declaration.
        add_action( 'before_woocommerce_init', function() {
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }
        } );
    }

    function plugins_loaded() {
        load_plugin_textdomain( 'pw-black-friday', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    function woocommerce_init() {
        $this->holidays = apply_filters( 'pwbf_holidays', array(
            'black_friday' => array(
                'title' => __( 'Black Friday', 'pw-black-friday' ),
                'date'  => date( 'Y-m-d', pwbf_strtotime( '+1 day', pwbf_strtotime( 'fourth thursday of november' ) ) ),
                'icon'  => 'tags',
                'color' => '#000000'
            ),
            'cyber_monday' => array(
                'title' => __( 'Cyber Monday', 'pw-black-friday' ),
                'date'  => date( 'Y-m-d', pwbf_strtotime( '+4 days', pwbf_strtotime( 'fourth thursday of november' ) ) ),
                'icon'  => 'laptop',
                'color' => '#BA2633'
            ),
        ) );

        // If WooCommerce does not have Coupons enabled, we can't utilize them.
        if ( 'no' == get_option( 'woocommerce_enable_coupons', 'no' ) ) {
            $this->use_coupons = false;
        }

        add_action( 'init', array( $this, 'register_post_types' ), 9 );

        if ( is_admin() ) {

            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
            add_action( 'wp_ajax_pw-black-friday-save-event', array( $this, 'ajax_save_event' ) );
            add_action( 'wp_ajax_pw-black-friday-save-countdowns', array( $this, 'ajax_save_countdowns' ) );
            add_action( 'wp_ajax_pw-black-friday-delete-event', array( $this, 'ajax_deal_delete_event' ) );
            add_action( 'wp_ajax_pw-black-friday-save-deal', array( $this, 'ajax_save_deal' ) );
            add_action( 'wp_ajax_pw-black-friday-delete-deal', array( $this, 'ajax_deal_delete_deal' ) );
            add_action( 'wp_ajax_pw-black-friday-save-promo', array( $this, 'ajax_save_promo' ) );
            add_filter( 'woocommerce_order_get_items', array( $this, 'woocommerce_order_get_items' ), 10, 2 );
        } else {
            add_filter( 'woocommerce_product_is_on_sale', array( $this, 'woocommerce_product_is_on_sale' ), 10, 2 );
            add_action( 'woocommerce_shipping_free_shipping_is_available', array( $this, 'woocommerce_shipping_free_shipping_is_available' ) );
            add_filter( 'woocommerce_shipping_zone_shipping_methods', array( $this, 'woocommerce_shipping_zone_shipping_methods' ), 10, 4 );
            add_filter( 'woocommerce_quantity_input_args', array( $this, 'woocommerce_quantity_input_args' ), 10, 2 );
            add_filter( 'woocommerce_available_variation', array( $this, 'woocommerce_available_variation' ), 10, 3 );
            add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'woocommerce_add_to_cart_validation' ), 1, 5 );
            add_filter( 'woocommerce_update_cart_validation', array( $this, 'woocommerce_update_cart_validation' ), 1, 4 );
            add_filter( 'woocommerce_variation_prices', array( $this, 'woocommerce_variation_prices' ), 10, 2 );
            add_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 10, 2 );
            add_action( 'get_footer', array( $this, 'output_promos_and_countdowns' ) );
            add_filter( 'woocommerce_shortcode_products_query', array( $this, 'woocommerce_shortcode_products_query' ), 10, 3 );
            add_filter( 'shortcode_atts_products', array( $this, 'shortcode_atts_products' ), 10, 4 );

            if ( true === $this->use_coupons ) {
                add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'woocommerce_get_shop_coupon_data' ), 10, 2 );
                add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_apply_bogo_coupon' ) );
                add_action( 'woocommerce_check_cart_items', array( $this, 'maybe_apply_bogo_coupon' ) );
                add_filter( 'woocommerce_coupon_message', array( $this, 'woocommerce_coupon_message' ), 10, 3 );
                add_filter( 'woocommerce_coupon_error', array( $this, 'woocommerce_coupon_error' ), 10, 3 );
                add_filter( 'woocommerce_coupon_is_valid', array( $this, 'woocommerce_coupon_is_valid' ), 99, 2 );
                add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'woocommerce_cart_totals_coupon_label' ), 10, 2 );
                add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'woocommerce_cart_totals_coupon_html' ), 99, 3 );

                if ( $this->wc_min_version( '3.0' ) ) {
                    add_action( 'woocommerce_new_order_item', array( $this, 'woocommerce_new_order_item' ), 10, 3 );
                } else {
                    add_action( 'woocommerce_order_add_coupon', array( $this, 'woocommerce_order_add_coupon' ), 10, 5 );
                }

            } else {
                add_action( 'woocommerce_cart_calculate_fees' , array( $this, 'woocommerce_cart_calculate_fees' ) );
                add_action( 'woocommerce_cart_contents_total' , array( $this, 'woocommerce_cart_contents_total' ) );
            }

            if ( $this->wc_min_version( '3.0' ) ) {
                $this->price_override_hooks[] = 'woocommerce_product_get_price';
                $this->price_override_hooks[] = 'woocommerce_product_get_sale_price';
                $this->price_override_hooks[] = 'woocommerce_product_variation_get_price';
                $this->price_override_hooks[] = 'woocommerce_product_variation_get_sale_price';
            } else {
                $this->price_override_hooks[] = 'woocommerce_get_price';
                $this->price_override_hooks[] = 'woocommerce_get_sale_price';
                $this->price_override_hooks[] = 'woocommerce_get_variation_price';
                $this->price_override_hooks[] = 'woocommerce_get_variation_sale_price';
            }

            $this->hook_price_overrides();
        }

        add_filter( 'pwbf_to_default_currency', array( $this, 'pwbf_to_default_currency' ) );
    }

    function hook_price_overrides() {
        foreach ( $this->price_override_hooks as $hook ) {
            add_filter( $hook, array( $this, 'override_price' ), PW_BLACK_FRIDAY_PRICE_OVERRIDE_HOOK_PRIORITY, 2 );
        }
    }

    function unhook_price_overrides() {
        foreach ( $this->price_override_hooks as $hook ) {
            remove_filter( $hook, array( $this, 'override_price' ), PW_BLACK_FRIDAY_PRICE_OVERRIDE_HOOK_PRIORITY, 2 );
        }
    }

    function override_price( $value, $product ) {
        if ( empty( $value ) && ! is_a( $product, 'WC_Product_Woosb' ) ) {
            $value = $product->get_regular_price();
        }

        if ( empty( $value ) ) {
            return $value;
        }

        $deals = $this->get_product_deals( $product );
        foreach ( $deals as $deal ) {
            if ( in_array( $deal->deal_type, array( 'percentage', 'fixed' ) ) ) {

                $addon_cost = 0;

                // Patch for "WooCommerce Better Product Add-ons" that add the cost after.
                if ( class_exists( 'WC_Product_Addons' ) && isset( WC()->cart ) && !is_null( WC()->cart ) ) {
                    $this->unhook_price_overrides();

                    foreach ( WC()->cart->get_cart() as $cart_item ) {
                        if ( $cart_item['data'] === $product && isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
                            foreach ( $cart_item['addons'] as $addon ) {
                                if ( $addon['price'] > 0 ) {
                                    $addon_cost += $addon['price'];

                                    if ( isset( $cart_item['addons_price_before_calc'] ) && !empty( $cart_item['addons_price_before_calc'] ) ) {
                                        $value = $cart_item['addons_price_before_calc'];
                                    }
                                }
                            }
                        }
                    }

                    $this->hook_price_overrides();
                }

                // Patch for "WooCommerce TM Extra Product Options" plugin by themeComplete
                if ( defined( 'THEMECOMPLETE_EPO_PLUGIN_FILE' ) ) {
                    $this->unhook_price_overrides();

                    foreach ( WC()->cart->get_cart() as $cart_item ) {
                        if ( $cart_item['data'] !== $product || !isset( $cart_item['tmhasepo'] ) || !$cart_item['tmhasepo'] || !isset( $cart_item['tm_epo_options_prices'] ) ) {
                            continue;
                        }

                        $addon_cost += $cart_item['tm_epo_options_prices'];
                    }

                    $this->hook_price_overrides();
                }

                // Patch for "Advanced Product Fields" by StudioWombat that might add additional costs.
                if ( function_exists( 'wapf' ) && isset( WC()->cart ) && !is_null( WC()->cart ) ) {
                    foreach ( WC()->cart->get_cart() as $cart_item ) {
                        if ( $cart_item['data'] === $product && isset( $cart_item['wapf'] ) && is_array( $cart_item['wapf'] ) ) {
                            foreach ( $cart_item['wapf'] as $addon ) {
                                if ( isset( $addon['price'] ) && is_array( $addon['price'] ) ) {
                                    foreach ( $addon['price'] as $addon_price ) {
                                        $addon_cost += $addon_price['value'];
                                    }
                                }
                            }
                        }
                    }
                }

                if ( !isset( $deal->use_regular_price ) || $deal->use_regular_price ) {
                    if ( !empty( $product->get_regular_price() ) ) {
                        $value = $product->get_regular_price();
                    }
                }

                $discounted_price = $this->get_discounted_price( $deal, $value );

                if ( $discounted_price < $value ) {
                    $value = $discounted_price + $addon_cost;
                }
            }
        }

        return apply_filters( 'pwbf_to_default_currency', $value );
    }

    function pwbf_to_default_currency( $amount ) {
        // WooCommerce Currency Switcher by realmag777
        if ( isset( $GLOBALS['WOOCS'] ) ) {
            $cs = $GLOBALS['WOOCS'];
            $default_currency = false;
            $currencies = $cs->get_currencies();

            foreach ( $currencies as $currency ) {
                if ( $currency['is_etalon'] === 1 ) {
                    $default_currency = $currency;
                    break;
                }
            }

            if ( $default_currency ) {
                if ( $cs->current_currency != $default_currency['name'] ) {
                    $amount = (float) $cs->back_convert( $amount, $currencies[ $cs->current_currency ]['rate'] );
                }
            }
        }

        return $amount;
    }

    function woocommerce_variation_prices( $prices_array, $product ) {
        foreach( $prices_array['price'] as $variation_id => $price ) {
            $variation = wc_get_product( $variation_id );

            $deals = $this->get_product_deals( $variation );
            foreach ( $deals as $deal ) {
                if ( in_array( $deal->deal_type, array( 'percentage', 'fixed' ) ) ) {

                    if ( !isset( $deal->use_regular_price ) || $deal->use_regular_price ) {
                        $value = $prices_array['regular_price'][ $variation_id ];
                    } else {
                        $value = $price;
                    }

                    $discounted_price = $this->get_discounted_price( $deal, $value );
                    if ( $discounted_price < $price ) {
                        $prices_array['price'][ $variation_id ] = $discounted_price;
                        $prices_array['sale_price'][ $variation_id ] = $discounted_price;
                    }
                }
            }

        }

        return $prices_array;
    }

    function woocommerce_get_price_html( $price_html, $product ) {
        global $pw_black_friday;
        global $pwbf_deal;
        global $pwbf_price;

        if ( is_product() && !is_a( $product, 'WC_Product_Variation' ) ) {
            $deals = $this->get_product_deals( $product );
            foreach ( $deals as $deal ) {
                if ( false !== boolval( $deal->show_expiration ) ) {
                    $pwbf_deal = $deal;
                    $pwbf_price = $price_html;

                    $price_html = wc_get_template_html( 'pw-black-friday/sale-price-html.php', '', '', PWBF_PLUGIN_ROOT . 'templates/woocommerce/' );

                    $price_html = apply_filters( 'pwbf_price_html', $price_html, $pwbf_price, $pwbf_deal );

                    break;
                }
            }
        }

        return $price_html;
    }

    function get_product_deals( $product ) {
        $deals = array();

        if ( $this->wc_min_version( '3.0' ) ) {
            $parent_id = $product->get_parent_id() > 0 ? $product->get_parent_id() : $product->get_id();
            $product_id = $product->get_id();
        } else {
            if ( !empty( $product->variation_id ) ) {
                $parent_id = $product->id;
                $product_id = $product->variation_id;
            } else {
                $parent_id = !empty( $product->parent_id ) ? $product->parent_id : $product->id;;
                $product_id = $product->id;
            }
        }

        if ( !isset( $this->product_deals_cache[ $product_id ] ) ) {

            foreach ( $this->get_active_deals() as $deal ) {
                $product_included = false;

                if ( is_array( $deal->product_categories_included ) && count( $deal->product_categories_included ) > 0 ) {
                    // This is inside the loop in case there are no active deals. No need to query product_cat_ids unnecessarily.
                    if ( !isset( $product_categories ) ) {
                        $product_categories = wc_get_product_cat_ids( $parent_id );
                    }

                    // Included by category.
                    if ( count( array_intersect( $product_categories, $deal->product_categories_included ) ) > 0 ) {
                        $product_included = true;
                    }
                }

                if ( is_array( $deal->include_product_ids ) && ( in_array( $product_id, $deal->include_product_ids ) || in_array( $parent_id, $deal->include_product_ids ) ) ) {
                    $product_included = true;
                }

                if ( true === $product_included && is_array( $deal->exclude_product_ids ) ) {
                    // Ignore excluded products or variations.
                    if ( in_array( $product_id, $deal->exclude_product_ids ) || in_array( $parent_id, $deal->exclude_product_ids ) ) {
                        $product_included = false;
                    }
                }

                if ( true === $product_included ) {
                    $deals[] = $deal;

                    if ( ! PW_BLACK_FRIDAY_ALLOW_MULTIPLE_DEALS ) {
                        break;
                    }
                }
            }

            // Clear out any null or empty deal objects.
            $deals = array_filter( $deals );

            $this->product_deals_cache[ $product_id ] = $deals;
        }

        return $this->product_deals_cache[ $product_id ];
    }

    function get_discounted_price( $deal, $price ) {
        if ( !empty( $price ) ) {
            switch ( $deal->deal_type ) {
                case 'percentage':
                    $decimals = apply_filters( 'wc_get_price_decimals', get_option( 'woocommerce_price_num_decimals', 2 ) );
                    $price = round( $price * 0.9, $decimals );
                break;

                case 'fixed':
                    $price = max( 0, $price - 10 );
                break;
            }
        }

        return apply_filters( 'pwbf_discounted_price', $price, $deal );
    }

    function woocommerce_product_is_on_sale( $is_on_sale, $product ) {
        $deals = $this->get_product_deals( $product );
        foreach ( $deals as $deal ) {
            if ( in_array( $deal->deal_type, array( 'percentage', 'fixed' ) ) ) {
                $is_on_sale = true;
                break;
            }
        }

        return $is_on_sale;
    }

    function offer_free_shipping() {
        foreach ( $this->get_active_deals() as $deal ) {
            if ( $deal->free_shipping && isset( WC()->cart->cart_contents_total ) ) {
                $total = WC()->cart->get_displayed_subtotal();

                // Base the discount on the "Prices entered with tax" setting rather
                // than the Display Tax setting. Can be disabled by defining PW_BLACK_FRIDAY_BOGO_DISCOUNT_IGNORE_NEW_TAX_LOGIC
                if ( !defined( 'PW_BLACK_FRIDAY_BOGO_DISCOUNT_IGNORE_NEW_TAX_LOGIC' ) ) {
                    if ( 'yes' == get_option( 'woocommerce_prices_include_tax', 'no' ) ) {
                        $total = $total - ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );
                    } else {
                        $total = $total - WC()->cart->get_cart_discount_total();
                    }
                } else {
                    if ( 'incl' === WC()->cart->tax_display_cart ) {
                        $total = $total - ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );
                    } else {
                        $total = $total - WC()->cart->get_cart_discount_total();
                    }
                }

                if ( $total >= $deal->free_shipping_min_amount ) {
                    return true;
                }
            }
        }

        return false;
    }

    function woocommerce_shipping_free_shipping_is_available( $is_available ) {
        if ( !$is_available ) {
            $is_available = $this->offer_free_shipping();
        }

        return $is_available;
    }

    function woocommerce_shipping_zone_shipping_methods( $methods, $raw_methods, $allowed_classes, $zone ) {

        if ( !is_array( $methods ) || count( $methods ) == 0 ) {
            return $methods;
        }

        // In case we need Free Shipping, let's make sure it's enabled for this Zone.
        if ( isset( $allowed_classes['free_shipping'] ) ) {
            $free_shipping_class_name = $allowed_classes['free_shipping'];

            foreach ( $methods as $method ) {
                if ( is_a( $method, $free_shipping_class_name ) ) {
                    // Free Shipping is already enabled for this zone.
                    return $methods;
                }
            }

            // Now that we know Free Shipping is disabled, let's see if we really care after all.
            if ( $this->offer_free_shipping() ) {

                // If we get here, that means Free Shipping has been disabled for this zone. We need to temporarily activate it.
                $instance_id = max( array_keys( $methods ) ) + 1;
                $free_shipping = new $free_shipping_class_name( $instance_id );

                if ( is_object( $free_shipping ) ) {
                    $free_shipping->method_order  = 1;
                    $free_shipping->enabled       = 'yes';
                    $free_shipping->has_settings  = $free_shipping->has_settings();
                    $free_shipping->settings_html = $free_shipping->supports( 'instance-settings-modal' ) ? $free_shipping->get_admin_options_html() : false;

                    // Insert it at the top.
                    $methods = array( $instance_id => $free_shipping ) + $methods;
                }
            }
        }

        return $methods;
    }

    function woocommerce_quantity_input_args( $args, $product ) {
        $deals = $this->get_product_deals( $product );
        foreach ( $deals as $deal ) {

            // We don't restrict quantities for BOGO, the restriction comes from the calculated discount.
            if ( $deal->deal_type == 'bogo' ) { continue; }

            $order_limit = absint( $deal->order_limit );
            if ( empty( $order_limit ) ) {
                continue;
            }

            $args['max_value'] = min( $args['max_value'], $order_limit );
        }

        if ( $product->managing_stock() && !$product->backorders_allowed() ) {
            $stock = $product->get_stock_quantity();
            $args['max_value'] = min( $stock, $args['max_value'] );
        }

        return $args;
    }

    function woocommerce_available_variation( $args, $product, $variation ) {
        $deals = $this->get_product_deals( $product );
        foreach ( $deals as $deal ) {

            // We don't restrict quantities for BOGO, the restriction comes from the calculated discount.
            if ( $deal->deal_type == 'bogo' ) { continue; }

            $order_limit = absint( $deal->order_limit );
            if ( empty( $order_limit ) ) {
                continue;
            }

            $args['max_value'] = min( absint( $args['max_value'] ), $order_limit );
        }

        if ( $variation->managing_stock() && !$variation->backorders_allowed() ) {
            $stock = $variation->get_stock_quantity();
            $args['max_qty'] = min( absint( $args['max_qty'] ), $stock );
        }

        return $args;
    }

    function woocommerce_add_to_cart_validation( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) {
        $product = wc_get_product( $product_id );
        if ( !$product ) {
            return $passed;
        }

        $deals = $this->get_product_deals( $product );
        foreach ( $deals as $deal ) {

            // We don't restrict quantities for BOGO, the restriction comes from the calculated discount.
            if ( $deal->deal_type == 'bogo' ) { continue; }

            $order_limit = absint( $deal->order_limit );
            if ( empty( $order_limit ) ) {
                continue;
            }

            $quantity_in_cart = $this->get_cart_quantity( $deal );
            $product_title = $product->get_title();

            if ( !empty( $quantity_in_cart ) ) {
                if ( ( $quantity_in_cart + $quantity ) > $order_limit ) {
                    if ( class_exists( 'WooCommerce_Direct_Checkout' ) ) {
                        $direct_checkout = get_option( 'direct_checkout_enabled' );
                        $direct_checkout_url = get_option( 'direct_checkout_cart_redirect_url' );
                        if ( $direct_checkout && $direct_checkout_url ) {
                            wp_safe_redirect( esc_url_raw( $direct_checkout_url ) );
                            exit();
                        }
                    }

                    $passed = false;
                    $this->add_order_limit_notice( $deal );
                    break;
                }
            } else {
                if ( $quantity > $order_limit ) {
                    $passed = false;
                    $this->add_order_limit_notice( $deal );
                    break;
                }
            }
        }

        return $passed;
    }

    function woocommerce_update_cart_validation( $passed, $cart_item_key, $values, $quantity ) {
        $product_id = $values['product_id'];
        $product = wc_get_product( $product_id );
        if ( !$product ) {
            return $passed;
        }

        $deals = $this->get_product_deals( $product );
        foreach ( $deals as $deal ) {

            // We don't restrict quantities for BOGO, the restriction comes from the calculated discount.
            if ( $deal->deal_type == 'bogo' ) { continue; }

            $order_limit = absint( $deal->order_limit );
            if ( empty( $order_limit ) ) {
                continue;
            }

            $quantity_in_cart = $this->get_cart_quantity( $deal );
            $product_title = $product->get_title();

            if ( $quantity > $order_limit ) {
                $passed = false;
                $this->add_order_limit_notice( $deal );
                break;
            }
        }

        return $passed;
    }

    function add_order_limit_notice( $deal ) {
        $order_limit = absint( $deal->order_limit );
        $event = get_post( $deal->post_parent );
        wc_add_notice( sprintf( __( 'Limit %s discounted items per order. <a href="%s">View your cart</a>.', 'pw-black-friday' ), "$order_limit {$event->post_title}", esc_url( wc_get_cart_url() ) ), 'error' );
    }

    function woocommerce_get_shop_coupon_data( $data, $code ) {
        if ( empty( $code ) || empty( WC()->cart ) ) {
            return $data;
        }

        $discounts = $this->get_bogo_discounts( WC()->cart );

        foreach ( $this->get_active_deals( true ) as $deal ) {
            if ( $this->is_deal_coupon( $code, $deal ) ) {
                $event = get_post( $deal->post_parent );
                $amount = isset( $discounts[ $deal->ID ] ) ? $discounts[ $deal->ID ] : 0;

                // Creates a virtual coupon
                $data = array(
                    'id' => -1,
                    'code' => $code,
                    'description' => $event->post_title,
                    'amount' => $amount,
                    'coupon_amount' => $amount
                );
                break;
            }
        }

        return $data;
    }

    function woocommerce_order_get_items( $items, $order ) {
        foreach ( $items as $order_item_id => &$order_item ) {
            if ( $order_item->get_type() == 'coupon' && !empty( $order_item->get_meta( 'pwbf_deal_id' ) ) ) {
                $deal_id = is_array( $order_item->get_meta( 'pwbf_deal_id' ) ) ? $order_item->get_meta( 'pwbf_deal_id' )[0] : $order_item->get_meta( 'pwbf_deal_id' );
                $deal = get_post( absint( $deal_id ) );
                $event = get_post( $deal->post_parent );
                $order_item->set_name( $event->post_title . ' - ' . $deal->post_title );
            }
        }
        return $items;
    }

    function output_promos_and_countdowns( $post_object ) {
        if ( is_front_page() ) {
            wc_get_template( 'pw-black-friday/promo.php', '', '', PWBF_PLUGIN_ROOT . 'templates/woocommerce/' );
        }

        wc_get_template( 'pw-black-friday/countdown.php', '', '', PWBF_PLUGIN_ROOT . 'templates/woocommerce/' );
    }

    function get_cart_quantity( $deal ) {

        $quantity = 0;
        foreach( WC()->cart->get_cart() as $values ) {
            $product = wc_get_product( $values['product_id'] );
            $product_deals = $this->get_product_deals( $product );
            foreach ( $product_deals as $product_deal ) {
                if ( $product_deal->ID == $deal->ID ) {
                    $quantity += absint( $values['quantity'] );
                }
            }
        }

        return $quantity;
    }

    function register_post_types() {
        if ( !post_type_exists( PW_BLACK_FRIDAY_EVENT_POST_TYPE ) ) {
            register_post_type( PW_BLACK_FRIDAY_EVENT_POST_TYPE );
        }

        if ( !post_type_exists( PW_BLACK_FRIDAY_DEAL_POST_TYPE ) ) {
            register_post_type( PW_BLACK_FRIDAY_DEAL_POST_TYPE );
        }
    }

    function admin_menu() {
        if ( empty ( $GLOBALS['admin_page_hooks']['pimwick'] ) ) {
            add_menu_page(
                'Black Friday Deals',
                'Pimwick Plugins',
                PW_BLACK_FRIDAY_REQUIRES_PRIVILEGE,
                'pimwick',
                array( $this, 'index' ),
                plugins_url( '/assets/images/pimwick-icon-120x120.png', __FILE__ ),
                6
            );

            add_submenu_page(
                'pimwick',
                'Black Friday Deals',
                'Pimwick Plugins',
                PW_BLACK_FRIDAY_REQUIRES_PRIVILEGE,
                'pimwick',
                array( $this, 'index' )
            );

            remove_submenu_page( 'pimwick', 'pimwick' );
        }

        add_submenu_page(
            'pimwick',
            'Black Friday Deals',
            'Black Friday Deals',
            PW_BLACK_FRIDAY_REQUIRES_PRIVILEGE,
            'pw-black-friday',
            array( $this, 'index' )
        );
    }

    function index() {
        global $pw_black_friday;

        echo '<div id="pwbf-container">';

            if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit_promo' && isset( $_REQUEST['event_id'] ) && absint( $_REQUEST['event_id'] ) > 0 ) {
                $event = $this->get_event( absint( $_REQUEST['event_id'] ) );
                if ( $event->post_type == PW_BLACK_FRIDAY_EVENT_POST_TYPE ) {
                    require( 'ui/edit-promo.php' );
                    return;
                }
            }

            if ( isset( $_REQUEST['event_id'] ) ) {
                $event_id = absint( $_REQUEST['event_id'] );
                $pwbf_event = $this->get_event( $event_id );
            }

            if ( isset( $_REQUEST['deal_id'] ) ) {
                $deal_id = absint( $_REQUEST['deal_id'] );
                $pwbf_deal = $this->get_deal( $deal_id );
            }

            require( 'ui/index.php' );

        echo '</div>';
    }

    function field_required( $value, $error_message, $step = '' ) {
        if ( empty( $value ) ) {
            $this->field_error( $error_message, $step );
        }
    }

    function field_error( $message, $step = '' ) {
        $result['complete'] = false;
        $result['message'] = $message;
        if ( !empty( $step ) ) {
            $result['step'] = $step;
        }

        wp_send_json( $result );
    }

    function ajax_save_event() {
        if ( !isset( $_REQUEST['form'] ) ) {
            wp_die( __( 'Invalid query string.', 'pw-black-friday' ) );
        }

        check_ajax_referer( 'pw-black-friday-save-event', 'security' );

        $form = array();
        parse_str( $_REQUEST['form'], $form );

        $event_id                               = absint( $form['event_id'] );
        $title                                  = stripslashes( wc_clean( $form['title'] ) );
        $begin_date                             = wc_clean( $form['begin_date'] );
        $begin_time                             = wc_clean( $form['begin_time'] );
        $end_date                               = wc_clean( $form['end_date'] );
        $end_time                               = wc_clean( $form['end_time'] );
        $begin_datetime_string                  = "$begin_date $begin_time";
        $end_datetime_string                    = "$end_date $end_time";

        //
        // BEGIN VALIDATION
        //
        $this->field_required( $title, __( 'Title is required.', 'pw-black-friday' ) );
        $this->field_required( $begin_date, __( 'Begin Date is required.', 'pw-black-friday' ) );
        $this->field_required( $begin_time, __( 'Begin Time is required.', 'pw-black-friday' ) );
        $this->field_required( $end_date, __( 'End Date is required.', 'pw-black-friday' ) );
        $this->field_required( $end_time, __( 'End Time is required.', 'pw-black-friday' ) );
        if ( false === pwbf_strtotime( $begin_datetime_string ) ) {
            $this->field_error( sprintf( __( '%s is not a valid date and time.', 'pw-black-friday' ), $begin_datetime_string ) );
        }
        if ( false === pwbf_strtotime( $end_datetime_string ) ) {
            $this->field_error( sprintf( __( '%s is not a valid date and time.', 'pw-black-friday' ), $end_datetime_string ) );
        }
        if ( pwbf_strtotime( $begin_datetime_string ) >= pwbf_strtotime( $end_datetime_string ) ) {
            $this->field_error( __( 'Begin date must come before End date.', 'pw-black-friday' ) );
        }
        //
        // END VALIDATION
        //

        $begin_datetime = pwbf_strtotime($begin_datetime_string);
        $end_datetime   = pwbf_strtotime($end_datetime_string);

        // Updating an event
        if ( !empty( $event_id ) ) {
            $event = get_post( $event_id );
            if ( $event && $event->post_type == PW_BLACK_FRIDAY_EVENT_POST_TYPE ) {
                $event->post_title = $title;
                wp_update_post( $event );
            } else {
                unset( $event );
            }
        }

        // Adding an event
        if ( !isset( $event ) ) {
            $event = array();
            $event['post_type'] = PW_BLACK_FRIDAY_EVENT_POST_TYPE;
            $event['post_status'] = 'publish';
            $event['post_title'] = $title;
            $event_id = wp_insert_post( $event );
        }

        if ( !empty( $event_id ) && !is_wp_error( $event_id ) ) {
            update_post_meta( $event_id, 'begin_datetime', $begin_datetime );
            update_post_meta( $event_id, 'end_datetime', $end_datetime );

            $result['complete'] = true;
            wp_send_json( $result );

        } else {
            $this->field_error( $event_id->get_error_message() );
        }
    }

    function ajax_save_countdowns() {
        if ( !isset( $_REQUEST['form'] ) ) {
            wp_die( __( 'Invalid query string.', 'pw-black-friday' ) );
        }

        check_ajax_referer( 'pw-black-friday-save-countdowns', 'security' );

        $form = array();
        parse_str( $_REQUEST['form'], $form );

        $event_id               = absint( $form['event_id'] );
        $upcoming_countdown     = stripslashes( $form['upcoming_countdown'] );
        $upcoming_offset        = $form['upcoming_offset'];
        $ending_countdown       = stripslashes( $form['ending_countdown'] );

        update_post_meta( $event_id, 'upcoming_countdown', $upcoming_countdown );
        update_post_meta( $event_id, 'upcoming_offset', $upcoming_offset );
        update_post_meta( $event_id, 'ending_countdown', $ending_countdown );

        wp_send_json( array( 'complete' => true ) );
    }

    function ajax_deal_delete_event() {
        check_ajax_referer( 'pw-black-friday-delete-event', 'security' );

        $event_id = absint( $_POST['event_id'] );
        $event = get_post( $event_id );
        if ( $event && $event->post_type == PW_BLACK_FRIDAY_EVENT_POST_TYPE ) {

            $deals = get_posts( array(
                'post_parent' => $event->ID,
                'post_type' => PW_BLACK_FRIDAY_DEAL_POST_TYPE,
                'nopaging' => true
            ) );
            foreach ( $deals as $deal ) {
                wp_delete_post( $deal->ID, true );
            }

            wp_delete_post( $event_id, true );
        }
        wp_die();
    }

    function ajax_save_deal() {
        if ( !isset( $_REQUEST['form'] ) ) {
            wp_die('Invalid query string.');
        }

        check_ajax_referer( 'pw-black-friday-save-deal', 'security' );

        $form = array();
        parse_str( $_REQUEST['form'], $form );

        if ( !$this->wc_min_version( '3.0' ) ) {
            if ( is_array( $form['include_product_ids'] ) ) {
                $form['include_product_ids'] = explode( ',', $form['include_product_ids'][0] );
            }
            if ( is_array( $form['exclude_product_ids'] ) ) {
                $form['exclude_product_ids'] = explode( ',', $form['exclude_product_ids'][0] );
            }
        }

        $deal_id                                = absint( $form['deal_id'] );
        $event_id                               = absint( $form['event_id'] );

        $product_categories_included            = isset( $form['product_categories_included'] ) && is_array( $form['product_categories_included'] ) ? array_map( 'intval', $form['product_categories_included'] ) : array();
        $include_product_ids                    = isset( $form['include_product_ids'] ) && is_array( $form['include_product_ids'] ) ? array_map( 'intval', $form['include_product_ids'] ) : array();
        $exclude_product_ids                    = isset( $form['exclude_product_ids'] ) && is_array( $form['exclude_product_ids'] ) ? array_map( 'intval', $form['exclude_product_ids'] ) : array();
        $deal_type                              = $form['deal_type'];
        $use_regular_price                      = isset( $form['use_regular_price'] ) ? true : false;
        $bogo_identical_products                = isset( $form['bogo_identical_products'] ) ? true : false;
        $bogo_identical_variations              = isset( $form['bogo_identical_variations'] ) ? true : false;
        $free_shipping                          = isset( $form['free_shipping'] ) ? true : false;
        $free_shipping_min_amount               = wc_format_decimal( $form['free_shipping_min_amount'] );
        $order_limit                            = absint( $form['order_limit'] ) > 0 ? absint( $form['order_limit'] ) : '';
        $coupon_code                            = isset( $form['coupon_code'] ) ? trim( $form['coupon_code'] ) : '';
        $individual_use                         = isset( $form['individual_use'] ) ? true : false;
        $individual_use_message                 = isset( $form['individual_use_message'] ) ? trim( $form['individual_use_message'] ) : '';
        $title                                  = stripslashes( wc_clean( $form['title'] ) );
        $show_expiration                        = isset( $form['show_expiration'] ) ? true : false;

        //
        // BEGIN VALIDATION
        //
        $this->field_required( $event_id, 'Event ID is required.', 1 );
        if ( count( $product_categories_included ) == 0 && count( $include_product_ids ) == 0 ) {
            $this->field_error( __( 'Select at least 1 Product Category or 1 Included Product.', 'pw-black-friday' ), 1 );
        }
        if ( $free_shipping_min_amount < 0 ) {
            $this->field_error( __( 'Free Shipping Minimum Amount must be greater than zero.', 'pw-black-friday' ), 2 );
        }

        $this->field_required( $title, __( 'Title is required.', 'pw-black-friday' ), 4 );
        //
        // END VALIDATION
        //

        // Updating a deal
        if ( !empty( $deal_id ) ) {
            $deal = get_post( $deal_id );

            if ( $deal && $deal->post_type == PW_BLACK_FRIDAY_DEAL_POST_TYPE ) {
                $deal->post_title = $title;
                wp_update_post( $deal );
            } else {
                unset( $deal );
            }
        }

        // Adding a deal
        if ( !isset( $deal ) ) {
            $deal = array();
            $deal['post_type'] = PW_BLACK_FRIDAY_DEAL_POST_TYPE;
            $deal['post_status'] = 'publish';
            $deal['post_title'] = $title;
            $deal['post_parent'] = $event_id;

            $deal_id = wp_insert_post( $deal );
        }

        if ( !empty( $deal_id ) && !is_wp_error( $deal_id ) ) {
            update_post_meta( $deal_id, 'product_categories_included', $product_categories_included );
            update_post_meta( $deal_id, 'include_product_ids', $include_product_ids );
            update_post_meta( $deal_id, 'exclude_product_ids', $exclude_product_ids );
            update_post_meta( $deal_id, 'deal_type', $deal_type );
            update_post_meta( $deal_id, 'use_regular_price', $use_regular_price );
            update_post_meta( $deal_id, 'bogo_identical_products', $bogo_identical_products );
            update_post_meta( $deal_id, 'bogo_identical_variations', $bogo_identical_variations );
            update_post_meta( $deal_id, 'free_shipping', $free_shipping );
            update_post_meta( $deal_id, 'free_shipping_min_amount', $free_shipping_min_amount );
            update_post_meta( $deal_id, 'order_limit', $order_limit );
            update_post_meta( $deal_id, 'coupon_code', $coupon_code );
            update_post_meta( $deal_id, 'individual_use', $individual_use );
            update_post_meta( $deal_id, 'individual_use_message', $individual_use_message );
            update_post_meta( $deal_id, 'show_expiration', $show_expiration );

            $result['complete'] = true;
            wp_send_json( $result );

        } else {
            $this->field_error( $deal_id->get_error_message(), 1 );
        }
    }

    function ajax_deal_delete_deal() {
        check_ajax_referer( 'pw-black-friday-delete-deal', 'security' );

        $deal_id = absint( $_POST['deal_id'] );
        $deal = get_post( $deal_id );
        if ( $deal && $deal->post_type == PW_BLACK_FRIDAY_DEAL_POST_TYPE ) {
            wp_delete_post( $deal_id, true );
        }
        wp_die();
    }

    function ajax_save_promo() {
        $event = get_post( absint( $_POST['event_id'] ) );
        if ( !$event || $event->post_type != PW_BLACK_FRIDAY_EVENT_POST_TYPE ) {
            wp_send_json( array( 'message' => __( 'Invalid Event ID', 'pw-black-friday' ) . ' ' ) );
        }

        check_ajax_referer( 'pw-black-friday-save-promo', 'security' );

        $event->post_content = stripslashes( $_POST['content'] );
        wp_update_post( $event );

        wp_send_json( array( 'message' => 'success' ) );
    }

    function admin_enqueue_scripts( $hook ) {
        global $pwbf_last_step;

        if ( !empty( $hook ) && substr( $hook, -strlen( 'pw-black-friday' ) ) === 'pw-black-friday' ) {
            wp_register_style( 'jquery-ui-style', $this->relative_url( '/assets/css/jquery-ui-style.min.css' ), array(), PW_BLACK_FRIDAY_VERSION );
            wp_enqueue_style( 'jquery-ui-style' );

            wp_enqueue_script( 'jquery-ui-datepicker' );

            wp_enqueue_script( 'wc-admin-meta-boxes' );
            wp_enqueue_style( 'woocommerce_admin_styles' );

            wp_register_style( 'pw-black-friday-font-awesome', $this->relative_url( '/assets/css/font-awesome.min.css' ), array(), PW_BLACK_FRIDAY_VERSION );
            wp_enqueue_style( 'pw-black-friday-font-awesome' );

            wp_register_style( 'pw-black-friday-style', $this->relative_url( '/assets/css/style.css' ), array( 'woocommerce_admin_styles' ), PW_BLACK_FRIDAY_VERSION );
            wp_enqueue_style( 'pw-black-friday-style' );

            wp_enqueue_script( 'pw-black-friday', $this->relative_url( '/assets/js/script.js' ), array( 'wc-admin-meta-boxes', 'jquery' ), PW_BLACK_FRIDAY_VERSION );

            $admin_url_event_id = isset( $_REQUEST['event_id'] ) ? '&event_id=' . absint( $_REQUEST['event_id'] ) : '';
            $admin_url_deal_id  = isset( $_REQUEST['deal_id'] ) ? '&deal_id=' . absint( $_REQUEST['deal_id'] ) : '';

            $pwbf_step = 1;
            while ( file_exists( dirname( __FILE__ ) . '/ui/deals/step' . $pwbf_step . '.php' ) ) {
                $pwbf_last_step = $pwbf_step;
                $pwbf_step++;
            }

            wp_localize_script( 'pw-black-friday', 'pwbf', array(
                'admin_url' => admin_url( 'admin.php?page=pw-black-friday' . $admin_url_event_id . $admin_url_deal_id ),
                'admin_url_root' => admin_url( 'admin.php?page=pw-black-friday' ),
                'holidays' => $this->holidays,
                'last_step' => $pwbf_last_step,
                'nonces' => array(
                    'save_event' => wp_create_nonce( 'pw-black-friday-save-event' ),
                    'save_deal' => wp_create_nonce( 'pw-black-friday-save-deal' ),
                    'save_countdowns' => wp_create_nonce( 'pw-black-friday-save-countdowns' ),
                    'save_promo' => wp_create_nonce( 'pw-black-friday-save-promo' ),
                    'delete_event' => wp_create_nonce( 'pw-black-friday-delete-event' ),
                    'delete_deal' => wp_create_nonce( 'pw-black-friday-delete-deal' ),
                ),
                'i18n' => array(
                    'mon_jan' => __( 'January', 'pw-black-friday' ),
                    'mon_feb' => __( 'February', 'pw-black-friday' ),
                    'mon_mar' => __( 'March', 'pw-black-friday' ),
                    'mon_apr' => __( 'April', 'pw-black-friday' ),
                    'mon_may' => __( 'May', 'pw-black-friday' ),
                    'mon_jun' => __( 'June', 'pw-black-friday' ),
                    'mon_jul' => __( 'July', 'pw-black-friday' ),
                    'mon_aug' => __( 'August', 'pw-black-friday' ),
                    'mon_sep' => __( 'September', 'pw-black-friday' ),
                    'mon_oct' => __( 'October', 'pw-black-friday' ),
                    'mon_nov' => __( 'November', 'pw-black-friday' ),
                    'mon_dec' => __( 'December', 'pw-black-friday' ),
                    'error' => __( 'Error', 'pw-black-friday' ),
                    'discard_changes' => __( 'Discard changes?', 'pw-black-friday' ),
                    'activating' => __( 'Activating, please wait...', 'pw-black-friday' ),
                    'confirm_delete_event' => __( 'Are you sure you want to delete this event? This cannot be undone.', 'pw-black-friday' ),
                    'confirm_delete_deal' => __( 'Are you sure you want to delete this deal? This cannot be undone.', 'pw-black-friday' ),
                    'countdowns' => __( 'Countdowns', 'pw-black-friday' ),
                ),
            ) );
        }

        wp_register_style( 'pw-black-friday-icon', $this->relative_url( '/assets/css/icon-style.css' ), array(), PW_BLACK_FRIDAY_VERSION );
        wp_enqueue_style( 'pw-black-friday-icon' );
    }

    function relative_url( $url ) {
        return plugins_url( $url, __FILE__ );
    }

    function wc_min_version( $version ) {
        return version_compare( WC()->version, $version, ">=" );
    }

    /**
     * Source: http://wordpress.stackexchange.com/questions/14652/how-to-show-a-hierarchical-terms-list
     * Recursively sort an array of taxonomy terms hierarchically. Child categories will be
     * placed under a 'children' member of their parent term.
     * @param Array   $cats     taxonomy term objects to sort
     * @param Array   $into     result array to put them in
     * @param integer $parentId the current parent ID to put them in
     */
    function sort_terms_hierarchicaly( array $cats, array &$into, $parentId = 0 ) {
        foreach ( $cats as $i => $cat ) {
            if ( $cat->parent == $parentId ) {
                $into[$cat->term_id] = $cat;
            }
        }

        foreach ( $into as $topCat ) {
            $topCat->children = array();
            $this->sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
        }
    }

    function hierarchical_select( $categories, $selected_category_ids, $level = 0, $parent = NULL, $prefix = '' ) {
        foreach ( $categories as $category ) {
            $selected = selected( in_array( $category->term_id, $selected_category_ids ), true, false );
            echo "<option value='" . esc_attr( $category->term_id ) . "' $selected>$prefix " . esc_html( $category->name ) . "</option>\n";

            if ( $category->parent == $parent ) {
                $level = 0;
            }

            if ( count( $category->children ) > 0 ) {
                echo $this->hierarchical_select( $category->children, $selected_category_ids, ( $level + 1 ), $category->parent, "$prefix " . esc_html( $category->name ) . " &#8594;" );
            }
        }
    }

    function hierarchical_string( $categories, $selected_category_ids, $level = 0, $parent = NULL, $prefix = '' ) {
        foreach ( $categories as $category ) {
            if ( in_array( $category->term_id, $selected_category_ids ) ) {
                echo $prefix . ' ' . esc_html( $category->name ) . "<br>\n";
            }

            if ( $category->parent == $parent ) {
                $level = 0;
            }

            if ( count( $category->children ) > 0 ) {
                echo $this->hierarchical_string( $category->children, $selected_category_ids, ( $level + 1 ), $category->parent, "$prefix " . esc_html( $category->name ) . " &#8594;" );
            }
        }
    }

    function local_date_and_time( $datetime, $date_format = '', $time_format = '' ) {
        if ( empty( $date_format ) ) {
            $date_format = wc_date_format();
        }

        if ( empty( $time_format ) ) {
            $time_format = wc_time_format();
        }

        return $this->local_date( $datetime, $date_format ) . ' ' . $this->local_time( $datetime, $time_format );
    }

    function local_date( $datetime, $date_format = '' ) {
        if ( empty( $date_format ) ) {
            $date_format = wc_date_format();
        }

        return $this->get_local_date_in_format( $date_format, $datetime );
    }

    function local_time( $datetime, $time_format = '' ) {
        if ( empty( $time_format ) ) {
            $time_format = wc_time_format();
        }

        return $this->get_local_date_in_format( $time_format, $datetime );
    }

    function get_local_date_in_format( $format, $datetime ) {
        $timezone = new DateTimeZone( wc_timezone_string() );
        if ( is_numeric( $datetime ) ) {
            $dt = new DateTime( "@{$datetime}" );
            $dt->setTimezone( $timezone );
        } else {
            $dt = new DateTime( $datetime, $timezone );
        }

        $timestamp = $dt->getTimestamp() + $dt->getOffset();
        if ( PWBF_USE_DATE_I18N ) {
            return date_i18n( $format, $timestamp );
        } else {
            return date( $format, $timestamp );
        }
    }

    function get_event( $event, $get_deals = false ) {
        if ( is_numeric( $event ) ) {
            $event = get_post( absint( $event ) );
            if ( !$event || $event->post_type != PW_BLACK_FRIDAY_EVENT_POST_TYPE ) {
                wp_die( __( 'Invalid event parameter for get_event()', 'pw-black-friday' ) );
            }

        } elseif ( !$event instanceof WP_Post ) {
            wp_die( sprintf( __( '%s is not a valid type for get_event().', 'pw-black-friday' ), gettype( $event ) ) );
        }

        $event->begin_date = $this->get_local_date_in_format( 'Y-m-d', $event->begin_datetime );
        $event->begin_time = $this->local_time( $event->begin_datetime );
        $event->end_date = $this->get_local_date_in_format( 'Y-m-d', $event->end_datetime );
        $event->end_time = $this->local_time( $event->end_datetime );

        if ( true === $get_deals ) {
            $event->deals = $this->get_deals( $event->ID );
        }

        return $event;
    }

    function get_all_events() {
        if ( is_null( $this->all_events_cache ) ) {
            $this->all_events_cache = array();

            $events = array();

            $event_posts = get_posts( array(
                'post_type' => PW_BLACK_FRIDAY_EVENT_POST_TYPE,
                'nopaging' => true,
                'post_status' => 'publish'
            ) );

            foreach ( $event_posts as &$event ) {
                $this->all_events_cache[] = $this->get_event( $event, true );
            }

            usort( $this->all_events_cache, array( $this, 'sort_events_by_date' ) );

        }

        return $this->all_events_cache;
    }

    function get_active_events() {
        if ( is_null( $this->active_events_cache ) ) {
            $this->active_events_cache = array();

            $all_events = $this->get_all_events();
            foreach ( $all_events as &$event ) {
                if ( $this->is_event_active( $event ) ) {
                    $this->active_events_cache[] = $event;
                }
            }
        }

        return $this->active_events_cache;
    }

    function is_event_active( $event ) {
        return ( time() >= $event->begin_datetime && time() <= $event->end_datetime );
    }

    function get_deal( $deal ) {
        if ( is_numeric( $deal ) ) {
            $deal = get_post( absint( $deal ) );
            if ( !$deal || $deal->post_type != PW_BLACK_FRIDAY_DEAL_POST_TYPE ) {
                wp_die( __( 'Invalid deal parameter for get_deal().', 'pw-black-friday' ) );
            }

        } elseif ( !$deal instanceof WP_Post ) {
            wp_die( sprintf( __( '%s is not a valid type for get_deal().', 'pw-black-friday' ), gettype( $deal ) ) );
        }

        $deal->product_categories_included = (array) $deal->product_categories_included;
        $deal->include_product_ids = (array) $deal->include_product_ids;
        $deal->exclude_product_ids = (array) $deal->exclude_product_ids;

        return $deal;
    }

    function get_deals( $event_id ) {
        $deals = array();
        $deal_posts = get_posts( array(
            'post_parent' => $event_id,
            'post_type' => PW_BLACK_FRIDAY_DEAL_POST_TYPE,
            'nopaging' => true,
            'post_status' => 'publish',
            'orderby' => 'post_title',
            'order' => 'ASC'
        ) );

        foreach ( $deal_posts as &$deal ) {
            $deals[] = $this->get_deal( $deal );
        }

        return $deals;
    }

    function sort_events_by_date( $a, $b ) {
        if ( $a->begin_datetime == $b->begin_datetime ) {
            return strcmp( $a->post_title, $b->post_title );
        } else {
            return $b->begin_datetime - $a->begin_datetime;
        }
    }

    function woocommerce_cart_calculate_fees( $cart ) {
        $bogo_discounts = $this->get_bogo_discounts( $cart );
        $active_bogo_deals = $this->get_active_bogo_deals();

        foreach ( $bogo_discounts as $deal_id => $discount ) {

            // Get the coupon title.
            $bogo_title = __( 'Discount', 'pw-black-friday' );
            foreach ( $active_bogo_deals as $bogo_deal ) {
                if ( $bogo_deal->ID == $deal_id ) {
                    $bogo_title = $bogo_deal->post_title;
                    break;
                }
            }

            $cart->add_fee( $bogo_title, ( $discount * -1 ) );
        }
    }

    function woocommerce_cart_contents_total( $cart_contents_total ) {
        WC()->cart->calculate_fees();
        $fees = WC()->cart->get_fees();
        foreach ( $this->get_active_bogo_deals() as $bogo_deal ) {
            $fee_id = sanitize_title( $bogo_deal->post_title );
            foreach ( $fees as $fee ) {
                if ( $fee->id == $fee_id ) {
                    return wc_price( WC()->cart->cart_contents_total + $fee->amount );
                }
            }
        }

        return $cart_contents_total;
    }

    function maybe_apply_bogo_coupon() {
        if ( false === $this->use_coupons ) {
            return;
        }

        $cart = WC()->cart;

        $discounts = $this->get_bogo_discounts( $cart );
        asort( $discounts );

        // Delete any invalid coupons.
        foreach ( $cart->get_applied_coupons() as $coupon_code ) {
            $removed = false;

            foreach ( $this->get_all_events() as $event ) {
                if ( $removed ) { break; }

                foreach ( $event->deals as $deal ) {
                    if ( $removed ) { break; }

                    if ( $this->is_bogo_coupon( $coupon_code, $deal ) ) {
                        if ( !$this->is_event_active( $event ) || !isset( $discounts[ $deal->ID ] ) ) {
                            $cart->remove_coupon( $coupon_code );
                            $removed = true;
                        }
                    }
                }
            }
        }

        foreach ( $discounts as $deal_id => $bogo_discount ) {
            $deal = $this->get_deal( $deal_id );
            $coupon_exists = false;

            // Use the existing deal coupon if it exists.
            foreach ( $cart->get_applied_coupons() as $coupon_code ) {
                if ( $this->is_deal_coupon( $coupon_code, $deal ) ) {
                    $coupon_exists = true;
                    break;
                }
            }

            // If it doesn't exist, we need to create one for the BOGO.
            if ( !$coupon_exists ) {
                $bogo_coupon_code = $this->get_bogo_coupon_code( $deal );
                $cart->add_discount( $bogo_coupon_code );
                WC()->session->set( 'refresh_totals', true );
            }
        }
    }

    function woocommerce_coupon_message( $msg, $msg_code, $coupon ) {
        if ( $msg_code == WC_Coupon::WC_COUPON_SUCCESS ) {
            if ( $this->wc_min_version( '3.0' ) ) {
                $coupon_code = $coupon->get_code();
            } else {
                $coupon_code = $coupon->code;
            }

            foreach ( $this->get_active_deals() as $deal ) {
                if ( $this->is_deal_coupon( $coupon_code, $deal ) ) {
                    $event = get_post( $deal->post_parent );
                    $msg = sprintf( __( '%s discount applied.', 'pw-black-friday' ), $event->post_title );
                    break;
                }
            }
        }

        return $msg;
    }

    function woocommerce_coupon_error( $msg, $msg_code, $coupon ) {
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $deals = $this->get_product_deals( $cart_item['data'] );
            foreach ( $deals as $deal ) {
                if ( $deal->individual_use && !empty( $deal->individual_use_message ) ) {
                    return $deal->individual_use_message;
                }
            }
        }

        return $msg;
    }

    function woocommerce_coupon_is_valid( $valid_for_cart, $coupon ) {
        if ( $this->wc_min_version( '3.0' ) ) {
            $coupon_code = $coupon->get_code();
        } else {
            $coupon_code = $coupon->code;
        }

        if ( $this->is_deal_coupon( $coupon_code ) ) {
            $valid_for_cart = true;
        } else {
            if ( empty( WC()->cart ) ) {
                return $valid_for_cart;
            }

            foreach ( WC()->cart->get_cart() as $cart_item ) {
                // WooCommerce Product Bundles - the bundled products shouldn't be considered independently.
                if ( isset( $cart_item['bundled_by'] ) ) {
                    continue;
                }

                $deals = $this->get_product_deals( $cart_item['data'] );
                foreach ( $deals as $deal ) {
                    if ( $deal->individual_use ) {
                        if ( $this->wc_min_version( '3.0' ) ) {
                            $coupon_code = $coupon->get_code();
                        } else {
                            $coupon_code = $coupon->code;
                        }

                        if ( !$this->is_bogo_coupon( $coupon_code, $deal ) ) {
                            return false;
                        }
                    }
                }
            }
        }

        return $valid_for_cart;
    }

    function woocommerce_cart_totals_coupon_label( $label, $coupon ) {
        if ( $this->wc_min_version( '3.0' ) ) {
            $coupon_code = $coupon->get_code();
        } else {
            $coupon_code = $coupon->code;
        }

        foreach ( $this->get_active_deals() as $deal ) {
            if ( $this->is_deal_coupon( $coupon_code, $deal ) ) {
                $event = get_post( $deal->post_parent );
                $label = sprintf( __( 'Coupon: %s', 'pw-black-friday' ), $event->post_title );
                break;
            }
        }

        return $label;
    }

    function woocommerce_cart_totals_coupon_html( $coupon_html, $coupon, $discount_amount_html ) {
        if ( $this->wc_min_version( '3.0' ) ) {
            foreach ( $this->get_active_deals() as $deal ) {
                if ( $this->is_deal_coupon( $coupon->get_code(), $deal ) ) {
                    $amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax );
                    if ( empty( $amount ) ) {
                        $coupon_html = __( 'Applied to cart items', 'pw-black-friday' );
                        $coupon_html .= ' <a href="' . esc_url( add_query_arg( 'remove_coupon', rawurlencode( $coupon->get_code() ), defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'pw-black-friday' ) . '</a>';
                    }
                }
            }
        }

        return $coupon_html;
    }

    function woocommerce_new_order_item( $order_item_id, $item, $order_id ) {
        if ( is_a( $item, 'WC_Order_Item_Coupon' ) ) {
            $this->maybe_add_coupon_to_order_item( $order_item_id, $item->get_code() );
        }
    }

    function woocommerce_order_add_coupon( $order_id, $order_item_id, $code, $discount_amount, $discount_amount_tax ) {
        $this->maybe_add_coupon_to_order_item( $order_item_id, $code );
    }

    function maybe_add_coupon_to_order_item( $order_item_id, $code ) {
        foreach ( $this->get_active_deals( true ) as $deal ) {
            if ( $this->is_deal_coupon( $code, $deal ) ) {
                wc_add_order_item_meta( $order_item_id, 'pwbf_deal_id', $deal->ID );
                break;
            }
        }
    }

    function get_bogo_coupon_code( $deal ) {
        return wc_sanitize_taxonomy_name( $deal->post_title ) . '-' . $deal->ID;
    }

    function is_deal_coupon( $code, $deal = '' ) {
        if ( !empty( $deal ) ) {
            if ( strtolower( $code ) == strtolower( $deal->coupon_code ) ) {
                return true;
            }

            if ( $this->is_bogo_coupon( $code, $deal ) ) {
                return true;
            }

        } else {
            foreach ( $this->get_active_deals( true ) as $active_deal ) {
                if ( strtolower( $code ) == strtolower( $active_deal->coupon_code ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    function is_bogo_coupon( $code, $deal ) {
        return ( strtolower( $code ) == strtolower( $this->get_bogo_coupon_code( $deal ) ) );
    }

    function get_bogo_discounts( $cart ) {
        if ( is_null( $this->bogo_discounts_cache ) ) {
            $this->bogo_discounts_cache = array();

            // Expand the list of cart items, one element per quantity.
            $cart_items = $this->flatten_cart( $cart );

            // Sort the cart by price from higest to lowest for the Eligible products, lowest to highest for the Discounted products.
            $cart_items_desc = $cart_items;
            $cart_items_asc = $cart_items;
            usort( $cart_items_desc, function( $a, $b ) { return ( floatval( $a['price'] ) > floatval( $b['price'] ) ) ? -1 : 1; } );
            usort( $cart_items_asc, function( $a, $b ) { return ( floatval( $a['price'] ) < floatval( $b['price'] ) ) ? -1 : 1; } );

            $already_applied_cart_items = array();

            foreach ( $this->get_active_bogo_deals() as $deal ) {
                $considered_for_bogo = $already_applied_cart_items;
                $bogo_percentage = !empty( $deal->bogo_percentage ) ? $deal->bogo_percentage : 100;
                $percentage = $bogo_percentage / 100;
                $identical_products_only = boolval( $deal->bogo_identical_products );
                $identical_variations_only = boolval( $deal->bogo_identical_variations );
                $buy_limit = !empty( $deal->bogo_buy ) ? $deal->bogo_buy : 1;
                $get_limit = !empty( $deal->bogo_get ) ? $deal->bogo_get : 1;
                $order_limit = absint( $deal->order_limit );
                $order_limit_count = 1;

                // When considering eligible items, add the non-discounted items first. This will make it so that we can discount more expensive
                // items if necessary, when the Eligible Products are wide-open and the Discounted Products are for a specific category.
                $eligible_items = array();
                foreach ( $cart_items_desc as $ci ) {
                    if ( !isset( $product_deals[ $ci['cart_item_index'] ] ) ) {
                        $product_deals[ $ci['cart_item_index'] ] = $this->get_product_deals( $ci['cart_item']['data'] );
                    }

                    foreach ( $product_deals[ $ci['cart_item_index'] ] as $product_deal ) {
                        if ( $product_deal->ID == $deal->ID ) {
                            $eligible_items[ $ci['key'] ] = $ci['cart_item'];
                        }
                    }
                }

                $discounted_items = array();
                foreach ( $cart_items_asc as $ci ) {
                    if ( !isset( $product_deals[ $ci['cart_item_index'] ] ) ) {
                        $product_deals[ $ci['cart_item_index'] ] = $this->get_product_deals( $ci['cart_item']['data'] );
                    }

                    foreach ( $product_deals[ $ci['cart_item_index'] ] as $product_deal ) {
                        if ( $product_deal->ID == $deal->ID ) {
                            $discounted_items[ $ci['key'] ] = $ci['cart_item'];
                        }
                    }
                }

                $discount = 0;
                $id = '0';
                $item_index[ $id ] = 0;
                $discounted_item_count[ $id ] = 0;
                $discount_iterations = 0;

                foreach ( $eligible_items as $eligible_cart_item_key => $cart_item ) {
                    if ( in_array( $eligible_cart_item_key, $considered_for_bogo ) ) {
                        continue;
                    }

                    $considered_for_bogo[] = $eligible_cart_item_key;

                    if ( $identical_products_only === true ) {
                        if ( $identical_variations_only === true && $cart_item['variation_id'] != '0' ) {
                            $id = (string) $cart_item['variation_id'];
                        } else {
                            $id = (string) $cart_item['product_id'];
                        }
                    }

                    if ( !isset( $item_index[ $id ] ) ) { $item_index[ $id ] = 0; }
                    if ( !isset( $discounted_item_count[ $id ] ) ) { $discounted_item_count[ $id ] = 0; }

                    $item_index[ $id ]++;
                    if ( $item_index[ $id ] < $buy_limit ) {
                        continue;
                    } else {
                        $discount_iterations++;
                        $item_index[ $id ] = 0;
                        $discounted_item_count[ $id ] = 0;
                    }

                    foreach ( $discounted_items as $discounted_cart_item_key => $discounted_cart_item ) {
                        if ( in_array( $discounted_cart_item_key, $considered_for_bogo ) ) {
                            continue;
                        }

                        if ( $order_limit > 0 && $order_limit_count > $order_limit ) {
                            continue;
                        }

                        if ( $identical_products_only === true ) {
                            if ( $identical_variations_only === true && $cart_item['variation_id'] != '0'  ) {
                                if ( $discounted_cart_item['variation_id'] != $id ) {
                                    continue;
                                }
                            } else {
                                if ( $discounted_cart_item['product_id'] != $id ) {
                                    continue;
                                }
                            }
                        }

                        if ( $discounted_item_count[ $id ] >= $get_limit ) {
                            break;
                        }

                        $price = 0;
                        if ( function_exists( 'wc_get_price_excluding_tax' ) && function_exists( 'wc_get_price_including_tax' ) ) {
                            $product = $discounted_cart_item['data'];

                            // Base the discount on the "Prices entered with tax" setting rather
                            // than the Display Tax setting. Can be disabled by defining PW_BLACK_FRIDAY_BOGO_DISCOUNT_IGNORE_NEW_TAX_LOGIC
                            if ( !defined( 'PW_BLACK_FRIDAY_BOGO_DISCOUNT_IGNORE_NEW_TAX_LOGIC' ) ) {
                                if ( 'yes' == get_option( 'woocommerce_prices_include_tax', 'no' ) ) {
                                    $product_price = wc_get_price_including_tax( $product );
                                } else {
                                    $product_price = wc_get_price_excluding_tax( $product );
                                }
                            } else {
                                if ( PW_BLACK_FRIDAY_BOGO_DISCOUNT_PRICE_INCLUDES_TAX === true ) {
                                    $product_price = wc_get_price_excluding_tax( $product );

                                } else {
                                    if ( 'incl' === $cart->tax_display_cart ) {
                                        $product_price = wc_get_price_including_tax( $product );
                                    } else {
                                        $product_price = wc_get_price_excluding_tax( $product );
                                    }
                                }
                            }

                            $price = apply_filters( 'woocommerce_cart_product_price', $product_price, $product );
                        }

                        // Old way of getting price.
                        if ( empty( $price ) ) {
                            $price = $discounted_cart_item['data']->get_price();
                        }

                        $discount += $price;
                        $discounted_item_count[ $id ]++;
                        $considered_for_bogo[] = $discounted_cart_item_key;

                        $order_limit_count++;

                        foreach ( $considered_for_bogo as $cart_item_key ) {
                            if ( !in_array( $cart_item_key, $already_applied_cart_items ) ) {
                                $already_applied_cart_items[] = $cart_item_key;
                            }
                        }
                    }
                }

                if ( !empty( $discount ) ) {
                    $this->bogo_discounts_cache[ $deal->ID ] = $discount;
                }
            }
        }

        return $this->bogo_discounts_cache;
    }

    function flatten_cart( $cart, $product_id = 0, $variation_id = 0 ) {
        // Expand the list of cart items, one element per quantity.
        $cart_items = array();
        foreach ( $cart->get_cart() as $cart_item_index => $cart_item ) {
            if ( !empty( $variation_id ) && $cart_item['variation_id'] != $variation_id ) {
                continue;
            }

            if ( !empty( $product_id ) && $cart_item['product_id'] != $product_id ) {
                continue;
            }

            // WooCommerce Product Bundles - the bundled products shouldn't be considered independently.
            if ( isset( $cart_item['bundled_by'] ) ) {
                continue;
            }

            for ( $i = 0; $i < $cart_item['quantity']; $i++ ) {

                if ( !empty( $cart_item['data'] ) ) {
                    $price = $cart_item['data']->get_price();
                } else {
                    $price = 0;
                }

                $cart_items[] = array(
                    'cart_item_index'   => $cart_item_index,
                    'key'               => $cart_item_index . '_' . $i,
                    'cart_item'         => $cart_item,
                    'price'             => $price
                );
            }
        }

        return apply_filters( 'pw_black_friday_cart_items', $cart_items );
    }

    function get_active_deals( $ignore_coupon_code = false ) {
        if ( is_null( $this->active_deals_cache ) ) {
            $this->active_deals_cache = array();

            foreach ( $this->get_active_events() as $event ) {
                foreach ( $event->deals as $deal ) {
                    $this->active_deals_cache[] = $deal;
                }
            }
        }

        $deals = array();

        if ( false === $ignore_coupon_code ) {

            foreach ( $this->active_deals_cache as $deal ) {
                if ( !empty( $deal->coupon_code ) ) {
                    if ( WC()->cart ) {
                        foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
                            if ( strtolower( $coupon_code ) == strtolower( $deal->coupon_code ) ) {
                                $deals[] = $deal;
                                break;
                            }
                        }
                    }
                } else {
                    $deals[] = $deal;
                }
            }
        } else {
            $deals = $this->active_deals_cache;
        }

        return $deals;
    }

    function get_active_bogo_deals() {
        if ( is_null( $this->active_bogo_deals_cache ) ) {
            $this->active_bogo_deals_cache = array();

            foreach ( $this->get_active_deals() as $deal ) {
                if ( $deal->deal_type == 'bogo' ) {
                    $this->active_bogo_deals_cache[] = $deal;
                }
            }
        }

        return $this->active_bogo_deals_cache;
    }

    function sort_cart_items( &$cart_items, $direction ) {
        usort( $cart_items, function( $a, $b ) use ( $direction ) {
            if ( $a['price'] == $b['price'] ) {
                if ( $a['variation_id'] == $b['variation_id'] ) {
                    return ( $a['product_id'] < $b['product_id'] ) ? 1 : -1;
                } else {
                    return ( $a['variation_id'] < $b['variation_id'] ) ? 1 : -1;
                }
            } else {
                return ( $a['price'] < $b['price'] ) ? ( 1 * $direction ) : ( -1 * $direction );
            }
        });
    }

    function woocommerce_shortcode_products_query( $query_args, $attributes, $type  ) {
        global $pw_black_friday;

        if ( isset( $pw_black_friday ) ) {
            if ( 'products' === $type && isset( $attributes['pw_black_friday'] ) && is_numeric( $attributes['pw_black_friday'] ) ) {
                $event = $pw_black_friday->get_event( $attributes['pw_black_friday'], true );

                // By default, we will show all of the products even if the event is not active.
                // Previously, this would not show products unless the event is active.
                if ( apply_filters( 'pwbf_shortcode_only_show_when_active', false ) ) {
                    if ( ! $this->is_event_active( $event ) ) {
                        $query_args['post_type'] = 'donotshowresults';
                        return;
                    }
                }

                foreach ( $event->deals as $deal ) {
                    if ( isset( $deal->product_categories_included ) && is_array( $deal->product_categories_included ) ) {
                        $this->set_categories_query_args( $query_args, $deal->product_categories_included );
                    }

                    if ( isset( $deal->include_product_ids ) && is_array( $deal->include_product_ids ) ) {
                        $this->set_products_query_args( $query_args, $deal->include_product_ids, 'in' );
                    }

                    if ( isset( $deal->exclude_product_ids ) && is_array( $deal->exclude_product_ids ) ) {
                        $this->set_products_query_args( $query_args, $deal->exclude_product_ids, 'not_in' );
                    }
                }
            }
        }

        return $query_args;
    }

    function shortcode_atts_products( $out, $pairs, $atts, $shortcode ) {
        if ( isset( $atts['pw_black_friday'] ) ) {
            $out['pw_black_friday'] = $atts['pw_black_friday'];
        }

        return $out;
    }

    function set_categories_query_args( &$query_args, $categories ) {
        if ( ! empty( $categories ) ) {
            $categories = array_map( 'absint', $categories );

            $product_cat_exists = false;
            foreach ( $query_args['tax_query'] as &$q ) {
                if ( $q['taxonomy'] == 'product_cat' ) {
                    $q['terms'] = array_merge( $q['terms'], $categories );
                    $product_cat_exists = true;
                    break;
                }
            }

            if ( !$product_cat_exists ) {
                $query_args['tax_query'][] = array(
                    'taxonomy'         => 'product_cat',
                    'terms'            => $categories,
                    'field'            => 'term_id',
                    'operator'         => 'IN',
                    'include_children' => true,
                );
            }
        }
    }

    function set_products_query_args( &$query_args, $ids, $operator ) {
        if ( ! empty( $ids ) ) {
            $ids = array_map( 'trim', $ids );
            $ids = array_map( 'absint', $ids );

            if ( isset( $query_args['post__' . $operator] ) ) {
                $query_args['post__' . $operator] = array_merge( $query_args['post__' . $operator], $ids );
            } else {
                $query_args['post__' . $operator] = $ids;
            }
        }
    }
}

global $pw_black_friday;
$pw_black_friday = new PW_Black_Friday();

endif;

function pw_black_friday_is_product_on_sale( $product ) {
    global $pw_black_friday;

    $deals = $pw_black_friday->get_product_deals( $product );

    if ( count( $deals ) > 0 ) {
        return true;
    } else {
        return false;
    }
}

if ( !function_exists( 'boolval' ) ) {
    function boolval( $val ) {
        return (bool) $val;
    }
}

if ( ! function_exists( 'pwbf_strtotime' ) ) {
    // Source: https://mediarealm.com.au/articles/wordpress-timezones-strtotime-date-functions/
    function pwbf_strtotime( $str ) {
        // This function behaves a bit like PHP's StrToTime() function, but taking into account the Wordpress site's timezone
        // CAUTION: It will throw an exception when it receives invalid input - please catch it accordingly
        // From https://mediarealm.com.au/

        $tz_string = get_option('timezone_string');
        $tz_offset = get_option('gmt_offset', 0);

        if (!empty($tz_string)) {
            // If site timezone option string exists, use it
            $timezone = $tz_string;

        } elseif (0 == $tz_offset) {
            // get UTC offset, if it isn’t set then return UTC
            $timezone = 'UTC';

        } else {
            $timezone = $tz_offset;

            if (substr($tz_offset, 0, 1) != '-' && substr($tz_offset, 0, 1) != '+' && substr($tz_offset, 0, 1) != 'U') {
                $timezone = '+' . $tz_offset;
            }
        }

        $datetime = new DateTime($str, new DateTimeZone($timezone));
        return $datetime->format('U');
    }
}
