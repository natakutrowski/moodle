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
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\mission_route_base;
use block_gearup\local\repository\mission_instance_query;
use block_gearup\local\repository\user_query;
use block_gearup\local\routing\url;
use block_gearup\output\template;
use block_gearup\table\mission_instances;
use block_gearup\table\mission_instances_filterset;
use block_gearup\table\mission_users_filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_users extends mission_route_base {

    /** @var filterset */
    protected $filterset;
    /** @var object */
    protected $table;
    /** @var string */
    protected $missionnavname = 'mission_users';
    /** @var bool */
    protected $supportsgroups = true;

    protected function define_optional_params() {
        return [
            ['term', null, PARAM_NOTAGS, true],
            ['status', null, PARAM_ALPHANUMEXT, true],

            ['action', false, PARAM_ALPHANUMEXT, false],
            ['download', null, PARAM_ALPHANUMEXT, false],
            ['downloadfilename', null, PARAM_NOTAGS, false],
            ['missioninstid', 0, PARAM_INT, false],
        ];
    }

    protected function can_export(): bool {
        return $this->lm->use_export_recruits();
    }

    protected function get_subpage_html_head_title() {
        return get_string('recruits', 'block_gearup');
    }

    protected function get_page_wide_user_preference_name() {
        return 'block_gearup_mission_instances_wideview_' . $this->missionhelper->get_type($this->mission);
    }

    protected function get_download_filename() {
        $groupidstr = $this->get_group_id() ? '-' . $this->get_group_id() : '';
        return $this->get_param('downloadfilename') ?: 'mission-' . $this->mission->get_id()
            . $groupidstr . '-report-' . date('Y-m-d');
    }

    protected function get_recruits_count() {
        $repository = di::get('repository');
        $query = (new user_query($this->context))
            ->set_context_id($this->context->id)
            ->set_group_id($this->get_group_id())
            ->set_mission_id($this->mission->get_id());
        return $repository->count_users_from_query($query);
    }

    protected function is_page_wide() {
        return (bool) get_user_preferences($this->get_page_wide_user_preference_name(), false);
    }

    protected function pre_content() {
        parent::pre_content();

        if ($this->get_param('action') === 'togglewideview') {
            if ($this->is_page_wide()) {
                unset_user_preference($this->get_page_wide_user_preference_name());
            } else {
                set_user_preference($this->get_page_wide_user_preference_name(), 1);
            }
            $this->redirect();
        }

        $table = $this->get_table();
        if ($table->is_downloading() && $this->can_export()) {
            $table->send_file();
        }
    }

    protected function get_table() {
        if (!$this->table) {
            $repository = di::get('repository');
            if ($this->is_challenge() && !$this->get_param('download')) {
                $table = (new \block_gearup\table\mission_users())
                    ->define_mission($this->mission)
                    ->define_repository($repository)
                    ->define_url_resolver($this->urlresolver)
                    ->define_mission_helper($this->missionhelper)
                    ->define_has_dropdown(true)
                    ->define_wideview($this->is_page_wide())
                    ->define_query(new user_query($this->context))
                    ->init();
                $filterset = new mission_users_filterset();
                if ($term = $this->get_param('term')) {
                    $filterset->add_filter(new string_filter('term', null, [$term]));
                }
                if ($groupid = $this->get_group_id()) {
                    $filterset->add_filter(new integer_filter('groupid', null, [$groupid]));
                }
                $table->set_filterset($filterset);

            } else if ($this->is_streak() && !$this->get_param('download')) {
                $table = (new \block_gearup\table\streak\users())
                    ->define_mission($this->mission)
                    ->define_repository($repository)
                    ->define_url_resolver($this->urlresolver)
                    ->define_mission_helper($this->missionhelper)
                    ->define_wideview($this->is_page_wide())
                    ->define_query(new user_query($this->context))
                    ->init();
                $filterset = new mission_users_filterset();
                if ($term = $this->get_param('term')) {
                    $filterset->add_filter(new string_filter('term', null, [$term]));
                }
                if ($groupid = $this->get_group_id()) {
                    $filterset->add_filter(new integer_filter('groupid', null, [$groupid]));
                }
                $table->set_filterset($filterset);

            } else {
                $query = new mission_instance_query($this->context);
                $table = (new mission_instances())
                    ->define_mission($this->mission)
                    ->define_repository($repository)
                    ->define_url_resolver($this->urlresolver)
                    ->define_mission_helper($this->missionhelper)
                    ->define_has_dropdown(true)
                    ->define_wideview($this->is_page_wide())
                    ->define_query($query);
                if ($this->can_export()) {
                    $table->define_download_format($this->get_param('download'));
                    $table->define_download_filename($this->get_download_filename());
                }
                $table->init();

                $filterset = new mission_instances_filterset();
                if ($groupid = $this->get_group_id()) {
                    $filterset->add_filter(new integer_filter('groupid', null, [$groupid]));
                }
                if (!$table->is_downloading()) {
                    if ($term = $this->get_param('term')) {
                        $filterset->add_filter(new string_filter('subject:term', null, [$term]));
                    }
                    if ($status = $this->get_param('status')) {
                        $filterset->add_filter(new string_filter('status', null, [$status]));
                    }
                }
                $table->set_filterset($filterset);

            }
            $table->define_baseurl($this->pageurl);
            $this->table = $table;
        }
        return $this->table;
    }

    protected function content() {
        $this->page_mission_header();
        $this->page_mission_navigation();
        $this->page_advanced_heading();

        $table = $this->get_table();
        $table->out(20);
    }

    protected function page_advanced_heading() {
        global $PAGE;
        $output = $this->get_renderer();

        $togglewideviewurl = new url($this->pageurl, ['action' => 'togglewideview']);
        $totalrecruits = $this->get_recruits_count();

        $filterset = $this->get_table()->get_filterset();
        $hasfilters = $filterset ? $filterset->has_any() : false;
        $hasgroup = (bool) $this->get_group_id();
        $iseditable = $this->missionhelper->is_active($this->mission);

        $intro = new \lang_string('recruitsexplaination', 'block_gearup');
        if ($this->is_streak()) {
            $intro = new \lang_string('recruitsexplanationstreak', 'block_gearup');
        }

        echo $output->advanced_heading('', [
            'intro' => $intro,
            'actions' => array_filter([
                $iseditable ? new template('block_gearup/recruit_users_button', ['missionid' => $this->mission->get_id()]) : null,
            ]),
            'menu' => $totalrecruits > 0 ? array_filter([
                [
                    'label' => get_string($this->is_page_wide() ? 'normalview' : 'wideview', 'block_gearup'),
                    'href' => $togglewideviewurl,
                ],
                $this->can_export() ? [
                    'label' => $hasgroup ? get_string('exportallingroup', 'block_gearup') : get_string('exportall', 'block_gearup'),
                    'data-action' => 'open-form',
                    'data-form-class' => 'block_gearup\form\table_download_dynamic_form',
                    'data-form-args__pageurl' => $this->get_page_url_for_actions()->out_as_local_url(false),
                    'data-form-args__filename' => $this->get_download_filename(),
                    'data-form-args__guctxid' => $this->context->id,
                    'data-modal-buttons__save__label' => get_string('download', 'core'),
                    'data-modal-title' => get_string('exportall', 'block_gearup'),
                    'href' => '#',
                ] : null,
                ['divider'],
                $iseditable ? [
                    'label' => get_string('deleteall', 'core'),
                    'danger' => true,
                    'data-action' => 'open-form',
                    'data-form-class' => 'block_gearup\form\delete_mission_recruits_dynamic_form',
                    'data-form-args__id' => $this->mission->get_id(),
                    'data-modal-buttons__save__label' => get_string('delete', 'core'),
                    'data-modal-buttons__save__danger' => 'true',
                    'data-modal-title' => get_string('deleteallrecruits', 'block_gearup'),
                    'disabled' => $hasfilters,
                    'href' => '#',
                ] : null,
            ]) : [],
        ]);
        $PAGE->requires->js_call_amd('block_gearup/modal_form', 'registerOpen', ['[data-action="open-form"]']);
    }

}
