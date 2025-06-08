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

namespace block_gearup\local\setting;

use block_gearup\di;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\quest;
use block_gearup\local\mission\streak;
use block_gearup\output\tracker;
use core_collator;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/adminlib.php');

/**
 * Setting.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracker_missions_order extends \admin_setting {

    /**
     * Constructor.
     */
    public function __construct() {
        $orders = array_unique(array_map(function($keys) {
            if (!di::get('lm')->use_challenges()) {
                $keys = array_diff($keys, [challenge::class]);
            }
            return implode(',', $keys);
        }, [
            [challenge::class, quest::class, achievement::class],
            [challenge::class, achievement::class, quest::class],
            [quest::class, challenge::class, achievement::class],
            [quest::class, achievement::class, challenge::class],
            [achievement::class, quest::class, challenge::class],
            [achievement::class, challenge::class, quest::class],
        ]));

        $options = array_reduce($orders, function($carry, $keys) {
            $keys = explode(',', $keys);
            $label = implode(', ', array_map(function($key) {
                if ($key === achievement::class) {
                    return get_string('achievements', 'block_gearup');
                } else if ($key === challenge::class) {
                    return get_string('challenges', 'block_gearup');
                } else if ($key === quest::class) {
                    return get_string('quests', 'block_gearup');
                } else if ($key === streak::class) {
                    return get_string('streaks', 'block_gearup');
                }
                return '?';
            }, $keys));

            $value = implode(',', $keys);
            if (empty($carry)) {
                $value = ''; // The first value is the default value.
            }

            $carry[$value] = $label;
            return $carry;
        }, []);

        parent::__construct('block_gearup/trackermissionsorder', get_string('trackermissionsorder', 'block_gearup'),
            get_string('trackermissionsorder_desc', 'block_gearup'), '', $options);
    }

    /**
     * Get the normalised value.
     *
     * @return array
     */
    public function get_setting() {
        $value = $this->config_read($this->name);
        return tracker::normalise_missions_order_setting($value);
    }

    /**
     * Write setting.
     *
     * @param array $data The data.
     * @return string Empty if no errors.
     */
    public function write_setting($data) {
        if (!is_array($data) || empty($data)) {
            return '';
        }

        $data = array_filter($data, function($type) {
            $classtype = 'block_gearup\\local\\mission\\' . $type;
            return interface_exists($classtype);
        }, ARRAY_FILTER_USE_KEY);
        core_collator::asort($data, core_collator::SORT_NUMERIC);
        $value = implode(',', array_keys($data));

        $result = $this->config_write($this->name, $value);
        return ($result ? '' : get_string('errorsetting', 'admin'));
    }


    /**
     * Create form.
     *
     * @param array $data The data.
     * @param string $query The search query.
     * @return string
     */
    public function output_html($data, $query='') {
        global $OUTPUT;

        $defaultinfo = implode(', ', array_map(function($type) {
            return static::get_type_label($type);
        }, tracker::get_default_missions_order()));

        // Prepare context.
        $sections = [];
        foreach ($data as $idx => $type) {
            $label = static::get_type_label($type);
            $sections[] = [
                'n' => $idx,
                'type' => str_replace('block_gearup\\local\\mission\\', '', $type),
                'isfirst' => $idx === 0,
                'label' => $label,
                'options' => array_map(function($i) use ($idx) {
                    return [
                        'value' => $i,
                        'label' => $i + 1,
                        'selected' => $i === $idx,
                    ];
                }, range(0, count($data) - 1)),
            ];
        }

        $context = (object) [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'readonly' => $this->is_readonly(),
            'sections' => $sections,
        ];

        $inputid = $this->get_id() . reset($sections)['n'];
        $element = $OUTPUT->render_from_template('block_gearup/admin/setting/tracker_missions_order', $context);
        return format_admin_setting($this, $this->visiblename, $element, $this->description, $inputid, '', $defaultinfo, $query);
    }

    /**
     * Get the type label.
     *
     * @param string $type The type.
     * @return string
     */
    protected static function get_type_label($type) {
        if ($type === 'achievement') {
            return get_string('achievements', 'block_gearup');
        } else if ($type === 'challenge') {
            return get_string('challenges', 'block_gearup');
        } else if ($type === 'quest') {
            return get_string('quests', 'block_gearup');
        } else if ($type === 'streak') {
            return get_string('streaks', 'block_gearup');
        }
        return '?';
    }

}
