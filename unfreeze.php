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
 * unfreeze page for block_lifecycle to unfreeze a frozen course.
 *
 * @package    block_lifecycle
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */

namespace block_lifecycle;

use context_course;
use core\output\notification;
use moodle_exception;
use moodle_url;

require_once('../../config.php');

// Course ID.
$courseid = required_param('id', PARAM_INT);

// Get course instance.
if (!$course = get_course($courseid)) {
    throw new moodle_exception('course not found.', 'block_lifecycle');
}

// Make sure user is authenticated.
require_login($course);

// Unfreeze the course.
try {
    manager::unfreeze_course($courseid);
    // Unfreeze done. Redirect back to the course page.
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} catch (moodle_exception $e) {
    // Unfreeze failed. Redirect back to the course page with error message.
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]), $e->getMessage(), null, notification::NOTIFY_ERROR);
}
