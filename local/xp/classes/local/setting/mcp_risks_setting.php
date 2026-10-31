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

namespace local_xp\local\setting;

use local_xp\local\mcp\tool_definition;

/**
 * MCP risks setting.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mcp_risks_setting extends \admin_setting_configmulticheckbox {
    /**
     * Constructor.
     *
     * @param string $name Unique setting name.
     * @param string $visiblename Localised setting name.
     * @param string $description Localised setting description.
     * @param int $defaultsetting Default risk bitmask.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting) {
        $choices = [
            tool_definition::RISK_READ => get_string('mcpriskread', 'block_xp'),
            tool_definition::RISK_PERSONAL => get_string('mcpriskpersonal', 'block_xp'),
            tool_definition::RISK_WRITE => get_string('mcpriskwrite', 'block_xp'),
        ];
        $selection = [];
        foreach (array_keys($choices) as $risk) {
            if ((int) $defaultsetting & $risk) {
                $selection[$risk] = 1;
            }
        }
        parent::__construct($name, $visiblename, $description, $selection, $choices);
    }

    /**
     * Get the setting.
     *
     * @return array|null
     */
    public function get_setting() {
        $value = $this->config_read($this->name);
        if ($value === null) {
            return null;
        }

        $setting = [];
        foreach (array_keys($this->choices) as $risk) {
            if ((int) $value & (int) $risk) {
                $setting[$risk] = 1;
            }
        }
        return $setting;
    }

    /**
     * Save the setting.
     *
     * @param array $data Setting data.
     * @return string
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $value = 0;
        foreach (array_keys($this->choices) as $risk) {
            if (!empty($data[$risk])) {
                $value |= $risk;
            }
        }
        return $this->config_write($this->name, $value) ? '' : get_string('errorsetting', 'core_admin');
    }
}
