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
 * Mission instances table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\table;

use action_menu_filler;
use action_menu_link;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\helper;
use block_gearup\local\repository\mission_instance_query;
use block_gearup\local\utils\human_utils;
use block_gearup\local\utils\user_utils;
use block_gearup\output\mission_instances_filters;
use core_user\fields;
use html_writer;
use moodle_url;

/**
 * Mission instances table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_instances extends improved_table {

    /** @var bool Is compulsory. */
    protected $iscompulsory;
    /** @var mission The mission. */
    protected $mission;
    /** @var helper The helper. */
    protected $missionhelper;
    /** @var object The repository. */
    protected $repository;
    /** @var int|null The subjectid ID. */
    protected $subjectid = null;
    /** @var mission_instance_query The instance query. */
    protected $query;
    /** @var string|null The download format. */
    protected $downloadformat;
    /** @var string The download file name. */
    protected $downloadfilename = 'file';
    /** @var bool Whether we are using a wide view. */
    protected $iswideview = false;
    /** @var bool Whether we can edit stuff. */
    protected $iseditable = false;

    /** @var string The date time format. */
    protected $datetimeformat;
    /** @var array Our ID fields. */
    protected $idfields;
    /** @var fields The user fields. */
    protected $userfields;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_mission_instances');
        $this->datetimeformat = get_string('strftimedatetimeshort', 'langconfig');
    }

    /**
     * Define the download filename.
     *
     * @param string $downloadfilename The download filename.
     * @return self
     */
    public function define_download_filename(string $downloadfilename): self {
        $this->downloadfilename = $downloadfilename;
        return $this;
    }

    /**
     * Define the download format.
     *
     * @param string|null $download format The download format.
     * @return self
     */
    public function define_download_format(?string $downloadformat): self {
        $this->downloadformat = $downloadformat ?: null;
        return $this;
    }

    /**
     * Define the mission.
     *
     * @param mission $mission The mission.
     * @return self
     */
    public function define_mission(mission $mission): self {
        $this->mission = $mission;
        return $this;
    }

    /**
     * Define the mission helper.
     *
     * @param object $missionhelper
     * @return self
     */
    public function define_mission_helper($missionhelper): self {
        $this->missionhelper = $missionhelper;
        return $this;
    }

    /**
     * Define the query.
     *
     * @param mission_instance_query $query
     * @return self
     */
    public function define_query(mission_instance_query $query): self {
        $this->query = $query;
        return $this;
    }

    /**
     * Define the repository.
     *
     * @param object $repository
     * @return self
     */
    public function define_repository($repository): self {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Define the subject.
     *
     * @param int $subjectid
     * @return self
     */
    public function define_subject_id(int $subjectid): self {
        $this->subjectid = $subjectid;
        return $this;
    }

    /**
     * Define the wideview.
     *
     * @param bool $iswideview
     * @return self
     */
    public function define_wideview(bool $iswideview): self {
        $this->iswideview = $iswideview;
        return $this;
    }

    /**
     * Initialise the table.
     *
     * @return self
     */
    public function init(): self {
        $required = ['mission', 'missionhelper', 'repository', 'query'];
        foreach ($required as $key) {
            if (!$this->$key) {
                throw new \coding_exception("You must define $key before calling init()");
            }
        }

        // Compute all the user fields.
        $this->uniqueid .= '-' . $this->missionhelper->get_type($this->mission);
        $this->userfields = fields::for_identity($this->mission->get_context(), false)->with_name()->with_userpic();
        $this->idfields = user_utils::get_visible_identity_fields($this->mission->get_context());
        $this->iscompulsory = $this->missionhelper->is_compulsory($this->mission);
        $this->iseditable = $this->missionhelper->is_active($this->mission);

        // Are we downloading this?
        $this->is_downloading(!$this->subjectid ? $this->downloadformat : null, $this->downloadfilename);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([]);

        // Define columns, and headers.
        $columnsdefinition = $this->generate_columns_definition();
        $columns = array_keys($columnsdefinition);
        $headers = array_map(function ($header) {
            return (string) $header;
        }, array_values($columnsdefinition));
        $this->define_columns($columns);
        $this->define_headers($headers);

        // Define various table settings.
        $this->sortable(true, $this->iscompulsory ? 'timestarted' : 'timeassigned', SORT_DESC);
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->initialbars(false);

        return $this;
    }

    /**
     * Generate the columns definition.
     *
     * @return array
     */
    protected function generate_columns_definition() {
        $idfields = $this->idfields;

        if ($this->is_downloading()) {
            $cols = array_merge([
                'fullname' => get_string('fullname', 'core'),
                'firstname' => get_string('firstname', 'core'),
                'lastname' => get_string('lastname', 'core'),
            ], $idfields);

            if ($this->missionhelper->is_repeating($this->mission)) {
                $cols['repeatnumber'] = get_string('repeatnumber', 'block_gearup');
            }

            if ($this->missionhelper->is_a_streak($this->mission)) {
                $cols['counter'] = get_string('streak', 'block_gearup');
            }

            $cols['hascompleted'] = get_string('completed', 'block_gearup');
            $cols['completionrate'] = get_string('completionrate', 'block_gearup');
            $cols['timeassigned'] = get_string('timeassigned', 'block_gearup');
            $cols['timestarted'] = get_string('timestarted', 'block_gearup');
            $cols['timecompleted'] = get_string('timecompleted', 'block_gearup');
            $cols['timeended'] = get_string('timeended', 'block_gearup');

            if ($this->missionhelper->is_a_streak($this->mission)) {
                $cols['timelost'] = get_string('timelost', 'block_gearup');
            } else {
                $cols['timetocomplete_approx'] = get_string('timetocomplete', 'block_gearup');
                $cols['timetocomplete_mins'] = get_string('timetocomplete', 'block_gearup')
                    . ' (' . get_string('minutes', 'core') . ')';
            }

            return $cols;
        }
        $cols = [
            'fullname' => get_string('fullname', 'core'),
        ];
        if ($this->iswideview) {
            $cols += $idfields;
        }

        if (!$this->missionhelper->is_a_streak($this->mission)) {
            $cols += [
                'timeassigned' => get_string('assigned', 'block_gearup'),
                'timestarted' => get_string('started', 'block_gearup'),
                'completed' => get_string('completed', 'block_gearup'),
                'timeended' => get_string('ended', 'block_gearup'),
            ];

            if ($this->iscompulsory) {
                unset($cols['timeassigned']);
            }
            if (!$this->missionhelper->is_finishable($this->mission)) {
                unset($cols['timeended']);
            }

        } else {
            $cols += [
                'timestarted' => get_string('started', 'block_gearup'),
                'counter' => get_string('streak', 'block_gearup'),
                'timelost' => get_string('timelost', 'block_gearup'),
            ];
        }

        $cols += [
            'actions' => '',
        ];

        if ($this->subjectid) {
            unset($cols['fullname']);
        }

        return $cols;
    }

    protected function prepare_query(): void {
        global $USER;

        $query = $this->query;
        $query->set_mission_id($this->mission->get_id());
        if ($this->subjectid) {
            $query->set_subject_id($this->subjectid);
        }

        // Apply filterset.
        if ($filterset = $this->get_filterset()) {
            if ($filterset->has_filter('subject:term')) {
                $query->filter_subject_by_term($filterset->get_filter('subject:term')->current());
            }
            if ($filterset->has_filter('groupid')) {
                // Note that when we introduce dynamic tables, it would be possible for the user to remove
                // the groupid entirely and thus get access to all users. At the moment it is not a problem
                // because we do not allow users who cannot view all participants to get here, but that is
                // something to consider in the future.
                $groupid = $filterset->get_filter('groupid')->current();
                if (user_utils::can_select_group($this->mission->get_context(), $USER->id, $groupid)) {
                    $query->set_group_id($groupid);
                }
            }
            if ($filterset->has_filter('status')) {
                $query->filter_by_status($filterset->get_filter('status')->current());
            }
        }

        $sortby = [['lastname', SORT_ASC], ['firstname', SORT_ASC], ['iteration', SORT_ASC]];
        if (!$this->is_downloading()) {
            ['sortby' => $sortby, 'sortorder' => $sortorder] = $this->get_primary_sort_order();
            if ($sortby === 'completed') {
                $sortby = [['completionratio', $sortorder], ['timecompleted', $sortorder]];
            } else {
                $sortby = [[$sortby, $sortorder]];
            }
        }

        foreach ($sortby as $sort) {
            $query->add_order_by($sort[0], $sort[1]);
        }
    }

    protected function get_count(): int {
        return $this->repository->count_instances_from_query($this->query);
    }

    protected function get_rows(): iterable {
        return $this->repository->get_instances_from_query($this->query, $this->get_page_start(), $this->get_page_size());
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_actions($row) {
        $instanceurl = $this->get_instance_url($row);
        $actions = [];

        $action = new action_menu_link($instanceurl, null, get_string('view', 'core'));
        $actions[] = $action;

        if ($this->iseditable) {
            $actions[] = new action_menu_filler();
            $action = new action_menu_link($instanceurl, null, get_string('delete', 'core'), false, [
                'data-gu-action' => 'open-form',
                'data-form-class' => 'block_gearup\form\delete_mission_instance_dynamic_form',
                'data-form-args__missionid' => $row->instance->get_mission()->get_id(),
                'data-form-args__missioninstid' => $row->instance->get_id(),
                'data-modal-buttons__save__danger' => "true",
                'data-modal-buttons__save__label' => get_string('delete', 'core'),
                'data-modal-title' => fullname($row->user),
                'data-modal-large' => 'false',
            ]);
            $action->add_class('text-danger');
            $actions[] = $action;
        }

        return $this->format_dropdown($actions);
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_counter($row) {
        if (!$this->missionhelper->is_a_streak($this->mission)) {
            return '-';
        }
        return $row->instance->get_counter();
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_completed($row) {
        if (!$this->missionhelper->has_completed($row->instance) || $this->is_incomplete_challenge($row->instance)) {
            $completionratio = $row->instance->get_completion_ratio();
            return human_utils::percentage($completionratio) . '%';
        }
        return userdate_htmltime(
            $row->instance->get_time_completed()->getTimestamp(),
            get_string('strftimedatetimeshort', 'langconfig')
        );
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_completionrate($row) {
        $ratio = $row->instance->get_completion_ratio();
        if ($this->missionhelper->has_completed($row->instance) && !$this->is_incomplete_challenge($row->instance)) {
            $ratio = 1;
        }
        return $this->format_ratio($ratio);
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_fullname($row) {
        $name = fullname($row->user);
        if ($this->is_downloading()) {
            return $name;
        }
        return $this->get_renderer()->render_from_template('block_gearup/table/col_fullname', [
            'fullname' => $name,
            'picurl' => $this->get_renderer()->get_user_picture($row->user)->out(false),
            'linkurl' => $this->get_instance_url($row),
        ]);
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_hascompleted($row) {
        if ($this->missionhelper->has_completed($row->instance) && !$this->is_incomplete_challenge($row->instance)) {
            return get_string('yes', 'core');
        }
        return get_string('no', 'core');
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_repeatnumber($row) {
        if (!$this->missionhelper->is_repeating($row->instance)) {
            return '-';
        }
        $n = $row->instance->get_iteration_number();
        return $n + 1;
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timeassigned($row) {
        $content = $this->format_datetime($row->instance->get_time_assigned());
        if ($this->is_downloading()) {
            return $content;
        }

        $url = null;
        if (!$this->iscompulsory && $this->subjectid) {
            $url = $this->get_instance_url($row);
        }
        return $url ? html_writer::link($url, $content) : $content;
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timecompleted($row) {
        if (!$this->missionhelper->has_completed($row->instance) || $this->is_incomplete_challenge($row->instance)) {
            return '-';
        }
        return $this->format_datetime($row->instance->get_time_completed());
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timetocomplete_approx($row) {
        if (!$this->missionhelper->has_completed($row->instance) || $this->is_incomplete_challenge($row->instance)) {
            return '-';
        }
        $timestarted = $row->instance->get_time_started()->getTimestamp();
        $timecompleted = $row->instance->get_time_completed()->getTimestamp();
        return $this->format_duration_approx($timecompleted - $timestarted);
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timetocomplete_mins($row) {
        if (!$this->missionhelper->has_completed($row->instance) || $this->is_incomplete_challenge($row->instance)) {
            return '-';
        }
        $timestarted = $row->instance->get_time_started()->getTimestamp();
        $timecompleted = $row->instance->get_time_completed()->getTimestamp();
        return $this->format_duration_mins(($timecompleted - $timestarted));
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timeended($row) {
        if (!$this->missionhelper->is_ended($row->instance)) {
            return '-';
        }
        return  $this->format_datetime($row->instance->get_time_ended());
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timelost($row) {
        if (!$this->missionhelper->is_a_streak($this->mission)) {
            return '-';
        } else if (!$this->missionhelper->is_ended($row->instance)) {
            return '-';
        }

        // When the deadline passes the streak is lost, typically on the next day. But we lost it on the day of the
        // deadline really. Except when the streak is manually ended by someone, in which case we use the ended date.
        $timelost = min($row->instance->get_deadline()->getTimestamp(), $row->instance->get_time_ended()->getTimestamp());
        return $this->format_datetime(new \DateTimeImmutable('@' . $timelost));
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_timestarted($row) {
        if (!$this->missionhelper->has_started($row->instance)) {
            return '-';
        }

        $content = $this->format_datetime($row->instance->get_time_started());
        if ($this->is_downloading()) {
            return $content;
        }

        $url = null;
        if ($this->iscompulsory && $this->subjectid) {
            $url = $this->get_instance_url($row);
        }
        return $url ? html_writer::link($url, $content) : $content;
    }

    /**
     * Return the URL for the instance.
     *
     * @param stdClass $row Table row.
     * @return moodle_url
     */
    protected function get_instance_url($row) {
        $url = $this->get_url_resolver()->reverse('mission_instance', [
            'missionid' => $row->instance->get_mission()->get_id(),
            'missioninstid' => $row->instance->get_id(),
        ]);
        if ($this->subjectid) {
            $url->param('returnto', 'user');
        }
        return $url;
    }

    /**
     * Convert a instance to keyed table data.
     *
     * @param object $instance The instance object.
     * @return array Will be passed to {@link self::add_data_keyed}.
     */
    protected function prepare_row($instance) {
        // TODO Do not fetch user from here.
        $usersql = $this->userfields->get_sql('', false, '', '', false);
        $user = \core_user::get_user($instance->get_subject_id(), $usersql->selects);
        $data = (object) [
            'user' => $user,
            'instance' => $instance,
        ];
        foreach ($this->userfields->get_required_fields() as $field) {
            $data->{$field} = $user->{$field} ?? '';
        }
        return $data;
    }

    /**
     * Whether this is an incomplete challenge.
     *
     * @param object $instance The instance.
     * @return bool
     */
    protected function is_incomplete_challenge($instance) {
        return $this->missionhelper->is_incomplete($instance) && $this->missionhelper->is_a_challenge($instance);
    }

    /**
     * Render the filters.
     *
     * @return string
     */
    protected function render_filters(): string {

        // No filters when showing a person's instances
        if ($this->subjectid) {
            return '';
        }

        return $this->get_renderer()->render(new mission_instances_filters($this->mission,
            $this->baseurl->params(),
            $this->filterset
        ));
    }

    protected function render_no_results(): string {
        return $this->get_renderer()->zero_state(
            get_string('norecruits', 'block_gearup'),
            get_string('norecruitsmatchingfilters', 'block_gearup'),
            $this->get_renderer()->render_from_template('block_gearup/icons/users', [])
        );
    }

    protected function render_zero_state(): string {
        $title = get_string('norecruits', 'block_gearup');
        $intro = get_string('nobodyrecruitedyet', 'block_gearup');
        if ($this->subjectid) {
            $title = get_string('nousermissions', 'block_gearup');
            $intro = get_string('nousermissionsyet', 'block_gearup');
        }
        return $this->get_renderer()->zero_state($title,
            $intro,
            $this->get_renderer()->render_from_template('block_gearup/icons/users', [])
        );
    }

}
