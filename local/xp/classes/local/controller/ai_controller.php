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

use block_xp\di;
use block_xp\local\controller\page_controller;
use context_system;
use local_xp\local\mcp\tool_definition;
use local_xp\local\world\global_world;
use moodle_url;

/**
 * AI page controller.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_controller extends page_controller {
    /** @var string The route name. */
    protected $routename = 'ai';

    /**
     * Check permissions.
     *
     * @return void
     */
    protected function permissions_checks() {
        parent::permissions_checks();
        require_capability('local/xp:usemcp', context_system::instance());
        require_capability('local/xp:usemcp', $this->world->get_context());
    }

    /**
     * Define optional parameters.
     *
     * @return array
     */
    protected function define_optional_params() {
        return [
            ['revoke', false, PARAM_BOOL, false],
        ];
    }

    /**
     * Handle access revocation before output.
     *
     * @return void
     */
    protected function pre_content() {
        global $USER;

        parent::pre_content();
        if (!di::get('config')->get('enablemcp')) {
            throw new \moodle_exception('errorfeaturenotenabled', 'block_xp');
        }

        if ($this->get_param('revoke') && confirm_sesskey()) {
            di::get('oauth_service')->revoke_all_user_grants($USER->id);
            return $this->redirect(null, get_string('aiaccessrevoked', 'block_xp'));
        }
    }

    /**
     * Get the browser page title.
     *
     * @return string
     */
    protected function get_page_html_head_title() {
        return get_string('ai', 'block_xp');
    }

    /**
     * Get the page heading.
     *
     * @return string
     */
    protected function get_page_heading() {
        return get_string('ai', 'block_xp');
    }

    /**
     * Output the AI premium notice.
     *
     * @return void
     */
    protected function page_content() {
        global $USER;

        $config = di::get('config');
        $revokeurl = new moodle_url($this->pageurl, ['sesskey' => sesskey(), 'revoke' => 1]);
        $showchatgpt = (bool) $config->get('enablechatgpt');
        $showclaude = (bool) $config->get('enableclaude');
        $instructions = (string) $config->get('mcpconnectinstructions');
        $hasinstructions = !empty($instructions);
        $hasconnections = di::get('oauth_service')->has_usable_tokens($USER->id);
        $allowedrisks = (int) $config->get('mcprisksallowed');
        $permissions = [];
        foreach (
            [
            tool_definition::RISK_READ => 'mcpriskread',
            tool_definition::RISK_PERSONAL => 'mcpriskpersonal',
            tool_definition::RISK_WRITE => 'mcpriskwrite',
            ] as $risk => $string
        ) {
            $permissions[] = [
                'name' => get_string($string, 'block_xp'),
                'allowed' => (bool) ($allowedrisks & $risk),
            ];
        }

        $params = [
            'mcpurl' => di::get('oauth_service')->get_mcp_url()->out(false),
        ];

        $startingprompt = get_string('aistartingprompttext', 'block_xp');
        $context = $this->world->get_context();
        if ($context->contextlevel == CONTEXT_COURSE) {
            $course = get_course($context->instanceid);
            $shortname = format_string($course->shortname, true, ['context' => $context]);
            $startingprompt .= ' ' . get_string('aistartingpromptcourse', 'block_xp', $shortname);
        } else if ($this->world instanceof global_world) {
            $startingprompt .= ' ' . get_string('aistartingpromptoverall', 'block_xp');
        }

        $output = $this->get_renderer();
        echo $output->advanced_heading(get_string('aipagetitle', 'block_xp'), [
            'intro' => get_string('aipageintro', 'block_xp'),
            'help' => new \help_icon('aipagetitle', 'block_xp'),
        ]);

        echo $output->render_from_template('local_xp/ai-page-content', [
            'chatgptinstructions' => $showchatgpt
                ? markdown_to_html(get_string('aichatgptinstructions', 'block_xp', $params))
                : null,
            'claudeinstructions' => $showclaude
                ? markdown_to_html(get_string('aiclaudeinstructions', 'block_xp', $params))
                : null,
            'custominstructions' => $hasinstructions
                ? format_text($instructions, FORMAT_HTML, ['context' => context_system::instance()])
                : null,
            'hascustominstructions' => $hasinstructions,
            'showchatgpt' => $showchatgpt,
            'showclaude' => $showclaude,
            'startingprompt' => $startingprompt,
            'hasanyinstructions' => $hasinstructions || $showchatgpt || $showclaude,
            'hasconnections' => $hasconnections,
            'permissions' => $permissions,
            'revokeurl' => $revokeurl->out(false),
        ]);
    }
}
