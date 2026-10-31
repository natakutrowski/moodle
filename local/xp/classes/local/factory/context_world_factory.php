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

namespace local_xp\local\factory;

use block_xp\local\config\config;
use block_xp\local\config\config_stack;
use block_xp\local\config\immutable_config;
use block_xp\local\config\static_config;
use block_xp\local\factory\navbar_world_factory;
use context;
use block_xp\local\world;
use context_system;
use local_xp\local\world\global_world;

/**
 * Factory.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_world_factory implements
    \block_xp\local\factory\context_world_factory,
    \block_xp\local\factory\navbar_world_factory {
    /** @var config */
    protected $adminconfig;
    /** @var \block_xp\local\factory\context_world_factory */
    protected $parentfactory;
    /** @var course_config_factory */
    protected $configfactory;
    /** @var world */
    protected $globalcache;

    /**
     * Constructor.
     *
     */
    public function __construct(
        config $adminconfig,
        \block_xp\local\factory\context_world_factory $parentfactory,
        course_config_factory $configfactory,
    ) {
        $this->adminconfig = $adminconfig;
        $this->parentfactory = $parentfactory;
        $this->configfactory = $configfactory;
    }

    /**
     * Get the world.
     *
     * @param \context $context
     * @return world|null
     */
    public function get_world_for_navbar(\context $context): ?world {
        $config = $this->adminconfig;
        if (!$config->get('navbardisplay')) {
            return null;
        }

        $issitewide = $config->get('context') == CONTEXT_SYSTEM;
        if (!$issitewide && $config->get('enableglobalworld') && $config->get('navbarshowoverall')) {
            return $this->get_world_from_context(context_system::instance());
        }

        if (!$this->parentfactory instanceof navbar_world_factory) {
            return null;
        }
        return $this->parentfactory->get_world_for_navbar($context);
    }

    /**
     * Get the world.
     *
     * @param \context $context The context.
     * @return world
     */
    public function get_world_from_context(context $context): world {
        if ($this->adminconfig->get('context') == CONTEXT_COURSE && $this->adminconfig->get('enableglobalworld')) {
            $finalcontext = $context->get_course_context(false);
            if (!$finalcontext || $finalcontext->instanceid == SITEID) {
                $this->globalcache ??= new global_world(
                    new config_stack([
                        new immutable_config(new static_config([
                            'enabled' => true,
                            'enablegroupladder' => false,
                            'blockrecentactivity' => 0,
                        ])),
                        $this->configfactory->get_config(SITEID), // Change this.
                    ])
                );
                return $this->globalcache;
            }
        }
        return $this->parentfactory->get_world_from_context($context);
    }
}
