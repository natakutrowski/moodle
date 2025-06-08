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
 * Container.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local;

use cache;

/**
 * Container.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_container implements container {

    /** @var array The objects supported by this container. */
    protected static $supports = [
        'achievement_badges_repository' => true,
        'access_permissions_factory' => true,
        'action_maker' => true,
        'action_processor' => true,
        'api_client' => true,
        'assigner_processor' => true,
        'assigner_type_resolver' => true,
        'block_class' => true,
        'clock' => true,
        'completion_ratio_calculator' => true,
        'context_manager' => true,
        'exporter_factory' => true,
        'html_injector' => true,
        'lm' => true,
        'metadata_cache' => true,
        'mission_operator' => true,
        'objective_operator' => true,
        'objective_processor' => true,
        'objective_type_resolver' => true,
        'outcome_processor' => true,
        'outcome_type_resolver' => true,
        'quest_narrator_visuals_repository' => true,
        'renderer' => true,
        'repository' => true,
        'router' => true,
        'routes_config' => true,
        'system_router' => true,
        'system_url_resolver' => true,
        'url_resolver' => true,
        'url_resolver_factory' => true,
        'usage_reporter' => true,
    ];

    /** @var array Object instances. */
    protected $instances = [];

    /**
     * Get a thing.
     *
     * @param string $id The thing's name.
     * @return mixed
     */
    public function get($id) {
        if (!isset($this->instances[$id])) {
            $method = 'get_' . $id;
            $this->instances[$id] = $this->{$method}();
        }
        return $this->instances[$id];
    }

    /**
     * Get the action maker.
     *
     * @return action\maker\maker
     */
    protected function get_achievement_badges_repository() {
        return new visual\achievement_badges_repository(
            $this->get('metadata_cache'),
            $this->get('api_client')
        );
    }

    /**
     * Get the action maker.
     *
     * @return action\maker\maker
     */
    protected function get_action_maker() {
        return new action\maker\event_action_maker();
    }

    /**
     * Get the action maker.
     *
     * @return factory\access_permissions_factory
     */
    protected function get_access_permissions_factory() {
        return new factory\access_permissions_factory();
    }

    /**
     * Get the action maker.
     *
     * @return action\processor\processor
     */
    protected function get_action_processor() {
        if (!$this->get('lm')->is_active()) {
            return new action\processor\dummy_processor();
        }
        return new action\processor\default_processor(
            $this->get('repository'),
            $this->get('objective_type_resolver'),
            $this->get('mission_operator'),
            $this->get('objective_operator'),
        );
    }

    /**
     * Get the API client.
     *
     * @return http\api_client
     */
    protected function get_api_client() {
        return new http\api_client();
    }

    /**
     * Get the assigner type resolver.
     *
     * @return assigner\resolver
     */
    protected function get_assigner_processor() {
        if (!$this->get('lm')->is_active()) {
            return new assigner\processor\dummy_processor();
        }
        return new assigner\processor\default_processor(
            $this->get('assigner_type_resolver'),
            $this->get('repository'),
            $this->get('mission_operator'),
        );
    }

    /**
     * Get the assigner type resolver.
     *
     * @return assigner\resolver\resolver
     */
    protected function get_assigner_type_resolver() {
        return new assigner\resolver\default_resolver($this->get('lm'));
    }

    /**
     * Get the block class.
     *
     * @return string
     */
    protected function get_block_class() {
        if (!$this->get('lm')->is_active()) {
            return 'block_gearup\\local\\block\\placeholder';
        }
        return 'block_gearup\\local\\block\\standard';
    }

    /**
     * Get the clock.
     *
     * @return object
     */
    protected function get_clock() {
        if (interface_exists(\core\clock::class)) {
            throw new \coding_exception('Incorrect dependency resolution, the clock should be retrieved from core.');
        }
        return new utils\clock();
    }

    /**
     * Get the completion ratio calculator.
     *
     * @return object
     */
    protected function get_completion_ratio_calculator() {
        // The completion ratio calculator is meant to contain calculate_completion_ratio($missioninst), but
        // instead of creating a new interface and a dedicated class, for now we've just put the method in
        // the mission helper. Note that the completion ratio calculator has its own entry in the DI container
        // because it might be useful in the future to be able to swap it for another one.
        return $this->get('mission_helper');
    }

    /**
     * Get the context manager.
     *
     * @return object
     */
    protected function get_context_manager() {
        return new \block_gearup\local\context\context_manager();
    }

    /**
     * Get the exporter factory.
     *
     * @return exporter\factory
     */
    protected function get_exporter_factory() {
        return new factory\exporter_factory(
            $this->get('access_permissions_factory'),
            $this->get('mission_helper')
        );
    }

    /**
     * Get the HTML injector.
     *
     * @return html\injector
     */
    protected function get_html_injector() {
        return new html\injector();
    }

    /**
     * Get the licence manager.
     *
     * @return object
     */
    protected function get_lm() {
        return new plugin\lm($this->get('metadata_cache'));
    }

    /**
     * Get the metadata cache.
     *
     * @return cache
     */
    protected function get_metadata_cache() {
        return cache::make('block_gearup', 'metadata');
    }

    /**
     * Get the mission helper.
     *
     * @return mission\helper
     */
    protected function get_mission_helper() {
        return new mission\helper();
    }

    /**
     * Get the mission operator.
     *
     * @return operator\mission_operator
     */
    protected function get_mission_operator() {
        $repository = $this->get('repository');
        $missionhelper = $this->get('mission_helper');
        $objectiveoperator = $this->get('objective_operator');
        $completionratiocalculator = $this->get('completion_ratio_calculator');
        return new operator\mission_operator($repository, $missionhelper, $completionratiocalculator, $objectiveoperator);
    }

    /**
     * Get the objective operator.
     *
     * @return operator\objective_operator
     */
    protected function get_objective_operator() {
        return new operator\objective_operator();
    }

    /**
     * Get the objective processor.
     *
     * @return objective\processor\default_processor
     */
    protected function get_objective_processor() {
        return new objective\processor\default_processor(
            $this->get('objective_type_resolver'),
            $this->get('repository')
        );
    }

    /**
     * Get the objective type resolver.
     *
     * @return objective\resolver
     */
    protected function get_objective_type_resolver() {
        return new objective\resolver\default_resolver($this->get('lm'));
    }

    /**
     * Get the outcome processor.
     *
     * @return outcome\processor\default_processor
     */
    protected function get_outcome_processor() {
        return new outcome\processor\default_processor(
            $this->get('outcome_type_resolver'),
            $this->get('repository')
        );
    }

    /**
     * Get the outcome type resolver.
     *
     * @return outcome\resolver
     */
    protected function get_outcome_type_resolver() {
        return new outcome\resolver\default_resolver($this->get('lm'));
    }

    /**
     * Get the quest narator visuals repository.
     *
     * @return visual\visuals_repository
     */
    protected function get_quest_narrator_visuals_repository() {
        return new visual\quest_narrators_repository(
            $this->get('metadata_cache'),
            $this->get('api_client')
        );
    }

    /**
     * Get the renderer.
     *
     * @return object
     */
    protected function get_renderer() {
        global $PAGE;
        if (!$PAGE->has_set_url() && !WS_SERVER && !AJAX_SCRIPT) {
            debugging('The renderer was requested too early in the process.', DEBUG_DEVELOPER);
        }
        $renderer = $PAGE->get_renderer('block_gearup');
        $renderer->set_lm($this->get('lm'));
        return $renderer;
    }

    /**
     * Get the repository.
     *
     * @return repository\repository
     */
    protected function get_repository() {
        $assignertyperesolver = $this->get('assigner_type_resolver');
        $objtyperesolver = $this->get('objective_type_resolver');
        $outcometyperesolver = $this->get('outcome_type_resolver');
        return new repository\repository($objtyperesolver, $outcometyperesolver, $assignertyperesolver);
    }

    /**
     * Get the router.
     *
     * @return object
     */
    protected function get_router() {
        if (!$this->get('lm')->is_activated()) {
            return new routing\activation_router();
        } else if (!$this->get('lm')->is_active()) {
            return new routing\inactive_router();
        }
        return new routing\router($this->get('url_resolver'));
    }

    /**
     * Get the routes config.
     *
     * @return object
     */
    protected function get_routes_config() {
        return new routing\routes_config_default();
    }

    /**
     * Get the router.
     *
     * @return object
     */
    protected function get_system_router() {
        return new routing\router($this->get('system_url_resolver'));
    }

    /**
     * Get the router.
     *
     * @return object
     */
    protected function get_system_url_resolver() {
        return new routing\url_resolver_default(
            new \moodle_url('/blocks/gearup/system.php'),
            new routing\system_routes_config()
        );
    }

    /**
     * Get the URL resolver.
     *
     * @return object
     */
    protected function get_url_resolver() {
        return new routing\url_resolver_default(
            new \moodle_url('/blocks/gearup/index.php'),
            $this->get('routes_config')
        );
    }

    /**
     * Get the URL resolver factory.
     *
     * @return object
     */
    protected function get_url_resolver_factory() {
        return new factory\default_url_resolver_factory($this->get('url_resolver'));
    }

    /**
     * Get usage reporter.
     *
     * @return plugin\usage_reporter
     */
    protected function get_usage_reporter() {
        return new plugin\usage_reporter(new plugin\usage_report_maker(), $this->get('api_client'));
    }

    /**
     * Whether this container can return an entry for the given identifier.
     *
     * @param string $id The thing's name.
     * @return bool
     */
    public function has($id) {
        return array_key_exists($id, static::$supports);
    }

}
