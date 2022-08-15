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
 *  * Borrowed from /mod/forum/post.php
 *
 * @package    format_demo
 * @copyright  2022 Your name <youremail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
//require_once($CFG->dirroot . '/theme/usp_corporate/locallib.php');

$courseid = required_param('course', PARAM_INT); // ... course id.
$forum = required_param('forum', PARAM_INT); // ... course id.

$reply   = optional_param('reply', 0, PARAM_INT);
$forum   = optional_param('forum', 0, PARAM_INT);
$edit    = optional_param('edit', 0, PARAM_INT);
$delete  = optional_param('delete', 0, PARAM_INT);
$prune   = optional_param('prune', 0, PARAM_INT);
$name    = optional_param('name', '', PARAM_CLEAN);
$confirm = optional_param('confirm', 0, PARAM_INT);
$groupid = optional_param('groupid', null, PARAM_INT);
$subject = optional_param('subject', '', PARAM_TEXT);

$redirecturl = new moodle_url('/course/view.php', array('id' => $courseid));

if ($data = data_submitted()) {
    require_sesskey();

    if (isset($data->cancel)) {
        redirect($redirecturl);
        die;
    }

    $vaultfactory = mod_forum\local\container::get_vault_factory();
    $managerfactory = mod_forum\local\container::get_manager_factory();
    $legacydatamapperfactory = mod_forum\local\container::get_legacy_data_mapper_factory();

    $forumvault = $vaultfactory->get_forum_vault();
    $forumdatamapper = $legacydatamapperfactory->get_forum_data_mapper();

    // User is starting a new discussion in a forum.
    $forumentity = $forumvault->get_from_id($forum);
    if (empty($forumentity)) {
        print_error('invalidforumid', 'forum');
        //redirect($redirecturl);
    }

    $capabilitymanager = $managerfactory->get_capability_manager($forumentity);
    $forum = $forumdatamapper->to_legacy_object($forumentity);
    $course = $forumentity->get_course_record();
    if (!$cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
        print_error("invalidcoursemodule");
        //redirect($redirecturl);
    }

    // Retrieve the contexts.
    $modcontext = $forumentity->get_context();
    $coursecontext = context_course::instance($course->id);

    if ($forumentity->is_in_group_mode() && null === $groupid) {
        $groupid = groups_get_activity_group($cm);
    }

    if (!$capabilitymanager->can_create_discussions($USER, $groupid)) {
        if (!isguestuser()) {
            if (!is_enrolled($coursecontext)) {
                if (enrol_selfenrol_available($course->id)) {
                    $SESSION->wantsurl = qualified_me();
                    $SESSION->enrolcancel = get_local_referer(false);
                    redirect(new moodle_url('/enrol/index.php', array('id' => $course->id,
                        'returnurl' => '/mod/forum/view.php?f=' . $forum->id)),
                        get_string('youneedtoenrol'));
                }
            }
        }
        print_error('nopostforum', 'forum');
    }

    require_login($course, false, $cm);

    $discussion = new stdClass();
    $discussion->course = $course->id;
    $discussion->forum = $forum->id;
    $discussion->name = $data->subject;
    $discussion->message = trim($data->message['text']);
    $discussion->messageformat = $data->message['format'];
    $discussion->itemid = $data->message['itemid'];
    $discussion->messagetrust = 0;
    $discussion->mailnow = 0;
    $discussion->pinned = FORUM_DISCUSSION_UNPINNED;
    $discussion->timelocked = 0;
    $discussion->timestart = 0;
    $discussion->timeend = 0;
/*
    $draftideditor = file_get_submitted_draft_itemid('message');
    $editoropts = theme_usp_corporate_announcement_form::editor_options();
    $currenttext = file_prepare_draft_area($draftideditor, $modcontext->id, 'mod_forum', 'post', null, $editoropts, $post->message);
*/
    // Use the value for all participants instead.
    $groupstopostto[] = -1;

    foreach ($groupstopostto as $group) {
        if (!$capabilitymanager->can_create_discussions($USER, $groupid)) {
            print_error('cannotcreatediscussion', 'forum');
        }

        $discussion->groupid = $group;
        $message = '';
        if ($discussion->id = forum_add_discussion($discussion, $data)) {

            $params = array(
                'context' => $modcontext,
                'objectid' => $discussion->id,
                'other' => array(
                    'forumid' => $forum->id,
                )
            );
            $event = \mod_forum\event\discussion_created::create($params);
            $event->add_record_snapshot('forum_discussions', $discussion);
            $event->trigger();

            $message .= '<p>'.get_string("postaddedsuccess", "forum") . '</p>';
            $message .= '<p>'.get_string("postaddedtimeleft", "forum", format_time($CFG->maxeditingtime)) . '</p>';
        } else {
            print_error("couldnotadd", "forum", $errordestination);
        }
    }

    // Redirect back to the discussion.
    redirect(
        forum_go_back_to($redirecturl->out()),
        $message,
        null,
         \core\output\notification::NOTIFY_SUCCESS
    );
}

redirect($redirecturl);
