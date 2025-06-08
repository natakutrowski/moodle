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
 * Missions table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use block_gearup\local\mission\mission;
use block_gearup\local\routing\url_resolver;
use renderer_base;
use flexible_table;
use context;
use html_writer;

/**
 * Missions table.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated Since Quest v1.3.0
 */
class missions_table extends flexible_table {

    /** @var object The mission helper. */
    protected $missionhelper;
    /** @var context The context. */
    protected $context;
    /** @var object The repository. */
    protected $repository;
    /** @var renderer_base The Renderer. */
    protected $renderer = null;
    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param object $repository The resolver.
     * @param renderer_base $renderer The renderer.
     * @param url_resolver $urlresolver The URL resolver.
     * @param context $context The context.
     */
    public function __construct($repository, renderer_base $renderer, url_resolver $urlresolver,
            $missionhelper, ?context $context = null) {
        parent::__construct('block_gearup_missions');

        $this->repository = $repository;
        $this->urlresolver = $urlresolver;
        $this->renderer = $renderer;
        $this->context = $context;
        $this->missionhelper = $missionhelper;

        $columnsdefinition = [
            'title' => get_string('missiontitle', 'block_gearup'),
            'type' => get_string('missiontype', 'block_gearup'),
            'assignmentbehaviour' => '',
            'actions' => '',
        ];

        // Define columns, and headers.
        $columns = array_keys($columnsdefinition);
        $headers = array_map(function($header) {
            return (string) $header;
        }, array_values($columnsdefinition));
        $this->define_columns($columns);
        $this->define_headers($headers);

        // Define various table settings.
        $this->sortable(false);
        $this->collapsible(false);
    }

    /**
     * Output the table.
     */
    public function out($pagesize) {
        $this->setup();
        $this->pagesize($pagesize, $this->repository->count_missions($this->context));

        $missions = $this->repository->get_missions($this->context, $this->get_page_start(), $this->get_page_size());
        foreach ($missions as $mission) {
            $this->add_data_keyed($this->mission_to_keyed_data($mission));
        }

        $this->finish_output();
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
    public function col_assignmentbehaviour($row) {
        $mission = $row->mission;
        if (!$this->missionhelper->is_a_quest($row->mission)) {
            return '';
        }
        if ($this->missionhelper->is_discoverable($mission)) {
            return get_string('discoverablequest', 'block_gearup');
        } else if ($this->missionhelper->is_compulsory($mission)) {
            return get_string('compulsoryquest', 'block_gearup');
        } else if ($this->missionhelper->is_optional($mission)) {
            return get_string('optionalquest', 'block_gearup');
        }
        return '';
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

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_title($row) {
        $title = $row->mission->get_title();
        if (!$this->is_downloading()) {
            return html_writer::link
                ($this->urlresolver->reverse('mission', ['missionid' => $row->mission->get_id()]),
                s($title)
            );
        }
        return $title;
    }

    /**
     * Convert a mission to keyed table data.
     *
     * @param mission $mission The rank object.
     * @return array Will be passed to {@link self::add_data_keyed}.
     */
    protected function mission_to_keyed_data($mission) {
        $data = (object) [
            'mission' => $mission,
        ];
        return [
            'title' => $this->col_title($data),
            'type' => $this->col_type($data),
            'assignmentbehaviour' => $this->col_assignmentbehaviour($data),
            'actions' => $this->col_actions($data),
        ];
    }

}
