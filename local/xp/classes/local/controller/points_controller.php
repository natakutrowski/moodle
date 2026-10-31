<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\controller;

use action_link;
use block_xp\di;
use block_xp\local\controller\page_controller;
use core_reportbuilder\system_report_factory;
use local_xp\local\report\source_courses;
use local_xp\local\world\global_world;
use moodle_url;

/**
 * Points page controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class points_controller extends page_controller {
    /** @var string The route name. */
    protected $routename = 'points';

    /**
     * Define action parameters.
     *
     * @return array
     */
    protected function define_optional_params() {
        return [
            ['action', null, PARAM_ALPHA, false],
            ['id', null, PARAM_INT, false],
        ];
    }

    /**
     * Check permissions.
     *
     * @return void
     */
    protected function permissions_checks() {
        parent::permissions_checks();
        if (!$this->world instanceof global_world) {
            throw new \moodle_exception('errorfeaturenotenabled', 'block_xp');
        }
    }

    /**
     * Process removals before output.
     */
    protected function pre_content() {
        parent::pre_content();
        $action = $this->get_param('action');
        if ($action === 'remove' || $action === 'removeall') {
            require_sesskey();
            $conditions = ['contextid' => $this->world->get_context()->id];
            if ($action === 'remove') {
                $conditions['id'] = $this->get_param('id');
            }
            di::get('db')->delete_records('local_xp_source', $conditions);
            $this->redirect();
        }
    }

    /**
     * Get the browser page title.
     *
     * @return string
     */
    protected function get_page_html_head_title() {
        return get_string('points', 'block_xp');
    }

    /**
     * Get the page heading.
     *
     * @return string
     */
    protected function get_page_heading() {
        return get_string('globalpoints', 'block_xp');
    }

    /**
     * Output page content.
     *
     * @return void
     */
    protected function page_content() {
        global $PAGE;

        $output = $this->get_renderer();

        $siteid = SITEID;
        $sourcecontextids = di::get('db')->get_fieldset_select(
            'local_xp_source',
            'sourcecontextid',
            'contextid = ?',
            [$this->world->get_context()->id]
        );
        $sourcecontextidsjson = json_encode(array_map('intval', $sourcecontextids));
        $PAGE->requires->js_amd_inline(<<<EOT
            require(['block_xp/role-button', 'local_xp/source-course-selector'], function(RoleButton, SourceSelector) {
                RoleButton.delegateClick('body', '[data-xp-action="add-source"]', function(e) {
                    SourceSelector.openSelector({
                        contextId: {$this->world->get_context()->id},
                        siteId: $siteid,
                        sourceContextIds: $sourcecontextidsjson,
                    });
                });
            });
        EOT);

        echo $output->advanced_heading($this->get_page_heading(), [
            'intro' => new \lang_string('globalpointsintro', 'block_xp'),
            'menu' => [
                [
                    'label' => get_string('addcourses', 'block_xp'),
                    'href' => '#',
                    'data-xp-action' => 'add-source',
                ],
                [],
                [
                    'label' => get_string('removeall', 'block_xp'),
                    'href' => new moodle_url($this->pageurl->get_compatible_url(), [
                        'action' => 'removeall', 'sesskey' => sesskey(),
                    ]),
                    'danger' => true,
                    'data-confirmation' => 'modal',
                    'data-confirmation-type' => 'delete',
                    'data-confirmation-title-str' => json_encode(['confirm', 'core']),
                    'data-confirmation-content-str' => json_encode(['sourceremoveallconfirm', 'block_xp']),
                    'data-confirmation-yes-button-str' => json_encode(['removeall', 'block_xp']),
                ],
            ],
        ]);

        if (!$sourcecontextids) {
            echo $output->render_from_template('local_xp/source-courses-empty', []);
            return;
        }

        $report = system_report_factory::create(source_courses::class, $this->world->get_context());
        echo $report->output();
    }
}
