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
        return array('course-view' => true);
    }

    /**
     * Return contents to be displayed in the block.
     *
     * @return stdClass|string|null
     * @throws coding_exception
     */
    public function get_content() {
        if (!has_capability('block/lifecycle:view', $this->context)) {
            return null;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_lifecycle');
        $this->content->text = $renderer->fetch_block_content();

        return $this->content;
    }
}
