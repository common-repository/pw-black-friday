<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<div id="pwbf-intro-container" class="pwbf-intro-container">
    <p>
        <?php _e( 'Black Friday and Cyber Monday are two of the biggest shopping events of the year.', 'pw-black-friday' ); ?>
    </p>
    <p>
        <?php _e( 'Schedule these events without interfering with any existing sales. You can even specify the minute the promotion will officially begin!', 'pw-black-friday' ); ?>
    </p>
    <div class="pwbf-intro-header">
        <?php _e( 'Select an event to get started.', 'pw-black-friday' ); ?>
    </div>
    <div class="pwbf-action-button-container">
        <?php
            foreach( $pw_black_friday->holidays as $slug => $holiday ) {
                ?>
                <div class="pwbf-action-button pwbf-intro-button pwbf-create-event-button" data-slug="<?php echo esc_attr( $slug ); ?>">
                    <div style="background-color: <?php echo $holiday['color']; ?>">
                        <i class="fa fa-<?php echo $holiday['icon']; ?> fa-4x" aria-hidden="true"></i>
                    </div>
                    <div class="pwbf-action-title"><?php echo esc_html( $holiday['title'] ); ?></div>
                </div>
                <?php
            }
        ?>
        <div class="pwbf-action-button pwbf-intro-button pwbf-create-event-button" data-slug="other">
            <div style="background-color: #0073AA;">
                <i class="fa fa-calendar fa-4x" aria-hidden="true"></i>
            </div>
            <div class="pwbf-action-title"><?php _e( 'Other Event', 'pw-black-friday' ); ?></div>
        </div>
    </div>
</div>
