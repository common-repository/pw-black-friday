<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

?>
<form id="pwbf-edit-promo-form" action="<?php echo trailingslashit( get_site_url() ); ?>" method="POST" target="_blank">
    <input type="hidden" name="pwbf_preview" value="true">
    <input type="hidden" id="pwbf-edit-promo-event-id" name="event_id" value="<?php echo $event->ID; ?>">
    <div class="pwbf-heading" style="margin-top: 24px; margin-bottom: 8px;"><?php echo esc_html( $event->post_title ); ?> <?php _e( 'Promo', 'pw-black-friday' ); ?></div>
    <p>
        <?php _e( 'This will be shown on your front page once the event has started. Use it to highlight special parts of the event, add links to specific products, or anything else you would like. If this is blank, nothing will be shown.', 'pw-black-friday' ); ?>
    </p>
    <div id="pwbf-edit-promo-saving">
        <i class="fa fa-cog fa-spin fa-fw"></i> <?php _e( 'Saving', 'pw-black-friday' ); ?>
    </div>
    <div id="pwbf-edit-promo-button-container" class="pwbf-edit-promo-button-container">
        <a href="#" id="pwbf-edit-promo-button-save" class="button button-primary pwbf-edit-promo-button"><i class="fa fa-save" aria-hidden="true"></i> <?php _e( 'Save', 'pw-black-friday' ); ?></a>
        <a href="#" id="pwbf-edit-promo-button-preview" class="button button-secondary pwbf-edit-promo-button"><i class="fa fa-eye" aria-hidden="true"></i> <?php _e( 'Preview', 'pw-black-friday' ); ?></a>
        <a href="#" id="pwbf-edit-promo-button-cancel" class="pwbf-edit-promo-button pwbf-cancel-link"><?php _e( 'Cancel', 'pw-black-friday' ); ?></a>
    </div>
    <div style="padding-right: 20px;">
        <?php
            wp_editor( $event->post_content, 'pwbf-edit-promo-editor' );
        ?>
    </div>
</form>
