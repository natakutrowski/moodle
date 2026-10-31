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

namespace local_xp\local\world;

use block_xp\di;
use context;
use context_system;
use block_xp\local\config\config;
use block_xp\local\factory\levels_info_factory;
use block_xp\local\logger\collection_logger;
use block_xp\local\permission\access_permissions;
use block_xp\local\strategy\collection_strategy;
use block_xp\local\xp\levels_info;
use block_xp\local\xp\state_store_with_query;
use block_xp\local\world\world_with_features;
use local_xp\local\config\default_course_world_config;
use local_xp\local\logger\global_world_collection_logger;
use local_xp\local\xp\global_world_state_store;
use local_xp\local\xp\user_global_state;
use Override;

/**
 * Global world.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_world implements world_with_features {
    /** @var config The config. */
    protected $config;
    /** @var context The context. */
    protected $context;
    /** @var levels_info The levels info. */
    protected $levelsinfo;
    /** @var access_permissions The access permissions. */
    protected $perms;
    /** @var levels_info_factory The levels info factory. */
    protected $levelsinfofactory;
    /** @var collection_logger|null The logger. */
    protected $logger = null;
    /** @var state_store_with_query|null The store. */
    protected $store = null;

    /**
     * Constructor.
     *
     * @param config $config The course config.
     */
    public function __construct(config $config) {
        $this->config = $config;
        $this->context = context_system::instance();
        $this->perms = new \block_xp\local\permission\context_permissions($this->context);
        $this->levelsinfofactory = di::get('levels_info_factory');
    }

    /**
     * Whether a feature is supported by the world.
     *
     * @param string $feature A FEAT_* constant.
     * @return bool|null
     */
    public function supports(string $feature): ?bool {
        if (
            in_array($feature, [
            self::FEATURE_TEAM_LEADERBOARD,
            self::FEATURE_LOCAL_POINTS_MANAGEMENT,
            self::FEATURE_RECENT_ACTIVITY,
            ], true)
        ) {
            return false;
        }
        return null;
    }

    /**
     * Get access permissions.
     *
     * @return \block_xp\local\permission\access_permissions
     */
    public function get_access_permissions() {
        return $this->perms;
    }

    /**
     * Get config.
     *
     * @return \block_xp\local\config\config
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Get collection strategy.
     *
     * @return \block_xp\local\strategy\collection_strategy
     */
    public function get_collection_strategy() {
        return new class () implements collection_strategy {
        };
    }

    /**
     * Get context.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get levels info.
     *
     * @return levels_info
     */
    public function get_levels_info() {
        if (!$this->levelsinfo) {
            $this->levelsinfo = $this->levelsinfofactory->get_world_levels_info($this);
        }
        return $this->levelsinfo;
    }

    /**
     * Get the logger.
     *
     * @return collection_logger
     */
    public function get_logger() {
        $this->logger ??= new global_world_collection_logger();
        return $this->logger;
    }

    /**
     * Get store.
     *
     * @return state_store_with_query
     */
    public function get_store() {
        if ($this->store !== null) {
            return $this->store;
        }

        $userstatefactory = null;
        $levelsinfo = $this->get_levels_info();

        if ($this->config->get('progressbarmode') == default_course_world_config::PROGRESS_BAR_MODE_OVERALL) {
            $userstatefactory = function ($user, $points) use ($levelsinfo) {
                return new user_global_state($user, $points, $levelsinfo);
            };
        }

        $this->store = new global_world_state_store($levelsinfo, $userstatefactory);
        return $this->store;
    }
}
