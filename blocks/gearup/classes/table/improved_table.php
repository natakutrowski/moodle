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
use block_gearup\local\routing\url_resolver;
use block_gearup\local\utils\human_utils;
use core\output\notification;
use flexible_table;
use html_writer;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class improved_table extends flexible_table {

    /** @var bool Whether has a dropdown. */
    private $hasdropdown = false;
    /** @var \block_gearup\output\renderer The renderer. */
    private $renderer;
    /** @var url_resolver The URL resolver. */
    private $urlresolver;

    /**
     * Get the total number of records.
     *
     * @return int
     */
    abstract protected function get_count(): int;

    /**
     * Return a generator of rows.
     *
     * @return iterable
     */
    abstract protected function get_rows();

    /**
     * Define the base URL.
     *
     * @param moodle_url $url
     * @return static
     */
    public function define_baseurl($url): self {
        parent::define_baseurl($url);
        return $this;
    }

    /**
     * Define whether the table contains dropdowns.
     *
     * @param bool $hasdropdown
     * @return static
     */
    public function define_has_dropdown($hasdropdown): self {
        $this->hasdropdown = (bool) $hasdropdown;
        return $this;
    }

    /**
     * Define the URL resolver.
     *
     * @param url_resolver $urlresolver
     * @return static
     */
    public function define_url_resolver(url_resolver $urlresolver): self {
        $this->urlresolver = $urlresolver;
        return $this;
    }

    /**
     * Format a date and time.
     *
     * @param \DateTimeImmutable $dt
     * @return string
     */
    protected function format_datetime(\DateTimeImmutable $dt) {
        if ($this->is_downloading()) {
            return userdate($dt->getTimestamp(), '%Y-%m-%d %H:%M:%S', 99, false, false);
        }
        return userdate_htmltime($dt->getTimestamp(), get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Format a dropdown.
     *
     * @param action_menu_link[] The actions.
     * @return string
     */
    protected function format_dropdown(array $actions) {
        return $this->get_renderer()->control_menu($actions);
    }

    /**
     * Format a duration.
     *
     * @param int $secs The seconds.
     * @return string
     */
    protected function format_duration_approx(int $secs) {
        $value = human_utils::duration_approx($secs);
        if ($value < 60) {
            return $value . ' ' . get_string('seconds', 'core');
        }
        return format_time($value);
    }

    /**
     * Format a duration.
     *
     * @param int $secs The seconds.
     * @return string
     */
    protected function format_duration_mins(int $secs) {
        return (string) floor(($secs) / 60);
    }

    /**
     * Format a ratio.
     *
     * @param float $ratio The ratio.
     * @return string
     */
    protected function format_ratio($ratio) {
        if ($this->is_downloading()) {
            return $ratio;
        }
        return human_utils::percentage($ratio) . '%';
    }

    /**
     * Get the URL resolver.
     *
     * @return \block_gearup\output\renderer The renderer.
     */
    public function get_renderer() {
        if (!$this->renderer) {
            $this->renderer = di::get('renderer');
        }
        return $this->renderer;
    }

    /**
     * Get the URL resolver.
     *
     * @return url_resolver
     */
    public function get_url_resolver() {
        if (!$this->urlresolver) {
            throw new \coding_exception("You must define the URL resolver before using it.");
        }
        return $this->urlresolver;
    }

    /**
     * Init.
     *
     * The method in which we should be doing the setting up of the table which
     * would typically take place in the constructor. This gives us a chance to
     * assign other objects.
     *
     * @return static
     */
    abstract protected function init(): self;

    /**
     * Initial bars are not allowed.
     *
     * @param bool $bool Whether allowed.
     */
    final public function initialbars($bool) {
    }

    /**
     * Whether the table is being filtered.
     *
     * @return bool
     */
    protected function is_filtered() {
        if (!$this->filterset) {
            return false;
        }
        $filters = $this->filterset->get_filters();
        return !empty($filters);
    }

    /**
     * Output the table.
     */
    final public function out($pagesize) {
        $this->init();
        $this->setup();
        $this->prepare_query();

        $count = $this->get_count();
        $this->pagesize($pagesize, $count);

        if ($count > 0) {
            foreach ($this->get_rows() as $row) {
                $this->add_data_keyed($this->format_row($this->prepare_row($row)));
            }
        }

        $this->finish_output();
    }

    /**
     * Prepare the query.
     *
     * To be overriden by the implementing class to build the query if that is necessary.
     *
     * @return void
     */
    protected function prepare_query(): void {
        // We must keep this empty as the child does not need to call it.
    }

    /**
     * Prepare the row.
     *
     * This method is called for every row before it is passed the default {@link self::format_row}.
     *
     * @param mixed $row The row.
     * @return object|array The rows as object.
     */
    protected function prepare_row($row) {
        // We must not do anything else here, it is purely for the implementing class use.
        return $row;
    }

    /**
     * When there is nothing to show.
     *
     * @return void
     */
    final public function print_nothing_to_display() {
        if ($this->is_filtered()) {
            echo $this->render_filters();
            echo $this->render_no_results();
            return;
        }
        echo $this->render_zero_state();
    }

    /**
     * Method to render after the output is complete.
     *
     * @return string
     */
    protected function render_after_finish(): string {
        return '';
    }

    /**
     * Method to render the table filters, if any.
     *
     * @return string
     */
    protected function render_filters(): string {
        return '';
    }

    /**
     * Render when there is no results.
     *
     * @return string
     */
    protected function render_no_results(): string {
        $notification = new notification(get_string('nothingtodisplay', 'core'), notification::NOTIFY_INFO, false);
        return $this->get_renderer()->render($notification);
    }

    /**
     * Render when there is no results.
     *
     * @return string
     */
    final protected function render_status_bar(): string {
        $showingfrom = $this->currpage * $this->pagesize + 1;
        $showingto = min($showingfrom + $this->pagesize - 1, $this->totalrows);

        $o = '';
        $o .= html_writer::start_div('gu-flex gu-my-2 gu-text-xs');
        $o .= html_writer::div(get_string('showingfromtoof', 'block_gearup', [
            'from' => $showingfrom,
            'to' => $showingto,
            'of' => $this->totalrows,
        ]), 'gu-text-gray-500 gu-grow');
        $o .= html_writer::div($this->render_reset_button());
        $o .= html_writer::end_div();

        return $o;
    }

    /**
     * Render a zero state.
     *
     * @return string
     */
    protected function render_zero_state(): string {
        return $this->render_no_results();
    }

    /**
     * Save preferences.
     *
     * Override to avoid debugging when downloading...
     *
     * @param array $oldprefs The old preferences.
     */
    protected function save_preferences($oldprefs): void {
        if ($this->is_downloading()) {
            return;
        }
        parent::save_preferences($oldprefs);
    }

    /**
     * Start HTML.
     *
     * Overridden to get rid of a lot of stuff that is otherwise being outputted at the top of table.
     */
    final public function start_html() {
        echo $this->get_dynamic_table_html_start();
        echo $this->render_filters();
        echo $this->render_status_bar();
        $this->wrap_html_start();
        echo html_writer::start_tag('div', ['class' => $this->hasdropdown ? '' : 'no-overflow']);
        echo html_writer::start_tag('table', $this->attributes) . $this->render_caption();
    }

    /**
     * Finish HTML.
     *
     * Overridden to support calling something after everything is done.
     */
    final public function finish_html() {
        parent::finish_html();
        echo $this->render_after_finish();
    }

    /**
     * Convenience method to download the table.
     *
     * The out() method is kinda disgusting, so we just made this one to
     * hide the ugliness into a more descriptive method.
     *
     * @return void
     */
    final public function send_file() {
        if (!$this->is_downloading()) {
            throw new \coding_exception('What are you doing?');
        }
        \core\session\manager::write_close();
        $this->out(0, false);   // Page size is irrelevant when downloading.
        die();
    }

}
