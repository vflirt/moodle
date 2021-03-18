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
 * Lib php
 *
 * @package    auth_loginredir
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function auth_loginredir_after_require_login($courseorid, $autologinguest, $cm, $setwantsurltome, $preventredirect) {
    global $PAGE;
    if (!empty($courseorid)) {
        $config = get_config('auth_loginredir');
        $course = null;
        if (is_object($courseorid)) {
            $courseid = $courseorid->id;
            $course = $courseorid;
        }
        else {
            $courseid = $courseorid;
        }

        $should_redirect = !empty($config->redirect_to) && ($config->redirect_to != SITEID) && $config->redirect_to == $courseid;

        if ($should_redirect && $PAGE->has_set_url() &&
            $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {

            $edit = optional_param('edit', -1, PARAM_BOOL);
            if (($edit == 0 || $edit == 1) && confirm_sesskey()) {
                // This is a request to turn editing mode on or off, do not redirect here.
                return;
            }

            if ($PAGE->user_allowed_editing()) {
                // If user can edit do not redirect.
                // $PAGE->user_is_editing();
                return;
            }

            if (!$course) {
                $course = get_course($courseid);
            }

            $modinfo = get_fast_modinfo($course);

            $sectionnum = optional_param('section', 0, PARAM_INT);
            if (!$sectionnum) {
                $sections = $modinfo->get_section_info_all();
                // Redirect to first section.
                if (isset($sections[1])) {
                    $sectionnum = 1;
                }
            }

            if (!empty($sectionnum)) {
                $cms = $modinfo->get_cms();
                $redirect = false;
                $url = null;
                foreach ($cms as $module) {
                    if ($module->sectionnum == $sectionnum) {
                        // If we have more than 1 module in the section we cannot redirect.
                        if ($redirect) {
                            $redirect = false;
                            break;
                        } else {
                            $redirect = true;
                            $url = $module->url;
                        }
                    }
                }

                if ($redirect) {
                    redirect($url);
                }
            }

        }
    }
}

