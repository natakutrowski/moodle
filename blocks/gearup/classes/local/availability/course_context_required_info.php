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
 * Course context required info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

use lang_string;

/**
 * Course context required info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_context_required_info implements info {

    /** @var \context The context. */
    protected $context;
    /** @var bool Whether the front page is considered a course. */
    protected $includefrontpage;

    /**
     * Constructor.
     *
     * @param \context $context The context.
     * @param bool $includefrontpage Whether the frontpage is allowed as a course.
     */
    public function __construct(\context $context, $includefrontpage = false) {
        $this->context = $context;
        $this->includefrontpage = $includefrontpage;
    }

    public function is_available(): bool {
        $coursecontext = $this->context->get_course_context(false);
        return $coursecontext !== false && (!$this->includefrontpage ? $coursecontext->instanceid != SITEID : true);
    }

    public function get_reasons(): array {
        if (!$this->is_available()) {
            return [new lang_string('requirestobeincourse', 'block_gearup')];
        }
        return [];
    }

}
