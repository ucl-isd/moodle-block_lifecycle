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
}
