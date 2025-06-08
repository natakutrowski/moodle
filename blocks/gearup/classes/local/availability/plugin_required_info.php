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
 * Plugin required info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

/**
 * Plugin required info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_required_info implements info {

    /** @var string The plugin component. */
    protected $component;
    /** @var string The plugin name. */
    protected $name;
    /** @var string|null The release name for the version number. */
    protected $release;
    /** @var int|null The minimum version number. */
    protected $version;

    /**
     * Constructor.
     *
     * @param string $component The plugin component.
     * @param string $name The plugin name.
     * @param int|null $version The version.
     * @param string|null $release The version's release name.
     */
    public function __construct(string $component, string $name, ?int $version = null, ?string $release = null) {
        $this->component = $component;
        $this->name = $name;
        $this->version = $version;
        $this->release = $release;
    }

    public function is_available(): bool {
        $plugman = \core_plugin_manager::instance();
        $plugin = $plugman->get_plugin_info($this->component);

        if (!$plugin) {
            return false;
        }

        $isenabled = $plugin->is_enabled() ?? true;
        if (!$isenabled) {
            return false;
        }

        if ($this->version && (int) $plugin->versiondisk < $this->version) {
            return false;
        }

        return true;
    }

    public function get_reasons(): array {
        if (!$this->is_available()) {
            $plugman = \core_plugin_manager::instance();
            $plugin = $plugman->get_plugin_info($this->component);

            if (!$plugin) {
                $params = ['name' => $this->name, 'component' => $this->component];
                return [new \lang_string('requiresplugin', 'block_gearup', $params)];

            } else if (!($plugin->is_enabled() ?? true)) {
                $params = ['name' => $plugin->displayname, 'component' => $this->component];
                return [new \lang_string('pluginnotenabled', 'block_gearup', $params)];

            } else if ($this->version) {
                $params = ['name' => $plugin->displayname, 'component' => $this->component,
                    'release' => $this->release ?? $this->version];
                return [new \lang_string('pluginoutdated', 'block_gearup', $params)];
            }
        }
        return [];
    }

}
