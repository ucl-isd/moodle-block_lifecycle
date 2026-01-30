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

namespace block_lifecycle\task;

use block_lifecycle\manager;

/**
 * Task for freezing course context
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class freezecontext extends \core\task\scheduled_task {
    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:freezecontext', 'block_lifecycle');
    }

    /**
     * Remove old entries from table block_recent_activity
     * @throws \dml_exception
     */
    public function execute() {
        if (get_config('block_lifecycle', 'enabled_scheduled_task')) {
            if ($courses = manager::get_courses_for_context_freezing()) {
                foreach ($courses as $course) {
                    try {
                        manager::freeze_course($course->id);
                        mtrace($course->id . '_' . $course->fullname . ' is frozen now.');
                    } catch (\Exception $e) {
                        mtrace($e->getMessage());
                    }
                }
            }
        }
    }
}
