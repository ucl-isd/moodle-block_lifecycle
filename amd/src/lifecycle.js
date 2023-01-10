import Ajax from 'core/ajax';
import notification from 'core/notification';

// Default auto suggested read-only date.
let defaultfreezedate = '';
// The datepicker original value before user make any changes.
let originalfreezedatevalue = '';

export const init = (courseid) => {
    // The course is read-only. Do nothing.
    if (!document.getElementById('lifecycle-settings-container')) {
        return;
    }

    initscheduledfreezedateblock(courseid);
    document.getElementById('update_auto_freezing_preferences_button').addEventListener('click', () => {
        updatepreferences(courseid);
    });

    document.getElementById('override-freeze-date-button').addEventListener("click", function(e) {
        e.preventDefault();
        togglesettings();
    });
};

/**
 * Toggle the automatic read only settings container.
 */
function togglesettings() {
    let content = document.getElementById('automatic-read-only-settings');
    document.getElementById('override-freeze-date-button').classList.toggle('active');
    if (content.style.maxHeight) {
        content.style.maxHeight = null;
    } else {
        content.style.maxHeight = content.scrollHeight + "px";
    }
}

/**
 * Validate the preferences.
 * @return {boolean}
 */
function validate() {
    let freezedateelement = document.getElementById('delayfreezedate');
    let togglefreezebutton = document.getElementById('togglefreezebutton');

    // The override freeze date should not be saved when auto read-only is disabled.
    if (!togglefreezebutton.checked && freezedateelement.value.length > 0) {
        notification.alert(
            'Invalid selection',
            'Please enable automatic read only or remove the overrides Read-Only date.',
            'OK'
        );
        return false;
    }

    if (freezedateelement.value.length > 0) {
        // The default suggested date is not initialized, so cannot continue the checking.
        if (defaultfreezedate === '') {
                notification.alert(
                    'Error',
                    'Could not get the automatically suggested date, please try again later.',
                    'OK'
                );
            freezedateelement.value = originalfreezedatevalue;
            return false;
        } else {
            let defaultfreezedateobj = new Date(defaultfreezedate);
            let freezedateobj = new Date(freezedateelement.value);

            // The override freeze date should not be saved when it is earlier than the default suggested date.
            if (freezedateobj < defaultfreezedateobj || freezedateobj < new Date()) {
                notification.alert(
                    'Invalid Selection',
                    'The date for a Read-Only override must be post the automatically suggested date (' +
                    defaultfreezedateobj.toLocaleDateString() +
                    '), earlier dates may not be used.',
                    'OK'
                );
                freezedateelement.value = originalfreezedatevalue;
                return false;
            }
        }
    }

    return true;
}

/**
 * Initialize the scheduled freeze date container.
 *
 * @param {int} courseid
 */
function initscheduledfreezedateblock(courseid) {
    let scheduledfreezedatecontainer = document.getElementById('scheduled-freeze-date-container');
    originalfreezedatevalue = document.getElementById('delayfreezedate').value;

    // Fetch the scheduled course read-only date.
    if (document.getElementById('togglefreezebutton').checked) {
        document.getElementById('scheduled-freeze-date').innerHTML = '';
        scheduledfreezedatecontainer.style.display = 'block';
        Ajax.call([{
            methodname: 'block_lifecycle_get_scheduled_freeze_date',
            args: {
                'courseid': courseid
            },
        }])[0].done(function(response) {
            document.getElementById('scheduled-freeze-date').innerHTML = response.scheduledfreezedate;
            // Set the default suggested date.
            if (response.success === 'true') {
                defaultfreezedate = response.defaultfreezedate;
            }
        }).fail(function(err) {
            window.console.log(err);
        });
    } else {
        scheduledfreezedatecontainer.style.display = 'none';
    }
}

/**
 * Update the auto context freezing preferences.
 * @param {int} courseid
 */
function updatepreferences(courseid) {
    let preferences = {
        togglefreeze: document.getElementById('togglefreezebutton').checked,
        delayfreezedate: document.getElementById('delayfreezedate').value
    };

    if (validate()) {
        Ajax.call([{
            methodname: 'block_lifecycle_update_auto_freezing_preferences',
            args: {
                'courseid': courseid,
                'preferences': JSON.stringify(preferences)
            },
        }])[0].done(function(response) {
            notification.addNotification({
                message: response.message,
                type: response.success ? 'success' : 'error'
            });
            initscheduledfreezedateblock(courseid);
            togglesettings();
        }).fail(function(err) {
            window.console.log(err);
        });
    }
}
