jQuery(function() {

    window.addEventListener('popstate', function(event) {
        if (event.state) {
            if (event.state.type == 'step') {
                var step = JSON.stringify(event.state.step);
                pwbfWizardLoadStep(step, false, true);
            } else if (event.state.type == 'edit_event') {
                pwbfEditEventOpen();
            } else if (event.state.type == 'edit_countdowns') {
                pwbfEditCountdownsOpen();
            }
        } else {
            if (jQuery('.pwbf-wizard-step:visible').length > 0) {
                pwbfWizardClose();
            } else {
                pwbfEditEventClose();
                pwbfEditCountdownsClose();
            }
        }
    });

    jQuery('#pwbf-product-categories-included-select-all').on('click', function(e) {
        jQuery('#pwbf-product-categories-included option').prop('selected', true);
        jQuery('#pwbf-product-categories-included').focus();
        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-product-categories-included-select-none').on('click', function(e) {
        jQuery('#pwbf-product-categories-included').val([]);
        e.preventDefault();
        return false;
    });

    jQuery('.pwbf-edit-event-link, .pwbf-event-button-edit-event').on('click', function(e) {
        var event = jQuery(this).closest('.pwbf-event-container');
        var eventId = event.attr('data-event-id');
        var beginDate = event.attr('data-begin-date');
        var beginTime = event.attr('data-begin-time');
        var endDate = event.attr('data-end-date');
        var endTime = event.attr('data-end-time');
        var title = event.attr('data-title');

        pwbfEditEvent(eventId, beginDate, beginTime, endDate, endTime, title);

        e.preventDefault();
        return false;
    });

    jQuery('.pwbf-event-button-edit-countdowns').on('click', function(e) {
        var event = jQuery(this).closest('.pwbf-event-container');
        var eventId = event.attr('data-event-id');
        var title = event.attr('data-title');
        var upcomingCountdown = event.attr('data-upcoming-countdown');
        var upcomingOffset = event.attr('data-upcoming-offset');
        var endingCountdown = event.attr('data-ending-countdown');

        pwbfEditCountdowns(eventId, title, upcomingCountdown, upcomingOffset, endingCountdown);

        e.preventDefault();
        return false;
    });

    jQuery('.pwbf-event-button-add-deal').on('click', function() {
        var event = jQuery(this).closest('.pwbf-event-container');
        var eventId = event.attr('data-event-id');
        jQuery('#pwbf-wizard-form-event-id').val(eventId);
        pwbfWizardLoadStep(1);
    });

    jQuery('.pwbf-event-button-edit-promo').on('click', function() {
        var event = jQuery(this).closest('.pwbf-event-container');
        var eventId = event.attr('data-event-id');
        window.location = pwbf.admin_url + '&action=edit_promo&event_id=' + eventId;
    });

    jQuery('.pwbf-create-event-button').on('click', function() {
        var slug = jQuery(this).attr('data-slug');
        var holiday = pwbf.holidays[slug];

        if (holiday) {
            var monthNames = [pwbf.i18n.mon_jan, pwbf.i18n.mon_feb, pwbf.i18n.mon_mar, pwbf.i18n.mon_apr, pwbf.i18n.mon_may, pwbf.i18n.mon_jun, pwbf.i18n.mon_jul, pwbf.i18n.mon_aug, pwbf.i18n.mon_sep, pwbf.i18n.mon_oct, pwbf.i18n.mon_nov, pwbf.i18n.mon_dec];
            var holidayDate = new Date(holiday.date);
            var title = holiday.title + ' ' + monthNames[holidayDate.getMonth()] + ' ' + (holidayDate.getDate() + 1) + ', ' + holidayDate.getFullYear();

            pwbfEditEvent('', holiday.date, '12:00 AM', holiday.date, '11:59 PM', holiday.title, title, holiday.color);
        } else {
            pwbfCreateEvent();
        }
    });

    jQuery('#pwbf-title').keypress(function (e) {
        if (e.which == 13) {
            pwbfWizardFinish();
            e.preventDefault();
            return false;
        }
    });

    jQuery('.pwbf-event-button-delete-event').on('click', function(e) {
        var event = jQuery(this).closest('.pwbf-event-container');
        var eventId = event.attr('data-event-id');
        pwbfDeleteEvent(eventId);
        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-promo-button-save').on('click', function(e) {
        var eventId = jQuery('#pwbf-edit-promo-event-id').val();
        var content = jQuery('#wp-pwbf-edit-promo-editor-wrap').hasClass('tmce-active') ? tinyMCE.activeEditor.getContent() : jQuery('#pwbf-edit-promo-editor').val();

        jQuery('#pwbf-edit-promo-button-container, #pwbf-edit-promo-saving').toggle();

        jQuery.post(ajaxurl, {'action': 'pw-black-friday-save-promo', 'event_id': eventId, 'content': content, 'security': pwbf.nonces.save_promo}, function(result) {
            if (result.message == 'success') {
                window.location = pwbf.admin_url;
            } else {
                jQuery('#pwbf-edit-promo-saving').html('<span style="color: red; font-weight: 600;">' + result.message + '</span>');
                jQuery('#pwbf-edit-promo-button-container').toggle();
            }

        }).fail(function(xhr, textStatus, errorThrown) {
            jQuery('#pwbf-edit-promo-button-container, #pwbf-edit-promo-saving').toggle();

            if (errorThrown) {
                alert(pwbf.i18n.error + ': ' + errorThrown);
            }
        });

        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-promo-button-preview').on('click', function(e) {
        jQuery('#pwbf-edit-promo-form').submit();
        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-promo-button-cancel').on('click', function(e) {
        if (confirm(pwbf.i18n.discard_changes)) {
            window.location = pwbf.admin_url;
        }

        e.preventDefault();
        return false;
    });

    jQuery('.pwbf-limited-lines-toggle').on('click', function(e) {
        var limited = jQuery(this).closest('.pwbf-limited-lines');
        var unlimted = limited.next('.pwbf-unlimited-lines');
        limited.hide();
        unlimted.show();

        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-countdowns-save').on('click', function(e) {
        jQuery('#pwbf-edit-countdowns').css('display', 'none');
        jQuery('#pwbf-edit-countdowns-saving').css('display', 'inline-block');

        var form = jQuery('#pwbf-edit-countdowns-form').serialize();

        jQuery.post(ajaxurl, {'action': 'pw-black-friday-save-countdowns', 'form': form, 'security': pwbf.nonces.save_countdowns}, function( result ) {
            if (result.complete === true) {
                window.location = pwbf.admin_url_root;

            } else {
                jQuery('#pwbf-edit-countdowns').css('display', 'inline-block');
                jQuery('#pwbf-edit-countdowns-saving').css('display', 'none');
                alert(result.message);
            }

        }).fail(function(xhr, textStatus, errorThrown) {
            if (errorThrown) {
                alert(pwbf.i18n.error + ': ' + errorThrown);
            }
            window.location = pwbf.admin_url;
        });

        e.preventDefault();
        return false;
    });

    jQuery('.pwbf-edit-countdowns-preview-button').on('click', function(e) {
        var field = jQuery(this).attr('data-field');
        jQuery('#pwbf-edit-countdowns-field').val(field);
        jQuery('#pwbf-edit-countdowns-form').submit();
        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-countdowns-cancel').on('click', function(e) {
        history.back();
        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-event-save').on('click', function(e) {
        if (pwbfValidateEvent()) {
            jQuery('#pwbf-edit-event, #pwbf-intro-container').css('display', 'none');
            jQuery('#pwbf-edit-event-saving').css('display', 'inline-block');

            var form = jQuery('#pwbf-edit-event-form').serialize();

            jQuery.post(ajaxurl, {'action': 'pw-black-friday-save-event', 'form': form, 'security': pwbf.nonces.save_event}, function( result ) {
                if (result.complete == true) {
                    window.location = pwbf.admin_url_root;

                } else {
                    jQuery('#pwbf-edit-event').css('display', 'inline-block');
                    jQuery('#pwbf-edit-event-saving').css('display', 'none');
                    alert(result.message);
                }

            }).fail(function(xhr, textStatus, errorThrown) {
                if (errorThrown) {
                    alert(pwbf.i18n.error + ': ' + errorThrown);
                }
                window.location = pwbf.admin_url;
            });
        }

        e.preventDefault();
        return false;
    });

    jQuery('#pwbf-edit-event-cancel').on('click', function(e) {
        history.back();
        e.preventDefault();
        return false;
    });
});

function pwbfWizardLoadStep(step, validate, skipHistory) {
    // This is located in wizard_steps.php since it's dynamic.
    if (validate === true && pwbfWizardValidateStep(step - 1) === false) {
        return;
    }

    var loadFunction = window["pwbfWizardLoadStep" + step];
    if (typeof loadFunction === 'function') {
        loadFunction();
    }

    if (!skipHistory) {
        history.pushState({ type: 'step', step: step}, null, pwbf.admin_url + '&step=' + parseInt(step));
    }

    jQuery('.pwbf-share-container').hide();
    jQuery('.pwbf-main-content').css('display', 'none');
    jQuery('.pwbf-wizard-step').css('display', 'none');
    jQuery('#pwbf-wizard-step-saving').css('display', 'none');
    jQuery('#pwbf-wizard-step-' + parseInt(step)).css('display', 'inline-block');
    jQuery('#pwbf-wizard-step-' + parseInt(step)).find('input[type=text],input[type=number],textarea,select').filter(':visible:first').focus();
}

function pwbfWizardValidateStep(step) {
    var validateFunction = window["pwbfWizardValidateStep" + step];
    if (typeof validateFunction === 'function') {
        if (!validateFunction()) {
            return false;
        }
    }

    return true;
}

function pwbfWizardFinish() {
    // This is located in wizard_steps.php since it's dynamic.
    if (pwbfWizardValidateStep(pwbf.last_step) === false) {
        return;
    }

    jQuery('.pwbf-wizard-step').css('display', 'none');
    jQuery('#pwbf-wizard-step-saving').css('display', 'inline-block');

    var form = jQuery('#pwbf-wizard-form').serialize();

    jQuery.post(ajaxurl, {'action': 'pw-black-friday-save-deal', 'form': form, 'security': pwbf.nonces.save_deal}, function(result) {
        if (result.complete === true) {
            pwbfWizardClose();

        } else {
            alert(result.message);

            if (result.step) {
                pwbfWizardLoadStep(result.step);
            } else {
                pwbfWizardLoadStep(pdbf.last_step);
            }
        }

    }).fail(function(xhr, textStatus, errorThrown) {
        if (errorThrown) {
            alert(pwbf.i18n.error + ': ' + errorThrown);
        }
        window.location = pwbf.admin_url;
    });
}

function pwbfWizardClose() {
    window.location = pwbf.admin_url_root;
}

function pwbfCreateEvent() {
    var today = new Date();
    var tomorrowString = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + (today.getDate() + 1);

    var beginDate = tomorrowString;
    var beginTime = '12:00 AM';
    var endDate = tomorrowString;
    var endTime = '11:59 PM';
    var title = '';
    var header = '';
    var color = '';

    pwbfEditEvent('', beginDate, beginTime, endDate, endTime, title, header, color);
}

function pwbfEditEvent(eventId, beginDate, beginTime, endDate, endTime, title, header, color) {
    jQuery('#pwbf-edit-event-event_id').val(eventId);
    jQuery('#pwbf-edit-event-begin_date').val(beginDate);
    jQuery('#pwbf-edit-event-begin_time').val(beginTime);
    jQuery('#pwbf-edit-event-end_date').val(endDate);
    jQuery('#pwbf-edit-event-end_time').val(endTime);
    jQuery('#pwbf-edit-event-title').val(title);
    jQuery('#pwbf-edit-event-header').text(header).toggle(header !== '');
    jQuery('#pwbf-edit-event').css('border-color', color);

    history.pushState({ type: 'edit_event'}, null, pwbf.admin_url + '&action=edit_event');

    pwbfEditEventOpen();
}

function pwbfAddButton(input, label, selectedDate) {
    if (jQuery(input).is('#pwbf-edit-event-begin_date')) {
        setTimeout(function () {
            jQuery('.ui-priority-secondary[data-handler="today"]').hide();
            jQuery('.ui-datepicker-close').removeClass('ui-priority-primary').addClass('ui-priority-secondary');

            var buttonPane = jQuery(input).datepicker("widget").find(".ui-datepicker-buttonpane");

            jQuery("<button>", {
                text: label,
                click: function () {
                    pwbfSelectDate(jQuery('#pwbf-edit-event-begin_date'), selectedDate);
                    pwbfSelectDate(jQuery('#pwbf-edit-event-end_date'), selectedDate);
                    jQuery('#ui-datepicker-div').hide();
                    jQuery(input).datepicker('setDate', selectedDate);
                    jQuery(input).datepicker('hide');
                    jQuery(input).blur();
                }
            }).appendTo(buttonPane).addClass("ui-datepicker-current ui-state-default ui-priority-primary ui-corner-all");

        }, 1);
    }
}

function pwbfSelectDate(input, selectedDate) {
    var option   = jQuery(input).is('#pwbf-edit-event-begin_date') ? 'minDate' : 'maxDate';
    var instance = jQuery(input).data('datepicker');
    var date     = jQuery.datepicker.parseDate( instance.settings.dateFormat || jQuery.datepicker._defaults.dateFormat, selectedDate, instance.settings);
    pwbfDates.not(input).datepicker('option', option, date);
}

function pwbfValidateEvent() {
    if (!jQuery('#pwbf-edit-event-begin_date').val()) {
        alert('Begin Date is required.');
        jQuery('#pwbf-edit-event-begin_date').focus();
        return false;
    }
    if (!jQuery('#pwbf-edit-event-begin_time').val()) {
        alert('Begin Time is required.');
        jQuery('#pwbf-edit-event-begin_time').focus();
        return false;
    }

    if (!jQuery('#pwbf-edit-event-end_date').val()) {
        alert('End Date is required.');
        jQuery('#pwbf-edit-event-end_date').focus();
        return false;
    }
    if (!jQuery('#pwbf-edit-event-end_time').val()) {
        alert('End Time is required.');
        jQuery('#pwbf-edit-event-end_time').focus();
        return false;
    }

    if (!jQuery('#pwbf-edit-event-title').val() || jQuery('#pwbf-edit-event-title').val().trim() == '') {
        alert('Title is required.');
        jQuery('#pwbf-edit-event-title').focus();
        return false;
    }

    return true;
}

function pwbfEditEventOpen() {
    jQuery('.pwbf-main-content').hide();
    jQuery('#pwbf-intro-container').hide();
    jQuery('#pwbf-edit-event').css('display', 'inline-block');
}

function pwbfEditEventClose() {
    jQuery(".pwbf-main-content").show()
    jQuery("#pwbf-intro-container").show();
    jQuery('#pwbf-edit-event').hide();
}

function pwbfEditCountdowns(eventId, title, upcomingCountdown, upcomingOffset, endingCountdown) {
    jQuery('#pwbf-edit-countdowns-event_id').val(eventId);
    jQuery('#pwbf-edit-countdowns-upcoming_countdown').val(upcomingCountdown);
    jQuery('#pwbf-edit-countdowns-upcoming_offset').val(upcomingOffset);
    jQuery('#pwbf-edit-countdowns-ending_countdown').val(endingCountdown);
    jQuery('#pwbf-edit-countdowns-title').text(title + ' ' + pwbf.i18n.countdowns);

    history.pushState({ type: 'edit_countdowns'}, null, pwbf.admin_url + '&action=edit_countdowns');

    pwbfEditCountdownsOpen();
}

function pwbfEditCountdownsOpen() {
    jQuery('.pwbf-main-content').hide();
    jQuery('#pwbf-edit-countdowns').css('display', 'inline-block');
}

function pwbfEditCountdownsClose() {
    jQuery('.pwbf-main-content').show();
    jQuery('#pwbf-edit-countdowns').hide();
}

function pwbfDeleteEvent(eventId) {
    if (eventId && eventId > 0) {
        if (confirm(pwbf.i18n.confirm_delete_event)) {
            jQuery.post(ajaxurl, {'action': 'pw-black-friday-delete-event', 'event_id': eventId, 'security': pwbf.nonces.delete_event}, function() {
                location.reload();
            });
        }
    }
}

function pwbfDeleteDeal(dealId) {
    if (confirm(pwbf.i18n.confirm_delete_deal)) {
        jQuery.post(ajaxurl, {'action': 'pw-black-friday-delete-deal', 'deal_id': dealId, 'security': pwbf.nonces.delete_deal}, function() {
            location.reload();
        });
    }
}

function pwbfIsNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}