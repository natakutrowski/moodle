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

namespace block_gearup\table\streak;

use block_gearup\di;
use block_gearup\local\repository\mission_query;
use block_gearup\local\routing\url_resolver;
use block_gearup\table\improved_table;
use renderer_base;
use context;
use html_writer;

/**
 * Streaks table.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class listing extends improved_table {

    /** @var object The mission helper. */
    protected $missionhelper;
    /** @var mission_query The query. */
    protected $query;
    /** @var \block_gearup\local\repository\repository The repository. */
    protected $repository;
    /** @var renderer_base The Renderer. */
    protected $renderer = null;

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_streaks');
    }

    public function init(): self {
        $this->repository = di::get('repository');
        $this->renderer = di::get('renderer');
        $this->missionhelper = di::get('mission_helper');

        $columnsdefinition = [
            'title' => get_string('missiontitle', 'block_gearup'),
            'recruits' => get_string('recruits', 'block_gearup'),
            'streak' => get_string('streak', 'block_gearup'),
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
        $this->sortable(false);
        $this->collapsible(false);

        return $this;
    }

    public function define_query(mission_query $query): self {
        $this->query = $query;
        return $this;
    }

    protected function get_count(): int {
        return $this->repository->count_missions_from_query($this->query);
    }

    protected function get_rows() {
        return $this->repository->get_missions_from_query($this->query);
    }

    protected function prepare_query(): void {
        $this->query
            ->annotate_recruit_count()
            ->annotate_highest_counter()
            ->annotate_highest_counter_current()
            ->add_order_by_state_natural(SORT_ASC)
            ->add_order_by('title', SORT_ASC)
            ->add_order_by('timecreated', SORT_ASC);
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
    public function col_recruits($row) {
        $content = $row->annotations->recruit_count;
        if (!$this->is_downloading()) {
            $url = $this->get_url_resolver()->reverse('mission_users', ['missionid' => $row->mission->get_id()]);
            return html_writer::link($url, $content);
        }
        return $content;
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_streak($row) {
        if ($this->missionhelper->is_archived($row->mission)) {
            return \html_writer::tag('abbr',
                "{$row->annotations->highest_counter}",
                ['title' => get_string('beststreak', 'block_gearup')]
            );
        }
        return \html_writer::tag('abbr',
            "{$row->annotations->highest_counter_current} / {$row->annotations->highest_counter}",
            ['title' => get_string('currentstreak', 'block_gearup') . ' / ' . get_string('beststreak', 'block_gearup')]
        );
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_title($row) {
        $title = $row->mission->get_title();
        if (!$this->is_downloading()) {
            $suffix = '';
            if ($this->missionhelper->is_archived($row->mission)) {
                $suffix .= html_writer::span(get_string('archived', 'block_gearup'), 'gu-ml-1 badge gu-badge-secondary');
            }
            return html_writer::link($this->get_url_resolver()->reverse('mission', ['missionid' => $row->mission->get_id()]),
                s($title)
            ) . $suffix;
        }
        return $title;
    }

}
