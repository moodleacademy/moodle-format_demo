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
 * Output cm for the format_demo plugin.
 *
 * @package   format_demo
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_demo\output\courseformat\content;

use core_courseformat\output\local\content\cm as cm_base;
use context_module;
use moodle_url;
use html_writer;

class cm extends cm_base {

    public function export_for_template(\renderer_base $output): \stdClass {
        global $PAGE, $OUTPUT;

        $data = parent::export_for_template($output);

        if (!$PAGE->user_is_editing()) {
            return $data;
        }

        $mod = $this->mod;
        $modcontext = context_module::instance($mod->id);
        $canchangeactivityvisibility = has_capability('moodle/course:activityvisibility', $modcontext);

        if ($canchangeactivityvisibility) {
            $baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));

            $strmodhide = get_string('modhide');
            $strmodshow = get_string('modshow');

            $controls = html_writer::start_tag('div',array('class' => 'quick-controls-cm hidden-xs-down d-inline-block'));
            $sectionvisible = $mod->get_section_info()->visible;
			// Only include the show/hide option here.
			$displayedoncoursepage = $mod->visible && $mod->visibleoncoursepage && $sectionvisible;
			$unavailable = !$mod->visible;
			$stealth = $mod->visible && (!$mod->visibleoncoursepage || !$sectionvisible);
			if ($displayedoncoursepage) {
				$controls .= html_writer::link(
					new moodle_url(new moodle_url($baseurl, array('hide' => $mod->id))),
                    $OUTPUT->pix_icon('t/hide', $strmodhide),
                    array('class' => 'editing_hide', 'data-action' => 'hide'));
			} else if (!$displayedoncoursepage && $sectionvisible) {
				// Offer to "show" only if the section is visible.
				$controls .= html_writer::link(
					new moodle_url(new moodle_url($baseurl, array('show' => $mod->id))),
                    $OUTPUT->pix_icon('t/show', $strmodshow, 'moodle', array('class' => 'active-icon')),
                    array('class' => 'editing_show', 'data-action' => 'show'));
			}

            $controls .= html_writer::end_tag('div');
            $data->afterlink .= $controls;
        }

        return $data;
    }
}
