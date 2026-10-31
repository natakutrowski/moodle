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

namespace local_xp\local\report;

use block_xp\di;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\utils\user_utils;
use block_xp\local\xp\state_store_with_presence;
use context_course;
use core_reportbuilder\local\entities\course;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use html_writer;
use lang_string;
use local_xp\local\world\global_world;
use stdClass;

/**
 * User source courses report.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_source_courses extends system_report {
    /**
     * Initialise the report.
     */
    protected function initialise(): void {
        $entity = new course();
        $course = $entity->get_table_alias('course');
        $this->add_entity($entity);
        $this->set_main_table('local_xp_source', 'source');
        $this->add_base_fields('source.id');
        $this->add_join('JOIN {context} target ON target.id = source.sourcecontextid AND target.contextlevel = ' . CONTEXT_COURSE);
        $this->add_join("JOIN {course} {$course} ON {$course}.id = target.instanceid");
        $this->add_join("JOIN {block_xp} xp ON xp.courseid = {$course}.id");
        $this->add_join('JOIN {user} u ON u.id = xp.userid AND u.deleted = 0');
        $this->add_base_condition_simple('source.contextid', $this->get_context()->id);
        $this->add_base_condition_simple('xp.userid', $this->get_report_persistent()->get('itemid'));

        $this->add_column((new column('name', new lang_string('course', 'core'), 'course'))
            ->add_fields("{$course}.fullname, {$course}.shortname, {$course}.visible, {$course}.id AS courseid")
            ->add_fields(user_utils::name_fields('u'))
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_callback(static function ($value, stdClass $row): string {
                $context = context_course::instance($row->courseid);
                $options = ['context' => $context];
                $name = format_string($value, true, $options);
                $world = di::get('context_world_factory')->get_world_from_context($context);
                $permissions = $world->get_access_permissions();
                if ($permissions instanceof access_report_permissions && $permissions->can_access_report()) {
                    $url = di::get('world_navigator_factory')->get_navigator_for_world($world)->get_url('report');
                    $url->param('term', fullname($row));
                    $name = html_writer::link($url, $name);
                }
                return $name . html_writer::div(format_string($row->shortname, true, $options), 'small text-muted');
            }));

        $this->add_column((new column('xp', new lang_string('points', 'block_xp'), 'course'))
            ->add_field('xp.xp')
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_callback(static function ($value): string {
                return di::get('renderer')->xp($value);
            }));

        $this->set_initial_sort_column('course:name', SORT_ASC);
    }

    /**
     * Check access, including AJAX requests.
     *
     * @return bool
     */
    protected function can_view(): bool {
        $world = di::get('context_world_factory')->get_world_from_context($this->get_context());
        $permissions = $world->get_access_permissions();
        $store = $world->get_store();
        $userid = $this->get_report_persistent()->get('itemid');
        return $world instanceof global_world
            && $this->get_context()->id == $world->get_context()->id
            && $permissions instanceof access_report_permissions
            && $permissions->can_access()
            && $permissions->can_access_report()
            && $store instanceof state_store_with_presence
            && $store->has($userid)
            && user_utils::is_valid_target($this->get_context(), $userid);
    }

    /**
     * Get the report name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('sources', 'block_xp');
    }
}
