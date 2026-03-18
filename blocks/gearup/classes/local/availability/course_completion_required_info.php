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

/**
 * Course completion required.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

use completion_info;
use lang_string;

/**
 * Course completion required.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_completion_required_info implements info {

    /** @var \context The context. */
    protected $context;

    /**
     * Constructor.
     *
     * @param \context $context The context.
     */
    public function __construct(\context $context) {
        $this->context = $context;
    }

    public function is_available(): bool {
        global $CFG;

        $coursecontext = $this->context->get_course_context(false);
        if (!$coursecontext) {
            return false;
        }

        require_once($CFG->libdir . '/completionlib.php');
        $modinfo = get_fast_modinfo($coursecontext->instanceid);
        $completioninfo = new completion_info($modinfo->get_course());
        return $completioninfo->is_enabled() != COMPLETION_DISABLED;
    }

    public function get_reasons(): array {
        if (!$this->is_available()) {
            return [new lang_string('completionnotenabledforcourse', 'core_completion')];
        }
        return [];
    }

}
