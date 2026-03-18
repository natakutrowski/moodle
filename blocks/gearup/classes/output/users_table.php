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

namespace block_gearup\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use block_gearup\local\repository\repository;
use block_gearup\local\routing\url_resolver;
use renderer_base;
use context;
use core\output\notification;
use html_writer;
use table_sql;

/**
 * Table.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @deprecated Since Quest 1.4, use \block_gearup\table\users instead.
 */
class users_table extends table_sql {

    /** @var context The context. */
    protected $context;
    /** @var repository The repo. */
    protected $repo;
    /** @var renderer_base The renderer. */
    protected $renderer = null;
    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param context $context The context.
     * @param renderer_base $renderer The renderer.
     * @param url_resolver $urlresolver The URL resolver.
     */
    public function __construct(context $context, renderer_base $renderer, url_resolver $urlresolver, repository $repo) {
        parent::__construct('block_gearup_users');

        debugging('The class users_table is deprecated, use \block_gearup\table\users instead.', DEBUG_DEVELOPER);

        $this->context = $context;
        $this->urlresolver = $urlresolver;
        $this->repo = $repo;
        $this->renderer = $renderer;

        $columnsdefinition = [
            'fullname' => get_string('fullname', 'core'),
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

        $sqlfields = 'u.*';
        $sqlfrom = '{user} u';
        $sqlwhere = 'u.deleted = 0
                 AND EXISTS (SELECT mi.id
                               FROM {block_gearup_mission_inst} mi
                               JOIN {block_gearup_mission} m
                                 ON m.id = mi.missionid
                              WHERE m.contextid = ?
                                AND mi.subjectid = u.id)';
        $sqlparams = [$this->context->id];
        $this->set_sql($sqlfields, $sqlfrom, $sqlwhere, $sqlparams);

        // Define various table settings.
        $this->sortable(true, 'lastname');
        $this->no_sorting('missions');
        $this->no_sorting('actions');
        $this->collapsible(false);
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
        return html_writer::link(
            $this->urlresolver->reverse('user', [
                'userid' => $row->id,
            ]),
            fullname($row)
        );
    }

    /**
     * Format the column.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_missions($row) {
        return $this->repo->count_user_missions($row->id, $this->context);
    }

    /**
     * This function is not part of the public api.
     */
    public function print_nothing_to_display() {
        $hasfilters = false;
        $showfilters = false;

        if ($this->can_be_reset()) {
            $hasfilters = true;
            $showfilters = true;
        }

        // Render button to allow user to reset table preferences, and the initial bars if some filters
        // are used. If none of the filters are used and there is nothing to display, then it's empty.
        echo $this->render_reset_button();
        if ($showfilters) {
            $this->print_initials_bar();
        }

        if ($hasfilters) {
            $notification = new notification(get_string('nothingtodisplay', 'core'), notification::NOTIFY_INFO, false);
            echo $this->renderer->render($notification);
            return;
        }

        echo $this->renderer->zero_state(
            get_string('norecruits', 'block_gearup'),
            '...',
            $this->renderer->render_from_template('block_gearup/icons/users', [])
        );
    }
}
