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


namespace block_gearup\output;

use block_gearup\di;
use block_gearup\external\external_single_structure;
use block_gearup\external\external_value;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\routing\url_resolver;
use core\output\named_templatable;
use renderable;
use renderer_base;
use moodle_url;

/**
 * Tracker.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instance_page implements renderable, named_templatable {

    /** @var mission_instance The instance. */
    protected $missioninst;
    /** @var string */
    protected $returnto = 'list';
    /** @var helper The mission helper. */
    protected $mh;
    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param mission_instance $missioninst The mission instance.
     */
    public function __construct(mission_instance $missioninst, url_resolver $urlresolver) {
        $this->mh = di::get('mission_helper');
        $this->missioninst = $missioninst;
        $this->urlresolver = $urlresolver;
        $this->returnto = 'list';
    }

    /**
     * Set return to.
     *
     * @param string|null $returnto
     * @return self
     */
    public function set_return_to(?string $returnto) {
        $this->returnto = $returnto;
        return $this;
    }

    public function export_for_template(renderer_base $output) {
        $missioninst = $this->missioninst;
        $urlresolver = $this->urlresolver;

        $baseurl = $urlresolver->reverse('mission_instance', ['missionid' => $missioninst->get_mission()->get_id(),
            'missioninstid' => $missioninst->get_id()]);

        $exporter = di::get('exporter_factory')->get_mission_instance_exporter($missioninst);
        $starturl = new moodle_url($baseurl, ['action' => 'startmission', 'sesskey' => sesskey()]);

        $listurl = $urlresolver->reverse('mission_users', ['missionid' => $missioninst->get_mission()->get_id()]);
        if ($this->returnto === 'user') {
            $listurl = $urlresolver->reverse('mission_user', ['missionid' => $missioninst->get_mission()->get_id(),
                'userid' => $missioninst->get_subject_id()]);
        }

        $data = [
            'returnto' => $this->returnto,
            'isreturntouser' => $this->returnto === 'user',
            'isreturntouserslist' => $this->returnto !== 'user',
            'gupagectxid' => $baseurl->param('gupagectxid') ?: 0,
            'listurl' => $listurl->out(false),
            'startmissionurl' => $starturl->out(false),
        ];

        $missioninstdata = (array) $exporter->export($output);
        $missionhelper = di::get('mission_helper');
        $iseditable = $missionhelper->is_active($missioninst);

        $canreset = $iseditable && !($missionhelper->is_repeating($missioninst) && $missionhelper->is_ended($missioninst));
        $candelete = $iseditable;

        $menu = new menu(array_filter([
            $canreset ? [
                'label' => get_string('reset', 'core'),
                'href' => '#',
                'danger' => true,
                'data-gu-action' => 'open-form',
                'data-form-class' => 'block_gearup\form\reset_mission_instance_dynamic_form',
                'data-form-args__missionid' => $missioninst->get_mission()->get_id(),
                'data-form-args__missioninstid' => $missioninst->get_id(),
                'data-modal-buttons__save__danger' => "true",
                'data-modal-buttons__save__label' => get_string('reset', 'core'),
                'data-modal-title' => $missioninstdata['subject']->name,
                'data-modal-large' => 'false',
            ] : null,
            $candelete ? [
                'label' => get_string('delete', 'core'),
                'href' => '#',
                'danger' => true,
                'data-gu-action' => 'open-form',
                'data-form-class' => 'block_gearup\form\delete_mission_instance_dynamic_form',
                'data-form-args__missionid' => $missioninst->get_mission()->get_id(),
                'data-form-args__missioninstid' => $missioninst->get_id(),
                'data-form-args__redirecturl' => $listurl->out_as_local_url(false),
                'data-modal-buttons__save__danger' => "true",
                'data-modal-buttons__save__label' => get_string('delete', 'core'),
                'data-modal-title' => $missioninstdata['subject']->name,
                'data-modal-large' => 'false',
            ] : null,
        ]));

        return $data + [
            'menu' => $menu->export_for_template($output),
            'missioninst' => $missioninstdata,
        ];
    }

    public function get_template_name(renderer_base $renderer): string {
        if ($this->mh->is_a_streak($this->missioninst)) {
            return 'block_gearup/streak/instance';
        }
        return 'block_gearup/mission_instance';
    }

    public static function get_read_structure() {
        $exporterclass = di::get('exporter_factory')->get_mission_instance_exporter_class();
        return new external_single_structure([
            'returnto' => new external_value(PARAM_ALPHANUMEXT, '', VALUE_OPTIONAL, null),
            'isreturntouser' => new external_value(PARAM_BOOL, ''),
            'isreturntouserslist' => new external_value(PARAM_BOOL, ''),
            'gupagectxid' => new external_value(PARAM_INT, ''),
            'listurl' => new external_value(PARAM_URL, ''),
            'startmissionurl' => new external_value(PARAM_URL, ''),
            'menu' => menu::get_read_structure(),
            'missioninst' => $exporterclass::get_read_structure(),
        ]);
    }

}
