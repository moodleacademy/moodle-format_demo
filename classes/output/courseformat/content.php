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
 * Output content for the format_demo plugin.
 *
 * @package   format_demo
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_demo\output\courseformat;

use core_courseformat\output\local\content as content_base;

class content extends content_base {

    /**
     * Returns the output class template path.
     *
     * This method redirects the default template when the course content is rendered.
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_demo/local/content';
    }

    public function export_for_template(\renderer_base $output): \stdClass {
        $course = $this->format->get_course();

        // Get data from parent.
        $data = parent::export_for_template($output);
        // Add additional data for the quicknews block.
        $data->quicknews = format_demo_quick_news_block($course);

        return $data;
    }
}