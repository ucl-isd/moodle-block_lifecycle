import Ajax from 'core/ajax';
import notification from 'core/notification';
import {getStrings} from 'core/str';

// Default auto suggested read-only date.
let defaultfreezedate = '';
// The datepicker original value before user make any changes.
let originalfreezedatevalue = '';

export const init = (courseid) => {
    // The course is read-only. Initialize the unfreeze button and return.
    if (!document.getElementById('lifecycle-settings-container')) {
        // Initialize the unfreeze button.
        initUnfreezeButton(courseid);
        return;
    }

    initscheduledfreezedateblock(courseid);
    // On click listener for "Disable Automatic Read-Only" toggle.
    document.getElementById('togglefreezebutton').addEventListener('click', (event) => {
        togglefreezebutton(event.target.checked);
    });

    // Save button.
    document.getElementById('update_auto_freezing_preferences_button').addEventListener('click', () => {
        updatepreferences(courseid);
    });

    // Drop down settings button.
    document.getElementById('override-freeze-date-button').addEventListener("click", function(e) {
        e.preventDefault();
        togglesettings();
    });
};

/**
 * Disable read-only date input when "Disable Automatic Read-Only button" is on.
 *
 * @param {boolean} checked
 */
function togglefreezebutton(checked) {
    let readonlydateinput = document.getElementById('delayfreezedate');
    if (checked) {
        readonlydateinput.value = '';
        readonlydateinput.disabled = true;
    } else {
        readonlydateinput.disabled = false;
    }
}

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

    // Hide scheduled read-only date text at the beginning.
    scheduledfreezedatecontainer.style.display = 'none';

    // Get scheduled read-only dates.
    Ajax.call([{
        methodname: 'block_lifecycle_get_scheduled_freeze_date',
        args: {
            'courseid': courseid
        },
    }])[0].done(function(response) {
        // Show scheduled date.
        if (!document.getElementById('togglefreezebutton').checked) {
            document.getElementById('scheduled-freeze-date').innerHTML = response.scheduledfreezedate;
            scheduledfreezedatecontainer.style.display = 'block';
        } else {
            // Disable read-only date input depends on freeze button status.
            togglefreezebutton(true);
        }
        // Set the default suggested date.
        if (response.success === 'true') {
            defaultfreezedate = response.defaultfreezedate;
        }
    }).fail(function(err) {
        window.console.log(err);
    });
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

/**
 * Initialize the unfreeze button.
 *
 * @param {int} courseid
 */
function initUnfreezeButton(courseid) {
    // Get the unfreeze button.
    let unfreezeButton = document.getElementById('unfreeze-button');

    // The course is not frozen. Do nothing.
    if (!unfreezeButton) {
        return;
    }

    let contextname = unfreezeButton.getAttribute('data-contextname');

    unfreezeButton.addEventListener('click', event => {
        event.preventDefault();

        const requiredStrings = [
            {key: 'confirmcontextunlock', component: 'admin', param: {'contextname': contextname}},
        ];

        getStrings(requiredStrings).then(([unlockBody]) => {
            return notification.confirm('Enable editing', unlockBody, 'Confirm', null, () => {
                    Ajax.call([{
                        methodname: 'block_lifecycle_unfreeze_course',
                        args: {
                            'courseid': courseid
                        },
                    }])[0].done(function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            notification.addNotification({
                                message: response.message || 'An error occurred while enabling editing.',
                                type: 'error'
                            });
                            // Scroll to the top of the page to show the error message.
                            window.scrollTo({top: 0, behavior: "instant"});
                        }
                    }).fail(function(err) {
                        window.console.log(err);
                    });
            });
        }).catch(
            (error) => {
                window.console.log(error);
                return error;
            }
        );
    });
}
