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
 * Context manager.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\context;

/**
 * Context manager.
 *
 * We shall make this an interface when needed. Developers, do not extend
 * this class as it could be removed for the purpose of an interface.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_manager {

    /**
     * Normalise the context.
     *
     * @param \context $context The context.
     * @return \context The normalised context.
     */
    public function normalise_context(\context $context): \context {
        $finalcontext = $context->get_course_context(false);
        if (!$finalcontext) {
            $finalcontext = \context_system::instance();
        }
        if ($finalcontext instanceof \context_course && $finalcontext->instanceid == SITEID) {
            $finalcontext = \context_system::instance();
        }
        // System, or course context, but not frontpage.
        return $finalcontext;
    }

}
