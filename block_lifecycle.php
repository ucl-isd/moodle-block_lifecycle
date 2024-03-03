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
 * Class block_lifecycle
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class block_lifecycle extends block_base {

    /**
     * Initialize the block with its title.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_lifecycle');
    }

    /**
     * Define the pages that the block can be added.
     *
     * @return bool[]
     */
    public function applicable_formats() {
        return ['site-index' => true];
    }

    /**
     * Return contents to be displayed in the block.
     *
     * @return stdClass|string|null
     * @throws coding_exception
     */
    public function get_content() {
        $courseid = $this->page->course->id;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $context = context_course::instance($courseid);
        if (has_capability("block/lifecycle:view", $context)) {
            // Load javascript.
            $this->page->requires->js_call_amd('block_lifecycle/lifecycle', 'init', [$courseid]);

            $this->content = new stdClass();
            $this->content->footer = '';
            $renderer = $this->page->get_renderer('block_lifecycle');

            $html = '';
            if (manager::should_show_ay_label($courseid)) {
                $html .= $renderer->fetch_clc_content($courseid);
                if (manager::is_course_frozen($courseid)) {
                    $html .= $renderer->fetch_course_read_only_notification();
                }
                $html .= $renderer->fetch_course_dates($courseid);
            }
            if (manager::should_show_auto_freezing_preferences($courseid)) {
                $html .= $renderer->fetch_block_content($courseid);
            }
            if (manager::is_course_frozen($courseid)) {
                if (has_capability("moodle/site:managecontextlocks", $context)) {
                    $html .= $renderer->show_unfreeze_button($courseid);
                }
            }

            $this->content->text = $html;

            return $this->content;
        }
    }

    /**
     * Allow the block to have a configuration page.
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}
