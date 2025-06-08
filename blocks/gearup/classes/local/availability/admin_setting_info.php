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
 * Admin setting info.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

use lang_string;

/**
 * Admin setting info.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_info implements info {

    /** @var string The expected value. */
    protected $expected;
    /** @var string The setting's label. */
    protected $label;
    /** @var string The setting's name. */
    protected $name;

    /**
     * Constructor.
     *
     * @param string $name The setting name.
     * @param string $label The setting label.
     * @param mixed $expected The expected value.
     */
    public function __construct($name, lang_string $label, $expected = true) {
        $this->name = $name;
        $this->expected = $expected;
        $this->label = $label;
    }

    public function is_available(): bool {
        global $CFG;
        $value = (bool) ($CFG->{$this->name} ?? false);
        return $value === $this->expected;
    }

    public function get_reasons(): array {
        $reasons = [];
        if (!$this->is_available()) {
            $reasons[] = new lang_string('infodisabledbyadminsetting', 'block_gearup', [
                'name' => $this->name,
                'label' => (string) $this->label,
            ]);
        }
        return $reasons;
    }

}
