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
// https://levelup.plus

namespace local_xp\output;

use block_xp\di;
use block_xp\local\course_world;
use block_xp\local\factory\reason_from_log_entry_factory;
use block_xp\local\reason\reason_with_short_description;
use block_xp\local\ruletype\resolver as ruletype_resolver;
use block_xp\local\routing\url;
use context_system;
use coding_exception;
use html_writer;
use local_xp\local\reason\reason_with_location;
use local_xp\local\rule\static_instance;
use local_xp\local\team\team_membership_resolver;
use stdClass;

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
    /** @var array Array of team objects indexed by user ID. */
    protected $teamscache = [];
    /** @var int Filter by rule ID, falsy means not filtering. */
    protected $filterbyruleid;
    /** @var array Array of rule columns. */
    protected $rulecolumns = ['id', 'contextid', 'childcontextid', 'points', 'type', 'filter',
        'filtercourseid', 'filtercmid', 'filterint1', 'filterchar1'];
    protected $localrulecolumns = ['limitmax', 'limitwindow', 'repeatwindow', 'repeatscope'];

    /**
     * Constructor.
     *
     * @param course_world $world The world.
     * @param reason_from_log_entry_factory $reasonfactory The reason factory.
     * @param int $groupid The group ID.
     * @param string|null|array $downloadformat The download format, or a tuple with format and filename.
     * @param team_membership_resolver|null $teamresolver The team resolver.
     * @param int|null $userid The user ID to filter by.
     */
    public function __construct(
        course_world $world,
        reason_from_log_entry_factory $reasonfactory,
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

        // Download support - must be set before parent::init() runs so generate_columns_definition gets correct columns.
        $downloadfilename = 'xp_log_' . $world->get_context()->id;
        if (is_array($downloadformat)) {
            [$downloadformat, $downloadfilename] = $downloadformat;
        }
        $this->is_downloading($downloadformat, $downloadfilename);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([]);

        parent::__construct($world, $reasonfactory, $groupid, $userid);

        $this->no_sorting('location');
        $this->no_sorting('location_url');
        $this->no_sorting('reason');
        $this->no_sorting('rule');
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
        $cols['reason'] = get_string('reason', 'block_xp');
        $cols['location'] = !$isdownloading ? '' : get_string('reasonlocation', 'local_xp');
        if ($isdownloading) {
            $cols['location_url'] = get_string('reasonlocationurl', 'local_xp');
        }
        $cols['rule'] = get_string('rule', 'block_xp');

        return $cols;
    }

    /**
     * Init SQL.
     *
     * @return void
     */
    protected function init_sql() {
        parent::init_sql();

        $this->sql->fields .= ', u.email, u.idnumber, u.username';

        // Rules could technically be linked to a deleted context, but in theory this is very unlikely to
        // happen because the context defines the world, and if the context is deleted all associated with
        // the context should be deleted, including the rule, the logs, etc. The same applies to childcontext.
        $this->sql->from .= ' LEFT JOIN {block_xp_rule} r ON r.id = x.ruleid';
        $this->sql->from .= ' LEFT JOIN {local_xp_rule} lr ON lr.ruleid = r.id';
        $this->sql->from .= ' LEFT JOIN {context} ctx ON ctx.id = r.contextid';
        $this->sql->from .= ' LEFT JOIN {context} childctx ON childctx.id = r.childcontextid';

        $this->sql->fields .= ', ' . implode(', ', array_map(function ($col) {
            return 'r.' . $col . ' AS rule_' . $col;
        }, $this->rulecolumns));
        $this->sql->fields .= ', ' . implode(', ', array_map(function ($col) {
            return 'lr.' . $col . ' AS rule_' . $col;
        }, $this->localrulecolumns));
        $this->sql->fields .= ', ctx.id AS rule_contextid, childctx.id AS rule_childcontextid';

        if ($this->filterbyruleid) {
            $this->sql->where .= ' AND x.ruleid = :ruleid';
            $this->sql->params = array_merge($this->sql->params, ['ruleid' => $this->filterbyruleid]);
        }
    }

    /**
     * Reason location.
     *
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_location($row) {
        $reason = $this->reasonfactory->get_reason_from_log_entry($row->reason, $row);
        if ($reason instanceof reason_with_location) {
            $name = $reason->get_location_name();
            $url = $reason->get_location_url();
            if (!$name) {
                return '';
            }
            $name = !$this->is_downloading() ? s($name) : $name;
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
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_location_url($row) {
        $reason = $this->reasonfactory->get_reason_from_log_entry($row->reason, $row);
        if ($reason instanceof reason_with_location) {
            $url = $reason->get_location_url();
            return $url ? $url->out(false) : '';
        }
        return '';
    }

    /**
     * Reason.
     *
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_reason($row) {
        $reason = $this->reasonfactory->get_reason_from_log_entry($row->reason, $row);
        if ($reason instanceof reason_with_short_description) {
            $desc = $reason->get_short_description();
        } else {
            $desc = '';
        }
        if ($this->is_downloading()) {
            return $desc;
        }
        return html_writer::tag('span', s($desc));
    }

    /**
     * Column.
     *
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_rule($row) {
        if (empty($row->ruleid)) {
            $label = '';
            $url = '';
            $reason = $this->reasonfactory->get_reason_from_log_entry($row->reason, $row);
            if ($reason instanceof \block_xp\local\reason\event_reason) {
                $label = get_string('eventsrules', 'block_xp');
                $url = $this->urlresolver->reverse('rules', ['courseid' => $this->world->get_courseid()]);
            } else if ($reason instanceof \local_xp\local\reason\graded_reason) {
                $label = get_string('graderules', 'block_xp');
                $url = $this->urlresolver->reverse('graderules', ['courseid' => $this->world->get_courseid()]);
            }

            if (!$label) {
                return '';
            }

            if (!$this->is_downloading()) {
                return html_writer::link($url, s($label));
            }
            return s($label);
        }

        $rulerecord = (object) [];
        foreach (array_merge($this->rulecolumns, $this->localrulecolumns) as $col) {
            $rulerecord->{$col} = $row->{"rule_{$col}"} ?? null;
        }

        // The rule no longer exists, or its context is missing.
        if (empty($rulerecord->id) || empty($rulerecord->contextid)) {
            if ($this->is_downloading()) {
                return get_string('deleted', 'core');
            }
            return html_writer::tag('em', get_string('deleted', 'core'));
        }

        $rule = new static_instance($rulerecord);
        $label = $this->ruledescriptor->get_type_name($rule);
        $sublabel = $this->ruledescriptor->get_description($rule);

        if ($this->is_downloading()) {
            return $label . ' - ' . $sublabel;
        }

        $filterbyrule = '';
        if (!$this->filterbyruleid) {
            $filterbyrule = $this->renderer->action_icon(
                new url($this->baseurl, ['ruleid' => $rulerecord->id]),
                new \pix_icon('i/search', get_string('filterbyrule', 'block_xp'))
            );
        }

        $viewurl = $this->urlresolver->reverse('actionrules', ['courseid' => $this->world->get_courseid()]);
        $labelhtml = html_writer::div(html_writer::link($viewurl, s($label)), 'xp-truncate', [
            'title' => $label,
        ]);
        $sublabelhtml = html_writer::div(s($sublabel), 'xp-truncate xp-text-sm', [
            'title' => $sublabel,
        ]);
        $texthtml = html_writer::div($labelhtml . $sublabelhtml, 'xp-flex xp-flex-col xp-overflow-hidden xp-max-w-[24ch]');

        return html_writer::div(
            $texthtml . html_writer::div($filterbyrule, 'xp-flex-0'),
            'xp-flex'
        );
    }

    /**
     * Team column.
     *
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_team($row) {
        if (!$this->teamresolver) {
            return '';
        }

        if (!isset($this->teamscache[$row->userid])) {
            $this->teamscache[$row->userid] = $this->teamresolver->get_teams_of_member($row->userid);
        }

        return implode(', ', array_map(function ($team) {
            return $team->get_name();
        }, $this->teamscache[$row->userid]));
    }

    /**
     * Timerecorded column.
     *
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_timerecorded($row) {
        return userdate($row->timerecorded, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Points column.
     *
     * @param stdClass $row The row.
     * @return string
     */
    protected function col_points($row) {
        if ($this->is_downloading()) {
            return $row->points;
        }
        return $this->renderer->xp($row->points);
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
    }
}
