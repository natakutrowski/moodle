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
// See <https://levelup.plus>.

namespace local_xp\local\navigation;

use block_xp\di;
use block_xp\local\navigation\nav_item;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\utils\world_utils;
use local_xp\local\config\default_course_world_config;
use pix_icon;

/**
 * Course world navigator.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_world_navigator extends \block_xp\local\navigation\course_world_navigator {
    /**
     * Get the block navigation.
     *
     * @return nav_item[]
     */
    public function get_block_navigation(): array {
        $links = parent::get_block_navigation();
        $config = $this->world->get_config();

        // Add the group ladder when the ladder is not already present.
        if (
            world_utils::supports_team_leaderboard($this->world)
                && $config->get('enablegroupladder') != default_course_world_config::GROUP_LADDER_NONE
        ) {
            $hasladder = false;
            $insertat = 0;
            foreach ($links as $index => $link) {
                if ($link->get_id() === 'ladder') {
                    $hasladder = true;
                }
                if ($link->get_id() === 'infos') {
                    $insertat = $index + 1;
                }
            }

            if (!$hasladder) {
                $link = (new nav_item(get_string('navladder', 'block_xp'), $this->get_url('group_ladder'), 'group_ladder'))
                    ->set_icon(new pix_icon('i/ladder', '', 'block_xp'));
                array_splice($links, $insertat, 0, [$link]);
            }
        }

        return $links;
    }

    /**
     * Get the navigation.
     *
     * @return nav_item[]
     */
    public function get_navigation(): array {
        $links = parent::get_navigation();
        $tohide = [];
        $accessperms = $this->world->get_access_permissions();

        $canaccessreport = $accessperms instanceof access_report_permissions && $accessperms->can_access_report();
        if (!$canaccessreport) {
            $tohide[] = 'import';
        }

        $canseeaipage = di::get('config')->get('enablemcp')
            && $accessperms->can_manage()
            && has_capability('local/xp:usemcp', \context_system::instance())
            && has_capability('local/xp:usemcp', $this->world->get_context());
        if (!$canseeaipage) {
            $tohide[] = 'ai';
        }

        return $this->remove_items($links, $tohide);
    }

    /**
     * Remove items recursively.
     *
     * @param nav_item[] $items
     * @param string[] $toremove
     * @return nav_item[]
     */
    protected function remove_items(array $items, array $toremove) {
        if (empty($toremove)) {
            return $items;
        }
        return array_values(array_filter(array_map(function ($item) use ($toremove) {
            if (in_array($item->get_id(), $toremove)) {
                return null;
            } else if (!$item->get_children()) {
                return $item;
            }
            $item->set_children($this->remove_items($item->get_children(), $toremove));
            return $item;
        }, $items)));
    }
}
