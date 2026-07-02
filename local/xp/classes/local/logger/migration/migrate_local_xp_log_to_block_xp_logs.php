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

namespace local_xp\local\logger\migration;

use block_xp\di;
use block_xp\local\logger\migration\log_migrator;
use block_xp\local\reason\reason_with_subtype;
use block_xp\local\reason\reason_with_tracking;
use block_xp\local\reason\resolver;
use block_xp\local\reason\unknown_reason;
use block_xp\local\utils\reason_utils;
use local_xp\local\reason\maker_from_type_and_signature;
use moodle_database;

/**
 * Migrate local_xp_log to block_xp_logs.
 *
 * Migrates legacy log entries from local_xp_log into block_xp_logs. Skips
 * rows already migrated. Processes youngest logs first. Uses batch processing
 * for efficiency with large datasets.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_local_xp_log_to_block_xp_logs implements log_migrator {

    /** @var int Legacy source identifier for XP+. */
    const LEGACY_SOURCE_XP_PLUS = 2;

    /** @var moodle_database */
    protected $db;
    /** @var int Batch size for inserts. */
    protected $batchsize = 5000;
    /** @var int|null Maximum records to process per run. */
    protected $limit;
    /** @var int|null Maximum runtime in seconds, null for no limit. */
    protected $maxruntime;
    /** @var maker_from_type_and_signature */
    protected $reasonmaker;
    /** @var resolver */
    protected $reasonresolver;
    /** @var \progress_trace */
    protected $trace;
    /**
     * Constructor.
     */
    public function __construct() {
        $this->db = di::get('db');
        $this->reasonmaker = new maker_from_type_and_signature();
        $this->reasonresolver = di::get('reason_resolver');
        $this->trace = new \null_progress_trace();
    }

    /**
     * Migrate local_xp_log records to block_xp_logs.
     *
     * @return int Number of records migrated.
     */
    public function migrate(): int {
        $migrated = 0;
        $processed = 0;
        $batch = [];
        $clock = di::get('clock');
        $deadline = $this->maxruntime !== null ? $clock->time() + $this->maxruntime : null;

        $sql = "SELECT l.id, l.contextid, l.userid, l.type, l.signature, l.points, l.time, l.ruleid
                  FROM {local_xp_log} l
                 WHERE NOT EXISTS (
                    SELECT 1
                      FROM {block_xp_logs} x
                     WHERE x.legacysource = :legacysource
                       AND x.legacyid = l.id
                 )
              ORDER BY l.time DESC, l.id DESC";

        $rs = $this->db->get_recordset_sql($sql, [
            'legacysource' => self::LEGACY_SOURCE_XP_PLUS,
        ]);

        $this->trace->output('Starting migration local_xp_log -> block_xp_logs...');
        foreach ($rs as $record) {
            if ($this->limit !== null && $processed >= $this->limit) {
                $this->trace->output('Reached limit of ' . $this->limit . ' records, stopping...');
                break;
            } else if ($deadline !== null && $clock->time() >= $deadline) {
                $this->trace->output('Reached deadline of ' . $this->maxruntime . ' seconds, stopping...');
                break;
            }

            $reason = $this->reasonmaker->make_from_type_and_signature($record->type, $record->signature);
            if ($reason instanceof unknown_reason) {
                $reasonname = $record->type;
                $subtype = null;
                $envid = null;
                $parentid = null;
                $objectid = null;
            } else {
                $reasonname = $this->reasonresolver->get_name($reason);
                $subtype = $reason instanceof reason_with_subtype ? $reason->get_subtype() : null;
                if ($reason instanceof reason_with_tracking) {
                    $envid = $reason->get_env_id();
                    $parentid = $reason->get_parent_id();
                    $objectid = $reason->get_object_id();
                } else {
                    $backfill = reason_utils::get_backfilled_tracking_values($reason);
                    $envid = $backfill->envid ?? null;
                    $parentid = $backfill->parentid ?? null;
                    $objectid = $backfill->objectid ?? null;
                }
            }

            $batch[] = (object) [
                'contextid' => (int) $record->contextid,
                'userid' => (int) $record->userid,
                'points' => (int) $record->points,
                'reason' => $reasonname,
                'subtype' => $subtype,
                'envid' => $envid,
                'parentid' => $parentid,
                'objectid' => $objectid,
                'ruleid' => $record->ruleid ? (int) $record->ruleid : null,
                'reasontypehash' => $this->get_reason_type_hash($reasonname, $subtype),
                'timerecorded' => (int) $record->time,
                'legacysource' => self::LEGACY_SOURCE_XP_PLUS,
                'legacyid' => (int) $record->id,
            ];

            $processed++;

            if (count($batch) >= $this->batchsize) {
                $this->trace->output('Inserting batch of ' . count($batch) . ' records...');
                $this->db->insert_records('block_xp_logs', $batch);
                $migrated += count($batch);
                $batch = [];
            }
        }
        $rs->close();

        if (!empty($batch)) {
            $this->trace->output('Inserting final batch of ' . count($batch) . ' records...');
            $this->db->insert_records('block_xp_logs', $batch);
            $migrated += count($batch);
        }

        $this->trace->output('Migration finished, ' . $migrated . ' records migrated.');
        return $migrated;
    }

    /**
     * Get the reason type hash.
     *
     * Matches the logic in context_collection_logger::get_reason_type_hash().
     *
     * @param string $reasonname The reason name.
     * @param string|null $subtype The subtype.
     * @return string
     */
    protected function get_reason_type_hash(string $reasonname, ?string $subtype): string {
        return substr(sha1($reasonname . ':' . ($subtype ?? '')), 0, 9);
    }

    /**
     * Get the number of records still needing migration.
     *
     * @return int
     */
    public function get_remaining_migrations(): int {
        $sql = "SELECT COUNT(l.id)
                  FROM {local_xp_log} l
                 WHERE NOT EXISTS (
                    SELECT 1
                      FROM {block_xp_logs} x
                     WHERE x.legacysource = :legacysource
                       AND x.legacyid = l.id
                 )";

        return (int) $this->db->count_records_sql($sql, [
            'legacysource' => self::LEGACY_SOURCE_XP_PLUS,
        ]);
    }

    /**
     * Get the batch size.
     *
     * @return int
     */
    public function get_batch_size(): int {
        return $this->batchsize;
    }

    /**
     * Get the limit.
     *
     * @return int|null
     */
    public function get_limit(): ?int {
        return $this->limit;
    }

    /**
     * Get the maximum runtime.
     *
     * @return int|null
     */
    public function get_max_runtime(): ?int {
        return $this->maxruntime;
    }

    /**
     * Set batch size.
     *
     * @param int $size The batch size.
     */
    public function set_batch_size(int $size): void {
        $this->batchsize = max(1, $size);
    }

    /**
     * Set limit on records to process per run.
     *
     * @param int $limit The maximum number of records to migrate.
     */
    public function set_limit(int $limit): void {
        $this->limit = $limit;
    }

    /**
     * Set maximum runtime in seconds.
     *
     * @param int|null $seconds Maximum runtime in seconds, null for no limit.
     */
    public function set_max_runtime(?int $seconds): void {
        $this->maxruntime = $seconds;
    }

    /**
     * Set the trace.
     *
     * @param \progress_trace $trace
     */
    public function set_trace(\progress_trace $trace): void {
        $this->trace = $trace;
    }
}
