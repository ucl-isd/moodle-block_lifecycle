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
 * Web services for block_lifecycle.
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = [
    'block_lifecycle_update_auto_freezing_preferences' => [
        'classname' => 'block_lifecycle_external',
        'methodname' => 'update_auto_freezing_preferences',
        'classpath' => 'blocks/lifecycle/externallib.php',
        'description' => 'Update auto context freezing preferences',
        'ajax' => true,
        'type' => 'write',
        'readonlysession' => true,
        'loginrequired' => true,
    ],
    'block_lifecycle_get_scheduled_freeze_date' => [
        'classname' => 'block_lifecycle_external',
        'methodname' => 'get_scheduled_freeze_date',
        'classpath' => 'blocks/lifecycle/externallib.php',
        'description' => 'Get scheduled freeze date',
        'ajax' => true,
        'type' => 'read',
        'readonlysession' => true,
        'loginrequired' => true,
    ],
    'block_lifecycle_unfreeze_course' => [
        'classname' => 'block_lifecycle\external\unfreeze_course',
        'description' => 'Unfreeze a course',
        'ajax' => true,
        'type' => 'write',
        'readonlysession' => true,
        'loginrequired' => true,
    ],
];


