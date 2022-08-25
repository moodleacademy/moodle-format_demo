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
 * Basic renderer for topics format.
 *
 * @package    format_demo
 * @copyright  2022 Your name <youremail>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_demo\output;

use moodle_page;
use context_course;
use completion_info;
use core_courseformat\output\section_renderer;

class renderer extends section_renderer {

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
