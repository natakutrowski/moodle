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

namespace local_xp\local\controller\oauth;

use block_xp\di;
use block_xp\local\routing\request;
use block_xp\local\routing\url;
use context_system;
use html_writer;
use local_xp\local\model\oauth_client;
use local_xp\local\oauth\oauth_exception;
use moodle_url;
use single_button;

/**
 * OAuth authorization controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class authorize_controller extends base_controller {
    /**
     * Process the authorization request.
     *
     * @param request $request The request.
     * @return void
     */
    public function handle(request $request) {
        parent::handle($request);
        global $OUTPUT, $PAGE, $SITE, $USER;

        header('Cache-Control: no-store');
        header('Pragma: no-cache');

        require_login(null, false);

        $service = $this->service;
        $url = $service->get_authorize_url();
        $title = get_string('appauthorisation', 'block_xp');
        $context = context_system::instance();

        $PAGE->set_pagelayout('standard');
        $PAGE->add_body_class('limitedwidth');
        $PAGE->set_url($url);
        $PAGE->set_context($context);
        $PAGE->set_title($title);
        $PAGE->set_heading('');

        if (!di::get('config')->get('enablemcp')) {
            $this->render_error_page('The Level Up XP AI Connector features are not enabled on this site,
                please contact your administrator.');
        }

        if (isguestuser() || !has_capability('local/xp:usemcp', $context)) {
            $this->render_error_page('You do not have the permission to use the Level Up XP AI Connector features
                on this site, please contact your administrator.');
        }

        $method = $request->get_method();
        if ($method !== 'GET' && $method !== 'POST') {
            throw new oauth_exception('invalid_method', 400);
        }

        $params = $this->get_params();
        try {
            if ($params['response_type'] !== 'code') {
                throw new oauth_exception('invalid_response_type.', 400);
            }

            if (
                $params['code_challenge']
                    && !preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $params['code_challenge'])
            ) {
                throw new oauth_exception('invalid_code_challenge.', 400);
            }

            if (
                $params['code_challenge_method'] &&
                    !in_array($params['code_challenge_method'], $service->get_code_challenge_methods_supported(), true)
            ) {
                throw new oauth_exception('invalid_code_challenge_method.', 400);
            }

            if (\core_text::strlen($params['state']) > 1024 || \core_text::strlen($params['scope']) > 1024) {
                throw new oauth_exception('invalid_state_or_scope', 400);
            }

            if (\core_text::strlen($params['resource']) > 2048 || !$service->is_valid_resource($params['resource'])) {
                throw new oauth_exception('invalid_resource', 400);
            }

            if ($params['client_id'] === '' || \core_text::strlen($params['client_id']) > 255) {
                throw new oauth_exception('invalid_client_id', 400);
            }

            if ($params['redirect_uri'] === '' || \core_text::strlen($params['redirect_uri']) > 2048) {
                throw new oauth_exception('invalid_redirect_uri', 400);
            } else if (!$service->is_valid_redirect_uri($params['redirect_uri'])) {
                throw new oauth_exception('invalid_redirect_uri', 400);
            }

            $client = $this->get_client($params['client_id']);
            if (!$client || !$client->is_active()) {
                throw new oauth_exception('invalid_client_id', 400);
            }

            if (!$client->has_redirect_url($params['redirect_uri'])) {
                throw new oauth_exception('invalid_redirect_uri', 400);
            }

            if ($client->is_pkce_required()) {
                if (!$params['code_challenge']) {
                    // Validate that the code challenge was passed, format validation happened before.
                    throw new oauth_exception('invalid_code_challenge.', 400);
                } else if (!in_array($params['code_challenge_method'], $client->get_code_challenge_methods_supported())) {
                    // Validate that the client support the challenge method.
                    throw new oauth_exception('invalid_code_challenge_method.', 400);
                }
            }
        } catch (oauth_exception $e) {
            $this->render_error_page(get_string('invalidoauthrequest', 'block_xp', $e->errorcode));
        } catch (\Throwable $e) {
            $this->render_error_page(get_string('invalidoauthrequest', 'block_xp', $e->getMessage()));
        }

        if ($method === 'POST') {
            require_sesskey();
            $code = $service->issue_code(
                (int) $USER->id,
                $params['client_id'],
                $params['redirect_uri'],
                $params['code_challenge']
            );
            $separator = strpos($params['redirect_uri'], '?') === false ? '?' : '&';
            $redirect = $params['redirect_uri'] . $separator . 'code=' . rawurlencode($code);
            if ($params['state'] !== '') {
                $redirect .= '&state=' . rawurlencode($params['state']);
            }

            redirect($redirect);
            exit;
        }

        $renderer = di::get('renderer');
        $cancelbutton = $renderer->make_single_button(new moodle_url('/'), get_string('cancel', 'core'));
        $authorizeurl = new url($url);
        $authorizeurl->params($params);
        $authorizebutton = $renderer->make_single_button(
            $authorizeurl->get_compatible_url(),
            get_string('authorise', 'block_xp'),
            ['primary' => true, 'method' => 'post']
        );

        $clientname = $client->get('displayname');
        $clientlogourl = $client->get_logo_url();
        $sitename = format_string($SITE->fullname);

        echo $OUTPUT->header();
        echo html_writer::start_div('block_xp');
        echo $OUTPUT->render_from_template('local_xp/oauth/authorize', $this->get_template_data([
            'title' => get_string('appwantstoaccessyouraccount', 'block_xp', $clientname),
            'clientname' => $clientname,
            'logourl' => $clientlogourl ? $clientlogourl->out(false) : null,
            'origin' => $client->get_origin(),
            'signedinas' => get_string('signedinasxony', 'block_xp', (object) [
                'site' => $sitename,
                'user' => fullname($USER),
            ]),
            'redirecturi' => $params['redirect_uri'],
            'authorizebutton' => $OUTPUT->render($authorizebutton),
            'cancelbutton' => $OUTPUT->render($cancelbutton),
        ]));
        echo html_writer::end_div();
        echo $OUTPUT->footer();
    }

    /**
     * Get template data.
     *
     * @param array<string, mixed> $data Extra data.
     * @return array<string, mixed>
     */
    protected function get_template_data(array $data = []): array {
        // These describe potential access because administrators may change the enabled MCP tools after authorisation.
        return array_merge([
            'title' => get_string('appauthorisation', 'block_xp'),
            'permissionsheading' => get_string('appwillbeallowedto', 'block_xp'),
            'permissions' => [
                ['text' => get_string('apppermissionread', 'block_xp')],
                ['text' => get_string('apppermissionpersonal', 'block_xp')],
                ['text' => get_string('apppermissionchange', 'block_xp')],
            ],
            'redirectlabel' => get_string('authorisewillredirectto', 'block_xp'),
            'warning' => get_string('onlyauthoriseifyoutrustapp', 'block_xp'),
            'errorheading' => get_string('appauthorisationerror', 'block_xp'),
        ], $data);
    }

    /**
     * Get client.
     *
     * @param string $clientid Client ID.
     * @return oauth_client|null
     */
    protected function get_client(string $clientid): ?oauth_client {
        $service = $this->service;

        $client = oauth_client::get_by_client_id($clientid);
        if ($client) {
            return $client;
        }

        return $service->is_trusted_cimd_url($clientid) ? $service->register_cimd_client($clientid) : null;
    }

    /**
     * Get request parameters.
     *
     * @return array<string, string>
     */
    protected function get_params(): array {
        return [
            'response_type' => optional_param('response_type', '', PARAM_ALPHANUMEXT),
            'client_id' => optional_param('client_id', '', PARAM_RAW_TRIMMED),
            'redirect_uri' => optional_param('redirect_uri', '', PARAM_RAW_TRIMMED),
            'code_challenge' => optional_param('code_challenge', '', PARAM_RAW_TRIMMED),
            'code_challenge_method' => optional_param('code_challenge_method', '', PARAM_ALPHANUMEXT),
            'state' => optional_param('state', '', PARAM_RAW_TRIMMED),
            'resource' => optional_param('resource', $this->service->get_mcp_url()->out(false), PARAM_RAW_TRIMMED),
            'scope' => optional_param('scope', '', PARAM_RAW_TRIMMED),
        ];
    }

    /**
     * Render the error page.
     *
     * @param string $error
     */
    protected function render_error_page(string $error) {
        global $OUTPUT;

        $cancelbutton = new single_button(new moodle_url('/'), get_string('cancel', 'core'), 'get');
        echo $OUTPUT->header();
        echo html_writer::start_div('block_xp');
        echo $OUTPUT->render_from_template('local_xp/oauth/authorize-error', $this->get_template_data([
            'error' => $error,
            'cancelbutton' => $OUTPUT->render($cancelbutton),
        ]));
        echo html_writer::end_div();
        echo $OUTPUT->footer();

        die();
    }
}
