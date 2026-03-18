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
 * Watch video.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\video_watched;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\availability\info_stack;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\form_utils;
use context_module;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Watch video.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watch_video implements has_availability_info, type, type_with_supporting_url, type_with_update_after_restore {

    /** URL mode to course module. */
    const URLMODE_CM = 1;

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof video_watched) {
            return;
        }
        $state = $this->get_normalized_state($instance);
        $instance->increment_counter(1);
        $state->vids[] = $action->get_video_id();
        $instance->set_type_state($state);
    }

    public function get_availability_info(): info {
        $stack = [new plugin_required_info('media_videojs', 'VieoJS')];
        if (get_config('media_videojs', 'useflash')) {
            $stack[] = new static_info(false, [new lang_string('infoincompatiblewithsetting',
                'block_gearup',
                'media_videojs/useflash'
            )]);
        }
        return new info_stack($stack);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new watch_video_config_form_extender($mission);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typewatchvideo', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typewatchvideodesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?\moodle_url {
        if ($urltype === static::URLMODE_CM) {
            $config = $objective->get_type_config();
            $cmid = $config->cmid ?? 0;
            if ($cmid) {
                $cmctx = context_module::instance($cmid, IGNORE_MISSING);
                $coursectx = $cmctx ? $cmctx->get_course_context(false) : null;
                if ($cmctx && $coursectx) {
                    return course_utils::get_cm_info($coursectx->instanceid, 0, $cmid)->url ?? null;
                }
            }
        }
        return null;
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof video_watched;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        if (!$action instanceof video_watched) {
            return false;
        }

        $config = $instance->get_objective()->get_type_config();

        // Filter out by video ID.
        if (!empty($config->videoid) && $action->get_video_id() !== $config->videoid) {
            return false;
        }

        // Filter out if context is not matching the activity.
        if (!empty($config->cmid) && ($action->get_context()->contextlevel != CONTEXT_MODULE
                || $config->cmid != $action->get_context()->instanceid)) {
            return false;
        }

        $state = $this->get_normalized_state($instance);
        if (in_array($action->get_video_id(), $state->vids)) {
            return false;
        }

        return true;
    }

    /**
     * Get the state.
     *
     * @param objective_instance $instance The instance.
     * @return object
     */
    protected function get_normalized_state(objective_instance $instance) {
        $state = $instance->get_type_state();
        return $state ?? (object) ['vids' => []];
    }

    public function update_after_restore(restore_context $restore, objective $objective, mission $mission) {
        if (!$objective instanceof persisted_objective) {
            $restore->get_logger()->process("Cannot process after_restore of objective " . $objective->get_id(),
                backup::LOG_WARNING
            );
            return;
        }

        $config = $objective->get_type_config();
        $cmid = $config->cmid ?? null;
        $haschanged = false;

        if (empty($cmid)) {
            return;
        }

        if ($cmid) {
            $newcmid = $restore->get_mapping_id('course_module', $cmid);
            if ($newcmid) {
                $config->cmid = $newcmid;
                $haschanged = true;
            }
        }
        if (!$haschanged) {
            return;
        }

        try {
            $objective->get_persistent()->set('configdata', $config);
            $objective->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating objective " . $objective->get_id(), backup::LOG_WARNING);
        }
    }
}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watch_video_config_form_extender implements extender, extender_with_supporting_url_modes {

    protected $context;
    protected $coursecontext;
    protected $courseid;
    protected $incourse;
    protected $mission;
    protected $whichoptions;

    /**
     * Constructor.
     *
     * @param mission $mission The mission.
     */
    public function __construct(mission $mission) {
        $this->context = $mission->get_context();
        $this->mission = $mission;

        $coursecontext = $this->context->get_course_context(false);
        $isfrontpage = ($coursecontext && $coursecontext->instanceid == SITEID);
        $this->incourse = $coursecontext && !$isfrontpage;
        $this->coursecontext = $this->incourse ? $coursecontext : null;
        $this->courseid = $this->incourse ? $this->coursecontext->instanceid : null;
    }

    public function definition($mform): array {
        $els = [];

        if ($this->incourse) {
            $modinfo = course_utils::get_modinfo($this->courseid);
            $sections = $modinfo ? $modinfo->get_sections() : [];

            $options = [get_string('none', 'core') => [0 => get_string('none', 'core')]];
            foreach ($sections as $sectionnum => $cmids) {
                $modules = [];
                foreach ($cmids as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if (!in_array($cm->modname, ['page', 'url', 'resource'])) {
                        continue;
                    }
                    $modules[$cm->id] = format_string($cm->name, \context_module::instance($cm->id));
                }
                $options['#' . $sectionnum . ': ' . get_section_name($this->courseid, $sectionnum)] = $modules;
            }
            $els[] = $mform->addElement('selectgroups', 'cd_cmid', get_string('videorestricttoactivity', 'block_gearup'), $options);
            $mform->addHelpButton('cd_cmid', 'videorestricttoactivity', 'block_gearup');

            $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldEquals', [
                $mform->getAttribute('id'),
                'supportingurlmode',
                [watch_video::URLMODE_CM],
                'cd_cmid',
                [0],
            ]);
        }

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function get_supporting_url_modes(): array {
        if (!$this->incourse) {
            return [];
        }
        return [
            watch_video::URLMODE_CM => get_string('activitypage', 'block_gearup'),
        ];
    }

    public function validation($data, $files) {
    }

}
