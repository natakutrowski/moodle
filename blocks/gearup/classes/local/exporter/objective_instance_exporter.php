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

use block_gearup\local\mission\mission;
use block_gearup\local\objective\objective_instance;
use renderer_base;

/**
 * Exporter.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_instance_exporter extends \core\external\exporter {

    /** @var objective_instance The objective instance. */
    protected $objinst;

    /**
     * Constructor.
     *
     * @param objective_instance $objinst The objective instance.
     * @param array $related The related objects.
     */
    public function __construct(objective_instance $objinst, $related = []) {
        $this->objinst = $objinst;
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
            'mission' => 'block_gearup\local\mission\mission',
        ];
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'objective' => [
                'type' => objective_exporter::read_properties_definition(),
            ],
            'counter' => [
                'type' => PARAM_INT,
            ],
            'iscompleted' => [
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
        $objinst = $this->objinst;
        $objective = $objinst->get_objective();

        $oe = new objective_exporter($objective, [
            'context' => $this->related['context'],
            'mission' => $this->related['mission'],
        ]);

        return [
            'objective' => $oe->export($output),
            'counter' => $objinst->get_counter(),
            'iscompleted' => $objinst->is_completed(),
        ];
    }
}
