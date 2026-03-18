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
 * Missions controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\controller;

use block_gearup\di;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\quest;
use block_gearup\local\repository\mission_query;
use block_gearup\local\shortcodes\shortcodes;
use block_gearup\output\template;
use cache_helper;
use context;
use context_system;
use moodle_url;

/**
 * Missions controller.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class missions extends route_base {

    protected function define_optional_params() {
        return [
            ['action', null, PARAM_ALPHANUMEXT, false],
        ];
    }

    protected function get_page_html_head_title() {
        return get_string('overview', 'block_gearup');
    }

    protected function pre_content() {
        parent::pre_content();

        if ($this->get_param('action') === 'purge' && confirm_sesskey()) {
            cache_helper::purge_by_definition('block_gearup', 'metadata');
            $fs = get_file_storage();
            $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');

            return $this->redirect(null, get_string('cachepurged', 'block_gearup'));
        }
    }

    protected function content() {
        global $PAGE;

        $repository = di::get('repository');
        $output = $this->get_renderer();

        $createquesturl = $this->urlresolver->reverse('mission_create', ['type' => 'quest'])->out(false);
        $createachievementurl = $this->urlresolver->reverse('mission_create', ['type' => 'achievement'])->out(false);

        $createchallengeurl = null;
        if ($this->lm->use_challenges()) {
            $createchallengeurl = $this->urlresolver->reverse('mission_create', ['type' => 'challenge'])->out(false);
        }

        $query = (new mission_query($this->context))
            ->set_context_id($this->context->id)
            ->filter_types(array_filter([
                quest::class,
                achievement::class,
                $this->lm->use_challenges() ? challenge::class : null,
            ]))
            ->add_order_by('title');

        if (!$repository->count_missions_from_query($query)) {
            echo $output->navigation_for_management($this->urlresolver, 'missions');
            echo $output->render_from_template('block_gearup/missions_empty', [
                'createquesturl' => $createquesturl,
                'createachievementurl' => $createachievementurl,
                'createchallengeurl' => $createchallengeurl,
                'purgeurl' => (new moodle_url($this->pageurl, ['action' => 'purge', 'sesskey' => sesskey()]))->out(false),
            ]);
            return;
        }

        $searchresult = iterator_to_array($repository->get_missions_from_query($query));
        $ef = di::get('exporter_factory');

        echo $output->navigation_for_management($this->urlresolver, 'missions');
        echo $output->advanced_heading('', [
            'intro' => new \lang_string('missionsexplaination', 'block_gearup'),
            'actions' => [new template('block_gearup/create_mission_button', [
                'createquesturl' => $createquesturl,
                'createachievementurl' => $createachievementurl,
                'createchallengeurl' => $createchallengeurl,
            ]), ],
            'menu' => array_filter([
                static::get_embed_tracker_menu_entry($this->context),
            ]),
        ]);
        echo $output->render_from_template('block_gearup/missions_page', [
            'tablehtml' => '',
            'purgeurl' => (new moodle_url($this->pageurl, ['action' => 'purge', 'sesskey' => sesskey()]))->out(false),
        ]);
        echo $output->react_module('block_gearup/react-missions-lazy', [
            'missions' => array_values(array_map(function ($result) use ($output, $ef) {
                $exporter = $ef->get_mission_exporter($result->mission, ['url_resolver' => $this->urlresolver]);
                return $exporter->export($output);
            }, $searchresult)),
            'createachievementurl' => $createachievementurl,
            'createchallengeurl' => $createchallengeurl,
            'createquesturl' => $createquesturl,
            'usechallenges' => $this->lm->use_challenges(),
        ]);
    }

    /**
     * Get embed tracker menu entry.
     *
     * @param context $context
     * @return array
     */
    public static function get_embed_tracker_menu_entry(context $context) {
        global $PAGE;

        $output = di::get('renderer');

        $trackerrequired = new plugin_required_info('filter_shortcodes', 'Shortcodes');
        $trackershortcode = '[questtracker ctx=' . $context->id . ' secret='
                . substr(shortcodes::get_tracker_secret($context), 0, 7) . ']';

        $PAGE->requires->js_call_amd('block_gearup/modal', 'registerSimpleOpenModalActionObserver');

        return [
            'label' => get_string('embedtracker', 'block_gearup'),
            'href' => '#',
            'data-gu-action' => 'open-modal',
            'data-modal-title' => get_string('embedtracker', 'block_gearup'),
            'data-template' => 'block_gearup/shortcode_tracker_embed',
            'data-template-context__isavailable' => $trackerrequired->is_available() ? 1 : '',
            'data-template-context__pluginrequiredformatted' =>
                markdown_to_html(get_string('pluginshortcodesrequiredtousefeature', 'block_gearup')),
            'data-template-context__snippet' => $trackershortcode,
            'data-template-context__helpformatted' => $output->help_icon('shortcodes', 'block_gearup'),
        ];
    }

}
