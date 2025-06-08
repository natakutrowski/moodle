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
use block_gearup\di;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use block_gearup\local\outcome\persisted_outcome;
use curl;
use html_writer;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook implements type, type_with_update_after_restore {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $config = $outcome->get_type_config();
        if (empty($config->url) || strpos($config->url, 'http') !== 0) {
            return;
        }

        $data = [
            'type' => 'outcome.webhook',
            'payload' => [
                'missionid' => $missioninst->get_mission()->get_id(),
                'missionname' => $missioninst->get_mission()->get_title(),
                'missioninstid' => $missioninst->get_id(),
                'secret' => $missioninst->get_mission()->get_secret(),
                // We don't name it subjectid for future backwards compatibility.
                'userid' => $missioninst->get_subject_id(),
            ],
        ];

        $curl = new curl();
        $curl->setHeader('Content-Type: application/json; charset=utf-8');
        $curl->setopt(['CURLOPT_TIMEOUT' => 10]);
        $curl->post(new moodle_url($config->url), json_encode($data));
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new webhook_config_form_extender($mission);
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomewebhook', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomewebhookdesc', 'block_gearup');
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        if ($restore->is_same_site()) {
            return;
        }

        try {
            $config->url = null; // Remove the URL when restoring on another site.
            $outcome->get_persistent()->set('configdata', $config);
            $outcome->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating outcome " . $outcome->get_id(), backup::LOG_WARNING);
        }
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
class webhook_config_form_extender implements extender {

    protected $mission;

    public function __construct(mission $mission) {
        $this->mission = $mission;
    }

    public function definition($mform): array {
        $renderer = di::get('renderer');

        $sampledata = [
            'type' => 'outcome.webhook',
            'payload' => [
                'missionid' => $this->mission->get_id(),
                'missionname' => $this->mission->get_title(),
                'missioninstid' => -9999,
                'secret' => $this->mission->get_secret(),
                'userid' => -9999,
            ],
        ];

        $els[] = $mform->addElement('text', 'cd_url', get_string('outcomewebhookurl', 'block_gearup'), ['size' => 60]);
        $mform->setType('cd_url', PARAM_URL);
        $mform->addRule('cd_url', get_string('err_required', 'core_form'), 'required', null, 'client');

        $els[] = $mform->addElement('static', 'secret', get_string('outcomewebhooksecret', 'block_gearup'),
            html_writer::tag('code', $this->mission->get_secret()));
        $mform->addHelpButton('secret', 'outcomewebhooksecret', 'block_gearup');

        $els[] = $mform->addElement('static', 'details', '', $renderer->code_instructions(markdown_to_html(
            get_string('outcomewebhookinstructions', 'block_gearup', [
                'payload' => str_replace('-9999', '<strong>123</strong>', s(json_encode($sampledata, JSON_PRETTY_PRINT))),
            ])
        )));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        return $errors;
    }

}
