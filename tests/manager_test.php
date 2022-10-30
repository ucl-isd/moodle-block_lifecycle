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
        global $DB;
        parent::setUp();

        $this->resetAfterTest();
        $dg = $this->getDataGenerator();

        $catid = $dg->create_custom_field_category(['name' => 'CLC'])->get('id');
        $this->field1 = $dg->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'course_year']);
        $this->field2 = $dg->create_custom_field(['categoryid' => $catid, 'type' => 'text', 'shortname' => 'new_field']);

        // Create default courses.
        $this->course1 = $dg->create_course(['customfield_course_year' => '2020']);
        $this->course2 = $dg->create_course(['customfield_course_year' => '2021']);
        $this->course3 = $dg->create_course(['customfield_course_year' => '2022']);
        $this->courseshouldbefrozen = $dg->create_course(
            ['customfield_course_year' => '2021', 'startdate' => 1630450800, 'enddate' => 1656543600]
        );
        $this->coursewithoutacademicyear = $dg->create_course(['startdate' => 1598914800, 'enddate' => 1625007600]);
        $this->coursewithfutureenddate = $dg->create_course(['startdate' => 1598914800, 'enddate' => strtotime('+ 1 month')]);
        $this->coursewithoutenddate = $dg->create_course(
            ['customfield_course_year' => '2020', 'startdate' => 1598914800, 'enddate' => 0]
        );

        // Create roles.
        $this->teacherroleid = $dg->create_role(
            array('shortname' => 'test_teacher',
                'name' => 'test_teacher',
                'description' => 'test teacher role',
                'archetype' => 'editingteacher'));

        $this->studentroleid = $dg->create_role(
            array('shortname' => 'test_student',
                'name' => 'test_student',
                'description' => 'student role',
                'archetype' => 'student'));

        // Create users.
        $this->user1 = $dg->create_user();
        $this->user2 = $dg->create_user();

        // Create block_lifecyce record for $this->courseshouldbefrozen.
        $this->preferences = new \stdClass();
        $this->preferences->courseid = $this->courseshouldbefrozen->id;
        $this->preferences->freezeexcluded = 0;
        $this->preferences->freezedate = strtotime('2022-10-31');
        $this->preferences->timecreated = time();
        $this->preferences->timemodified = time();
        $this->preferencesrecordid = $DB->insert_record(manager::DEFAULT_TABLE, $this->preferences);
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
        $coursestofreeze = manager::get_courses_for_context_freezing();

        // Test only one valid course can be found.
        $this->assertCount(1, $coursestofreeze);
        $this->assertEquals($this->courseshouldbefrozen->id, $coursestofreeze[0]->id);
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

        // Test freezing turned off by user.
        $preferences = new \stdClass();
        $preferences->togglefreeze = false;
        $preferences->delayfreezedate = '';
        manager::update_auto_freezing_preferences($course->id, $preferences);
        $check = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course]
        );
        $this->assertFalse($check);

        // Test with future freeze date add by user.
        $preferences = new \stdClass();
        $preferences->togglefreeze = true;
        $preferences->delayfreezedate = date('Y-m-d', strtotime('+1 week'));
        manager::update_auto_freezing_preferences($course->id, $preferences);
        $check = $reflectedmethod->invokeArgs(
            $mockedinstance, [$course]
        );
        $this->assertFalse($check);

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

    /**
     * Test get_course_lifecycle_info.
     *
     * @covers \block_lifecycle\manager::get_course_lifecycle_info()
     * @return void
     * @throws \ReflectionException
     */
    public function test_get_course_lifecycle_info() {
        // Test course academic year is 2020.
        $result = manager::get_course_lifecycle_info($this->course1->id);
        $this->assertEquals(array('class' => '', 'text' => 'Moodle 2020/21'), $result);

        // Test course academic year is current academic year.
        $result = manager::get_course_lifecycle_info($this->course3->id);
        $this->assertEquals(array('class' => 'current', 'text' => 'Moodle 2022/23'), $result);
    }

    /**
     * Test should_show_ay_label().
     *
     * @covers \block_lifecycle\manager::should_show_ay_label()
     * @return void
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function test_should_show_ay_label() {
        $dg = $this->getDataGenerator();
        // Create courses.
        // Start date 2021-09-01.
        $course1 = $dg->create_course(['customfield_course_year' => '2021',
            'startdate' => 1630450800]);
        // Start date 2022-09-01, end date current time plus one week.
        $course2 = $dg->create_course(['customfield_course_year' => '2022',
            'startdate' => 1661986800, 'enddate' => strtotime('+1 week')]);
        // Start date 2021-09-01, end date 2022-06-30.
        $course3 = $dg->create_course(['customfield_course_year' => '2021',
            'startdate' => 1630450800, 'enddate' => 1656543600]);

        // Test course no end date.
        $result = manager::should_show_ay_label($course1->id);
        $this->assertFalse($result);

        // Test current time within course start date and end date.
        $result = manager::should_show_ay_label($course2->id);
        $this->assertFalse($result);

        // Test teacher can see the info.
        $dg->enrol_user($this->user1->id, $course3->id, $this->teacherroleid);
        $this->setUser($this->user1->id);

        $result = manager::should_show_ay_label($course3->id);
        $this->assertTrue($result);

        // Test student role cannot see the info.
        $dg->enrol_user($this->user2->id, $course3->id, $this->studentroleid);
        $this->setUser($this->user2->id);

        $result = manager::should_show_ay_label($course3->id);
        $this->assertFalse($result);
    }


    /**
     * Test should_show_auto_freezing_preferences().
     *
     * @covers \block_lifecycle\manager::should_show_auto_freezing_preferences()
     * @return void
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function test_should_show_auto_freezing_preferences() {
        $dg = $this->getDataGenerator();

        // Test teacher can see the block options.
        $dg->enrol_user($this->user1->id, $this->courseshouldbefrozen->id, $this->teacherroleid);
        $this->setUser($this->user1);
        $result = manager::should_show_auto_freezing_preferences($this->courseshouldbefrozen->id);
        $this->assertTrue($result);

        // Test course without end date.
        $dg->enrol_user($this->user1->id, $this->coursewithoutenddate->id, $this->teacherroleid);
        $result = manager::should_show_auto_freezing_preferences($this->coursewithoutenddate->id);
        $this->assertFalse($result);

        // Test course end date is in the future.
        $dg->enrol_user($this->user1->id, $this->coursewithfutureenddate->id, $this->teacherroleid);
        $result = manager::should_show_auto_freezing_preferences($this->coursewithfutureenddate->id);
        $this->assertFalse($result);

        // Test no course without CLC academic year.
        $dg->enrol_user($this->user1->id, $this->coursewithoutacademicyear->id, $this->teacherroleid);
        $result = manager::should_show_auto_freezing_preferences($this->coursewithoutacademicyear->id);
        $this->assertFalse($result);

        // Test student cannot see the block options.
        $dg->enrol_user($this->user2->id, $this->courseshouldbefrozen->id, $this->studentroleid);
        $this->setUser($this->user2);
        $result = manager::should_show_auto_freezing_preferences($this->courseshouldbefrozen->id);
        $this->assertFalse($result);
    }

    /**
     * Test Test update_auto_freezing_preferences().
     *
     * @covers \block_lifecycle\manager::update_auto_freezing_preferences()
     * @return void
     * @throws \dml_exception
     * @throws coding_exception
     */
    public function test_update_auto_freezing_preferences() {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $preferences = new \stdClass();
        $preferences->togglefreeze = true;
        $preferences->delayfreezedate = '2022-11-30';

        // Test insert preferences.
        $result = manager::update_auto_freezing_preferences($course->id, $preferences);
        $this->assertTrue($result->success);
        $this->assertEquals(get_string('error:updatepreferencessuccess', 'block_lifecycle'), $result->message);

        // Test update preferences.
        $preferences->togglefreeze = false;
        $preferences->delayfreezedate = '';
        $result = manager::update_auto_freezing_preferences($course->id, $preferences);
        $this->assertTrue($result->success);
        $record = manager::get_auto_context_freezing_preferences($course->id);
        $this->assertEquals('1', $record->freezeexcluded);
        $this->assertEquals('0', $record->freezedate);

        // Test exception.
        $preferences = new \stdClass();
        $preferences->invalidfield1 = false;
        $preferences->invalidfield2 = '';
        $result = manager::update_auto_freezing_preferences($course->id, $preferences);
        $this->assertFalse($result->success);
        $this->assertEquals(get_string('error:updatepreferencesfailed', 'block_lifecycle'), $result->message);
    }

    /**
     * Test Test is_course_frozen().
     *
     * @covers \block_lifecycle\manager::is_course_frozen()
     * @return void
     */
    public function test_is_course_frozen() {
        // Test course is not frozen.
        $result = manager::is_course_frozen($this->course1->id);
        $this->assertFalse($result);

        // Test course is frozen.
        $context = context_course::instance($this->course1->id);
        $context->set_locked(true);
        $result = manager::is_course_frozen($this->course1->id);
        $this->assertTrue($result);
    }

    /**
     * Test get_auto_context_freezing_preferences().
     *
     * @covers \block_lifecycle\manager::get_auto_context_freezing_preferences()
     * @return void
     * @throws \dml_exception
     */
    public function test_get_auto_context_freezing_preferences() {
        $result = manager::get_auto_context_freezing_preferences($this->courseshouldbefrozen->id);
        $this->assertEquals('0', $result->freezeexcluded);
        $this->assertEquals(strtotime('2022-10-31'), $result->freezedate);
    }

    /**
     * Test get_scheduled_freeze_date().
     *
     * @covers \block_lifecycle\manager::get_scheduled_freeze_date()
     * @return void
     * @throws \dml_exception
     */
    public function test_get_scheduled_freeze_date() {
        global $DB;

        // Test course without CLC academic year, no scheduled freeze date should be returned.
        $result = manager::get_scheduled_freeze_date($this->coursewithoutacademicyear->id);
        $this->assertFalse($result);

        // Test the scheduled freeze date is in the past.
        $result = manager::get_scheduled_freeze_date($this->courseshouldbefrozen->id);
        $this->assertEquals(date('d/m/Y', strtotime('+1 day')), $result);

        // Test the scheduled freeze date is a future date.
        $this->preferences->id = $this->preferencesrecordid;
        $this->preferences->freezedate = strtotime('+1 week');
        $DB->update_record(manager::DEFAULT_TABLE, $this->preferences);
        $result = manager::get_scheduled_freeze_date($this->courseshouldbefrozen->id);
        $this->assertEquals(date('d/m/Y', strtotime('+1 week')), $result);

        // Test with one week delay.
        $this->preferences->id = $this->preferencesrecordid;
        $this->preferences->freezedate = 0;
        $DB->update_record(manager::DEFAULT_TABLE, $this->preferences);
        set_config('weeks_delay', 1, 'block_lifecycle');
        set_config('late_summer_assessment_end_2021', date('Y-m-d', time()), 'block_lifecycle');
        $result = manager::get_scheduled_freeze_date($this->courseshouldbefrozen->id);
        $datetime = new \DateTime();
        $datetime->modify('+7 day');
        $this->assertEquals($datetime->format('d/m/Y'), $result);
    }

    /**
     * Test get_weeks_delay_in_seconds().
     *
     * @covers \block_lifecycle\manager::get_weeks_delay_in_seconds()
     * @return void
     * @throws \dml_exception
     */
    public function test_get_weeks_delay_in_seconds() {
        // Test week delay config is 0.
        $result = manager::get_weeks_delay_in_seconds();
        $this->assertEquals('0', $result);

        // Test week delay config is 1.
        set_config('weeks_delay', 1, 'block_lifecycle');
        $result = manager::get_weeks_delay_in_seconds();
        $this->assertEquals('604800', $result);
    }
}

