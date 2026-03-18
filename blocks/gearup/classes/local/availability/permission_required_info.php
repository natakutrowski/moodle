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
 * Permission required info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

/**
 * Permission required info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission_required_info implements info {

    /** @var \context The permission context. */
    protected $context;
    /** @var string[] The permission names. */
    protected $permnames;
    /** @var int The user ID. */
    protected $userid;

    /**
     * Constructor.
     *
     * @param string|string[] $permnameornames The permission name(s).
     * @param \context $context The context.
     * @param int $userid The user ID.
     */
    public function __construct($permnameornames, \context $context, int $userid) {
        $this->permnames = is_array($permnameornames) ? $permnameornames : [$permnameornames];
        $this->context = $context;
        $this->userid = $userid;
    }

    public function is_available(): bool {
        return has_all_capabilities($this->permnames, $this->context, $this->userid);
    }

    public function get_reasons(): array {
        $reasons = [];
        if (!$this->is_available()) {
            foreach ($this->permnames as $permname) {
                if (has_capability($permname, $this->context, $this->userid)) {
                    continue;
                }
                [$plugintype, $barepermname] = explode('/', $permname, 2);
                [$pluginname, $rest] = explode(':', $barepermname, 2);
                $component = $plugintype === 'moodle' ? 'core_role' : $plugintype . '_' . $pluginname;
                $reasons[] = new \lang_string('requirespermission', 'block_gearup', [
                    'label' => get_string($barepermname, $component),
                    'name' => $permname,
                ]);
            }
        }
        return $reasons;
    }

}
