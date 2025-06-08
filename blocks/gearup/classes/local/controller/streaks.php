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

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\mission\streak;
use block_gearup\local\repository\mission_query;
use block_gearup\table\streak\listing;
use context;

/**
 * Streaks controller.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class streaks extends route_base {

    protected function get_page_html_head_title() {
        return get_string('streaks', 'block_gearup');
    }

    protected function post_login() {
        parent::post_login();
        if (!$this->lm->use_streaks()) {
            return redirect($this->urlresolver->reverse('missions'));
        }
    }

    protected function pre_content() {
        parent::pre_content();
    }

    protected function content() {
        $repository = di::get('repository');
        $output = $this->get_renderer();
        $createurl = $this->urlresolver->reverse('mission_create', ['type' => 'streak'])->out(false);

        $query = static::prepare_query($this->context)
            ->add_order_by('timecreated', SORT_DESC);
        $count = $repository->count_missions_from_query($query);

        if (!$count) {
            echo $output->navigation_for_management($this->urlresolver, 'streaks');
            echo $output->render_from_template('block_gearup/streaks_empty', [
                'createurl' => $createurl,
            ]);
            return;
        }

        echo $output->navigation_for_management($this->urlresolver, 'streaks');
        echo $output->advanced_heading('', [
            'intro' => new \lang_string('streaksexplanation', 'block_gearup'),
            'actions' => [],
            'menu' => array_filter([
                [
                    'href' => $createurl,
                    'label' => get_string('addanother', 'block_gearup'),
                ],
                missions::get_embed_tracker_menu_entry($this->context),
            ]),
        ]);

        $table = (new listing())
            ->define_baseurl($this->pageurl)
            ->define_url_resolver($this->urlresolver)
            ->define_query($query);
        $table->out(20);
    }

    /**
     * Prepare the query.
     *
     * @param context $context
     * @return mission_query
     */
    protected static function prepare_query(context $context) {
        return (new mission_query($context))
            ->set_context_id($context->id)
            ->set_type(streak::class);
    }
}
