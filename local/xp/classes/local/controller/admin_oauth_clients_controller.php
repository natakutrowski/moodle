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
use block_xp\local\controller\admin_route_controller;
use context_system;
use core_reportbuilder\system_report_factory;
use local_xp\local\model\oauth_client;
use local_xp\local\report\oauth_clients;

/**
 * OAuth clients.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_oauth_clients_controller extends admin_route_controller {
    /** @var string The section name. */
    protected $sectionname = 'block_xp_admin_oauth_clients';

    /**
     * Define optional parameters.
     *
     * @return array
     */
    protected function define_optional_params() {
        return [
            ['action', null, PARAM_ALPHANUMEXT, false],
            ['id', null, PARAM_INT, false],
        ];
    }

    /**
     * Process client actions before output.
     *
     * @return void
     */
    protected function pre_content() {
        parent::pre_content();

        $action = $this->get_param('action');
        if ($action) {
            require_sesskey();
            $client = oauth_client::get_record(['id' => $this->get_param('id')], MUST_EXIST);
            if ($action === 'enable') {
                if ($client->get('isvalid')) {
                    $client->set('isenabled', true);
                    $client->save();
                }
            } else if ($action === 'disable') {
                $client->set('isenabled', false);
                $client->save();
            } else if ($action === 'revoke') {
                di::get('oauth_service')->revoke_all_client_grants($client->get_client_id());
            } else if ($action === 'delete') {
                $client->delete();
            }

            $this->redirect(null);
        }
    }

    /**
     * Output the OAuth clients.
     *
     * @return void
     */
    protected function content() {
        global $PAGE;

        $renderer = di::get('renderer');
        echo $renderer->advanced_heading(get_string('oauthclients', 'block_xp'), [
            'intro' => get_string('oauthclients_desc', 'block_xp'),
            'level' => 2,
            'actions' => [
                new action_link($this->pageurl, get_string('addclient', 'block_xp'), null, [
                    'class' => 'btn btn-secondary btn-default',
                    'data-xp-action' => 'open-form',
                    'data-form-class' => 'local_xp\\form\\admin\\oauth_client',
                    'data-modal-title' => get_string('addclient', 'block_xp'),
                ]),
            ],
        ]);

        if (!\block_xp\di::get('config')->get('enablemcp')) {
            echo $renderer->notification_without_close(get_string('mcpdisabledadminnotice', 'block_xp'), 'info');
        }

        $report = system_report_factory::create(oauth_clients::class, context_system::instance());
        echo $report->output();

        $PAGE->requires->js_amd_inline(<<<EOT
            require(['core/str', 'core/templates', 'core/notification', 'block_xp/modal', 'block_xp/role-button',
                    'block_xp/compat', 'block_xp/utils'], function(Str, Templates, Notification, Modal, RoleButton, Compat, Utils) {
                RoleButton.delegateClick('body', '[data-xp-action="open-oauthclient"][data-id]', function(node) {
                    const modal = Modal.createInfoModal({
                        body: Utils.ws('local_xp_get_oauth_client', {id: node.dataset.id}).then(function(data) {
                            return Templates.render('local_xp/admin/oauth-client', data);
                        }).catch(Notification.exception),
                        large: true,
                        title: Str.get_string('oauthclient', 'block_xp'),
                    });
                    modal.then(function(modal) {
                        modal.show();
                    }).catch(Notification.exception);
                });
            });
        EOT);
    }
}
