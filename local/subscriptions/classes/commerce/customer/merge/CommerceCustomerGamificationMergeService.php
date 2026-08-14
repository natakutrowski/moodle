<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Consolidates Level Up XP and Level Up Quest user history during identity merges.
 *
 * The implementation is intentionally plugin-aware but dependency-light: tables are
 * detected at runtime so Commerce keeps working when one of the optional plugins is absent.
 */
final class CommerceCustomerGamificationMergeService {
    public function __construct(private readonly moodle_database $db) {}

    /** @return array<string,int> */
    public function preview(int $userid): array {
        return [
            'xp_totals' => $this->table_exists('block_xp') ? $this->db->count_records('block_xp', ['userid' => $userid]) : 0,
            'xp_logs' => ($this->table_exists('block_xp_logs') ? $this->db->count_records('block_xp_logs', ['userid' => $userid]) : 0)
                + ($this->table_exists('block_xp_log') ? $this->db->count_records('block_xp_log', ['userid' => $userid]) : 0)
                + ($this->table_exists('local_xp_log') ? $this->db->count_records('local_xp_log', ['userid' => $userid]) : 0),
            'xp_flags' => $this->table_exists('local_xp_user_flag') ? $this->db->count_records('local_xp_user_flag', ['userid' => $userid]) : 0,
            'quests' => $this->table_exists('block_gearup_mission_inst') ? $this->db->count_records('block_gearup_mission_inst', ['subjectid' => $userid]) : 0,
            'quest_objectives' => $this->table_exists('block_gearup_objective_inst') ? $this->db->count_records('block_gearup_objective_inst', ['subjectid' => $userid]) : 0,
        ];
    }

    /** @return array{xp:int,quests:int} */
    public function score_summary(int $userid): array {
        $xp = 0;
        if ($this->table_exists('block_xp')) {
            $xp = (int)$this->db->get_field_sql(
                'SELECT COALESCE(SUM(xp), 0) FROM {block_xp} WHERE userid = :userid',
                ['userid' => $userid]
            );
        }
        $quests = 0;
        if ($this->table_exists('block_gearup_mission_inst')) {
            $quests = (int)$this->db->count_records_select(
                'block_gearup_mission_inst',
                'subjectid = :userid AND state >= :completed',
                ['userid' => $userid, 'completed' => 2]
            );
        }
        return ['xp' => $xp, 'quests' => $quests];
    }

    /** @return array<string,int> */
    public function merge(int $sourceuserid, int $targetuserid): array {
        if ($sourceuserid === $targetuserid) {
            return [];
        }
        $result = [
            'xp_totals' => 0,
            'xp_logs' => 0,
            'xp_logs_deduplicated' => 0,
            'xp_flags' => 0,
            'quests' => 0,
            'quest_conflicts_merged' => 0,
            'quest_objectives' => 0,
        ];

        if ($this->table_exists('block_xp')) {
            foreach ($this->db->get_records('block_xp', ['userid' => $sourceuserid]) as $source) {
                $target = $this->db->get_record('block_xp', ['userid' => $targetuserid, 'courseid' => $source->courseid]);
                if ($target) {
                    $target->xp = (int)$target->xp + (int)$source->xp;
                    $target->lvl = max((int)($target->lvl ?? 1), (int)($source->lvl ?? 1));
                    $this->db->update_record('block_xp', $target);
                    $this->db->delete_records('block_xp', ['id' => $source->id]);
                } else {
                    $source->userid = $targetuserid;
                    $this->db->update_record('block_xp', $source);
                }
                $result['xp_totals']++;
            }
        }

        foreach ([
            ['block_xp_logs', ['contextid','points','reason','subtype','envid','parentid','objectid','ruleid','timerecorded','legacysource','legacyid']],
            ['block_xp_log', ['courseid','eventname','xp','time']],
            ['local_xp_log', ['contextid','type','signature','points','time','ruleid','hashkey']],
        ] as [$table, $fields]) {
            if (!$this->table_exists($table)) {
                continue;
            }
            foreach ($this->db->get_records($table, ['userid' => $sourceuserid]) as $row) {
                if ($table === 'block_xp_logs' && $row->legacysource !== null && $row->legacyid !== null
                    && $this->db->record_exists($table, [
                        'legacysource' => $row->legacysource, 'legacyid' => $row->legacyid,
                    ])) {
                    $this->db->delete_records($table, ['id' => $row->id]);
                    $result['xp_logs_deduplicated']++;
                    continue;
                }
                $conditions = ['userid' => $targetuserid];
                foreach ($fields as $field) {
                    if (property_exists($row, $field) && $row->{$field} !== null) {
                        $conditions[$field] = $row->{$field};
                    }
                }
                if ($this->db->record_exists($table, $conditions)) {
                    $this->db->delete_records($table, ['id' => $row->id]);
                    $result['xp_logs_deduplicated']++;
                } else {
                    $row->userid = $targetuserid;
                    $this->db->update_record($table, $row);
                    $result['xp_logs']++;
                }
            }
        }

        if ($this->table_exists('local_xp_user_flag')) {
            foreach ($this->db->get_records('local_xp_user_flag', ['userid' => $sourceuserid]) as $source) {
                $target = $this->db->get_record('local_xp_user_flag', [
                    'userid' => $targetuserid,
                    'contextid' => $source->contextid,
                ]);
                if ($target) {
                    if ($target->ladderparticipation === null && $source->ladderparticipation !== null) {
                        $target->ladderparticipation = $source->ladderparticipation;
                    }
                    $target->ladderparticipationlocked = max(
                        (int)$target->ladderparticipationlocked,
                        (int)$source->ladderparticipationlocked
                    );
                    $this->db->update_record('local_xp_user_flag', $target);
                    $this->db->delete_records('local_xp_user_flag', ['id' => $source->id]);
                } else {
                    $source->userid = $targetuserid;
                    $this->db->update_record('local_xp_user_flag', $source);
                }
                $result['xp_flags']++;
            }
        }

        if ($this->table_exists('block_gearup_mission_inst')) {
            foreach ($this->db->get_records('block_gearup_mission_inst', ['subjectid' => $sourceuserid]) as $source) {
                $target = $this->db->get_record('block_gearup_mission_inst', [
                    'subjectid' => $targetuserid,
                    'missionid' => $source->missionid,
                    'iteration' => $source->iteration,
                ]);
                if (!$target) {
                    $source->subjectid = $targetuserid;
                    $this->db->update_record('block_gearup_mission_inst', $source);
                    if ($this->table_exists('block_gearup_objective_inst')) {
                        $this->db->set_field('block_gearup_objective_inst', 'subjectid', $targetuserid, ['missioninstid' => $source->id]);
                    }
                    $result['quests']++;
                    continue;
                }

                $this->merge_mission_instance($target, $source);
                if ($this->table_exists('block_gearup_objective_inst')) {
                    $result['quest_objectives'] += $this->merge_objectives((int)$source->id, (int)$target->id, $sourceuserid, $targetuserid);
                }
                $this->db->delete_records('block_gearup_mission_inst', ['id' => $source->id]);
                $result['quest_conflicts_merged']++;
            }
        }

        // Defensive cleanup for orphan objective rows, should a plugin version contain any.
        if ($this->table_exists('block_gearup_objective_inst')) {
            $result['quest_objectives'] += $this->db->count_records('block_gearup_objective_inst', ['subjectid' => $sourceuserid]);
            $this->db->set_field('block_gearup_objective_inst', 'subjectid', $targetuserid, ['subjectid' => $sourceuserid]);
        }

        return $result;
    }

    private function merge_mission_instance(object $target, object $source): void {
        $target->state = max((int)$target->state, (int)$source->state);
        $target->counter = max((int)$target->counter, (int)$source->counter);
        $target->completionratio = max((float)$target->completionratio, (float)$source->completionratio);
        $target->deadline = max((int)$target->deadline, (int)$source->deadline);
        $target->needsattention = max((int)$target->needsattention, (int)$source->needsattention);
        $target->timestarted = $this->earliest_positive((int)$target->timestarted, (int)$source->timestarted);
        $target->timecompleted = max((int)$target->timecompleted, (int)$source->timecompleted);
        $target->timeended = max((int)$target->timeended, (int)$source->timeended);
        $target->timecreated = $this->earliest_positive((int)$target->timecreated, (int)$source->timecreated);
        $target->timemodified = max((int)$target->timemodified, (int)$source->timemodified);
        $this->db->update_record('block_gearup_mission_inst', $target);
    }

    private function merge_objectives(int $sourceinstid, int $targetinstid, int $sourceuserid, int $targetuserid): int {
        $count = 0;
        foreach ($this->db->get_records('block_gearup_objective_inst', ['missioninstid' => $sourceinstid]) as $source) {
            $target = $this->db->get_record('block_gearup_objective_inst', [
                'missioninstid' => $targetinstid,
                'objectiveid' => $source->objectiveid,
            ]);
            if (!$target) {
                $source->missioninstid = $targetinstid;
                $source->subjectid = $targetuserid;
                $this->db->update_record('block_gearup_objective_inst', $source);
            } else {
                $targetstatebefore = (int)$target->state;
                $target->state = max($targetstatebefore, (int)$source->state);
                $target->counter = max((int)$target->counter, (int)$source->counter);
                if ((int)$source->state > $targetstatebefore || empty($target->statedata)) {
                    $target->statedata = $source->statedata;
                }
                $target->dormantuntil = max((int)($target->dormantuntil ?? 0), (int)($source->dormantuntil ?? 0)) ?: null;
                $target->stalefrom = max((int)($target->stalefrom ?? 0), (int)($source->stalefrom ?? 0)) ?: null;
                $target->timecreated = $this->earliest_positive((int)$target->timecreated, (int)$source->timecreated);
                $target->timemodified = max((int)$target->timemodified, (int)$source->timemodified);
                $this->db->update_record('block_gearup_objective_inst', $target);
                $this->db->delete_records('block_gearup_objective_inst', ['id' => $source->id]);
            }
            $count++;
        }
        return $count;
    }

    private function earliest_positive(int $a, int $b): int {
        if ($a <= 0) { return $b; }
        if ($b <= 0) { return $a; }
        return min($a, $b);
    }

    private function table_exists(string $table): bool {
        return $this->db->get_manager()->table_exists(new \xmldb_table($table));
    }
}