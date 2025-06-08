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
 * Mission users table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\table\streak;

use block_gearup\local\mission\mission;
use block_gearup\local\repository\user_query;
use block_gearup\local\utils\user_utils;
use block_gearup\output\mission_instances_filters;
use block_gearup\table\improved_table;
use html_writer;

/**
 * Mission users table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users extends improved_table {

    /** @var mission The mission. */
    protected $mission;
    /** @var object The helper. */
    protected $missionhelper;
    /** @var object The repository. */
    protected $repository;
    /** @var user_query The query. */
    protected $query;
    /** @var bool Whether we are using a wide view. */
    protected $iswideview = false;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_streak_users');
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
     * @param object $query
     * @return self
     */
    public function define_query(user_query $query): self {
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
     * Init.
     */
    public function init(): self {
        $required = ['mission', 'missionhelper', 'repository', 'query'];
        foreach ($required as $key) {
            if (!$this->$key) {
                throw new \coding_exception("You must define $key before calling init()");
            }
        }

        $idfields = user_utils::get_visible_identity_fields($this->mission->get_context());
        $columnsdefinition = [
            'fullname' => get_string('fullname', 'core'),
        ];
        if ($this->iswideview) {
            $columnsdefinition += $idfields;
        }
        $columnsdefinition += [
            'current' => [
                'label' => get_string('currentstreak', 'block_gearup'),
                'help' => new \help_icon('currentstreak', 'block_gearup'),
            ],
            'best' => [
                'label' => get_string('beststreak', 'block_gearup'),
                'help' => new \help_icon('beststreak', 'block_gearup'),
            ],
            'lost' => [
                'label' => get_string('loststreak', 'block_gearup'),
                'help' => new \help_icon('loststreak', 'block_gearup'),
            ],
        ];

        // Define columns, and headers.
        $columns = array_keys($columnsdefinition);
        $headers = array_map(function($header) {
            return (string) (is_array($header) ? $header['label'] : $header);
        }, array_values($columnsdefinition));
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_help_for_headers(array_map(function($header) {
            return is_array($header) ? $header['help'] : null;
        }, array_values($columnsdefinition)));

        // Define various table settings.
        $this->sortable(true, 'firstname', SORT_ASC);
        $this->collapsible(false);
        $this->initialbars(false);

        return $this;
    }

    protected function prepare_query(): void {
        global $USER;

        $this->query
            ->annotate_mission_instance_counter_best()
            ->annotate_mission_instance_counter_latest()
            ->annotate_mission_instance_counter_reset_count();

        ['sortby' => $sortby, 'sortorder' => $sortorder] = $this->get_primary_sort_order();
        if ($sortby === 'best') {
            $sortby = 'mission_instance_counter_best';
        } else if ($sortby === 'current') {
            $sortby = 'mission_instance_counter_latest';
        } else if ($sortby === 'lost') {
            $sortby = 'mission_instance_counter_reset_count';
        }
        $this->query->add_order_by($sortby, $sortorder);

        $this->query->set_mission_id($this->mission->get_id());

        // Apply filterset.
        if ($filterset = $this->get_filterset()) {
            if ($filterset->has_filter('term')) {
                $this->query->filter_by_term($filterset->get_filter('term')->current());
            }
            if ($filterset->has_filter('groupid')) {
                // Note that when we introduce dynamic tables, it would be possible for the user to remove
                // the groupid entirely and thus get access to all users. At the moment it is not a problem
                // because we do not allow users who cannot view all participants to get here, but that is
                // something to consider in the future.
                $groupid = $filterset->get_filter('groupid')->current();
                if (user_utils::can_select_group($this->mission->get_context(), $USER->id, $groupid)) {
                    $this->query->set_group_id($groupid);
                }
            }
        }
    }

    protected function get_count(): int {
        return $this->repository->count_users_from_query($this->query);
    }

    protected function get_rows(): iterable {
        return $this->repository->get_users_from_query($this->query, $this->get_page_start(), $this->get_page_size());
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_fullname($row) {
        return $this->get_renderer()->render_from_template('block_gearup/table/col_fullname', [
            'fullname' => fullname($row),
            'picurl' => $this->get_renderer()->get_user_picture($row),
            'linkurl' => $this->get_url_resolver()->reverse('mission_user', [
                'missionid' => $this->mission->get_id(),
                'userid' => $row->id,
            ]),
        ]);
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_best($row) {
        return $row->mission_instance_counter_best ?: '-';
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_current($row) {
        return $row->mission_instance_counter_latest ?: '-';
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_lost($row) {
        return $row->mission_instance_counter_reset_count ? $row->mission_instance_counter_reset_count . 'x' : '-';
    }

    /**
     * Render the filters.
     *
     * @return string
     */
    protected function render_filters(): string {
        return $this->get_renderer()->render((new mission_instances_filters($this->mission, $this->baseurl->params(),
            $this->filterset))->with_state(false));
    }

    protected function render_no_results(): string {
        return $this->get_renderer()->zero_state(
            get_string('norecruits', 'block_gearup'),
            get_string('norecruitsmatchingfilters', 'block_gearup'),
            $this->get_renderer()->render_from_template('block_gearup/icons/users', [])
        );
    }

    protected function render_zero_state(): string {
        return $this->get_renderer()->zero_state(
            get_string('norecruits', 'block_gearup'),
            get_string('nobodyrecruitedyet', 'block_gearup'),
            $this->get_renderer()->render_from_template('block_gearup/icons/users', [])
        );
    }

}
