<?php

/**
 * This file defines an adhoc task to send notifications.
 *
 * @package    mod_loginredir
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_loginredir\task;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use stdClass;
use coding_exception;

class abandoned_email extends \core\task\adhoc_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * @var \stdClass   A shortcut to $USER.
     */
    protected $recipient;

    /**
     * @var string  The HTML content of the whole message.
     */
    protected $notificationhtml = '';

    /**
     * @var string  The subject of the message.
     */
    protected $postsubject = '';

    protected $course;

    /**
     * Send out messages.
     * @throws coding_exception
     */
    public function execute() {
        global $CFG;
        $starttime = time();

        $data = $this->get_custom_data();
        if (!empty($data->course_id)) {
            $this->course = get_course($data->course_id);
        }
        else {
            throw new coding_exception('The custom data \'course_id\' is required.');
        }

        $this->recipient = \core_user::get_user($this->get_userid());
        $this->log_start("Sending abandoned email for {$this->recipient->username} ({$this->recipient->id})");

        $this->add_message_header();

        $site = get_site();

        $data = new stdClass();
        $data->firstname = $this->recipient->firstname;
        $data->lastname  = $this->recipient->lastname;
        $data->username  = $this->recipient->username;
        $data->sitename  = format_string($site->fullname);
        $data->siteurl   = $CFG->wwwroot;

        $this->notificationhtml .= html_writer::tag('p', get_string('abandonedmailbody', 'auth_loginredir', $data));

        // Add the forum footer.
        $this->add_message_footer();

        if ($this->send_mail()) {
            $this->log_finish("Abandoned email sent.");
        } else {
            $this->log_finish("Issue sending abandoned email. Skipping.");
        }


        // We have finishied all emails, update $CFG->abandonedmailtimelast.
        set_config('abandonedmailtimelast', $starttime);
    }

    /**
     * Send the composed message to the user.
     * @throws coding_exception
     */
    protected function send_mail() {
        // Headers to help prevent auto-responders.
        $fromUser = \core_user::get_noreply_user();
        $userfrom->customheaders = array(
            "Precedence: Bulk",
            'X-Auto-Response-Suppress: All',
            'Auto-Submitted: auto-generated',
        );
        $this->recipient->mailformat = 1;

        $this->log('Sending email to ' . $this->recipient->email . ' with data: ' . var_export($this->notificationhtml, true), 1);

        if (!empty($this->course)) {
            $cinfo = new \completion_info($this->course);
            $iscomplete = $cinfo->is_course_complete($this->recipient->id);
        }
        else {
            throw new coding_exception('The custom data \'course_id\' is required.');
        }

        if (!$iscomplete) {
            return email_to_user($this->recipient, $fromUser, $this->postsubject, html_to_text($this->notificationhtml), $this->notificationhtml, '', '', true);
        }
        else {
            // Emulate we sent the mail.
            return true;
        }
    }

    /**
     * Add the header to this message.
     */
    protected function add_message_header() {
        $site = get_site();

        // Set the subject of the message.
        $this->postsubject = get_string('abandonedmailsubject', 'auth_loginredir', format_string($site->shortname, true));

        // And the content of the header in body.
        $headerdata = (object) [
            'sitename' => format_string($site->fullname, true),
        ];

        $this->notificationhtml .= html_writer::tag('p', get_string('abandonedmailheader', 'auth_loginredir', $headerdata));
        $this->notificationhtml .= html_writer::empty_tag('br');
        $this->notificationhtml .= html_writer::empty_tag('hr', [
            'size' => 1,
            'noshade' => 'noshade',
        ]);
    }

    /**
     * Add the footer to this message.
     */
    protected function add_message_footer() {
        $site = get_site();
        $data = new stdClass();
        $data->sitename  = format_string($site->fullname);

        $this->notificationhtml .= html_writer::tag('p', get_string('abandonedmailfooter', 'auth_loginredir', $data));
    }

}
