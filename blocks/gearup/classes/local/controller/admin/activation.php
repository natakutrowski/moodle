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

namespace block_gearup\local\controller\admin;

use block_gearup\di;
use block_gearup\local\controller\utils\route_base;
use block_gearup\local\http\api_exception;
use block_gearup\local\http\client_exception;
use cache_helper;
use context_system;
use core\notification;

/**
 * Controller.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activation extends route_base {

    protected $requiremanage = false;

    protected function get_page_html_head_title() {
        return get_string('activation', 'block_gearup');
    }

    protected function pre_content() {
        parent::pre_content();
        global $CFG;

        $lm = di::get('lm');
        if ($lm->is_activated()) {
            return $this->redirect($this->urlresolver->reverse('missions'));
        }

        $key = optional_param('activation-key', null, PARAM_ALPHANUMEXT);
        if ($this->request->get_method() === 'POST' && $key) {
            require_sesskey();
            $this->accessperms->require_manage_licence();

            $activationsecretkey = hash('sha256', random_bytes(128));
            set_config('activationsecret', (string) time() . ':' . $activationsecretkey, 'block_gearup');

            $client = di::get('api_client');
            $message = null;
            $messagelevel = notification::ERROR;

            try {
                $resp = $client->post('/api/v1/quest/activate', [
                    'siteidentifier' => $CFG->siteidentifier,
                    'wwwroot' => $CFG->wwwroot,
                    'licence_key' => $key,
                    'activation_secret' => $activationsecretkey,
                ]);
                if ($resp->http_code === 200) {
                    $lm->process_payload($resp->data->payload);

                    cache_helper::purge_by_definition('block_gearup', 'metadata');
                    $fs = get_file_storage();
                    $fs->delete_area_files(context_system::instance()->id, 'block_gearup', 'metadata');
                }
                $message = get_string('licenceactivated', 'block_gearup');
                $messagelevel = notification::SUCCESS;
            } catch (api_exception $e) {
                $message = get_string('activationerror', 'block_gearup',
                    $e->get_error_message() . ' (' . $e->get_error_code() . ')');
            } catch (client_exception $e) {
                $message = get_string('activationerror', 'block_gearup',
                    $e->getMessage() . ' (' . $e->get_http_code() . ')');
            } catch (\moodle_exception $e) {
                $message = get_string('activationerror', 'block_gearup', $e->getMessage() . ' (' . $e->errorcode . ')');
            }

            if ($message) {
                notification::add($message, $messagelevel);
            }

            unset_config('activationsecret', 'block_gearup');
            $this->redirect($this->pageurl);
        }
    }

    protected function content() {
        $output = $this->get_renderer();
        $templatename = 'block_gearup/activation-readonly';
        if ($this->accessperms->can_manage_licence()) {
            $templatename = 'block_gearup/activation';
        }

        echo $output->render_from_template($templatename, [
            'formurl' => $this->pageurl->out(false),
            'noteshtml' => markdown_to_html(get_string('pluginactivationnote', 'block_gearup')),
            'sesskey' => sesskey(),
        ]);
    }

}
