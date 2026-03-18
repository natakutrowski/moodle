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
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\exporter;

use block_gearup\local\outcome\outcome;
use block_gearup\local\outcome\type\user_facing_type;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class outcome_exporter extends \core\external\exporter {

    /** @var outcome The outcome. */
    protected $outcome;

    /**
     * Constructor.
     *
     * @param outcome $outcome The outcome.
     * @param array $related The related objects.
     */
    public function __construct(outcome $outcome, $related = []) {
        $this->outcome = $outcome;
        parent::__construct([], $related);
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'context' => 'context',
            'mission' => 'block_gearup\\local\\mission\\mission',
        ];
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
            ],
            'missionid' => [
                'type' => PARAM_INT,
            ],
            'label' => [
                'type' => PARAM_TEXT,
            ],
            'type' => [
                'type' => PARAM_TEXT,
            ],
            'isreward' => [
                'type' => PARAM_BOOL,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $outcome = $this->outcome;
        return [
            'id' => $outcome->get_id(),
            'missionid' => $this->related['mission']->get_id(),
            'label' => $outcome->get_label(),
            'type' => (string) $outcome->get_type()->get_display_name(),
            'isreward' => $outcome->get_type() instanceof user_facing_type,
        ];
    }
}
