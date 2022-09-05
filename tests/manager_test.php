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
 * Unit tests for block_lifecycle
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */
namespace block_lifecycle;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Test functions that rely on the DB tables
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
}

