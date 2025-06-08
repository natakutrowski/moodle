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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\repository\user_query;
use block_gearup\table\users as users_table;
use block_gearup\table\users_filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users extends route_base {

    /** @var object The repository. */
    protected $repository;
    protected $supportsgroups = true;

    protected function define_optional_params() {
        return [
            ['term', null, PARAM_RAW],
        ];
    }

    protected function get_page_html_head_title() {
        return get_string('recruits', 'block_gearup');
    }

    protected function post_login() {
        parent::post_login();
        $this->repository = di::get('repository');
    }

    protected function pre_content() {
        parent::pre_content();
        require_capability('moodle/user:viewdetails', $this->context);
    }

    protected function content() {
        global $PAGE;
        $output = $this->get_renderer();

        $filterset = (new users_filterset());
        if ($term = $this->get_param('term')) {
            $filterset->add_filter(new string_filter('term', null, [$term]));
        }

        if ($this->is_page_using_groups()) {
            if ($groupid = $this->get_group_id()) {
                $filterset->add_filter(new integer_filter('groupid', null, [$groupid]));
            }
        }

        $query = (new user_query($this->context))->set_context_id($this->context->id);
        $totalrecruits = $this->repository->count_users_from_query($query);

        echo $output->navigation_for_management($this->urlresolver, 'users');

        echo $output->advanced_heading('', [
            'intro' => new \lang_string('recruitsexplaination', 'block_gearup'),
            'menu' => $totalrecruits > 0 ? array_filter([
                [
                    'label' => get_string('deleteall', 'core'),
                    'danger' => true,
                    'data-action' => 'open-form',
                    'data-form-class' => 'block_gearup\form\delete_context_recruits_dynamic_form',
                    'data-form-args__guctxid' => $this->context->id,
                    'data-modal-buttons__save__label' => get_string('deleteall', 'core'),
                    'data-modal-buttons__save__danger' => 'true',
                    'data-modal-large' => 'false',
                    'data-modal-title' => get_string('deleteallrecruits', 'block_gearup'),
                    'disabled' => $filterset->has_any(),
                    'href' => '#',
                ],
            ]) : [],
        ]);

        $table = (new users_table())
            ->define_context($this->context)
            ->define_repository($this->repository)
            ->define_url_resolver($this->urlresolver)
            ->init();
        $table->set_filterset($filterset);
        $table->define_baseurl($this->pageurl);

        ob_start();
        $table->out(20, true);
        $tablehtml = ob_get_contents();
        ob_end_clean();

        echo $tablehtml;

        $PAGE->requires->js_call_amd('block_gearup/modal_form', 'registerOpen', ['[data-action="open-form"]']);
    }

}
