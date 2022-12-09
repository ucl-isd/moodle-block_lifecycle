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

/**
 * Configuration settings for block_lifecycle
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */

use block_lifecycle\manager;
use block_lifecycle\settings\admin_setting_configdate;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // General settings.
    $settings->add(new admin_setting_heading('block_lifecycle_general',
        get_string('generalsettings', 'block_lifecycle'),
        ''
    ));

    // The number of weeks to delay the context freezing.
    $options = array();
    for ($i = 0; $i <= 10; $i++) {
        $options[$i] = "$i";
    }

    $settings->add(new admin_setting_configselect(
        'block_lifecycle/weeks_delay',
        get_string('settings:weeksdelay', 'block_lifecycle'),
        get_string('settings:weeksdelay:desc', 'block_lifecycle'), 0, $options));

    // The CLC course custom field use for getting potential academic years.
    $options = array();
    $fields = manager::get_clc_custom_course_fields();
    if (!empty($fields)) {
        foreach ($fields as $field) {
            $options[$field->id] = $field->shortname;
        }
    }
    $settings->add(new admin_setting_configselect(
        'block_lifecycle/clcfield',
        get_string('settings:clcfield', 'block_lifecycle'),
        get_string('settings:clcfield:desc', 'block_lifecycle'), '', $options));

    // Setting to enable/disable the scheduled task for auto context freezing.
    $settings->add(new admin_setting_configcheckbox(
        'block_lifecycle/enabled_scheduled_task',
        get_string('settings:enablescheduledtask', 'block_lifecycle'),
        get_string('settings:enablescheduledtask:desc', 'block_lifecycle'),
        false,
        true,
        false
    ));

    // Academic year start date used to calculate the current academic year period.
    // Default '08-01', e.g. academic year for 2022 starts at 2022-08-01.
    $settings->add(new admin_setting_configtext('block_lifecycle/academic_year_start_date',
        get_string('settings:academicyearstartdate', 'block_lifecycle'),
        get_string('settings:academicyearstartdate:desc', 'block_lifecycle'),
        '08-01'
    ));

    // Generate settings based on possible academic years.
    if ($potentialacademicyears = manager::get_potential_academic_years()) {
        foreach ($potentialacademicyears as $year) {
            if (preg_match('/^\d{4}$/', $year->value) !== 1) {
                continue;
            }

            // Late summer assessment end dates.
            $settings->add(new admin_setting_configdate(
                'block_lifecycle/late_summer_assessment_end_' . $year->value,
                get_string('settings:latesummerassessment:end', 'block_lifecycle', $year->value),
                get_string('settings:latesummerassessment:end:desc', 'block_lifecycle', $year->value),
                $year->value + 1 . '-11-30'
            ));
        }
    }
}
