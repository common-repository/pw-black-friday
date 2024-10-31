<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php

global $pw_black_friday;
global $pwbf_step;
global $pwbf_last_step;

function pwbfWizardTitle( $title ) {
    ?>
    <div class="pwbf-heading">
        <div class="pwbf-heading-step"><?php printf( __( 'Step %s of %s', 'pw-black-friday' ), $GLOBALS['pwbf_step'], $GLOBALS['pwbf_last_step'] ); ?></div>
        <?php echo $title; ?>
    </div>
    <?php
}

?>
<form id="pwbf-wizard-form" method="POST">
    <input type="hidden" id="pwbf-wizard-form-event-id" name="event_id" value="<?php echo isset( $pwbf_event ) ? $pwbf_event->ID : ''; ?>">
    <input type="hidden" name="deal_id" value="<?php echo isset( $pwbf_deal ) ? $pwbf_deal->ID : ''; ?>">
    <?php

        // Load the steps.
        for ( $pwbf_step = 1; $pwbf_step <= $pwbf_last_step; $pwbf_step++ ) {
            // Prevent flickering on initial load. Don't want to hide step 1 then show it after page loads. Instead, start out visible.
            $hidden = ( $pwbf_step == 1 && isset( $pwbf_export ) ) ? '' : 'pwbf-hidden';

            ?>
            <div id="pwbf-wizard-step-<?php echo $pwbf_step; ?>" class="pwbf-wizard-step pwbf-bordered-container <?php echo $hidden; ?>">
                <?php
                    require( 'deals/step' . $pwbf_step . '.php' );
                ?>
                <div class="pwbf-bordered-content-button-container">
                    <div onClick="if (confirm('<?php _e( 'Cancel the wizard?', 'pw-black-friday' ); ?>\n\n<?php _e( 'Your changes will not be saved.', 'pw-black-friday' ); ?>')) { pwbfWizardClose(); }" class="pwbf-wizard-cancel-button pwbf-noselect"><?php _e( 'Cancel', 'pw-black-friday' ); ?></div>
                    <?php
                        if ( $pwbf_step != $pwbf_last_step ) {
                            ?>
                            <div onClick="pwbfWizardLoadStep(<?php echo ( $pwbf_step + 1 ); ?>, true);" class="pwbf-wizard-next-previous-button pwbf-wizard-next-button pwbf-noselect"><?php _e( 'Next', 'pw-black-friday' ); ?></div>
                            <?php
                        } else {
                            ?>
                            <div onClick="pwbfWizardFinish();" class="pwbf-wizard-next-previous-button pwbf-wizard-finish-button pwbf-noselect"><?php _e( 'Finish', 'pw-black-friday' ); ?></div>
                            <?php
                        }

                        if ( $pwbf_step > 1 ) {
                            ?>
                            <div onClick="history.back();" class="pwbf-wizard-next-previous-button pwbf-wizard-previous-button pwbf-noselect"><?php _e( 'Previous', 'pw-black-friday' ); ?></div>
                            <?php
                        }
                    ?>
                </div>
            </div>
            <?php
        }
    ?>
    <div id="pwbf-wizard-step-saving" class="pwbf-wizard-step pwbf-bordered-container pwbf-hidden">
        <div style="text-align: center;">
            <div class="pwbf-heading"><?php _e( 'Saving', 'pw-black-friday' ); ?></div>
            <i class="fa fa-cog fa-spin fa-3x fa-fw"></i>
        </div>
    </div>
</form>
<?php
    if ( isset( $pwbf_deal ) ) {
        ?>
        <script>
            jQuery(function() {
                pwbfWizardLoadStep(1);
            });
        </script>
        <?php
    }
?>
