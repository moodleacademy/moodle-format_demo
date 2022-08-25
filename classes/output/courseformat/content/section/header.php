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
 * Output header for the format_demo plugin.
 *
 * @package   format_demo
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_demo\output\courseformat\content\section;

use core_courseformat\output\local\content\section\header as header_base;
use context_course;
use html_writer;
use moodle_url;

class header extends header_base {

    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the section header is rendered.
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_demo/local/content/section/header';
    }

    public function export_for_template(\renderer_base $output): \stdClass {
        global $PAGE, $OUTPUT;

        $data = parent::export_for_template($output);

        if (!$PAGE->user_is_editing()) {
            return $data;
        }

        $course = $this->format->get_course();
        $coursecontext = context_course::instance($course->id);
        $canchangesectionvisibility = has_capability('moodle/course:sectionvisibility', $coursecontext);
        $data->canchangesectionvisibility = $canchangesectionvisibility;

		if ($canchangesectionvisibility && $data->num > 0) {
            $sectionreturn = $data->num;
		    $baseurl = course_get_url($course, $sectionreturn);
            $baseurl->param('sesskey', sesskey());
			$url = clone($baseurl);

            // Hide section url.
            $strhidefromothers = get_string('hidefromothers', 'format_'.$course->format);
            $url->param('hide', $data->num);

            $icon = 'i/hide';
            $name = $strhidefromothers;
            $attr = array('class' => 'icon editing_showhide', 'title' => $strhidefromothers,
                        'data-sectionreturn' => $sectionreturn, 'data-action' => 'hide');
            $alt = $strhidefromothers;

            $data->hideurl = html_writer::link(new moodle_url($url), $OUTPUT->pix_icon($icon, $alt), $attr);

            // Show section url.
            $url = clone($baseurl);
            $strshowfromothers = get_string('showfromothers', 'format_'.$course->format);
            $url->param('show',  $data->num);

			$icon = 'i/show';
			$name = $strshowfromothers;
			$attr = array('class' => 'icon editing_showhide', 'title' => $strshowfromothers,
                        'data-sectionreturn' => $sectionreturn, 'data-action' => 'show');
			$alt = $strshowfromothers;

			$data->showurl = html_writer::link(new moodle_url($url),
                $OUTPUT->pix_icon($icon, $alt, 'moodle', array('class' => 'active-icon')), $attr);
        }

        return $data;
    }
}
