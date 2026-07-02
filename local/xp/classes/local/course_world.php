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
 * Course World.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local;

use block_xp\di;
use moodle_database;
use block_xp\local\config\config;
use block_xp\local\factory\badge_url_resolver_course_world_factory;
use block_xp\local\factory\levels_info_factory;
use block_xp\local\logger\collection_logger;
use local_xp\local\config\default_course_world_config;
use local_xp\local\logger\context_collection_logger_extended;
use local_xp\local\strategy\collection_target_resolver_from_event;
use local_xp\local\strategy\world_action_collection_strategy;
use local_xp\local\xp\user_global_state;

/**
 * Course World.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_world extends \block_xp\local\course_world {

    /** @var strategy\course_world_collection_strategy */
    protected $strategy;
    /** @var collection_logger */
    protected $logger;
    /** @var collection_target_resolver_from_event */
    private $usercolletiontargetresolver;

    /**
     * Constructor.
     *
     * @param config $config The course config.
     * @param moodle_database $db The DB.
     * @param int $courseid The course ID.
     * @param collection_target_resolver_from_event $usercolletiontargetresolver The user collection target resolver.
     * @param badge_url_resolver_course_world_factory $urlresolverfactory The badge URL resolver factory.
     * @param levels_info_factory $levelsinfofactory The levels info factory.
     */
    public function __construct(
        config $config,
        moodle_database $db,
        $courseid,
        collection_target_resolver_from_event $usercolletiontargetresolver,
        badge_url_resolver_course_world_factory $urlresolverfactory,
        levels_info_factory $levelsinfofactory
    ) {

        parent::__construct($config, $db, $courseid, $urlresolverfactory, $levelsinfofactory);
        $this->usercolletiontargetresolver = $usercolletiontargetresolver;
    }

    /**
     * Return the collection strategy.
     *
     * @return \block_xp\local\strategy\collection_strategy
     */
    public function get_collection_strategy() {
        if (!$this->strategy) {
            $fm = $this->get_filter_manager();
            $calculator = new \local_xp\local\rule\stack_calculator([
                new \local_xp\local\rule\grade_calculator($fm->get_filters(\block_xp_filter::CATEGORY_GRADES)),
                new \local_xp\local\rule\filters_calculator($fm->get_filters(\block_xp_filter::CATEGORY_EVENTS)),
            ]);
            $this->strategy = new \local_xp\local\strategy\course_world_collection_strategy(
                $this->context,
                $this->get_config(),
                $this->get_store(),
                $calculator,
                $this->get_collection_logger(),
                $this->get_collection_logger(),
                $this->get_collection_logger(),
                new \block_xp\local\notification\course_level_up_notification_service($this->courseid),
                $this->usercolletiontargetresolver,
                $this->get_collection_logger()
            );
            $actionstrategy = new world_action_collection_strategy(
                $this,
                $this->get_collection_logger(),
                null,
                di::get('rule_type_resolver'),
                di::get('rule_filter_handler')
            );
            $actionstrategy->set_world_rule_manager(di::get('world_rule_manager_factory')->get_rule_manager($this));
            $actionstrategy->set_rule_sorter(di::get('rule_sorter'));
            $this->strategy->set_action_collection_strategy($actionstrategy);

            // The cheatguard for events needs to work slightly differently, as it needs to discard
            // the logs we've collected from other sources, such as the action rules. The way we
            // instantiate this isn't ideal as it is somewhat hardcoded but it should do for now.
            $cheatguardreader = $this->get_collection_logger();
            if ($cheatguardreader instanceof context_collection_logger_extended) {
                $cheatguardreader = new logger\event_cheatguard_reader($cheatguardreader);
                $this->strategy->set_event_cheatguard_reader($cheatguardreader);
            }
        }
        return $this->strategy;
    }

    /**
     * Get the state store.
     *
     * @return xp\course_user_state_store
     */
    public function get_store() {
        if (!$this->store) {

            $userstatefactory = null;
            $levelsinfo = $this->get_levels_info();

            if ($this->config->get('progressbarmode') == default_course_world_config::PROGRESS_BAR_MODE_OVERALL) {
                $userstatefactory = function ($user, $points) use ($levelsinfo) {
                    return new user_global_state($user, $points, $levelsinfo);
                };
            }

            $this->store = new \local_xp\local\xp\course_user_state_store(
                $this->db,
                $levelsinfo,
                $this->get_courseid(),
                $this->get_collection_logger(),
                $userstatefactory,
                $this->get_level_up_state_store_observer(),
                $this->get_points_increased_state_store_observer()
            );
        }
        return $this->store;
    }

    /**
     * Get the collection logger.
     *
     * @return object
     */
    private function get_collection_logger() {
        if (empty($this->logger)) {
            throw new \coding_exception('The collection logger was not set, call set_collection_logger first.');
        }
        return $this->logger;
    }

    /**
     * Get the user recent activity repository.
     *
     * @return user_recent_activity_repository
     */
    public function get_user_recent_activity_repository() {
        return $this->get_collection_logger();
    }
}
