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

namespace block_gearup;

use block_gearup\tests\base_testcase;

/**
 * Test case.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tasks_test extends base_testcase {

    /**
     * Tasks provider.
     *
     * @return array
     */
    public static function tasks_provider(): array {
        global $CFG;

        $tasks = [];
        include($CFG->dirroot . '/blocks/gearup/db/tasks.php');
        return array_map(function($task) {
            return [$task['classname']];
        }, $tasks);
    }

    /**
     * Test tasks run.
     *
     * @dataProvider tasks_provider
     * @covers \block_gearup\di
     */
    public function test_task_run($classname): void {
        try {
            ob_start();
            $task = \core\task\manager::get_scheduled_task('\\' . $classname);
            $task->execute();
            ob_end_clean();
        } catch (\Throwable $e) {
            $this->fail("Failed to run task $classname: " . $e->getMessage());
        }
    }

}
