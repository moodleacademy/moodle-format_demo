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
 * @package    format_demo
 * @copyright  2022 Your name <youremail>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Display Post news item block
 */
function format_demo_quick_news_block($course) {
	global $CFG, $DB, $USER;

	require_once($CFG->dirroot.'/mod/forum/lib.php');
	require_once('announcement_form.php');

	if (!$course->newsitems) {
		// Default to 3 news items
		$course->newsitems = 3;
	}

	if (!$forum = forum_get_course_forum($course->id, 'news')) {
        return ''; /// TODO: Course should always have a news forum.
	}

	$modinfo = get_fast_modinfo($course);
    if (empty($modinfo->instances['forum'][$forum->id])) {
        return '';
    }

	$cm = $modinfo->instances['forum'][$forum->id];

    if (!$cm->uservisible) {
        return '';
    }


    $context = context_module::instance($cm->id);

    // User must have perms to view discussions in that forum.
    if (!has_capability('mod/forum:viewdiscussion', $context)) {
        return '';
	}

    // First work out whether we can post to this group and if so, include a link.
    $groupmode = 0; // No groups.
	$currentgroup = -1;

	echo html_writer::start_tag('div', array('class' => 'quickpost-box mb-1'));
	echo html_writer::start_tag('div', array('class' => 'head'))
		. html_writer::tag('a', get_string('namenews', 'forum'),
			array('class' => 'heading',
				'data-toggle' => 'collapse',
				'href' => '#collapseAddForm'))
		. html_writer::end_tag('div');

    if (forum_user_can_post_discussion($forum, null, $groupmode, $cm, $context)) {
		echo html_writer::start_tag('div',
			array('class' => 'quickpost-form collapse', 'id' => 'collapseAddForm'));

		$mform = new format_demo_announcement_form($CFG->wwwroot . '/course/format/demo/announcement_post.php',
			[
				'course' => $course->id,
				'coursecontext' => $context,
				'forum' => $forum->id,
			],
			'post', '', array('id' => 'mformforum'));

		$mform->display();

		echo html_writer::end_tag('div');
	}

	echo html_writer::start_tag('div', array('class' => 'quickpost-items px-3 py-2'));

	/// Get all the recent discussions we're allowed to see

    // Displays the most recent posts in a forum in
    // descending order. The call to default sort order here will use
    // that unless the discussion that post is in has a timestart set
    // in the future.
    // This sort will ignore pinned posts as we want the most recent.
    $sort = forum_get_default_sort_order(true, 'p.modified', 'd', false);

	if (! $discussions = forum_get_discussions($cm, $sort, true, -1, $course->newsitems,
                            false, -1, 0, FORUM_POSTS_ALL_USER_GROUPS) ) {
        echo get_string('nonews', 'forum');
	}

	$strftimerecent = get_string('strftimerecent');
    $strmore = get_string('more', 'forum');


	$tabs = html_writer::start_tag('ul', array('class' => 'nav nav-tabs'));
	$tabcontents = html_writer::start_tag('div', array('id' => 'quicknews', 'class' => 'tab-content'));

	$index = 1;
	$timenow = time();
    foreach ($discussions as $discussion) {
		if (
			($discussion->timestart < $timenow)
			&& ($discussion->timeend == 0 || $discussion->timeend > $timenow)
		) {
			$extraclasses_tab = '';
			$extraclasses_tabcontent = '';

			$newflag = false;
			$newflagclass = '';
			if (time() - $discussion->created < (48 * 60 * 60)) { // Treat  < 2 days old as new
				$newflagclass = html_writer::tag('span', get_string('new', 'format_demo'),
						array('class' => 'new badge badge-pill badge-success mr-1'));

				$newflag = true;
			}

			if ($index == 1 && $newflag) {
				$extraclasses_tab = 'active';
				$extraclasses_tabcontent = 'active show';
			}

			$userposting = new stdClass();
			$userposting->id = $discussion->userid;
			$userposting->firstname = $discussion->firstname;
			$userposting->lastname = $discussion->lastname;
			$userposting->email = $discussion->email;
			$userposting->lastname = $discussion->lastname;
			$userposting->firstnamephonetic = $discussion->firstnamephonetic;
			$userposting->lastnamephonetic = $discussion->lastnamephonetic;
			$userposting->middlename = $discussion->middlename;
			$userposting->alternatename = $discussion->alternatename;

			// Tabs.
			$tabs .= html_writer::start_tag('li', array('class' => 'nav-item'));
			$attributes = array (
				'class' => 'nav-link ' . $extraclasses_tab,
				'data-toggle' => 'tab'
			);
			$tabs .= html_writer::link('#d' . $discussion->discussion, userdate($discussion->modified, $strftimerecent), $attributes);
			$tabs .=  html_writer::end_tag('li');

			// Get correct links to files and images.
            $modcontext = context_module::instance($cm->id);
            $msg = file_rewrite_pluginfile_urls($discussion->message, 'pluginfile.php', $modcontext->id, 'mod_forum', 'post', $discussion->id);
            $msg = format_text($msg, FORMAT_HTML);

			$MAX_POST_LENGTH = 1300; // 1300 characters.

			if (strlen($msg) >= $MAX_POST_LENGTH) {
				$readmorelink = html_writer::link(new moodle_url('/mod/forum/discuss.php',
				array('d' => $discussion->discussion)),
				html_writer::tag('span', get_string('readmore', 'format_demo'),
					array('class' => 'readmore btn btn-link')),
				array());
				$displaymsg = shorten_text($msg, $MAX_POST_LENGTH, false, '... ' . $readmorelink);
			} else {
				$displaymsg = $msg;

				if ($discussion->attachment) {
					$readmorelink = html_writer::link(new moodle_url('/mod/forum/discuss.php',
					array('d' => $discussion->discussion)),
					html_writer::tag('span', get_string('viewfullpost', 'format_demo'),
						array('class' => 'btn btn-secondary btn-sm')),
					array());
					$displaymsg .= '<p><span class="badge badge-info">' . get_string('containsattachments', 'format_demo')
						. '</span> ' . $readmorelink . '</p>';
				}
			}

			// Tab contents.
			$tabcontents .= html_writer::start_tag('div', array('id' => 'd' .$discussion->discussion, 'class' => 'tab-pane fade ' . $extraclasses_tabcontent))
				. html_writer::start_tag('div', array('class' => 'news-card card' . ($newflag ? ' new' : '')))
				// tab header
				. html_writer::start_tag('div', array('class' => 'card-header'))
				. $newflagclass
				. html_writer::link(new moodle_url('/mod/forum/discuss.php',
					array('d' => $discussion->discussion)),
					html_writer::tag('span', format_string($discussion->subject, true, $forum->course),
						array('class' => 'subject')),
					array())
				. get_string('by', 'format_demo')
				. html_writer::tag('span', fullname($userposting),
					array('class' => 'author'))
				. html_writer::end_tag('div')
				// tab card body
				. html_writer::start_tag('div', array('class' => 'card-body '))
				. html_writer::tag('div', $displaymsg, array('class' => ''))
				. html_writer::end_tag('div')
				. html_writer::end_tag('div')
				. html_writer::end_tag('div');

			$index++;
		}
    }

	$tabcontents .=  html_writer::end_tag('div');
	$tabs .= html_writer::end_tag('ul');

	echo $tabs;
	echo $tabcontents;

	echo html_writer::end_tag('div');
	echo html_writer::end_tag('div');

	if ($discussions) {
		echo html_writer::start_tag('div', array('class' => 'quickpost-more'));
		echo html_writer::link(new moodle_url('/mod/forum/view.php',
			array('f' => $forum->id)),
			html_writer::tag('span', get_string('viewolderpost', 'format_demo'),
				array('class' => 'btn btn-link')),
			array());
		echo html_writer::end_tag('div');
	}
}