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

namespace block_gearup\output;

use block_gearup\di;
use block_gearup\local\routing\url_resolver;
use context;
use core\output\named_templatable;
use renderable;
use renderer_base;
use moodle_url;

/**
 * Tracker.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracker implements renderable, named_templatable {

    /** @var context The context. */
    protected $context;
    /** @var moodle_url|null The page URL. */
    protected $pageurl;
    /** @var int The user ID. */
    protected $userid;
    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param context $context The context.
     * @param int $userid The user ID.
     * @param moodle_url|null $pageurl The page URL.
     */
    public function __construct(context $context, int $userid, ?url_resolver $urlresolver, ?moodle_url $pageurl) {
        $this->context = $context;
        $this->userid = $userid;
        $this->pageurl = $pageurl;
        $this->urlresolver = $urlresolver;
    }

    public function export_for_template(renderer_base $output) {
        $exporter = di::get('exporter_factory')->get_tracker_exporter($this->userid, [
            'context' => $this->context,
            'pageurl' => $this->pageurl,
            'url_resolver' => $this->urlresolver,
        ]);
        return $exporter->export($output);
    }

    public function get_template_name(renderer_base $renderer): string {
        return 'block_gearup/tracker';
    }

    /**
     * Get the default order.
     *
     * @return string[] List of short types.
     */
    public static function get_default_missions_order() {
        return array_values(array_filter([
            di::get('lm')->use_streaks() ? 'streak' : null,
            di::get('lm')->use_challenges() ? 'challenge' : null,
            'quest',
            'achievement',
        ]));
    }

    /**
     * Get the final order.
     *
     * @return string[] List of class types.
     */
    public static function get_missions_order() {
        $config = get_config('block_gearup', 'trackermissionsorder') ?: null;
        $order = static::normalise_missions_order_setting($config);
        return array_map(function($type) {
            return 'block_gearup\\local\\mission\\' . $type;
        }, $order);
    }

    /**
     * Normalise the setting.
     *
     * @param string|null|false $rawdata The raw config data.
     * @return array[] List of short types.
     */
    public static function normalise_missions_order_setting($rawdata) {
        $defaultorder = static::get_default_missions_order();
        if (!$rawdata) {
            return $defaultorder;
        }

        // Convert the config to values.
        $order = array_values(array_filter(array_map(function($type) use ($defaultorder) {
            $classtype = 'block_gearup\\local\\mission\\' . $type;
            if (!interface_exists($classtype) || !in_array($type, $defaultorder)) {
                return null;
            }
            return $type;
        }, explode(',', $rawdata))));

        // Check the missing types and restore them at their default positions.
        $missing = array_diff($defaultorder, $order);
        foreach ($missing as $type) {
            $defaultposition = array_search($type, $defaultorder);
            array_splice($order, $defaultposition, 0, [$type]);
        }

        return $order;
    }

}
