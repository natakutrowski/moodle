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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\table;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\repository\mission_query;
use block_gearup\local\utils\human_utils;
use html_writer;

/**
 * Table
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class missions extends improved_table {

    /** @var object The helper. */
    protected $missionhelper;
    /** @var object The repo. */
    protected $repository;
    /** @var object LM. */
    protected $lm;
    /** @var mission_query The query. */
    protected $query;
    /** @var string|null The download format. */
    protected $downloadformat;
    /** @var string The download file name. */
    protected $downloadfilename = 'file';

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup_missions');
        $this->missionhelper = di::get('mission_helper');
        $this->repository = di::get('repository');
        $this->lm = di::get('lm');
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
     * Define the query.
     *
     * @param mission_query $query
     * @return self
     */
    public function define_query(mission_query $query): self {
        $this->query = $query;
        return $this;
    }

    /**
     * Initialise the table.
     *
     * @return self
     */
    public function init(): self {
        $required = ['query'];
        foreach ($required as $key) {
            if (!$this->$key) {
                throw new \coding_exception("You must define $key before calling init()");
            }
        }

        // Are we downloading this?
        $this->is_downloading($this->downloadformat, $this->downloadfilename);
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
        $this->sortable(false);
        $this->collapsible(false);

        return $this;
    }

    /**
     * Generate the columns definition.
     *
     * @return array
     */
    protected function generate_columns_definition() {
        $cols = [
            'type' => get_string('missiontype', 'block_gearup'),
            'title' => get_string('missiontitle', 'block_gearup'),

            'recruitcount' => 'Recruits',
            'completedcount' => 'Completed',
            'notcompletedcount' => 'Not completed',
            'completionrate' => 'Completion rate',
            'successrate' => $this->lm->use_challenges() ? 'Success rate' : null,

            'inprogresszerocount' => 'Ongoing without progress',
            'inprogresspartialcount' => 'Ongoing with progress',
            'inprogressaveragerate' => 'Average progress rate',

            'fastestcompletiontime_approx' => 'Fastest completion',
            'fastestcompletiontime_mins' => 'Fastest completion'  . ' (' . get_string('minutes', 'core') . ')',
            'slowestcompletiontime_approx' => 'Slowest completion',
            'slowestcompletiontime_mins' => 'Slowest completion'  . ' (' . get_string('minutes', 'core') . ')',
            'averagecompletiontime_approx' => 'Average completion',
            'averagecompletiontime_mins' => 'Average completion'  . ' (' . get_string('minutes', 'core') . ')',
        ];

        return array_filter($cols, function ($v, $k) {
            return $v !== null;
        }, ARRAY_FILTER_USE_BOTH);
    }

    protected function prepare_query(): void {
        $query = $this->query;

        $query
            ->annotate_recruit_count()
            ->annotate_completed_count()
            ->annotate_not_completed_count()
            ->annotate_inprogress_average_rate()
            ->annotate_inprogress_partial_count()
            ->annotate_inprogress_zero_count()
            ->annotate_success_rate()
            ->annotate_fastest_completion_time()
            ->annotate_slowest_completion_time()
            ->annotate_average_completion_time();

        $query->add_order_by('title', SORT_ASC);
    }

    protected function get_count(): int {
        return $this->repository->count_missions_from_query($this->query);
    }

    protected function get_rows(): iterable {
        return $this->repository->get_missions_from_query($this->query, $this->get_page_start(), $this->get_page_size());
    }

    public function col_recruitcount($row) {
        return $row->annotations->recruit_count ?? 0;
    }

    public function col_completedcount($row) {
        return $row->annotations->completed_count ?? 0;
    }

    public function col_notcompletedcount($row) {
        return $row->annotations->not_completed_count ?? 0;
    }

    public function col_inprogresszerocount($row) {
        return $row->annotations->inprogress_zero_count ?? 0;
    }

    public function col_inprogresspartialcount($row) {
        return $row->annotations->inprogress_partial_count ?? 0;
    }

    public function col_inprogressaveragerate($row) {
        return $this->format_ratio($row->annotations->inprogress_average_rate ?? 0);
    }

    public function col_fastestcompletiontime_approx($row) {
        $time = $row->annotations->fastest_completion_time ?? null;
        return $time === null ? '-' : $this->format_duration_approx($time);
    }

    public function col_fastestcompletiontime_mins($row) {
        $time = $row->annotations->fastest_completion_time ?? null;
        return $time === null ? '-' : $this->format_duration_mins($time);
    }

    public function col_slowestcompletiontime_approx($row) {
        $time = $row->annotations->slowest_completion_time ?? null;
        return $time === null ? '-' : $this->format_duration_approx($time);
    }

    public function col_slowestcompletiontime_mins($row) {
        $time = $row->annotations->slowest_completion_time ?? null;
        return $time === null ? '-' : $this->format_duration_mins($time);
    }

    public function col_averagecompletiontime_approx($row) {
        $time = $row->annotations->average_completion_time ?? null;
        return $time === null ? '-' : $this->format_duration_approx(floor($time));
    }

    public function col_averagecompletiontime_mins($row) {
        $time = $row->annotations->average_completion_time ?? null;
        return $time === null ? '-' : $this->format_duration_mins(floor($time));
    }

    public function col_completionrate($row) {
        if ($this->missionhelper->is_a_challenge($row->mission)) {
            return 'N/A';
        }
        $count = $row->instances_count;
        $ratio = $count > 0 ? ($row->annotations->completed_count ?? 0) / $count : 0;
        return $this->format_ratio($ratio);
    }

    public function col_successrate($row) {
        if (!$this->missionhelper->is_a_challenge($row->mission)) {
            return 'N/A';
        }
        return $this->format_ratio($row->annotations->success_rate);
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
            return html_writer::link($this->get_url_resolver()->reverse('mission', ['missionid' => $row->mission->get_id()]),
                s($title)
            );
        }
        return $title;
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_type($row) {
        $mission = $row->mission;
        if ($this->missionhelper->is_an_achievement($mission)) {
            return get_string('achievement', 'block_gearup');
        } else if ($this->missionhelper->is_a_challenge($mission)) {
            return get_string('challenge', 'block_gearup');
        } else if ($this->missionhelper->is_a_quest($mission)) {
            return get_string('quest', 'block_gearup');
        }
        return '-';
    }

    protected function prepare_row($row) {
        $row->instances_count = ($row->annotations->completed_count ?? 0) + ($row->annotations->not_completed_count ?? 0);
        return $row;
    }

    protected function render_no_results(): string {
        return '';
    }

    protected function render_zero_state(): string {
        return '';
    }

}
