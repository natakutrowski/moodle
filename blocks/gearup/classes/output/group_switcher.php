<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

namespace block_gearup\output;

use block_gearup\local\utils\render_utils;
use block_gearup\local\utils\user_utils;
use context;
use core\output\named_templatable;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Group switcher.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_switcher implements renderable, templatable, named_templatable {

    /** @var moodle_url The base URL. */
    public $baseurl;
    /** @var int|object|context */
    public $courseish;
    /** @var int|null */
    public $groupid;

    /**
     * Constructor.
     *
     * @param moodle_url $baseurl The base URL.
     * @param int|object|context $courseish The course-ish or its context.
     * @param int|null $groupid The selected group ID.
     */
    public function __construct(moodle_url $baseurl, $courseish, $groupid) {
        $this->baseurl = $baseurl;
        $this->courseish = $courseish;
        $this->groupid = $groupid;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;

        $groupurl = new \moodle_url($this->baseurl);
        $groupurl->param('group', 'GROUPID');

        return [
            'groupoptions' => render_utils::flatten_select_options(
                user_utils::get_group_select_options($this->courseish, $USER->id),
                $this->groupid
            ),
            'url' => $groupurl->out(false),
        ];
    }

    public function get_template_name(renderer_base $renderer): string {
        return 'block_gearup/group_switcher';
    }

}
