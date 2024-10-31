<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php

    pwbfWizardTitle( __( 'Deal Name', 'pw-black-friday' ) );

    if ( isset( $pwbf_deal ) ) {
        $deal_title = esc_html( $pwbf_deal->post_title );
        $show_expiration = ( false !== boolval( $pwbf_deal->show_expiration ) );
    } else {
        $deal_title = '';
        $show_expiration = true;
    }

?>
<div class="pwbf-wizard-step-container">
    <div>
        <input type="text" id="pwbf-title" name="title" class="pwbf-input" style="width: 100%" value="<?php echo $deal_title; ?>" required="true">
    </div>
    <div class="pwbf-input-subtitle">
        <?php _e( 'The deal name may be shown to customers.', 'pw-black-friday' ); ?>
    </div>
    <div style="margin-bottom: 24px;">
        <label for="pwbf-show-expiration" class="pwbf-input-title">
            <input type="checkbox" id="pwbf-show-expiration" name="show_expiration" value="yes" <?php checked( $show_expiration ); ?>>
            <?php _e( 'Show expiration date on product page.', 'pw-black-friday' ); ?>
        </label>
        <div class="pwbf-input-subtitle">
            <?php _e( 'Nudge customers to purchase by showing the expiration date of the sale.', 'pw-black-friday' ); ?>
        </div>
    </div>
</div>
<script>

    function pwbfWizardLoadStep<?php echo $pwbf_step; ?>() {
        var title = jQuery('#pwbf-title');
        var len = title.val().length;
        title.focus();
        title[0].setSelectionRange(len, len);
    }

    function pwbfWizardValidateStep<?php echo $pwbf_step; ?>() {
        if (!jQuery('#pwbf-title').val() || jQuery('#pwbf-title').val().trim() == '') {
            alert('<?php _e( 'Title is required.', 'pw-black-friday' ); ?>');
            jQuery('#pwbf-title').focus();
            return false;
        }

        return true;

    }
</script>
