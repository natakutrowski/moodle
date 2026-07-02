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

namespace local_xp\local\reason;

use block_xp\local\reason\default_resolver;
use block_xp\local\reason\reason;

/**
 * Resolver.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resolver extends default_resolver {

    /**
     * Get class name candidates.
     *
     * @param string $name The reason name.
     * @return string[]
     */
    protected function get_class_name_candidates(string $name): array {
        $parentcandidates = parent::get_class_name_candidates($name);

        // If it's a fully-qualified name, do nothing more.
        if (strpos($name, '\\') !== false) {
            return $parentcandidates;
        }

        if (strrpos($name, '_reason') === strlen($name) - 7) {
            $name = substr($name, 0, -7);
        }

        return array_merge([
            'local_xp\\local\\reason\\' . $name . '_reason',
            'local_xp\\local\\reason\\' . $name,
        ], $parentcandidates);
    }

    /**
     * Get the reason name.
     *
     * @param reason|string $reason A reason instance, or class name.
     * @return string
     */
    public function get_name($reason): string {
        $name = get_class($reason);

        // Strip namespace to support overriding XP reasons.
        if (strpos($name, 'local_xp\\local\\reason\\') === 0) {
            $name = str_replace('local_xp\\local\\reason\\', '', $name);
            if (substr($name, -7) === '_reason') {
                $name = substr($name, 0, -7);
            }
            return $name;
        }

        return parent::get_name($reason);
    }
}
