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

namespace format_demo;

use cm_info;
use context_system;
use context_course;
use context_module;
use html_writer;
use moodle_url;

class course_renderer extends \core_course_renderer {

    /**
     * Renders HTML to display one course module in a course section
     *
     * This includes link, content, availability, completion info and additional information
     * that module type wants to display (i.e. number of unread forum posts)
     *
     * This function calls:
     * {@link core_course_renderer::course_section_cm_name()}
     * {@link core_course_renderer::course_section_cm_text()}
     * {@link core_course_renderer::course_section_cm_availability()}
     * {@link course_get_cm_edit_actions()}
     * {@link core_course_renderer::course_section_cm_edit_actions()}
     *
     * @param stdClass $course
     * @param completion_info $completioninfo
     * @param cm_info $mod
     * @param int|null $sectionreturn
     * @param array $displayoptions
     * @return string
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
        global $USER;

        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->is_visible_on_course_page()) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-'.$mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }

        $output .= html_writer::start_tag('div');

        if ($this->page->user_is_editing()) {
            $output .= course_get_cm_move($mod, $sectionreturn);
        }

        $output .= html_writer::start_tag('div', array('class' => 'mod-indent-outer w-100'));

        // This div is used to indent the content.
        $output .= html_writer::div('', $indentclasses);

        // Start a wrapper for the actual content to keep the indentation consistent
        $output .= html_writer::start_tag('div');

        // Display the link to the module (or do nothing if module has no url)
        $cmname = $this->course_section_cm_name($mod, $displayoptions);

        if (!empty($cmname)) {
            // Start the div for the activity title, excluding the edit icons.
            $output .= html_writer::start_tag('div', array('class' => 'activityinstance'));
            $output .= $cmname;

            // Add Hide/Show icon.
			if ($this->page->user_is_editing()) {
				$modcontext = context_module::instance($mod->id);
				$baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));

				if ($sectionreturn !== null) {
					$baseurl->param('sr', $sectionreturn);
				}

				$controls = '';
				if (has_capability('moodle/course:activityvisibility', $modcontext)) {

					$strmodhide = get_string('modhide');
					$strmodshow = get_string('modshow');

					$controls = '<div class="quick-controls-cm hidden-xs-down d-inline-block">';

					$sectionvisible = $mod->get_section_info()->visible;
					// Only include the show/hide option here
					$displayedoncoursepage = $mod->visible && $mod->visibleoncoursepage && $sectionvisible;
					$unavailable = !$mod->visible;
					$stealth = $mod->visible && (!$mod->visibleoncoursepage || !$sectionvisible);
					if ($displayedoncoursepage) {
						$controls .= html_writer::link(
						new moodle_url(new moodle_url($baseurl, array('hide' => $mod->id))), $this->output->pix_icon('t/hide', $strmodhide), array('class' => 'editing_hide', 'data-action' => 'hide'));
					} else if (!$displayedoncoursepage && $sectionvisible) {
						// Offer to "show" only if the section is visible.
						$controls .= html_writer::link(
						new moodle_url(new moodle_url($baseurl, array('show' => $mod->id))), $this->output->pix_icon('t/show', $strmodshow, 'moodle', array('class' => 'active-icon')), array('class' => 'editing_show', 'data-action' => 'show'));
					}

					$controls .= '</div>';
				}

				$output .= $controls;
			}

            // Module can put text after the link (e.g. forum unread)
            $output .= $mod->afterlink;

            // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
            $output .= html_writer::end_tag('div'); // .activityinstance
        }

        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->url;
        if (empty($url)) {
            $output .= $contentpart;
        }

        $modicons = '';
        if ($this->page->user_is_editing()) {
            $editactions = course_get_cm_edit_actions($mod, $mod->indent, $sectionreturn);
            $modicons .= ' '. $this->course_section_cm_edit_actions($editactions, $mod, $displayoptions);
            $modicons .= $mod->afterediticons;
        }

        if (!empty($modicons)) {
            $output .= html_writer::div($modicons, 'actions');
        }

        // Fetch completion details.
        $showcompletionconditions = $course->showcompletionconditions == COMPLETION_SHOW_CONDITIONS;
        $completiondetails = \core_completion\cm_completion_details::get_instance($mod, $USER->id, $showcompletionconditions);
        $ismanualcompletion = $completiondetails->has_completion() && !$completiondetails->is_automatic();

        // Fetch activity dates.
        $activitydates = [];
        if ($course->showactivitydates) {
            $activitydates = \core\activity_dates::get_dates_for_module($mod, $USER->id);
        }

        // Show the activity information if:
        // - The course's showcompletionconditions setting is enabled; or
        // - The activity tracks completion manually; or
        // - There are activity dates to be shown.
        if ($showcompletionconditions || $ismanualcompletion || $activitydates) {
            $output .= $this->output->activity_information($mod, $completiondetails, $activitydates);
        }

        // Show availability info (if module is not available).
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before
        if (!empty($url)) {
            $output .= $contentpart;
        }

        $output .= html_writer::end_tag('div'); // $indentclasses

        // End of indentation div.
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        return $output;
    }
}
