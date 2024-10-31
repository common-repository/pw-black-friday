<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<form id="pwbf-edit-countdowns-form" action="<?php echo trailingslashit( get_site_url() ); ?>" method="POST" target="_blank">
    <input type="hidden" id="pwbf-edit-countdowns-event_id" name="event_id">
    <input type="hidden" id="pwbf-edit-countdowns-field" name="countdown_field">

    <div id="pwbf-edit-countdowns" class="pwbf-bordered-container">
        <div id="pwbf-edit-countdowns-title" class="pwbf-heading pwbf-countdowns-heading"></div>

        <div class="pwbf-bordered-content">
            <div>
                <?php _e( 'Countdowns appear at the top of every page. They generate anticipation and create a sense of urgency.', 'pw-black-friday' ); ?>
            </div>
            <div style="margin-top: 12px;">
                <?php _e( 'You can use HTML, and the following codes are also available:', 'pw-black-friday' ); ?>
            </div>
            <div style="margin-left: 48px; margin-top: 12px;">
                <span class="pwbf-countdown-tag">{countdown}</span> <?php _e( 'Display a countdown timer (days, hours, minutes, and seconds).', 'pw-black-friday' ); ?><br>
                <span class="pwbf-countdown-tag">{begin_date}</span> <?php _e( 'The begin date of the event.', 'pw-black-friday' ); ?><br>
                <span class="pwbf-countdown-tag">{begin_time}</span> <?php _e( 'The begin time of the event.', 'pw-black-friday' ); ?><br>
                <span class="pwbf-countdown-tag">{end_date}</span> <?php _e( 'The end date of the event.', 'pw-black-friday' ); ?><br>
                <span class="pwbf-countdown-tag">{end_time}</span> <?php _e( 'The end time of the event.', 'pw-black-friday' ); ?><br>
            </div>

            <label for="pwbf-edit-countdowns-upcoming_countdown" class="pwbf-input-title"><?php _e( 'Upcoming event announcement', 'pw-black-friday' ); ?></label>
            <div class="pwbf-input-subtitle"><?php _e( 'Build excitement before the event begins. Leave blank to disable.', 'pw-black-friday' ); ?></div>
            <input type="text" id="pwbf-edit-countdowns-upcoming_countdown" name="upcoming_countdown" class="pwbf-input" style="width: 100%;">
            <a href="#" class="button button-secondary pwbf-edit-countdowns-preview pwbf-edit-countdowns-preview-button" data-field="upcoming_countdown"><?php echo __( 'Preview', 'pw-black-friday' ); ?></a>

            <div style="margin-top: 12px; margin-bottom: 48px;">
                <div class="pwbf-input-subtitle"><?php _e( 'How many days before the start of the event should we begin showing this announcement? Leave blank to start displaying immediately.', 'pw-black-friday' ); ?></div>
                <input id="pwbf-edit-countdowns-upcoming_offset" name="upcoming_offset" type="number" step="1" min="0" style="width: 48px;"> <?php _e( 'days', 'pw-black-friday' ); ?>
            </div>

            <label for="pwbf-edit-countdowns-ending_countdown" class="pwbf-input-title"><?php _e( 'End of the event countdown', 'pw-black-friday' ); ?></label>
            <div class="pwbf-input-subtitle"><?php _e( 'Motivate a purchase before the event is over. Leave blank to disable.', 'pw-black-friday' ); ?></div>
            <input type="text" id="pwbf-edit-countdowns-ending_countdown" name="ending_countdown" class="pwbf-input" style="width: 100%;">
            <a href="#" class="button button-secondary pwbf-edit-countdowns-preview pwbf-edit-countdowns-preview-button" data-field="ending_countdown"><?php echo __( 'Preview', 'pw-black-friday' ); ?></a>

            <div class="pwbf-bordered-content-button-container" style="margin-top: 48px;">
                <a href="#" id="pwbf-edit-countdowns-save" class="button button-primary" style="margin-right: 1.0em;"><?php _e( 'Save', 'pw-black-friday' ); ?></a>
                <a href="#" id="pwbf-edit-countdowns-cancel" class="pwbf-cancel-link"><?php _e( 'Cancel', 'pw-black-friday' ); ?></a>
            </div>
        </div>
    </div>
</form>
<div id="pwbf-edit-countdowns-saving" class="pwbf-edit-countdowns pwbf-bordered-container pwbf-hidden">
    <div style="text-align: center;">
        <div class="pwbf-heading"><?php _e( 'Saving', 'pw-black-friday' ); ?></div>
        <i class="fa fa-cog fa-spin fa-3x fa-fw"></i>
    </div>
</div>
<?php
