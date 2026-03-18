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
 * Base testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use backup;
use backup_controller;
use base_setting;
use block_gearup\di;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\has_availability_info_for_user;
use block_manager;
use context_course;
use core\persistent;
use core_component;
use moodle_page;
use moodle_url;
use ReflectionClass;
use restore_controller;

/**
 * Base testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_testcase extends \advanced_testcase {

    /** @var \block_gearup_generator The data generator. */
    protected $generator;

    public function setUp(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->reset_container();
        $this->setTimezone('Australia/Perth', 'Australia/Perth');
        $this->generator = $this->getDataGenerator()->get_plugin_generator('block_gearup');

        if (empty($CFG->phpunit_block_gearup_apihost)) {
            $this->markTestSkipped('Tests cannot be run without a defined Level Up test server.');
        }
        set_config('apihost', $CFG->phpunit_block_gearup_apihost, 'block_gearup');
        try {
            di::get('lm')->process_payload(di::get('api_client')->post('/api/v1/quest/activate', [])->data->payload);
        } catch (\Exception $e) {
            $this->fail('Could not use the Level Up test server.');
        }
    }

    protected function reset_container() {
        \block_gearup\di::set_container(new \block_gearup\local\default_container());
    }

    /**
     * Add the block to a course.
     *
     * @param int $courseid
     */
    protected function add_block_to_course(int $courseid) {
        $page = new moodle_page();
        $page->set_context(context_course::instance($courseid));
        $page->set_pagetype('page-type');
        $page->set_url(new moodle_url('/course/view.php', ['id' => $courseid]));
        $blockmanager = new block_manager($page);
        $blockmanager->add_regions(['gearuptest'], false);
        $blockmanager->set_default_region('gearuptest');
        $blockmanager->add_block('gearup', 'gearuptest', 0, false);
    }

    protected function assert_availability_info_compliance($reflectorclass) {
        $class = $reflectorclass instanceof ReflectionClass ? $reflectorclass : new ReflectionClass($reflectorclass);
        if ($class->hasMethod('get_availability_info')) {
            $this->assertTrue($class->implementsInterface(has_availability_info::class), $class->getName());
        }
        if ($class->hasMethod('get_availability_info_for_context')) {
            $this->assertTrue($class->implementsInterface(has_availability_info_for_context::class), $class->getName());
        }
        if ($class->hasMethod('get_availability_info_for_user')) {
            $this->assertTrue($class->implementsInterface(has_availability_info_for_user::class), $class->getName());
        }
    }

    /**
     * Asser that two persistents are equal.
     *
     * @param persistent $p1 Persistent 1.
     * @param persistent $p2 Persistent 2.
     * @param array $excludefields The fields to exclude from the comparison.
     */
    protected function assert_persistent_equals(persistent $p1, persistent $p2, array $excludefields = []) {
        $this->assertEquals(get_class($p1), get_class($p2));
        $record1 = $p1->to_record();
        $record2 = $p2->to_record();
        foreach ($excludefields as $field) {
            unset($record1->{$field});
            unset($record2->{$field});
        }
        $this->assertEquals($record1, $record2);
    }

    /**
     * Backs a course up.
     *
     * Inspired from tool_log.
     *
     * @param \stdClass $course Course object to backup.
     * @return string ID of backup.
     */
    protected function backup($course) {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );

        $settings = [
            'users' => true,
            'groups' => true,
            'blocks' => true,
            'role_assignments' => false,
            'permissions' => false,
            'files' => false,
            'filters' => false,
            'comments' => false,
            'badges' => false,
            'calendarevents' => false,
            'userscompletion' => false,
            'logs' => false,
            'grade_histories' => false,
            'questionbank' => false,
            'competencies' => false,
            'customfield' => false,
            'contentbankcontent' => false,
            'legacyfiles' => false,
        ];

        $plan = $bc->get_plan();
        foreach ($settings as $name => $value) {
            if (!$plan->setting_exists($name)) {
                continue;
            }
            $setting = $plan->get_setting($name);
            if ($setting->get_status() != base_setting::NOT_LOCKED) {
                $setting->set_status(base_setting::NOT_LOCKED);
            }
            $setting->set_value($value);
        }

        $backupid = $bc->get_backupid();

        $bc->execute_plan();
        $bc->destroy();
        return $backupid;
    }

    /**
     * Restore a course.
     *
     * @param string $backupid Backup ID.
     * @param string $courseid Destination course ID.
     * @param int $target The backup::TARGET_* constant.
     * @param array $settings Backup settings.
     */
    protected function restore($backupid, $courseid, $target, $settings = []) {
        global $USER;

        $rc = new restore_controller($backupid, $courseid, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, $target);

        $settings += [
            'users' => true,
            'groups' => true,
            'blocks' => true,
            'role_assignments' => false,
            'permissions' => false,
            'files' => false,
            'filters' => false,
            'comments' => false,
            'badges' => false,
            'calendarevents' => false,
            'userscompletion' => false,
            'logs' => false,
            'grade_histories' => false,
            'questionbank' => false,
            'competencies' => false,
            'customfield' => false,
            'contentbankcontent' => false,
            'legacyfiles' => false,
        ];

        $plan = $rc->get_plan();
        foreach ($settings as $name => $value) {
            if (!$plan->setting_exists($name)) {
                continue;
            }
            $setting = $plan->get_setting($name);
            if ($setting->get_status() != base_setting::NOT_LOCKED) {
                $setting->set_status(base_setting::NOT_LOCKED);
            }
            $setting->set_value($value);
        }

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
    }

    /**
     * Get the frozen clock.
     *
     * This skips the test if the clock is not mockable.
     *
     * @param int|null $ts
     * @return \frozen_clock
     */
    protected function get_frozen_clock(?int $ts = null): \frozen_clock {
        if (!method_exists($this, 'mock_clock_with_frozen')) {
            $this->markTestSkipped('This test requires the ability to mock clocks.');
        }
        $this->reset_container(); // Just in case our objects cached the time object.
        return $this->mock_clock_with_frozen($ts);
    }

    protected function get_instantiable_reflection_classes($namespace) {
        $classes = array_keys(core_component::get_component_classes_in_namespace('block_gearup', $namespace));
        foreach ($classes as $classname) {
            $class = new ReflectionClass($classname);
            if (!$class->isInstantiable()) {
                continue;
            }
            yield $class;
        }
    }

}
