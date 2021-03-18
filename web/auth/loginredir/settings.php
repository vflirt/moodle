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
 * Admin settings and defaults.
 *
 * @package    auth_loginredir
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $ADMIN->add('modules', new admin_category('auth_loginredir_settings', new lang_string('pluginname', 'auth_loginredir')));
    $settingspage = new admin_settingpage('manageauth_loginredir', new lang_string('manage', 'auth_loginredir'));

    if ($ADMIN->fulltree) {
        require_once($CFG->dirroot . '/lib/outputlib.php');
        require_once($CFG->dirroot . '/lib/datalib.php');

        // Introductory explanation.
        $settings->add(new admin_setting_heading('auth_loginredir/pluginname',
            new lang_string('auth_loginredir_heading', 'auth_loginredir'),
            new lang_string('auth_loginredir_description', 'auth_loginredir')));

        $courses = [];

        $courses = get_courses('all', 'c.shortname', 'c.id, c.fullname');

        $options = ['' => '- Select -'];
        foreach ($courses as $id => $course) {
            $options[$id] = $course->fullname;
        }

        $settings->add(new admin_setting_configselect('auth_loginredir/redirect_to',
            new lang_string('auth_loginredir_redirect_to', 'auth_loginredir'),
            new lang_string('auth_loginredir_redirect_to_desc', 'auth_loginredir'), 0, $options));

        $options = array();
        for ($i = 1; $i <= 72; $i++) {
            $options[$i] = sprintf("%02d",$i);
        }
        $settingspage->add(new admin_setting_configselect('abandonedmailtime', get_string('abandonedmailtime', 'auth_loginredir'),
            get_string('abandonedmailtime_desc', 'auth_loginredir'), 48, $options));
    }

    $ADMIN->add('modules', $settingspage);
}
