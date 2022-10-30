import Ajax from 'core/ajax';
import notification from 'core/notification';

export const init = (courseid) => {
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
    if (!document.getElementById('togglefreezebutton').checked && document.getElementById('delayfreezedate').value.length > 0) {
        notification.alert(
            'Invalid selection',
            'Please enable automatic read only or remove the overrides freeze date.',
            'OK'
        );
        return false;
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
