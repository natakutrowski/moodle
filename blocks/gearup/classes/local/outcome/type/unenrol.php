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
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use backup;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\availability\static_info;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use block_gearup\local\outcome\persisted_outcome;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unenrol implements type, type_with_update_after_restore, has_availability_info {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();

        // Bad config following a restore, probably.
        if (!$config->courseid) {
            return;
        }

        $enrol = enrol_get_plugin('manual');
        $instance = static::get_instance_in_course($config->courseid);
        if (!$enrol || !$instance) {
            return;
        }

        $enrol->unenrol_user($instance, $userid);
    }

    public function get_availability_info(): info {
        global $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $enrol = enrol_get_plugin('manual');
        return new static_info((bool) $enrol, [new lang_string('pluginnotenabled', 'block_gearup', [
            'name' => 'Manual enrolments',
            'component' => 'enrol_manual',
        ])]);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new unenrol_config_form_extender($mission);
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeunenrol', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeunenroldesc', 'block_gearup');
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        // We do not do anything if it's the same site.
        if ($restore->is_same_site()) {
            return;
        }

        $config = $outcome->get_type_config();

        try {
            $config->courseid = 0; // Invalidate as we don't know which course it would be.
            $outcome->get_persistent()->set('configdata', $config);
            $outcome->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating outcome " . $outcome->get_id(), backup::LOG_WARNING);
        }
    }

    /**
     * Get the manual enrolment instance in the course.
     *
     * @param int $courseid The course ID.
     * @return \stdClass|null
     */
    public static function get_instance_in_course($courseid) {
        global $CFG;
        require_once($CFG->libdir . '/enrollib.php');

        $instance = null;
        $enrolinstances = enrol_get_instances($courseid, true);
        foreach ($enrolinstances as $candidate) {
            if ($candidate->enrol == "manual") {
                $instance = $candidate;
                break;
            }
        }
        return $instance;
    }
}

/**
 * Form extender.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unenrol_config_form_extender implements extender {

    protected $context;
    protected $mission;
    protected $repository;

    public function __construct(mission $mission) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
    }

    public function definition($mform): array {
        $els[] = $mform->addElement('course', 'cd_courseid', get_string('course', 'core'), [
            'requiredcapabilities' => ['enrol/manual:enrol'],
            'multiple' => false,
            'includefrontpage' => false,
            'limitedtoenrolled' => false,
        ]);
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_courseid)) {
            $errors['cd_courseid'] = get_string('err_required', 'core_form');
        } else if (!enrol::get_instance_in_course($data->cd_courseid)) {
            $errors['cd_courseid'] = get_string('manualenrolinstanceneeded', 'block_gearup');
        }

        return $errors;
    }

}
