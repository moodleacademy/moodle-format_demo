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
require_once($CFG->dirroot.'/course/format/topics/renderer.php');

/**
 * Basic renderer for topics format.
 *
 * @copyright 2012 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_demo_renderer extends format_topics_renderer {

    /**
     * Constructor method, calls the parent constructor
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        $this->courserenderer = new \format_demo\course_renderer($page, $target);
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        // Quick news block.
        echo format_demo_quick_news_block($course);

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard.
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections.
        echo $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0) {
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    echo $this->section_header($thissection, $course, false, 0);
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    echo $this->section_footer();
                }

                continue;
            }

            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                    (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                echo $this->section_summary($thissection, $course, null);
            } else {
                echo $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    echo $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                echo $this->section_footer();
            }
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
        }
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        global $PAGE;

		if (!$PAGE->user_is_editing()) {
            return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
        }

		$coursecontext = context_course::instance($course->id);
		$controls = '<div class="quick-controls hidden-xs-down d-inline-block">';

		if ($section->section && has_capability('moodle/course:sectionvisibility', $coursecontext)) {
            $sectionreturn = $section->section;

		    $baseurl = course_get_url($course, $sectionreturn);
            $baseurl->param('sesskey', sesskey());
			$url = clone($baseurl);
            if ($section->visible) { // Show the hide/show eye.
                $strhidefromothers = get_string('hidefromothers', 'format_'.$course->format);
                $url->param('hide', $section->section);

				$icon = 'i/hide';
				$name = $strhidefromothers;
				$attr = array('class' => 'icon editing_showhide', 'title' => $strhidefromothers,
                    'data-sectionreturn' => $sectionreturn, 'data-action' => 'hide');
				$alt = $strhidefromothers;

				$controls .= html_writer::link(
                    new moodle_url($url), $this->output->pix_icon($icon, $alt), $attr);
            } else {
                $strshowfromothers = get_string('showfromothers', 'format_'.$course->format);
                $url->param('show',  $section->section);

				$icon = 'i/show';
				$name = $strshowfromothers;
				$attr = array('class' => 'icon editing_showhide', 'title' => $strshowfromothers,
                        'data-sectionreturn' => $sectionreturn, 'data-action' => 'show');
				$alt = $strshowfromothers;

				$controls .= html_writer::link(
                    new moodle_url($url),
					$this->output->pix_icon($icon, $alt, 'moodle', array('class' => 'active-icon')), $attr);
            }
        }

		$controls .= '</div>';

        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section)) . $controls;
    }

}
