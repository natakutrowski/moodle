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
use block_gearup\table\users_filterset;
use context;
use context_course;
use context_system;
use core\output\named_templatable;
use core_table\local\filter\filterset;
use renderable;
use renderer_base;
use templatable;

/**
 * Class users_filters
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_filters implements named_templatable, renderable, templatable {

    /** @var context The context. */
    public $context;
    /** @var users_filterset|null The filterset. */
    public $filterset;
    /** @var array The form params. */
    public $formparams;

    /**
     * Constructor.
     *
     * @param context $context The context.
     * @param array $formparams The form params.
     * @param filterset|null $filterset The filterset.
     */
    public function __construct(context $context, array $formparams, users_filterset $filterset) {
        $this->context = $context;
        $this->formparams = $formparams;
        $this->filterset = $filterset;
    }

    public function export_for_template(renderer_base $output) {
        global $USER;

        $term = null;
        $groupid = null;

        if ($this->filterset->has_filter('term')) {
            $term = $this->filterset->get_filter('term')->current();
        }
        if ($this->filterset->has_filter('groupid')) {
            $groupid = $this->filterset->get_filter('groupid')->current();
        }

        $formfields = [];
        foreach ($this->formparams as $key => $value) {
            if ($this->filterset && $this->filterset->has_filter($key)) {
                continue;
            }
            $formfields[] = ['name' => $key, 'value' => $value];
        }

        $selectoptions = user_utils::get_group_select_options($this->context, $USER->id);
        return [
            'term' => $term,
            'groupid' => $groupid,
            'withgroup' => !empty($selectoptions),
            'groupoptions' => render_utils::flatten_select_options($selectoptions, $groupid),
            'hiddenfields' => $formfields,
        ];
    }

    public function get_template_name(renderer_base $renderer): string {
        return 'block_gearup/table/users_filters';
    }

}
