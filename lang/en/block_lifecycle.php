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
 * English language files for block_lifecycle
 *
 * @package    block_lifecycle
 * @copyright  2022 onwards University College London {@link https://www.ucl.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Alex Yeung <k.yeung@ucl.ac.uk>
 */

$string['pluginname'] = 'Lifecycle';
$string['button:editsettings'] = 'Edit automatic Read-Only settings';
$string['button:toggleautoreadonly'] = 'Disable Automatic Read-Only';
$string['error:dateformat'] = 'Date must be in format YYYY-MM-DD';
$string['error:cannotgetscheduledfreezedate'] = 'Could not get the automatically suggested date.';
$string['error:updatepreferencessuccess'] = 'Auto read only settings updated successfully.';
$string['error:updatepreferencesfailed'] = 'Failed to update read only settings.';
$string['generalsettings'] = 'General Settings';
$string['help:togglefreezing'] = 'Disable Automatic Read-Only';
$string['help:togglefreezing_help'] = 'Disable Automatic Read-Only.';
$string['help:delayfreezedate'] = 'override Read-Only date';
$string['help:delayfreezedate_help'] = 'The date for a Read-Only override must be post the automatically suggested date, earlier dates may not be used.';
$string['label:readonlydate'] = 'This course will be made automatically Read Only on: ';
$string['label:readonlydateinput'] = 'Overrides Read-Only date:';
$string['lifecycle:addinstance'] = 'Add lifecycle block';
$string['lifecycle:enddate'] = 'This course\'s end date: {$a}';
$string['lifecycle:myaddinstance'] = 'Add my lifecycle block';
$string['lifecycle:overridecontextfreeze'] = 'Override default course context freezing settings';
$string['lifecycle:startdate'] = 'This course\'s start date: {$a}';
$string['lifecycle:coursereadonly'] = 'This Course is Read Only';
$string['lifecycle:view'] = 'View lifecycle block';
$string['privacy:metadata'] = 'The Lifecycle block does not store personal data';
$string['settings:academicyearstartdate'] = 'Academic year start date';
$string['settings:academicyearstartdate:desc'] = 'This field is used to calculate the current academic year period and in MM-DD format';
$string['settings:clcfield'] = 'CLC Field';
$string['settings:clcfield:desc'] = 'The CLC course custom field used to find potential academic years';
$string['settings:enablescheduledtask'] = 'Enable Scheduled task';
$string['settings:enablescheduledtask:desc'] = 'Enable the scheduled task for auto context freezing';
$string['settings:latesummerassessment:end'] = '{$a} LSA End Date';
$string['settings:latesummerassessment:end:desc'] = 'End date of {$a} late summer assessment';
$string['settings:weeksdelay'] = 'Weeks Delay';
$string['settings:weeksdelay:desc'] = 'The number of weeks after course end date to delay context freezing';
$string['task:freezecontext'] = 'Task to freeze course context';


