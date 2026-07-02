<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Serializer factory.
 *
 * @package    local_xp
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\factory;

use block_xp\di;
use block_xp\local\serializer\url_serializer;
use local_xp\local\serializer\level_serializer;
use local_xp\local\serializer\rule_serializer;

/**
 * Serializer factory.
 *
 * @package    local_xp
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class serializer_factory extends \block_xp\local\factory\serializer_factory {

    public function get_level_serializer() {
        return new level_serializer(new url_serializer());
    }

    public function get_rule_serializer() {
        $serializer = new rule_serializer(di::get('rule_filter_handler'));
        $serializer->set_limit_spec_serializer($this->get_limit_spec_serializer());
        return $serializer;
    }

}
