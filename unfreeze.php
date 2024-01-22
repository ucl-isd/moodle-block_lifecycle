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
 * Unfreeze a course context.
 *
 * @package    block_lifecycle
 * @copyright  2024 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Parameters.
$contextid = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

// Set page URL.
$PAGE->set_url('/blocks/lifecycle/unfreeze.php', ['id' => $contextid]);

// Get context information.
list($context, $course, $cm) = get_context_info_array($contextid);

// Check user is logged in and enrolled in the course.
require_login($course, false);

// Check permissions.
require_capability('block/lifecycle:unfreezecoursecontext', $context);

// Check if the context is locked. We only allow unlocking of locked contexts.
if (!$context->locked) {
    throw new moodle_exception('error:courseisnotreadonly', 'block_lifecycle');
}

// It is a course context and not the site course.
if ($course && $course->id != SITEID) {
    $PAGE->set_pagelayout('admin');
    $PAGE->navigation->clear_cache();

    if (null !== $confirm && confirm_sesskey()) {
        // Set context lock status.
        $context->set_locked(!empty($confirm));

        // Prepare message.
        $lockmessage = '';

        $a = (object)['contextname' => $context->get_context_name()];
        $lockmessage = get_string('managecontextlockunlocked', 'admin', $a);

        // Set return URL.
        $returnurl = empty($returnurl) ? $context->get_url() : new moodle_url($returnurl);

        // Redirect with message.
        redirect($returnurl, $lockmessage);
    }

    // Set page title and heading.
    $heading = get_string('managecontextlock', 'admin');
    $PAGE->set_title($heading);
    $PAGE->set_heading($heading);

    // Display header.
    echo $OUTPUT->header();

    // Display confirmation message.
    $confirmstring = get_string('confirmcontextunlock', 'admin', (object)['contextname' => $context->get_context_name()]);
    $confirmurl = new moodle_url($PAGE->url, ['confirm' => 0]);
    if (!empty($returnurl)) {
        $confirmurl->param('returnurl', $returnurl);
    }
    echo $OUTPUT->confirm($confirmstring, $confirmurl, $context->get_url());

    // Display footer.
    echo $OUTPUT->footer();
}
