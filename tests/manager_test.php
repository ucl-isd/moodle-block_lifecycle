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

use coding_exception;
use context_course;

/**
 * Unit tests for block_lifecycle's manager class.
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
class manager_test extends \advanced_testcase {

    /** @var \core_customfield\field_controller field2 */
    private $field2;

    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $catid = $dg->create_custom_field_category(['name' => 'CLC'])->get('id');
        $this->field1 = $dg->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'course_year']);
        $this->field2 = $dg->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'new_field']);

        // Create default course.
        $dg->create_course(['customfield_course_year' => '2020']);
        $dg->create_course(['customfield_course_year' => '2021']);
        $dg->create_course(['customfield_course_year' => '2022']);
    }

    /**
     * Test get_potential_academic_years
     *
     * @covers \block_lifecycle\manager::get_potential_academic_years
     * @return void
     * @throws \dml_exception
     */
    public function test_get_potential_academic_years() {
        $years = manager::get_potential_academic_years();
        $this->assertCount(3, $years);

        // Set to use configured CLC field id.
        set_config('clcfield', $this->field2->get('id'), 'block_lifecycle');

        $years = manager::get_potential_academic_years();
        $this->assertCount(0, $years);
    }

    /**
     * Test get_clc_custom_course_fields
     *
     * @covers \block_lifecycle\manager::get_clc_custom_course_fields()
     * @return void
     * @throws \dml_exception
     */
    public function test_get_clc_custom_course_fields() {
        $fields = manager::get_clc_custom_course_fields();
        $this->assertCount(2, $fields);
    }

    /**
     * Test get_courses_for_context_freezing
     *
     * @covers \block_lifecycle\manager::get_courses_for_context_freezing()
     * @throws \dml_exception
     */
    public function test_get_courses_for_context_freezing() {
        set_config('weeks_delay', 1, 'block_lifecycle');
        $dg = $this->getDataGenerator();

        // Create valid course, set start date 2020-09-01, end date 2021-06-30.
        $course1 = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);

        // Create invalid course with no course end date.
        $dg->create_course(['startdate' => 1598914800, 'enddate' => 0]);
        $coursestofreeze = manager::get_courses_for_context_freezing();

        // Test only one valid course can be found.
        $this->assertCount(1, $coursestofreeze);
        $this->assertEquals($course1->id, $coursestofreeze[0]->id);
    }


    /**
     * Test freeze_course.
     *
     * @covers \block_lifecycle\manager::freeze_course()
     * @throws \coding_exception
     */
    public function test_freeze_course() {
        $dg = $this->getDataGenerator();
        // Create course, set start date 2020-09-01, end date 2021-06-30.
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);
        $context = context_course::instance($course->id);
        // Test the course's context is not locked at first.
        $this->assertFalse($context->is_locked());

        // Test lock course.
        manager::freeze_course($course->id);
        $this->assertTrue($context->is_locked());

        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);
        $context = context_course::instance($course->id);

        $parentcontext = $context->get_parent_context();
        $parentcontext->set_locked(true);
        // Test parent context is locked.
        $this->expectException(coding_exception::class);
        manager::freeze_course($course->id);
    }

    /**
     * Test check_course_is_eligible_for_context_freezing.
     *
     * @covers \block_lifecycle\manager::check_course_is_eligible_for_context_freezing()
     * @throws \ReflectionException
     */
    public function test_check_course_is_eligible_for_context_freezing() {
        $mockedinstance = $this->getMockBuilder(manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedmethod = new \ReflectionMethod(
            manager::class,
            'check_course_is_eligible_for_context_freezing'
        );
        $reflectedmethod->setAccessible(true);

        // Set LSA end date.
        set_config('late_summer_assessment_end_2020', '2021-11-30', 'block_lifecycle');

        $dg = $this->getDataGenerator();
        // Create course, set start date 2020-09-01, end date 2021-06-30.
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);

        // Test valid course.
        $check = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course]
        );
        $this->assertTrue($check);

        // Create course without CLC course academic year field, set start date 2020-09-01, end date 2021-06-30.
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600]);
        // Test CLC course academic year is not set.
        $check = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course]
        );
        $this->assertFalse($check);

        // Set a future LSA end date.
        set_config('late_summer_assessment_end_2020', date('Y-m-d', strtotime('+1 week')), 'block_lifecycle');
        // Create course, set start date 2020-09-01, end date 2021-06-30.
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);
        // Test current date hasn't passed the LSA end date.
        $check = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course]
        );
        $this->assertFalse($check);
    }

    /**
     * Test get_course_clc_academic_year.
     *
     * @covers \block_lifecycle\manager::get_course_clc_academic_year()
     * @throws \ReflectionException
     */
    public function test_get_course_clc_academic_year() {
        $mockedinstance = $this->getMockBuilder(manager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflectedmethod = new \ReflectionMethod(
            manager::class,
            'get_course_clc_academic_year'
        );
        $reflectedmethod->setAccessible(true);

        $dg = $this->getDataGenerator();
        // Create course, set start date 2020-09-01, end date 2021-06-30.
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600, 'customfield_course_year' => '2020']);
        $academicyear = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course->id]
        );
        $this->assertEquals('2020', $academicyear);

        // Create course without CLC course academic year field, set start date 2020-09-01, end date 2021-06-30.
        $course = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600]);
        $academicyear = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course->id]
        );
        $this->assertNull($academicyear);
    }
}

