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

require_once($CFG->dirroot . '/blocks/lifecycle/lib.php');

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
     * @return string
     */
    public function fetch_block_content(): string {
        $content = html_writer::start_div('lifecycle');
        $content .= html_writer::div(block_lifecycle_get_course_lifecycle_info());
        $content .= html_writer::div(block_lifecycle_get_context_freezing_data());
        $content .= html_writer::end_div();

        return $content;
    }
}
