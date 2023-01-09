<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

use block_lifecycle\manager;

/**
 * Class block_lifecycle_renderer
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class block_lifecycle_renderer extends plugin_renderer_base {
    /**
     * Returns the content html for the block.
     *
     * @param int $courseid
     * @return string
     * @throws coding_exception|dml_exception
     */
    public function fetch_block_content(int $courseid): string {
        // Check if the course is frozen. If yes, disable inputs.
        $disabled = manager::is_course_frozen($courseid) ? 'disabled' : '';

        // Get auto context freezing preferences.
        $togglefreezing = 'checked';
        $delayfreezedate = '';
        $preferences = manager::get_auto_context_freezing_preferences($courseid);
        if ($preferences) {
            // Course excluded. Turn off context freezing.
            if ($preferences->freezeexcluded == 1) {
                $togglefreezing = '';
            }
            if ($preferences->freezedate > 0) {
                $delayfreezedate = date('Y-m-d', $preferences->freezedate);
            }
        }

        // Create disable freezing button help icon.
        $helpicontogglefreezing = new help_icon('help:togglefreezing', 'block_lifecycle');
        $helpicontogglefreezinghtml = $this->output->render($helpicontogglefreezing);

        // Toggle freezing button.
        $content = html_writer::start_div('lifecycle');

        // Scheduled freeze date.
        $content .= html_writer::div(
            '<h6>' . get_string('label:readonlydate', 'block_lifecycle') . '</h6>'.
            '<div class="scheduled-freeze-date" id="scheduled-freeze-date"></div>',
            '', array('id' => 'scheduled-freeze-date-container'));

        $content .= html_writer::div('<a class="btn btn-success"><i class="fa-edit fa fa-fw"></i>' .
            get_string('buttoneditsettings', 'block_lifecycle') .
            '</a>',
            'override-freeze-date-button', array('id' => 'override-freeze-date-button'));
        $content .= html_writer::start_div('automatic-read-only-settings', array('id' => 'automatic-read-only-settings'));
        $content .= html_writer::div(
            '<label>Enable:</label>'. $helpicontogglefreezinghtml .
            '<div class="form-check form-switch">'.
            '<input class="form-check-input" type="checkbox" role="switch" id="togglefreezebutton" '.
            $togglefreezing . ' ' . $disabled . '>'.
            '</div>', 'togglefreezebutton'
        );

        // Create delay freeze date help icon.
        $helpicondelayfreezedate = new help_icon('help:delayfreezedate', 'block_lifecycle');
        $helpicondelayfreezedatehtml = $this->output->render($helpicondelayfreezedate);

        // Delay freezing date input.
        $content .= html_writer::div(
            '<p><label>' . get_string('label:readonlydateinput', 'block_lifecycle') . '</label>'. $helpicondelayfreezedatehtml .
            '<input type="date" class="delayfreezedate-input" id="delayfreezedate" value="'. $delayfreezedate .'" ' . $disabled . '>
            </p>', 'delayfreezedate'
        );

        // Update button.
        $content .= html_writer::div(
            '<button id="update_auto_freezing_preferences_button" class="btn btn-primary" '. $disabled .'>Save</button>',
            'updatebutton'
        );

        $content .= html_writer::end_div();
        $content .= html_writer::end_div();

        return $content;
    }

    /**
     * Returns the html for course academic year info.
     *
     * @param int $courseid
     * @return string
     * @throws dml_exception
     */
    public function fetch_clc_content(int $courseid): string {
        $content = '';
        if ($info = manager::get_course_lifecycle_info($courseid)) {
            $content = html_writer::start_div('clc_info ' . $info['class']);
            $content .= html_writer::div($info['text']);
            $content .= html_writer::end_div();
        }

        return $content;
    }

    /**
     * Return the html for course dates.
     *
     * @param int $courseid
     * @return string
     * @throws dml_exception
     */
    public function fetch_course_dates(int $courseid): string {
        $content = '';
        if ($course = get_course($courseid)) {
            $content = html_writer::start_div('course-dates');
            $content .= html_writer::div(
                    get_string('lifecycle:startdate', 'block_lifecycle', date('d/m/Y', $course->startdate)) .'<br>'.
                    get_string('lifecycle:enddate', 'block_lifecycle', date('d/m/Y', $course->enddate))
            );
            $content .= html_writer::end_div();
        }

        return $content;
    }

    /**
     * Return the html for 'read-only' notification.
     *
     * @return string
     * @throws coding_exception
     */
    public function fetch_course_read_only_notification(): string {
        $content = html_writer::start_div('raad-only-notification');
        $content .= html_writer::div(get_string('lifecycle:coursereadonly', 'block_lifecycle'));
        $content .= html_writer::end_div();

        return $content;
    }
}
