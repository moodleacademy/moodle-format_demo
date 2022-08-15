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
 * Borrowed from /mod/forum/post_form.php
 *
 * @package    format_demo
 * @copyright  2022 Your name <youremail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/formslib.php');
require_once('locallib.php');

class format_demo_announcement_form extends moodleform {
    public function definition() {
        global $CFG, $OUTPUT;

        $mform =& $this->_form;

        $course = $this->_customdata['course'];
        $coursecontext = $this->_customdata['coursecontext'];
        $forum = $this->_customdata['forum'];

        $mform->addElement('text', 'subject', get_string('subject', 'forum'), '');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');
        $mform->addRule('subject', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //$mform->addElement('textarea', 'message', get_string('message', 'forum'), 'rows="5"');
        //$mform->setType('message', PARAM_TEXT);
        //$mform->addRule('message', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'message', get_string('message', 'forum'), null, self::editor_options());
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        $mform->addElement('hidden', 'course', $course);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'forum', $forum);
        $mform->setType('forum', PARAM_INT);

        $mform->addElement('hidden', 'discussion', 0);
        $mform->setType('discussion', PARAM_INT);

        $mform->addElement('hidden', 'parent', 0);
        $mform->setType('parent', PARAM_INT);

        $mform->addElement('hidden', 'groupid', 0);
        $mform->setType('groupid', PARAM_INT);

        $mform->addElement('hidden', 'edit', 0);
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('hidden', 'reply', 0);
        $mform->setType('reply', PARAM_INT);

        $this->add_action_buttons(true, get_string('addnews', 'format_demo'));
    }

    /**
     * Returns the options array to use in forum text editor
     *
     * @param context_module $context
     * @param int $postid post id, use null when adding new post
     * @return array
     */
    public static function editor_options() {
        global $COURSE, $PAGE, $CFG;
        // TODO: add max files and max size support
        $maxbytes = get_user_max_upload_file_size($PAGE->context, $CFG->maxbytes, $COURSE->maxbytes);
        return array(
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $maxbytes,
            'trusttext'=> true,
            'return_types'=> FILE_INTERNAL | FILE_EXTERNAL,
            'subdirs' => 0
        );
    }

    /**
     * Form validation
     *
     * @param array $data data from the form.
     * @param array $files files uploaded.
     * @return array of errors.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['message']['text'])) {
            $errors['message'] = get_string('erroremptymessage', 'forum');
        }
        if (empty($data['subject'])) {
            $errors['subject'] = get_string('erroremptysubject', 'forum');
        }
        return $errors;
    }
}
