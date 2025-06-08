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
 * Renderer.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\output;

use block_gearup\external\external_multiple_structure;
use block_gearup\external\external_single_structure;
use block_gearup\external\external_value;
use renderer_base;
use templatable;

/**
 * Renderer.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu implements templatable {

    /** @var array The menu items. */
    protected $items = [];

    public function __construct(array $items = []) {
        foreach ($items as $item) {
            if (empty($item['label'])) {
                $this->add_divider();
                continue;
            }
            $this->items[] = $item;
        }
    }

    public function add_divider(): self {
        $this->items[] = ['isdivider' => true];
        return $this;
    }

    public function export_for_template(renderer_base $output) {
        $menuitems = array_values(array_filter(array_map(function($item) {
            $attrs = [];
            $classes = [];
            if (empty($item['label'])) {
                return ['isdivider' => true];
            }
            foreach ($item as $key => $value) {
                if ($key === 'label' || $key === 'class') {
                    continue;

                } else if ($key === 'danger') {
                    if (empty($item['disabled'])) {
                        $classes[] = $value ? 'text-danger' : null;
                    }
                    continue;

                } else if ($key === 'disabled') {
                    if (!$value) {
                        continue;
                    }
                    $classes[] = 'disabled';
                    $value = 'disabled';
                }
                $attrs[] = [
                    'name' => $key,
                    'value' => $value instanceof \moodle_url ? $value->out(false) : (string) $value,
                ];
            }
            return [
                'label' => $item['label'],
                'attributes' => $attrs,
                'classes' => array_values(array_filter($classes)),
            ];
        }, $this->items)));

        // Filter out orphan or doubled dividers.
        $menuitems = array_values(array_filter($menuitems, function($v, $k) use ($menuitems) {
            if (empty($v['isdivider'])) {
                return true;
            }
            if ($k === 0 || $k === count($menuitems) - 1) {
                return false;
            } else if (array_key_exists('isdivider', $menuitems[$k - 1] ?? [])) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH));

        return [
            'hasmenu' => !empty($menuitems),
            'menuitems' => $menuitems,
        ];
    }

    public static function get_read_structure() {
        return new external_single_structure([
            'hasmenu' => new external_value(PARAM_BOOL, 'Whether the menu has items.'),
            'menuitems' => new external_multiple_structure(new external_single_structure([
                'isdivider' => new external_value(PARAM_BOOL, 'Whether the item is a divider.', VALUE_OPTIONAL),
                'label' => new external_value(PARAM_RAW, 'The label of the item.', VALUE_OPTIONAL),
                'attributes' => new external_multiple_structure(new external_single_structure([
                    'name' => new external_value(PARAM_ALPHANUMEXT, 'The name of the attribute.'),
                    'value' => new external_value(PARAM_RAW, 'The value of the attribute.'),
                ]), '', VALUE_OPTIONAL),
                'classes' => new external_multiple_structure(
                    new external_value(PARAM_RAW, 'The class of the item.'), '', VALUE_OPTIONAL),
            ])),
        ]);
    }

}
