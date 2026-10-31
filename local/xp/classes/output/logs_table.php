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

namespace local_xp\output;

use block_xp\di;
use block_xp\local\course_world;
use block_xp\local\world;
use block_xp\local\factory\reason_from_log_entry_factory;
use block_xp\local\logger\collection_logger_query;
use block_xp\local\logger\log;
use block_xp\local\navigation\navigator;
use block_xp\local\ruletype\resolver as ruletype_resolver;
use block_xp\local\routing\url;
use block_xp\local\routing\url_resolver;
use block_xp\local\serializer\rule_serializer;
use context;
use context_system;
use coding_exception;
use html_writer;
use local_xp\local\logger\log_with_rule;
use local_xp\local\reason\reason_with_location;
use local_xp\local\rule\rule_descriptor;
use local_xp\local\rulefilter\handler as filter_handler;
use local_xp\local\team\team_membership_resolver;
use local_xp\local\utils\context_utils;
use local_xp\local\world\global_world;

/**
 * Logs table class.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logs_table extends \block_xp\output\logs_table {
    /** @var rule_serializer Rule serializer. */
    protected $ruleserializer;
    /** @var ruletype_resolver Rule type resolver. */
    protected $ruletyperesolver;
    /** @var filter_handler Filter handler. */
    protected $rulefilterhandler;
    /** @var rule_descriptor Rule descriptor. */
    protected $ruledescriptor;
    /** @var team_membership_resolver Team resolver. */
    protected $teamresolver;
    /** @var url_resolver URL resolver. */
    protected $urlresolver;
    /** @var navigator URL resolver. */
    protected $navigator;
    /** @var array Array of team objects indexed by user ID. */
    protected $teamscache = [];
    /** @var int Filter by rule ID, falsy means not filtering. */
    protected $filterbyruleid;
    /** @var int[] Filter by rule IDs, empty means not filtering. */
    protected $filterbyruleids = [];

    /**
     * Constructor.
     *
     * @param world $world The world.
     * @param ?reason_from_log_entry_factory $reasonfactory The reason factory.
     * @param int $groupid The group ID.
     * @param string|null|array $downloadformat The download format, or a tuple with format and filename.
     * @param team_membership_resolver|null $teamresolver The team resolver.
     * @param int|null $userid The user ID to filter by.
     */
    public function __construct(
        world $world,
        ?reason_from_log_entry_factory $reasonfactory,
        $groupid,
        $downloadformat = null,
        ?team_membership_resolver $teamresolver = null,
        $userid = null
    ) {
        $this->teamresolver = $teamresolver;
        $this->ruleserializer = di::get('serializer_factory')->get_rule_serializer();
        $this->rulefilterhandler = di::get('rule_filter_handler');
        $this->ruletyperesolver = di::get('rule_type_resolver');
        $this->ruledescriptor = di::get('rule_descriptor');
        $this->urlresolver = di::get('url_resolver');
        $this->navigator = di::get('world_navigator_factory')->get_navigator_for_world($world);

        // Download support - must be set before parent::init() runs so generate_columns_definition gets correct columns.
        $downloadfilename = 'xp_log_' . $world->get_context()->id;
        if (is_array($downloadformat)) {
            [$downloadformat, $downloadfilename] = $downloadformat;
        }
        $this->is_downloading($downloadformat, $downloadfilename);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([]);

        parent::__construct($world, $reasonfactory, $groupid, $userid);

        $this->no_sorting('email');
        $this->no_sorting('idnumber');
        $this->no_sorting('username');
        $this->no_sorting('location');
        $this->no_sorting('location_url');
        $this->no_sorting('reason');
        $this->no_sorting('rule');
        $this->no_sorting('source');
        $this->no_sorting('source_url');
        $this->no_sorting('team');
    }

    /**
     * Generate the columns definition.
     *
     * @return array
     */
    protected function generate_columns_definition() {
        global $CFG;
        $isdownloading = $this->is_downloading();
        $context = $this->world->get_context();

        $cols = [
            'timerecorded' => get_string('eventtime', 'block_xp'),
        ];

        if ($isdownloading) {
            $cols['firstname'] = get_string('firstname', 'core');
            $cols['lastname'] = get_string('lastname', 'core');
        }
        $cols['fullname'] = get_string('fullname');

        if ($isdownloading) {
            if (has_capability('moodle/site:viewuseridentity', $context)) {
                $forwholesite = di::get('config')->get('context') == CONTEXT_SYSTEM;
                $hiddenidentityfields = explode(',', $CFG->hiddenuserfields);
                if ($forwholesite && has_capability('moodle/user:viewhiddendetails', context_system::instance())) {
                    $hiddenidentityfields = [];
                } else if (!$forwholesite && has_capability('moodle/course:viewhiddenuserfields', $context)) {
                    $hiddenidentityfields = [];
                }

                $showuseridentity = explode(',', $CFG->showuseridentity);
                $identityfields = array_diff_key(array_intersect_key([
                    'username' => get_string('username', 'core'),
                    'idnumber' => get_string('idnumber', 'core'),
                    'email' => get_string('email', 'core'),
                ], array_flip($showuseridentity)), array_flip($hiddenidentityfields));
                $cols = array_merge($cols, $identityfields);
            }

            if ($this->teamresolver) {
                $cols['team'] = get_string('team', 'local_xp');
            }
        }

        $cols['points'] = get_string('reward', 'block_xp');
        if (!$this->world instanceof course_world) {
            $cols['source'] = get_string('source', 'block_xp');
            if ($isdownloading) {
                $cols['source_url'] = get_string('sourceurl', 'block_xp');
            }
        }
        $cols['reason'] = get_string('reason', 'block_xp');
        $cols['location'] = !$isdownloading ? '' : get_string('reasonlocation', 'local_xp');
        if ($isdownloading) {
            $cols['location_url'] = get_string('reasonlocationurl', 'local_xp');
        }
        $cols['rule'] = get_string('rule', 'block_xp');

        return $cols;
    }

    /**
     * Make the query from the table filters and sorting preferences.
     *
     * @return collection_logger_query
     */
    protected function make_query(): collection_logger_query {
        $query = parent::make_query();
        if ($this->filterbyruleids) {
            $query->set_rule_ids($this->filterbyruleids);
        } else if ($this->filterbyruleid) {
            $query->set_rule_id($this->filterbyruleid);
        }
        return $query;
    }

    /**
     * Reason location.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_location($log) {
        $reason = $log->get_reason();
        if ($reason instanceof reason_with_location) {
            $name = $reason->get_location_name();
            $url = $reason->get_location_url();
            if (!$name) {
                return '';
            }
            $name = $this->escape($name);
            if ($url && !$this->is_downloading()) {
                return html_writer::link($url, $name);
            }
            return $name;
        }
        return '';
    }

    /**
     * Reason location URL.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_location_url($log) {
        $reason = $log->get_reason();
        if ($reason instanceof reason_with_location) {
            $url = $reason->get_location_url();
            return $url ? $this->escape($url->out(false)) : '';
        }
        return '';
    }

    /**
     * Reason.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_reason($log) {
        $desc = parent::col_reason($log);
        if ($this->is_downloading()) {
            return $desc;
        }
        return html_writer::tag('span', $desc);
    }

    /**
     * Column.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_rule($log) {
        if (!$log instanceof log_with_rule || !$log->get_rule_id()) {
            $label = '';
            $url = '';
            $reason = $log->get_reason();
            if ($reason instanceof \block_xp\local\reason\event_reason) {
                $label = get_string('eventsrules', 'block_xp');
                $url = $this->navigator->get_url('rules');
            } else if ($reason instanceof \local_xp\local\reason\graded_reason) {
                $label = get_string('graderules', 'block_xp');
                $url = $this->navigator->get_url('graderules');
            }

            if (!$label) {
                return '';
            }

            if (!$this->is_downloading()) {
                return html_writer::link($url, $this->escape($label));
            }
            return $this->escape($label);
        }

        $rule = $log->get_rule();
        // The rule no longer exists.
        if (!$rule) {
            if ($this->is_downloading()) {
                return get_string('deleted', 'core');
            }
            return html_writer::tag('em', get_string('deleted', 'core'));
        }

        $label = $this->ruledescriptor->get_type_name($rule);
        $sublabel = $this->ruledescriptor->get_description($rule);

        if ($this->is_downloading()) {
            return $this->escape($label . ' - ' . $sublabel);
        }

        $filterbyrule = '';
        if (!$this->filterbyruleid && !$this->filterbyruleids) {
            $filterbyrule = $this->renderer->action_icon(
                new url($this->baseurl, ['ruleid' => $rule->get_id()]),
                new \pix_icon('i/search', get_string('filterbyrule', 'block_xp'))
            );
        }

        $labelhtml = $this->escape($label);
        if (!$this->world instanceof global_world) {
            $labelhtml = html_writer::link($this->navigator->get_url('actionrules'), $labelhtml);
        }
        $labelhtml = html_writer::div($labelhtml, 'xp-truncate', [
            'title' => $label,
        ]);
        $sublabelhtml = html_writer::div($this->escape($sublabel), 'xp-truncate xp-text-sm', [
            'title' => $sublabel,
        ]);
        $texthtml = html_writer::div($labelhtml . $sublabelhtml, 'xp-flex xp-flex-col xp-overflow-hidden xp-max-w-[24ch]');

        return html_writer::div(
            $texthtml . html_writer::div($filterbyrule, 'xp-flex-0'),
            'xp-flex'
        );
    }

    /**
     * Source column.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_source($log) {
        $context = context::instance_by_id($log->get_context_id(), IGNORE_MISSING);
        if (!$context || $context->contextlevel != CONTEXT_COURSE) {
            return '';
        }

        $name = context_utils::get_course_name_short($context);
        $name = $this->escape(html_entity_decode($name ?? '', ENT_COMPAT));
        if ($this->is_downloading()) {
            return $name;
        }
        return html_writer::link(context_utils::get_url($context), $name);
    }

    /**
     * Source URL column.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_source_url($log) {
        $context = context::instance_by_id($log->get_context_id(), IGNORE_MISSING);
        if (!$context || $context->contextlevel != CONTEXT_COURSE) {
            return '';
        }

        $url = context_utils::get_url($context);
        return $url ? $this->escape($url->out(false)) : '';
    }

    /**
     * Team column.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_team($log) {
        if (!$this->teamresolver) {
            return '';
        }

        $userid = $log->get_user_id();
        if (!isset($this->teamscache[$userid])) {
            $this->teamscache[$userid] = $this->teamresolver->get_teams_of_member($userid);
        }

        return implode(', ', array_map(function ($team) {
            return $this->escape($team->get_name(), true);
        }, $this->teamscache[$userid]));
    }

    /**
     * Timerecorded column.
     *
     * @param log $log The log.
     * @return string
     */
    protected function col_timerecorded($log) {
        return userdate($log->get_time_recorded()->getTimestamp(), get_string('strftimedatetimeshort', 'core_langconfig'));
    }

    /**
     * Render user identity columns.
     *
     * @param string $column The column.
     * @param log $log The log.
     * @return string
     */
    public function other_cols($column, $log) {
        return $this->escape($log->get_user()->{$column} ?? null);
    }

    /**
     * Send file for download.
     *
     * @return void
     */
    public function send_file() {
        if (!$this->is_downloading()) {
            throw new coding_exception('What are you doing?');
        }
        \core\session\manager::write_close();
        $this->out(-1337, false);
        die();
    }

    /**
     * Set the filter by rule ID.
     *
     * @param int|null $ruleid The rule ID.
     * @return void
     */
    public function set_filter_by_rule_id(?int $ruleid) {
        $this->filterbyruleid = $ruleid;
        $this->filterbyruleids = [];
    }

    /**
     * Set the filter by rule IDs.
     *
     * @param int[] $ruleids The rule IDs, or an empty array to remove the filter.
     * @return void
     */
    public function set_filter_by_rule_ids(array $ruleids) {
        $this->filterbyruleid = null;
        $this->filterbyruleids = $ruleids;
    }
}
