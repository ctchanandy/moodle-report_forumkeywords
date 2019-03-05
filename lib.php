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
 * Library functions for the forumkeywords report.
 *
 * @package   report_forumkeywords
 * @copyright 2018 Andy Chan <ctchan.andy@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function report_forumkeywords_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/forumkeywords:view', $context)) {
        $url = new moodle_url('/report/forumkeywords/index.php', array('id' => $course->id));
        $navigation->add(get_string('pluginname', 'report_forumkeywords'), $url,
                navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Callback to verify if the given instance of store is supported by this report or not.
 *
 * @param string $instance store instance.
 *
 * @return bool returns true if the store is supported by the report, false otherwise.
 */
function report_forumkeywords_supports_logstore($instance) {
    if ($instance instanceof \core\log\sql_reader) {
        return true;
    }
    return false;
}