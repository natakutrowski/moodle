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
use block_xp\local\controller\admin_route_controller;
use core_collator;
use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use local_xp\local\mcp\tool_definition;

/**
 * OAuth clients.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_mcp_tools_controller extends admin_route_controller {
    /** @var string The section name. */
    protected $sectionname = 'block_xp_admin_mcp_tools';

    /**
     * Output the MCP tools.
     *
     * @return void
     */
    protected function content() {
        $renderer = di::get('renderer');
        echo $renderer->advanced_heading(get_string('mcptools', 'block_xp'), [
            'intro' => get_string('mcptools_desc', 'block_xp'),
            'level' => 2,
        ]);

        if (!\block_xp\di::get('config')->get('enablemcp')) {
            echo $renderer->notification_without_close(get_string('mcpdisabledadminnotice', 'block_xp'), 'info');
        }

        $configcontext = (int) \block_xp\di::get('config')->get('context');
        $allowedrisks = (int) \block_xp\di::get('config')->get('mcprisksallowed');

        $registry = di::get('mcp_tool_registry');
        $definitions = $registry->get_all_definitions();

        $enabledtag = $this->tag(get_string('enabled', 'core_admin'), 'success');
        $disabledtag = $this->tag(get_string('disabled', 'core_admin'), 'secondary');

        $risktags = [
            tool_definition::RISK_READ => $this->tag(get_string('riskread', 'block_xp'), 'light'),
            tool_definition::RISK_WRITE => $this->tag(get_string('riskwrite', 'block_xp'), 'warning'),
            tool_definition::RISK_PERSONAL => $this->tag(get_string('riskpersonal', 'block_xp'), 'info', [
                'title' => get_string('riskpersonal_desc', 'block_xp'),
            ]),
            tool_definition::RISK_DELETE => $this->tag(get_string('riskdelete', 'block_xp'), 'dark'),
        ];

        $table = new html_table();
        $table->head = [get_string('tool', 'block_xp'), get_string('risks', 'block_xp'), get_string('status', 'core')];
        foreach ($definitions as $definition) {
            $issupportedmode = in_array($configcontext, $definition->get_supported_modes(), true);
            $disabled = !$registry->is_tool_enabled($definition);

            $statussuffix = '';
            if (!$issupportedmode) {
                $statussuffix = get_string('sitewideonly', 'block_xp');
                if ($configcontext == CONTEXT_SYSTEM) {
                    $statussuffix = get_string('percourseonly', 'block_xp');
                }
                $statussuffix = html_writer::tag('span', $statussuffix, ['class' => 'badge bg-secondary badge-secondary']);
            }

            $risks = array_filter($risktags, function ($value, $key) use ($definition) {
                return (bool) ($definition->get_risk() & $key);
            }, ARRAY_FILTER_USE_BOTH);

            $row = new html_table_row([
                new html_table_cell(
                    html_writer::div(s($definition->get_title())) .
                    html_writer::div(s($definition->get_name()), 'xp-text-sm text-muted')
                ),
                implode(' ', $risks),
                ($disabled ? $disabledtag : $enabledtag) . ' ' . $statussuffix,
            ]);
            $table->data[] = $row;
        }

        echo \html_writer::table($table);
    }

    /**
     * Tag.
     *
     * @param string $text
     * @param string $variant
     * @param array $attrs
     * @return string
     */
    protected function tag(string $text, string $variant, array $attrs = []) {
        $classes = "badge-{$variant} bg-{$variant}";
        if ($variant === 'light') {
            $classes .= ' text-dark';
        }
        return html_writer::tag('span', $text, [
            'class' => "badge " . $classes,
        ] + $attrs);
    }
}
