<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

global $pw_black_friday;

$events = $pw_black_friday->get_all_events();

require( 'header.php' );
require( 'edit-event.php' );
require( 'edit-countdowns.php' );
require( 'edit-deal.php' );

if ( count( $events ) == 0 ) {
    require( 'intro.php' );
    return;
}

$product_categories = array();
$all_categories = get_terms(array('taxonomy' => 'product_cat', 'orderby' => 'name', 'hide_empty' => false ));
$unsorted_categories = get_terms(array('taxonomy' => 'product_cat', 'orderby' => 'name', 'hide_empty' => false ));
$pw_black_friday->sort_terms_hierarchicaly( $unsorted_categories, $product_categories );
$line_limit = 8;

function pwbfAddEventButton( $slug, $title, $color, $icon ) {
    ?>
    <div class="pwbf-action-button pwbf-action-button-medium pwbf-create-event-button" data-slug="<?php echo esc_attr( $slug ); ?>">
        <div style="background-color: <?php echo $color; ?>">
            <i class="fa fa-<?php echo $icon; ?> fa-2x" aria-hidden="true"></i>
        </div>
        <div class="pwbf-action-title pwbf-action-title-medium"><?php echo esc_html( $title ); ?></div>
    </div>
    <?php
}

function pwbfEventActionButton( $class, $title, $color, $icon, $size ) {
    ?>
    <div class="pwbf-action-button pwbf-action-button-<?php echo $size; ?> <?php echo $class; ?>">
        <div style="background-color: <?php echo $color; ?>;">
            <i class="fa fa-<?php echo $icon; ?>" aria-hidden="true"></i>
        </div>
        <div class="pwbf-action-title pwbf-action-title-<?php echo $size; ?>"><?php echo esc_html( $title ); ?></div>
    </div>
    <?php
}

function pwbfPrintProductList( $product_ids ) {
    if ( !is_array( $product_ids ) ) {
        return;
    }

    foreach ( $product_ids as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( is_object( $product ) ) {
            echo $product->get_formatted_name() . '<br>';
        }
    }
}

?>
<div class="pwbf-main-content">
    <div class="pwbf-add-event-container pwbf-noselect">
        <div id="pwbf-add-event-button-container">
            <div class="pwbf-add-event-title"><?php _e( 'Create another event', 'pw-black-friday' ); ?></div>
            <div class="pwbf-action-button-container pwbf-add-event-buttons">
                <?php
                    foreach( $pw_black_friday->holidays as $slug => $holiday ) {
                        pwbfAddEventButton( $slug, $holiday['title'], $holiday['color'], $holiday['icon'] );
                    }

                    pwbfAddEventButton( 'other', __( 'Other', 'pw-black-friday' ), '#0073AA', 'calendar' );
                ?>
            </div>
        </div>
    </div>
    <?php

        if ( count( $events ) == 1 && count( $events[0]->deals ) == 0 ) {
            require( 'first-event.php' );
        }

        foreach( $events as $event ) {
            ?>
            <div
                class="pwbf-event-container"
                data-event-id="<?php echo $event->ID; ?>"
                data-title="<?php echo esc_attr( $event->post_title ); ?>"
                data-begin-date="<?php echo esc_attr( $event->begin_date ); ?>"
                data-begin-time="<?php echo esc_attr( $event->begin_time ); ?>"
                data-end-date="<?php echo esc_attr( $event->end_date ); ?>"
                data-end-time="<?php echo esc_attr( $event->end_time ); ?>"
                data-upcoming-countdown="<?php echo esc_attr( htmlentities( $event->upcoming_countdown, ENT_COMPAT ) ); ?>"
                data-upcoming-offset="<?php echo $event->upcoming_offset; ?>"
                data-ending-countdown="<?php echo esc_attr( $event->ending_countdown ); ?>">

                <div class="pwbf-edit-event-link-container">
                    <a href="#" class="pwbf-edit-event-link"><?php echo $event->post_title; ?></a>
                </div>

                <div class="pwbf-event-dates">
                    <div>
                        <strong><?php echo esc_html( $pw_black_friday->get_local_date_in_format( 'l ', $event->begin_datetime ) . ' ' . $pw_black_friday->local_date_and_time( $event->begin_datetime ) ); ?></strong> <?php _e( 'until', 'pw-black-friday' ); ?>
                    </div>
                    <div>
                        <strong><?php echo esc_html( $pw_black_friday->get_local_date_in_format( 'l ', $event->end_datetime ) . ' ' . $pw_black_friday->local_date_and_time( $event->end_datetime ) ); ?></strong>
                    </div>
                </div>

                <?php
                    if ( count( $event->deals ) > 0 || count( $events ) > 1 ) {
                        ?>
                        <div class="pwbf-event-actions">
                            <?php
                                pwbfEventActionButton( 'pwbf-event-button-add-deal', __( 'Create a deal', 'pw-black-friday' ), '#329926', 'plus-square-o', 'small' );
                                pwbfEventActionButton( 'pwbf-event-button-edit-promo', __( 'Edit promo', 'pw-black-friday' ), '#0A8795', 'newspaper-o', 'small' );
                                pwbfEventActionButton( 'pwbf-event-button-edit-countdowns', __( 'Countdowns', 'pw-black-friday' ), '#E9A549', 'clock-o', 'small' );
                                pwbfEventActionButton( 'pwbf-event-button-edit-event', __( 'Settings', 'pw-black-friday' ), '#0073AA', 'cog', 'small' );
                                pwbfEventActionButton( 'pwbf-event-button-delete-event', __( 'Delete event', 'pw-black-friday' ), '#BA2633', 'trash-o', 'small' );
                            ?>
                        </div>
                        <table class="pwbf-deal-table">
                            <tr>
                                <th><?php _e( 'Name', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Categories', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Specific Products', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Excluded Products', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Discount', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Free Shipping', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Coupon Code', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Individual Use', 'pw-black-friday' ); ?></th>
                                <th><?php _e( 'Order Limit', 'pw-black-friday' ); ?></th>
                                <th>&nbsp;</th>
                            </tr>
                            <?php
                                if ( count( $event->deals ) > 0 ) {
                                    foreach( $event->deals as $deal ) {
                                        $edit_url = admin_url( "admin.php?page=pw-black-friday&event_id={$event->ID}&deal_id={$deal->ID}&step=1" );

                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo $edit_url; ?>" class="pwbf-link pwbf-deal-link"><?php echo $deal->post_title; ?></a>
                                            </td>
                                            <td>
                                                <div class="pwbf-limited-lines">
                                                    <?php
                                                        if ( count( $deal->product_categories_included ) == count( $unsorted_categories ) ) {
                                                            _e( 'All categories', 'pw-black-friday' );
                                                        } else {
                                                            if ( count( $deal->product_categories_included ) > $line_limit ) {
                                                                $pw_black_friday->hierarchical_string( $product_categories, array_slice( $deal->product_categories_included, 0, $line_limit ) );

                                                                echo '<a href="#" class="pwbf-limited-lines-toggle">' . ( count( $deal->product_categories_included ) - $line_limit ) . ' ' . __( 'More', 'pw-black-friday' ) . '</a></div><div class="pwbf-unlimited-lines">';
                                                            }

                                                            $pw_black_friday->hierarchical_string( $product_categories, $deal->product_categories_included );
                                                        }
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="pwbf-limited-lines">
                                                    <?php
                                                        if ( count( $deal->include_product_ids ) > $line_limit ) {
                                                            pwbfPrintProductList( array_slice( $deal->include_product_ids, 0, $line_limit ) );
                                                            echo '<a href="#" class="pwbf-limited-lines-toggle">' . ( count( $deal->include_product_ids ) - $line_limit ) . ' ' . __( 'More', 'pw-black-friday' ) . '</a></div><div class="pwbf-unlimited-lines">';
                                                        }

                                                        pwbfPrintProductList( $deal->include_product_ids );
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="pwbf-limited-lines">
                                                    <?php
                                                        if ( count( $deal->exclude_product_ids ) > $line_limit ) {
                                                            pwbfPrintProductList( array_slice( $deal->exclude_product_ids, 0, $line_limit ) );
                                                            echo '<a href="#" class="pwbf-limited-lines-toggle">' . ( count( $deal->exclude_product_ids ) - $line_limit ) . ' ' . __( 'More', 'pw-black-friday' ) . '</a></div><div class="pwbf-unlimited-lines">';
                                                        }

                                                        pwbfPrintProductList( $deal->exclude_product_ids );
                                                    ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                    switch ( $deal->deal_type ) {
                                                        case 'percentage':
                                                            echo '10% ' . __( 'off', 'pw-black-friday' );
                                                        break;

                                                        case 'fixed':
                                                            echo wc_price( 10 ) . ' ' . __( 'off', 'pw-black-friday' );
                                                        break;

                                                        case 'bogo':
                                                            echo 'BOGO';
                                                        break;
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                    if ( $deal->free_shipping ) {
                                                        echo __( 'Yes', 'pw-black-friday' );
                                                        echo '<br>';
                                                        echo '(' . wc_price( $deal->free_shipping_min_amount ) . ' ' . __( 'minimum', 'pw-black-friday' ) . ')';
                                                    } else {
                                                        echo __( 'No', 'pw-black-friday' );
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo $deal->coupon_code; ?>
                                            </td>
                                            <td>
                                                <?php echo $deal->individual_use ? __( 'Yes', 'pw-black-friday' ) : __( 'No', 'pw-black-friday' ); ?>
                                            </td>
                                            <td>
                                                <?php echo empty( $deal->order_limit ) ? __( 'No limit', 'pw-black-friday' ) : $deal->order_limit; ?>
                                            </td>
                                            <td>
                                                <a href="#" onClick="pwbfDeleteDeal(<?php echo $deal->ID; ?>); return false;" class="pwbf-link pwbf-delete-link" title="<?php _e( 'Delete deal', 'pw-black-friday' ); ?>"><i class="fa fa-trash-o"></i></a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="pwbf-no-deals">
                                                <?php _e( 'This event does not include any products. Click on the "Create a deal" button.', 'pw-black-friday' ); ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            ?>
                        </table>
                        <?php

                    } else {
                        ?>
                        <div>
                            <br>
                            <?php
                                pwbfEventActionButton( 'pwbf-event-button-add-deal', __( 'Create a deal', 'pw-black-friday' ), '#329926', 'plus-square-o fa-3x', 'medium' );
                            ?>
                        </div>
                        <?php
                    }
                ?>
            </div>
            <?php
        }

        require( 'footer.php' );
    ?>
</div>
