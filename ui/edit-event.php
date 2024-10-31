<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

?>
<form id="pwbf-edit-event-form" method="POST">
    <input type="hidden" id="pwbf-edit-event-event_id" name="event_id">

    <div id="pwbf-edit-event" class="pwbf-bordered-container">
        <div id="pwbf-edit-event-header" class="pwbf-heading pwbf-event-heading"></div>
        <div class="pwbf-bordered-content">

            <label for="pwbf-edit-event-title" class="pwbf-input-title" style="margin-top: 0;"><?php _e( 'Event name', 'pw-black-friday' ); ?></label><br>
            <input type="text" id="pwbf-edit-event-title" name="title" class="pwbf-input" required="true">
            <div class="pwbf-input-subtitle"><?php _e( 'The event name may be shown to customers.', 'pw-black-friday' ); ?></div>

            <div>
                <label for="pwbf-edit-event-begin_date" class="pwbf-input-title"><?php _e( 'Deal Begins', 'pw-black-friday' ); ?></label><br>
                <input type="text" id="pwbf-edit-event-begin_date" name="begin_date" class="pwbf-input" required="true">
                <input type="text" id="pwbf-edit-event-begin_time" name="begin_time" class="pwbf-input" required="true">
            </div>

            <div>
                <label for="pwbf-edit-event-end_date" class="pwbf-input-title"><?php _e( 'Deal Ends', 'pw-black-friday' ); ?></label><br>
                <input type="text" id="pwbf-edit-event-end_date" name="end_date" class="pwbf-input" required="true">
                <input type="text" id="pwbf-edit-event-end_time" name="end_time" class="pwbf-input" required="true">
            </div>

            <div class="pwbf-bordered-content-button-container">
                <a href="#" id="pwbf-edit-event-save" class="button button-primary" style="margin-right: 1.0em;"><?php _e( 'Save', 'pw-black-friday' ); ?></a>
                <a href="#" id="pwbf-edit-event-cancel" class="button button-secondary"><?php _e( 'Cancel', 'pw-black-friday' ); ?></a>
            </div>
        </div>
    </div>
</form>
<div id="pwbf-edit-event-saving" class="pwbf-edit-event pwbf-bordered-container pwbf-hidden">
    <div style="text-align: center;">
        <div class="pwbf-heading"><?php _e( 'Saving', 'pw-black-friday' ); ?></div>
        <i class="fa fa-cog fa-spin fa-3x fa-fw"></i>
    </div>
</div>
<script>

    jQuery(function() {
        window.pwbfDates = jQuery(this).find('#pwbf-edit-event-begin_date, #pwbf-edit-event-end_date');

        pwbfDates.datepicker({
            defaultDate: '',
            dateFormat: 'yy-mm-dd',
            numberOfMonths: 1,
            showButtonPanel: true,
            onSelect: function(selectedDate) {
                pwbfSelectDate(this, selectedDate);
            },
            beforeShow: function (input) {
                <?php
                    foreach( $pw_black_friday->holidays as $holiday ) {
                        echo "pwbfAddButton(input, '$holiday[title]', '$holiday[date]');\n";
                    }
                ?>
            },
            onChangeMonthYear: function (yy, mm, inst) {
                <?php
                    foreach( $pw_black_friday->holidays as $holiday ) {
                        echo "pwbfAddButton(inst.input, '$holiday[title]', '$holiday[date]');\n";
                    }
                ?>
            }
        });
    });
</script>
