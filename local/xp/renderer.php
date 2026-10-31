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

/**
 * Local renderer.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/xp/renderer.php');

use block_xp\local\world;
use block_xp\local\xp\state;
use local_xp\local\config\default_course_world_config;
use local_xp\local\factory\course_currency_factory;
use local_xp\local\drop\drop;
use local_xp\local\currency\currency;

/**
 * Local renderer class.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_xp_renderer extends block_xp_renderer {
    /** @var object */
    protected $currencyrendercache;

    /**
     * Render currency.
     *
     * @param currency $currency The currency.
     * @return string
     */
    public function currency(currency $currency) {
        $sign = $currency->get_sign();
        $classes = 'sign';

        if ($signurl = $currency->get_sign_url()) {
            $classes .= ' sign-img';
            $sign = html_writer::empty_tag('img', ['src' => $signurl, 'alt' => $sign]);
        } else if ($currency->use_sign_as_superscript()) {
            $classes .= ' sign-sup';
        }

        $o = '';
        $o .= html_writer::tag('span', $sign, ['class' => $classes]);
        return $o;
    }

    /**
     * Renders a drop.
     *
     * @param drop $drop The drop.
     */
    public function drop(drop $drop) {
        // This method existed but was never used, it is left in case something referenced it.
        // We may decide to use it at a later stage.
        return '';
    }

    /**
     * Get the currency factory.
     *
     * We can't inject the factory here or we have circular dependency issues.
     *
     * @return course_currency_factory
     * @deprecated Since XP 21, use currency_factory instead.
     */
    protected function get_course_currency_factory() {
        return \block_xp\di::get('course_currency_factory');
    }

    /**
     * Get the context of the navbar widget.
     *
     * @param world $world The world.
     * @param state $state The user's state.
     * @return array
     */
    protected function get_navbar_widget_context(world $world, state $state) {
        $data = parent::get_navbar_widget_context($world, $state);

        $navigator = \block_xp\di::get('world_navigator_factory')->get_navigator_for_world($world);
        $worldconfig = $world->get_config();

        $teamleaderboardurl = null;
        if ($worldconfig->get('enablegroupladder') != default_course_world_config::GROUP_LADDER_NONE) {
            $teamleaderboardurl = $navigator->get_url('group_ladder');
        }

        return array_merge($data, [
            'linkurl' => $data['linkurl'] ?? ($teamleaderboardurl ? $teamleaderboardurl->out(false) : null),
            'teamleaderboardurl' => $teamleaderboardurl ? $teamleaderboardurl->out(false) : null,
        ]);
    }

    /**
     * Get the progress bar mustache context.
     *
     * @param state $state The renderable object.
     * @param bool $percentagetogo Show the percentage to go.
     * @return array
     */
    protected function get_progress_bar_context(state $state, $percentagetogo = false) {
        $context = parent::get_progress_bar_context($state, $percentagetogo);
        $context['islevelless'] = $state instanceof \local_xp\local\xp\levelless_state
            || $state instanceof \local_xp\local\xp\user_global_state;
        return $context;
    }

    /**
     * Return the group picture.
     *
     * This is a legacy implementation that depends on more recent Moodle
     * versions, do not use. Instead refer to state_with_subject::get_picture,
     * and self::team_picture.
     *
     * @param stdClass $group The group.
     * @return string
     */
    public function group_picture($group) {
        if (!function_exists('get_group_picture_url')) {
            return;
        }
        $pic = get_group_picture_url($group, $group->courseid);
        if (empty($pic)) {
            return;
        }
        return $this->team_picture($pic, format_string($group->name, true, [
            'context' => context_course::instance($group->courseid),
        ]));
    }

    /**
     * Return the team picture.
     *
     * @param moodle_url $url The URL.
     * @param string $alt The alt text.
     * @return string
     */
    public function team_picture(moodle_url $url, $alt = '') {
        return html_writer::empty_tag('img', [
            'src' => $url->out(false),
            'class' => 'grouppic',
            'alt' => $alt,
        ]);
    }

    /**
     * Render the grade filter.
     *
     * This MUST match very closely the render_block_xp_filter method, but had to define
     * this here because the grade filter looks different and does not support the same
     * options.
     *
     * @param renderable $gradefilter The grade filter.
     * @return string
     */
    public function render_grade_filter(renderable $gradefilter) {
        $filter = $gradefilter->filter;

        static $i = 0;
        $o = '';
        $basename = 'gradefilters[' . $i++ . ']';

        $o .= html_writer::start_tag('li', ['class' => 'filter', 'data-basename' => $basename]);

        $content = '';
        $content .= get_string('studentsearnpointsforgradeswhen', 'local_xp');

        $o .= html_writer::tag('p', $content);
        $o .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'value' => $filter->get_id(),
                'name' => $basename . '[id]',
            ]);
        $o .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'value' => $filter->get_sortorder(),
                'name' => $basename . '[sortorder]',
            ]);
        $basename .= '[rule]';

        $o .= html_writer::start_tag('ul', ['class' => 'filter-rules']);
        $o .= $this->render($filter->get_rule(), ['iseditable' => true, 'basename' => $basename]);
        $o .= html_writer::end_tag('ul');
        $o .= html_writer::end_tag('li');

        return $o;
    }

    /**
     * Render the grade filters widget.
     *
     * @param renderable $widget The widget
     * @return void
     */
    public function render_grade_filters_widget(renderable $widget) {
        $containerid = html_writer::random_id();

        // Prepare Javascript.
        $this->page->requires->yui_module('moodle-block_xp-filters', 'Y.M.block_xp.Filters.init', [
            [
                'containerSelector' => '#' . $containerid,
                'filter' => $this->render($widget->filter),
                'rules' => array_reduce($widget->rules, function ($carry, $rule) {
                    $carry[] = [
                        'name' => $rule->name,
                        'info' => !empty($rule->info) ? $rule->info : null,
                        'template' => $this->render($rule->rule, ['iseditable' => true, 'basename' => 'XXXXX']),
                    ];
                    return $carry;
                }, []),
            ],
        ]);
        $this->page->requires->strings_for_js(['pickaconditiontype', 'deleterule', 'deletecondition'], 'block_xp');
        $this->page->requires->strings_for_js(['areyousure'], 'core');

        echo html_writer::start_div('block-xp-filters-wrapper block-xp-grade-filters-wrapper', ['id' => $containerid]);
        echo html_writer::start_tag('ul', ['class' => 'filters-list filters-editable']);

        // We always want an empty rule set in there.
        if (empty($widget->filters)) {
            echo $this->render($widget->filter);
        }

        foreach ($widget->filters as $filter) {
            echo $this->render($filter);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');
    }

    /**
     * Render XP.
     *
     * @param int $points The points.
     * @param currency|null $currency The currency.
     * @return string
     */
    public function xp($points, ?currency $currency = null) {
        $currency ??= \block_xp\di::get('currency_factory')->get_currency_from_context($this->page->context);
        $o = '';
        $o .= html_writer::start_tag('span', ['class' => 'block_xp-xp']);
        $o .= html_writer::tag('span', $this->xp_human($points), ['class' => 'pts']);
        $o .= $this->currency($currency);
        $o .= html_writer::end_tag('span');
        return $o;
    }

    /**
     * Render XP preview.
     *
     * @param int $points The points.
     * @param int|null $unused It was, no longer be used.
     * @param currency|null $currency The currency, overriding the course ID or admin default.
     * @return string
     */
    public function xp_preview($points, $unused = null, ?currency $currency = null) {
        $currency ??= \block_xp\di::get('currency');
        return html_writer::div($this->xp($points, $currency), 'xp-bg-gray-100 dark:xp-bg-gray-800 xp-px-2 xp-py-1 xp-rounded');
    }
}
