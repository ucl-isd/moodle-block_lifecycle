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

namespace block_lifecycle;

use context_course;

/**
 * Global library functions for block_lifecycle
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class manager {
    /** @var string Default CLC course custom field for finding potential academic years */
    const CLC_ACADEMIC_YEAR_FIELD = 'course_year';
    /** @var string  Default block_lifecycle database table */
    const DEFAULT_TABLE = 'block_lifecycle';

    /**
     * Returns the course lifecycle information.
     *
     * @param int $courseid
     * @return array|string
     * @throws \dml_exception
     */
    public static function get_course_lifecycle_info(int $courseid) {
        $result = '';
        // Get the academic year start date config.
        $academicyeardate = get_config('block_lifecycle', 'academic_year_start_date') ?: '08-01';
        // Course's academic year.
        $courseacademicyear = self::get_course_clc_academic_year($courseid);

        if (!empty($courseacademicyear)) {
            $class = '';
            // Course academic year start date.
            $startdate = $courseacademicyear . '-' . $academicyeardate;
            // Course academic year end date.
            $enddate = ((int)$courseacademicyear + 1) . '-' . $academicyeardate;

            // Current time within the course's academic year period.
            if (time() > strtotime($startdate) && time() < strtotime($enddate)) {
                $class = 'current';
                // Current time earlier than the course's academic year start date.
            } else if (time() < strtotime($startdate)) {
                $class = 'future';
            }

            $text = 'Moodle ' . $courseacademicyear . '/' . ((int) substr($courseacademicyear, -2) + 1);
            $result = array('class' => $class, 'text' => $text);
        }

        return $result;
    }

    /**
     * Get the potential academic years.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_potential_academic_years() : array {
        global $DB;

        $fieldid = null;
        if ($id = get_config('block_lifecycle', 'clcfield')) {
            $fieldid = $id;
        } else {
            if ($customfields = self::get_clc_custom_course_fields()) {
                foreach ($customfields as $field) {
                    if ($field->shortname === self::CLC_ACADEMIC_YEAR_FIELD) {
                        $fieldid = $field->id;
                    }
                }
            }
        }
        $sql = "SELECT DISTINCT cd.value
            FROM {customfield_data} cd JOIN {customfield_field} cf ON cd.fieldid = cf.id
            WHERE cf.id = :fieldid AND cd.value <> '' AND cd.value IS NOT NULL
            ORDER BY cd.value DESC";

        return $DB->get_records_sql($sql, array('fieldid' => $fieldid));
    }

    /**
     * Get CLC course custom fields.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_clc_custom_course_fields(): array {
        global $DB;

        $sql = "SELECT cf.id, cf.shortname
            FROM {customfield_field} cf JOIN {customfield_category} cc ON cf.categoryid = cc.id
            WHERE cc.name = 'CLC' AND cf.type = 'text'";

        return $DB->get_records_sql($sql);
    }

    /**
     * Get courses for context freezing.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_courses_for_context_freezing(): array {
        global $DB;

        $eligiblecourses = array();

        // Get weeks delay in seconds.
        $enddateextend = self::get_weeks_delay_in_seconds();

        $sql = "SELECT c.id, c.fullname, c.enddate
                FROM {course} c JOIN {context} ctx ON c.id = ctx.instanceid
                WHERE c.id <> :siteid AND ctx.contextlevel = 50 AND ctx.locked = 0
                AND c.enddate <> 0 AND (c.enddate + :enddateextend) < :currenttime";

        $potentialcourses = $DB->get_records_sql($sql,
            array('siteid' => SITEID, 'enddateextend' => $enddateextend, 'currenttime' => time()));

        if (!empty($potentialcourses)) {
            foreach ($potentialcourses as $course) {
                if (self::check_course_is_eligible_for_context_freezing($course)) {
                    $eligiblecourses[] = $course;
                }
            }
        }

        return $eligiblecourses;
    }

    /**
     * Freeze course context.
     *
     * @param int $courseid
     * @return void
     * @throws \coding_exception
     */
    public static function freeze_course(int $courseid) {
        $context = context_course::instance($courseid);
        $parentcontext = $context->get_parent_context();
        if ($parentcontext && !empty($parentcontext->locked)) {
            // Can't make changes to a context whose parent is locked.
            throw new \coding_exception('Parent context is locked. Course ID: ' . $courseid);
        }

        // Lock the course context.
        $context->set_locked(true);
    }

    /**
     * Check should the academic year label be displayed.
     *
     * @param int $courseid
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function should_show_ay_label(int $courseid): bool {
        $coursecontext = context_course::instance($courseid);
        $course = get_course($courseid);
        // Course has no enddate.
        if (empty($course->enddate)) {
            return false;
        }

        // Check user's permission.
        if (!has_capability('block/lifecycle:view', $coursecontext)) {
            return false;
        }

        return true;
    }

    /**
     * Check should the auto context freezing preferences be displayed.
     *
     * @param int $courseid
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function should_show_auto_freezing_preferences(int $courseid): bool {
        $coursecontext = context_course::instance($courseid);
        $course = get_course($courseid);
        // Course has no enddate.
        if (empty($course->enddate)) {
            return false;
        }

        // Course hasn't ended.
        if ($course->enddate > time()) {
            return false;
        }

        // Check user's permission.
        if (!has_capability('block/lifecycle:overridecontextfreeze', $coursecontext)) {
            return false;
        }

        // Check if the course has clc academic year set.
        if (is_null(self::get_course_clc_academic_year($courseid))) {
            return false;
        }

        // Do not show settings if course is read only.
        if (self::is_course_frozen($courseid)) {
            return false;
        }

        return true;
    }

    /**
     * Update auto context freezing preferences.
     *
     * @param int $courseid
     * @param \stdClass $preferences
     * @return \stdClass
     * @throws \coding_exception
     */
    public static function update_auto_freezing_preferences(int $courseid, \stdClass $preferences): \stdClass {
        global $DB;
        $result = new \stdClass();
        try {
            // Create data object.
            $data = new \stdClass();
            $data->courseid = $courseid;
            $data->freezeexcluded = ($preferences->togglefreeze) ? 1 : 0;
            $data->freezedate = strtotime($preferences->delayfreezedate);

            // Update record if exists, otherwise insert.
            if ($record = self::get_auto_context_freezing_preferences($courseid)) {
                $data->id = $record->id;
                $data->timemodified = time();
                $result->success = $DB->update_record(self::DEFAULT_TABLE, $data);
            } else {
                $data->timecreated = $data->timemodified = time();
                $result->success = (bool)$DB->insert_record(self::DEFAULT_TABLE, $data);
            }

            if ($result->success) {
                $result->message = get_string('error:updatepreferencessuccess', 'block_lifecycle');
            } else {
                $result->message = get_string('error:updatepreferencesfailed', 'block_lifecycle');
            }
        } catch (\Exception $exception) {
            $result->success = false;
            $result->message = get_string('error:updatepreferencesfailed', 'block_lifecycle');
        }

        return $result;
    }

    /**
     * Get auto context freezing preferences.
     *
     * @param int $courseid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function get_auto_context_freezing_preferences(int $courseid) {
        global $DB;
        return $DB->get_record(self::DEFAULT_TABLE, ['courseid'  => $courseid]);
    }

    /**
     * Check if the course is frozen.
     *
     * @param int $courseid
     * @return bool
     */
    public static function is_course_frozen(int $courseid): bool {
        $context = context_course::instance($courseid);
        return $context->is_locked();
    }

    /**
     * Get scheduled freeze date.
     *
     * @param int $courseid
     * @return array|false
     * @throws \dml_exception
     */
    public static function get_scheduled_freeze_date(int $courseid) {
        // No furthest date can be determined.
        if (!$defaultscheduledfreezedate = self::get_furthest_date($courseid)) {
            return false;
        }

        // Add one day if the scheduled freeze date is already passed.
        if ($defaultscheduledfreezedate < time()) {
            $defaultscheduledfreezedate = strtotime('+1 day');
        }

        // Set scheduled freeze date.
        $scheduledfreezedate = $defaultscheduledfreezedate;

        // Compare with the delay freeze date if any.
        if ($preferences = self::get_auto_context_freezing_preferences($courseid)) {
            if ($preferences->freezedate > $defaultscheduledfreezedate) {
                $scheduledfreezedate = $preferences->freezedate;
            }
        }

        return [
            'defaultfreezedate' => date('Y-m-d', $defaultscheduledfreezedate),
            'scheduledfreezedate' => date('d/m/Y', $scheduledfreezedate)
        ];
    }


    /**
     * Get weeks delay in seconds.
     *
     * @return float|int
     * @throws \dml_exception
     */
    public static function get_weeks_delay_in_seconds() {
        // Get weeks delay for context freezing.
        $weeksdelay = get_config('block_lifecycle', 'weeks_delay');

        $enddateextend = 0;
        if ($weeksdelay > 0) {
            // Course end date extend by seconds.
            $enddateextend = $weeksdelay * 604800;
        }

        return $enddateextend;
    }

    /**
     * Get the furthest date among LSA end date and course end date, plus weeks delay.
     *
     * @param int $courseid Course id.
     * @return false|int Unix timestamp or false if cannot be determined.
     * @throws \dml_exception
     */
    private static function get_furthest_date(int $courseid) {
        // No course academic year found.
        if (!$academicyear = self::get_course_clc_academic_year($courseid)) {
            return false;
        }

        // Get course object.
        $course = get_course($courseid);

        // The course has no end date.
        if (empty($course->enddate)) {
            return false;
        }

        // Get configured late summer assessment end date.
        $lsaenddate = strtotime(get_config('block_lifecycle', 'late_summer_assessment_end_' . $academicyear));

        // Get the furthest date of LSA end date and course end date.
        $furthestdate = $lsaenddate > $course->enddate ? $lsaenddate : $course->enddate;

        // Return furthest date plus weeks delay.
        return $furthestdate + self::get_weeks_delay_in_seconds();
    }

    /**
     * Check course is eligible for context freezing.
     *
     * @param \stdClass $course
     * @return bool
     * @throws \dml_exception
     */
    private static function check_course_is_eligible_for_context_freezing(\stdClass $course): bool {
        // Check if the course has clc academic year set.
        if (!$academicyear = self::get_course_clc_academic_year($course->id)) {
            return false;
        }

        // No default read-only date determined.
        if (!$furthestdate = self::get_furthest_date($course->id)) {
            return false;
        }

        // The default read-only date must be in the past.
        if ($furthestdate > time()) {
            return false;
        }

        // Check against auto context freezing preferences.
        $preferences = self::get_auto_context_freezing_preferences($course->id);
        if ($preferences) {
            if ($preferences->freezeexcluded == 1 || $preferences->freezedate > time()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get CLC course academic year.
     *
     * @param int $courseid
     * @return mixed|null
     */
    private static function get_course_clc_academic_year(int $courseid) {
        $academicyear = null;
        $handler = \core_course\customfield\course_handler::create();
        $data = $handler->get_instance_data($courseid, true);
        foreach ($data as $dta) {
            if ($dta->get_field()->get('shortname') === self::CLC_ACADEMIC_YEAR_FIELD) {
                $academicyear = !empty($dta->get_value()) ? $dta->get_value() : null;
            }
        }

        return $academicyear;
    }
}
