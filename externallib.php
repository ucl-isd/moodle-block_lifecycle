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

defined('MOODLE_INTERNAL') || die();

use block_lifecycle\manager;
require_once($CFG->libdir . "/externallib.php");

/**
 * Web service for external program.
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class block_lifecycle_external extends external_api {
    /**
     * Returns the description of query parameters
     * @return external_function_parameters
     */
    public static function update_auto_freezing_preferences_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
                'preferences' => new external_value(PARAM_RAW, 'Preferences', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Returns the description of query result.
     * @return external_description
     */
    public static function update_auto_freezing_preferences_returns() {
        return
            new external_single_structure(
                array(
                    'success' => new external_value(PARAM_TEXT, 'Request result'),
                    'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL),
                    'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL)
                )
            );
    }

    /**
     * Update the auto context freezing preferences.
     *
     * @param int $courseid
     * @param string $preferences
     * @return array Result
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function update_auto_freezing_preferences(int $courseid, string $preferences): array {
        // Parameters validation.
        $params = self::validate_parameters(
            self::update_auto_freezing_preferences_parameters(),
            array('courseid' => $courseid, 'preferences' => $preferences));

        return (array) manager::update_auto_freezing_preferences($params['courseid'], json_decode($params['preferences']));
    }

    /**
     * Returns the description of scheduled freeze date query parameters
     * @return external_function_parameters
     */
    public static function get_scheduled_freeze_date_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Returns the description of scheduled freeze date query result
     * @return external_description
     */
    public static function get_scheduled_freeze_date_returns() {
        return
            new external_single_structure(
                array(
                    'success' => new external_value(PARAM_TEXT, 'Request result'),
                    'scheduledfreezedate' => new external_value(PARAM_TEXT, 'Scheduled freeze date'),
                    'error' => new external_value(PARAM_TEXT, 'Error message', VALUE_OPTIONAL)
                ),
            );
    }

    /**
     * Get the scheduled freeze date.
     *
     * @param int $courseid
     * @return array URL
     * @throws invalid_parameter_exception|coding_exception
     */
    public static function get_scheduled_freeze_date(int $courseid): array {
        // Parameters validation.
        $params = self::validate_parameters(
            self::get_scheduled_freeze_date_parameters(),
            array('courseid' => $courseid));

        $scheduledfreezedate = manager::get_scheduled_freeze_date($params['courseid']);
        $scheduledfreezedate = !empty($scheduledfreezedate) ?
            $scheduledfreezedate : get_string('error:cannotgetscheduledfreezedate', 'block_lifecycle');

        return array('success' => true, 'scheduledfreezedate' => $scheduledfreezedate);
    }
}
