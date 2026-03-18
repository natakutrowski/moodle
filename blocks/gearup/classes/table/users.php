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
 * Table.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\table;

use block_gearup\local\repository\repository;
use block_gearup\local\repository\user_query;
use block_gearup\local\utils\user_utils;
use block_gearup\output\users_filters;
use context;
use context_course;

/**
 * Table.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users extends improved_table {

    /** @var context The context. */
    protected $context;
    /** @var repository The repo. */
    protected $repository;

    /** @var user_query The query. */
    protected $query;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_users');
    }

    /**
     * Define the context.
     *
     * @param context $context
     * @return self
     */
    public function define_context(context $context): self {
        $this->context = $context;
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
     * Init.
     */
    public function init(): self {
        $required = ['repository', 'context'];
        foreach ($required as $key) {
            if (!$this->$key) {
                throw new \coding_exception("You must define $key before calling init()");
            }
        }

        $idfields = user_utils::get_visible_identity_fields($this->context);
        $columnsdefinition = [
            'fullname' => get_string('fullname', 'core'),
        ];
        $columnsdefinition += $idfields;
        $columnsdefinition += [
            'missions' => get_string('missions', 'block_gearup'),
            'actions' => '',
        ];

        // Define columns, and headers.
        $columns = array_keys($columnsdefinition);
        $headers = array_map(function ($header) {
            return (string) $header;
        }, array_values($columnsdefinition));
        $this->define_columns($columns);
        $this->define_headers($headers);

        // Define various table settings.
        $this->sortable(true, 'lastname');
        $this->no_sorting('missions');
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->initialbars(false);

        return $this;
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_actions($row) {
        return '';
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
            'linkurl' => $this->get_url_resolver()->reverse('user', [
                'userid' => $row->id,
            ])->out(false),
            'picurl' => $this->get_renderer()->get_user_picture($row)->out(false),
        ]);
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_missions($row) {
        return $row->mission_count;
    }

    protected function prepare_query(): void {
        global $USER;

        $query = (new user_query($this->context))
            ->set_context_id((int) $this->context->id)
            ->annotate_mission_count();

        ['sortby' => $sortby, 'sortorder' => $sortorder] = $this->get_primary_sort_order();
        $query->add_order_by($sortby, $sortorder);

        if ($filterset = $this->get_filterset()) {
            if ($filterset->has_filter('term')) {
                $query->filter_by_term($filterset->get_filter('term')->current());
            }

            if ($filterset->has_filter('groupid')) {
                // Note that when we introduce dynamic tables, it would be possible for the user to remove
                // the groupid entirely and thus get access to all users. At the moment it is not a problem
                // because we do not allow users who cannot view all participants to get here, but that is
                // something to consider in the future.
                $groupid = $filterset->get_filter('groupid')->current();
                if (user_utils::can_select_group($this->context, $USER->id, $groupid)) {
                    $query->set_group_id($groupid);
                }
            }
        }

        $this->query = $query;
    }

    protected function get_count(): int {
        return $this->repository->count_users_from_query($this->query);
    }

    protected function get_rows() {
        return $this->repository->get_users_from_query($this->query, $this->get_page_start(), $this->get_page_size());
    }

    protected function render_filters(): string {
        return $this->get_renderer()->render(new users_filters($this->context, $this->baseurl->params(), $this->filterset));
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
