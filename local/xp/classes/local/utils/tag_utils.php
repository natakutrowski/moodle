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

namespace local_xp\local\utils;

use block_xp\di;
use context;
use core_tag_tag;

/**
 * Tag utils.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_utils {

    /**
     * Is the tag used in context and areas.
     *
     * @param mixed $name Tag name.
     * @param context $context Context whose subtree is searched.
     * @param array $areas Tag areas as tuples of [component, itemtype].
     * @return bool
     */
    public static function is_tag_used($name, context $context, array $areas = []): bool {
        $tagname = self::normalise_tag_name($name);
        if ($tagname === '') {
            return false;
        }
        [$sql, $params] = self::build_tag_used_query($tagname, $context, $areas);
        return di::get('db')->record_exists_sql($sql, $params);
    }

    /**
     * Normalise a tag.
     *
     * @param mixed $rawvalue Raw tag value.
     * @return string Normalised name, or empty string.
     */
    public static function normalise_tag_name($rawvalue): string {
        $tags = core_tag_tag::normalize([(string) $rawvalue]);
        return (reset($tags) ?: null) ?? '';
    }

    /**
     * Build SQL to find a tag instance for a normalised tag name in a context subtree, optionally restricted by areas.
     *
     * @param string $tagname Normalised tag name ({@see tag}.name).
     * @param context $context Context whose subtree is searched.
     * @param array<int, array{0: string, 1: string}> $areas Optional [component, itemtype] tuples; empty = any area.
     * @return array{0: string, 1: array}
     */
    protected static function build_tag_used_query(string $tagname, context $context, array $areas): array {
        $db = di::get('db');

        $conditions = [];
        $params = [];

        $conditions[] = '(ctx.id = :ctxid OR ' . $db->sql_like('ctx.path', ':childctxpath', false) . ')';
        $conditions[] = 'tg.name = :tagname';
        $params += [
            'ctxid' => $context->id,
            'childctxpath' => $context->path . '/%',
            'tagname' => $tagname,
        ];

        if (!empty($areas)) {
            $orparts = [];
            $i = 0;
            foreach ($areas as $area) {
                [$component, $itemtype] = $area;
                $paramname = 'comp' . $i;
                $orparts[] = "(ti.component = :{$paramname}0 AND ti.itemtype = :{$paramname}1)";
                $params[$paramname . '0'] = $component;
                $params[$paramname . '1'] = $itemtype;
                $i++;
            }
            $conditions[] = '(' . implode(' OR ', $orparts) . ')';
        }

        $where = '(' . implode(') AND (', $conditions) . ')';
        $sql = "SELECT 1
                  FROM {tag_instance} ti
                  JOIN {tag} tg ON tg.id = ti.tagid
                  JOIN {context} ctx ON ctx.id = ti.contextid
                 WHERE {$where}";

        return [$sql, $params];
    }
}
