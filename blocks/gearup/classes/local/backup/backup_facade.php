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
 * Backup.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\backup;

use backup_structure_dbops;
use backup_structure_step;
use base_logger;
use context_course;

/**
 * Backup.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_facade {

    /** @var backup_structure_step The step. */
    protected $step;

    /**
     * Constructor.
     *
     * The constructor is not public because we may change how this is constructed in the future.
     * We may also create an interface, etc. So for now this object must be created using the static
     * methods.
     *
     * @param backup_structure_step $step The step.
     */
    protected function __construct(backup_structure_step $step) {
        $this->step = $step;
    }

    /**
     * Get the backup ID.
     *
     * @return string
     */
    public function get_backup_id(): string {
        return $this->step->get_task()->get_backupid();
    }

    /**
     * Get the course ID.
     *
     * @return int
     */
    public function get_course_id(): int {
        return (int) $this->step->get_task()->get_courseid();
    }

    /**
     * Get the course context.
     *
     * @return context_course
     */
    public function get_course_context(): context_course {
        return context_course::instance($this->get_course_id());
    }

    /**
     * Get the logger.
     *
     * @return base_logger
     */
    public function get_logger(): base_logger {
        return $this->step->get_task()->get_logger();
    }

    /**
     * Set a mapping.
     *
     * @param string $name The name.
     * @param string|int $id The id.
     */
    public function set_mapping_id($name, $id) {
        backup_structure_dbops::insert_backup_ids_record($this->get_backup_id(), $name, $id);
    }

    /**
     * Make from structure step.
     *
     * @param backup_structure_step $step The step.
     */
    public static function from_structure_step($step) {
        return new static($step);
    }

}
