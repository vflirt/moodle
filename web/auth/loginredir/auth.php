<?php
require_once($CFG->libdir.'/authlib.php');

class auth_plugin_loginredir extends auth_plugin_base {

    private $course;

  /**
   * Constructor.
   */
  function __construct() {
    $this->authtype = 'loginredir';
    $this->config = get_config('auth_loginredir');
  }

  /*
   * Must override or an error is printed.
   * @return boolean False means login was not a success.
   */
  function user_login($username, $password) {
    false;
  }

    /**
     * @param object $user
     * @param string $username
     * @param string $password
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    function user_authenticated_hook(&$user, $username, $password): bool
    {
    global $CFG, $SESSION, $DB;

    // @todo: set where to redirect based on access.
    $should_redirect = !empty($this->config->redirect_to) && ($this->config->redirect_to != SITEID);

    if ($should_redirect) {
        $urltogo = $CFG->wwwroot .'/course/view.php?id=' . $this->config->redirect_to;

        $this->course = $course = get_course($this->config->redirect_to);

        // Add ad hock task for Abandoned email.
        $this->abandoned_email_task($user);

        // Enrollment
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        /** @var enrol_manual_plugin $manualplugin */
        $manualplugin = enrol_get_plugin('manual');
        $instances = enrol_get_instances($course->id, true);
        $instance = null;
        foreach ($instances as $inst) {
            if ($inst->enrol == 'manual') {
                $instance = $inst;
                break;
            }
        }

        if ($instance) {
            $manualplugin->enrol_user($instance, $user->id, $studentroleid, 0, 0, ENROL_USER_ACTIVE);
        }

        // Redirect to last user visible activity.
        // @todo: change to last visible section
        // If section is last then go to last activity

        $url = null;
        $info = get_fast_modinfo($course, $user->id);

        $sections = $info->get_section_info_all(); //get all sections information from modules
        foreach ($sections as $section) {
            if ($section->available) {
                $url = course_get_url($course, $section);

                $cms = $info->get_cms();

                $redirect = false;
                $module_url = null;
                foreach ($cms as $module) {
                    if ($module->sectionnum == $section->section) {
                        // If we have more than 1 module in the section we cannot redirect.
                        if ($redirect) {
                            $redirect = false;
                            break;
                        } else {
                            $redirect = true;
                            $module_url = $module->url;
                        }
                    }
                }

                if ($redirect && $module_url) {
                    $url = $module_url;
                }
            }
        }

//        foreach ($info->instances as $instance_type => $instances) {
//            foreach ($instances as $instance) {
//                $cm = $info->get_cm($instance->id);
//
//                if ($cm->uservisible) {
//                    $url = $instance->url;
//                }
//                else
//                    break;
//            }
//        }

        if ($url) {
            $urltogo = $url->out();
        }

        $SESSION->wantsurl = $urltogo;
    }

    return true;
  }

  private function abandoned_email_task($user) {
      global $CFG;

      if ($this->course) {
          $sendtime = time() + ($CFG->abandonedmailtime * 3600);

          $data = [
              'course_id' => $this->config->redirect_to,
          ];

          require_once("{$CFG->libdir}/completionlib.php");
          $cinfo = new \completion_info($this->course);
          $iscomplete = $cinfo->is_course_complete($user->id);

          if (!$iscomplete) {
              $task = new \auth_loginredir\task\abandoned_email();
              $task->set_userid($user->id);
              $task->set_component('auth_loginredir');
              $task->set_next_run_time($sendtime);
              $task->set_custom_data($data);
              \core\task\manager::reschedule_or_queue_adhoc_task($task);
          }
      }
  }

}

?>