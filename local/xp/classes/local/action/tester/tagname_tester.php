<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

namespace local_xp\local\action\tester;

use block_xp\local\action\action;
use block_xp\local\action\tester\action_tester;
use local_xp\local\utils\tag_utils;

/**
 * Tag name tester.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tagname_tester implements action_tester {

    /** @var array[] Tuples of [component, itemtype].*/
    protected $areas = [];
    /** @var string Tag name normalised using the core tag library. */
    protected $tagname;

    /**
     * Constructor.
     *
     * @param string $tagname Tag name.
     * @param array[] $areas Tag areas as tuples of [component, itemtype].
     */
    public function __construct($tagname, array $areas) {
        $this->tagname = trim((string) $tagname);
        $this->areas = $areas;
    }

    public function is_action_passing_constraints(action $action): bool {
        if ($this->tagname === '') {
            return false;
        }
        return tag_utils::is_tag_used($this->tagname, $action->get_context(), $this->areas);
    }

}
