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
    /** Default CLC course custom field for finding potential academic years */
    const CLC_ACADEMIC_YEAR_FIELD = 'course_year';

    /**
     * Returns the context freezing data.
     *
     * @return string
     */
    public static function get_context_freezing_data(): string {
        return 'Context freezing settings';
    }

    /**
     * Returns the course lifecycle information.
     *
     * @return string
     */
    public static function get_course_lifecycle_info(): string {
        return '2022-23';
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

        // Get weeks delay for context freezing.
        $weeksdelay = get_config('block_lifecycle', 'weeks_delay');

        $enddateextend = 0;
        if ($weeksdelay > 0) {
            // Course end date extend by seconds.
            $enddateextend = $weeksdelay * 604800;
        }

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

        // Get configured late summer assessment end date.
        $lsaenddate = strtotime(get_config('block_lifecycle', 'late_summer_assessment_end_' . $academicyear));

        // The LSA end date must be passed.
        if (time() < $lsaenddate) {
            return false;
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
