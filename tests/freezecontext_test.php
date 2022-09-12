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

use block_lifecycle\task\freezecontext;
use context_course;

/**
 * Unit tests for block_lifecycle's freezecontext class.
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class freezecontext_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $catid = $dg->create_custom_field_category(['name' => 'CLC'])->get('id');
        $this->field1 = $dg->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'course_year']);

        // Set configs.
        set_config('enabled_scheduled_task', true, 'block_lifecycle');
        set_config('weeks_delay', 0, 'block_lifecycle');
        set_config('late_summer_assessment_end_2020', '2021-11-30', 'block_lifecycle');

        $this->course = $dg->create_course(
            ['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);
    }

    /**
     * Test get_name.
     *
     * @covers \block_lifecycle\task\freezecontext::get_name()
     * @return void
     */
    public function test_get_name() {
        $task = new freezecontext();
        $this->assertEquals('Task to freeze course context', $task->get_name());
    }

    /**
     * Test execute.
     *
     * @covers \block_lifecycle\task\freezecontext::execute()
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_execute() {
        // Suppress text output during tests.
        $this->setOutputCallback(function(){
        });

        // Test freeze context task.
        $context = context_course::instance($this->course->id);
        $task = new freezecontext();
        $task->execute();
        $this->assertTrue($context->is_locked());

        // Test course's parent context is locked already.
        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);
        $context = context_course::instance($course->id);
        $parentcontext = $context->get_parent_context();
        $parentcontext->set_locked(true);
        $task->execute();
        $this->assertTrue($context->is_locked());
    }
}
