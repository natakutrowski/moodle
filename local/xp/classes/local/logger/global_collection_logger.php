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
// https://levelup.plus

namespace local_xp\local\logger;

use DateTime;
use moodle_database;
use block_xp\local\logger\collection_logger;

/**
 * Global collection logger.
 *
 * This class points to the same logs as the instances specific to a course,
 * it uses the same table, but it's useful as an implementation for quickly
 * deleting all the logs which should not be kept.
 *
 * Apart from that, this class has no use.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class global_collection_logger implements collection_logger {

    /** @var string The table name. */
    protected $table = 'local_xp_log';
    /** @var moodle_database The DB. */
    protected $db;
    /** @var collection_logger|null Parent logger. */
    protected $parentlogger;

    /**
     * Constructor.
     *
     * @param moodle_database $db The DB.
     * @param collection_logger|null $parentlogger The parent logger.
     */
    public function __construct(moodle_database $db, ?collection_logger $parentlogger = null) {
        $this->db = $db;
        $this->parentlogger = $parentlogger;
    }

    /**
     * Delete logs older than a certain date.
     *
     * @param \DateTime $dt The date.
     * @return void
     */
    public function delete_older_than(DateTime $dt) {
        $this->db->delete_records_select($this->table, 'time < ?', [$dt->getTimestamp()]);
        if ($this->parentlogger) {
            $this->parentlogger->delete_older_than($dt);
        }
    }

    /**
     * Log a thing.
     *
     * @param int $id The target.
     * @param int $points The points.
     * @param string $signature A signature.
     * @param DateTime|null $time When that happened.
     * @return void
     */
    public function log($id, $points, $signature, ?DateTime $time = null) {
        // Do nothing. We should not be using this to log.
    }

    /**
     * Purge all logs.
     *
     * @return void
     */
    public function reset() {
        // Unlikely that this was intentional, so we do nothing.
    }

}
