<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Conservative automatic XP candidate examiner. Completely separate from
 * the existing audit task and from the existing promotion replay task.
 *
 * Switches are independent: xp_auto_cron_enabled (schedule is logically on)
 * and xp_auto_mode=simulation|live (grant permission). Both OFF by default.
 */
final class CommerceXpAutomaticRepairService {
    public const MAX_SCAN = 25;
    public const MAX_GRANTS = 3;
    public const MAX_POINTS = 30;
    public const SETTLE_SECONDS = 1800;
    // Known B1 outage window only: 2026-11-08 14:13:14–14:29:59 CET.
    // This is NOT evidence that every absent gain in this period is owed.
    public const B1_START = 1794143594;
    public const B1_END = 1794144599;

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public static function is_cron_enabled(): bool {
        return (int)get_config('local_subscriptions', 'xp_auto_cron_enabled') === 1;
    }

    public static function mode(): string {
        return get_config('local_subscriptions', 'xp_auto_mode') === 'live' ? 'live' : 'simulation';
    }

    public static function is_live_enabled(): bool {
        return self::is_cron_enabled() && self::mode() === 'live';
    }

    /**
     * Pure, fail-closed predicate before DB-specific anti-cheat checks.
     * @return array{allowed:bool,reason:string}
     */
    public static function classify(array $report, int $now): array {
        if (empty($report['eligible']) || !empty($report['issues'])) {
            return ['allowed' => false, 'reason' => 'Analyse incomplète ou non éligible'];
        }
        $ob = $report['audit'] ?? null;
        if (!$ob || ($ob->status ?? '') !== 'review') {
            return ['allowed' => false, 'reason' => 'Observation non ouverte'];
        }
        // V4 completion-only findings are observational, NOT a grant proof.
        // This guard also protects future non-H5P modules if the active
        // operator ever switches the V3 worker to live.
        if (!in_array((string)($ob->reason ?? ''),
                ['no_personal_completion_log', 'no_promotion_source_ledger'], true)) {
            return ['allowed' => false, 'reason' => 'Observation V4 : correction automatique interdite'];
        }
        if (($report['badge'] ?? '') !== 'available' || (int)($report['existing'] ?? 1) !== 0) {
            return ['allowed' => false, 'reason' => 'Badge ou provenance déjà crédités'];
        }
        if ((int)$ob->points < 1 || (int)$ob->points > 15 ||
                (int)($report['proofid'] ?? 0) <= 0 || (int)($report['proofat'] ?? 0) <= 0 ||
                $now - (int)$report['proofat'] < self::SETTLE_SECONDS) {
            return ['allowed' => false, 'reason' => 'Preuve trop récente, manquante ou montant hors limites'];
        }
        if ($ob->kind === 'personal') {
            if ((int)$report['proofat'] < self::B1_START ||
                    (int)$report['proofat'] > self::B1_END) {
                return ['allowed' => false, 'reason' => 'Hors fenêtre B1 validée : décision humaine'];
            }
            return ['allowed' => true, 'reason' => 'Première réussite dans la fenêtre B1'];
        }
        if ($ob->kind === 'promotion') {
            if (empty($report['sourcekey']) || empty($report['personal']) ||
                    count($report['personal']) !== 1) {
                return ['allowed' => false, 'reason' => 'Aucune source Level Up positive unique'];
            }
            return ['allowed' => true, 'reason' => 'Source positive unique et promotion éligible'];
        }
        return ['allowed' => false, 'reason' => 'Récompense non prise en charge'];
    }

    /**
     * Strengthen the policy against Level Up rate-limit false positives.
     * For first personal success, nearby credited completions mean human review.
     * @return array{allowed:bool,reason:string}
     */
    public static function eligibility(array $report, \moodle_database $db, ?int $now = null): array {
        $decision = self::classify($report, $now ?? time());
        if (!$decision['allowed'] || $report['audit']->kind !== 'personal') {
            return $decision;
        }
        $ob = $report['audit'];
        $when = (int)$report['proofat'];
        $config = $db->get_record('block_xp_config', ['courseid' => SITEID],
            'id,enablecheatguard,maxactionspertime,timeformaxactions,timebetweensameactions', IGNORE_MISSING);
        if (!$config) {
            return ['allowed' => false, 'reason' => 'Paramètres anti-triche indisponibles'];
        }
        // We cannot reconstruct exact cheatguard state at the historical timestamp.
        // Any activity of the same rule near completion is ambiguous; never auto-award.
        if ((int)$config->enablecheatguard === 1) {
            $gap = max(180, (int)$config->timebetweensameactions);
            $near = $db->record_exists_sql(
                "SELECT 1 FROM {block_xp_logs} WHERE userid = :uid AND ruleid = :ruleid
                     AND points > 0 AND timerecorded >= :since AND timerecorded <= :until",
                ['uid' => (int)$ob->userid, 'ruleid' => (int)$ob->ruleid,
                    'since' => $when - $gap, 'until' => $when + $gap]
            );
            if ($near) {
                return ['allowed' => false, 'reason' => 'Gain proche : anti-répétition Level Up possible'];
            }
            $window = max(60, (int)$config->timeformaxactions);
            $limit = (int)$config->maxactionspertime;
            if ($limit > 0) {
                $count = $db->count_records_select('block_xp_logs',
                    'userid = :uid AND points > 0 AND timerecorded >= :since AND timerecorded <= :until',
                    ['uid' => (int)$ob->userid, 'since' => $when - $window,
                        'until' => $when + $window]);
                if ($count >= $limit) {
                    return ['allowed' => false, 'reason' => 'Plafond anti-triche potentiellement atteint'];
                }
            }
        }
        return $decision;
    }

    /**
     * Called by scheduled task only. A disabled cron is a no-op and does not
     * touch XP or the audit cursor. Simulation never calls repair_automatically.
     * @return array<string,mixed>
     */
    public function run(?int $now = null): array {
        $now ??= time();
        $stats = ['mode' => self::mode(), 'scanned' => 0, 'eligible' => 0,
            'credited' => 0, 'points' => 0, 'blocked' => 0, 'errors' => 0,
            'candidateids' => [], 'failids' => []];
        if (!self::is_cron_enabled()) {
            $stats['mode'] = 'disabled';
            return $stats;
        }
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_subscriptions_ped_xp');
        $lock = $lockfactory->get_lock('automatic-xp-repair-worker', 1);
        if (!$lock) {
            $stats['mode'] = 'locked';
            return $stats;
        }
        try {
            $cursor = (int)get_config('local_subscriptions', 'xp_auto_scan_cursor');
            $rows = $this->db->get_records_select('local_subs_xp_audit',
                'status = :status AND id > :cursor', ['status' => 'review', 'cursor' => $cursor],
                'id ASC', '*', 0, self::MAX_SCAN);
            if (!$rows) {
                $rows = $this->db->get_records('local_subs_xp_audit',
                    ['status' => 'review'], 'id ASC', '*', 0, self::MAX_SCAN);
            }
            $service = CommerceXpAssistedRepairService::create($this->db);
            foreach ($rows as $ob) {
                $stats['scanned']++;
                $cursor = (int)$ob->id;
                try {
                    $report = $service->analyse((int)$ob->id);
                    $decision = self::eligibility($report, $this->db, $now);
                    if (!$decision['allowed']) {
                        $stats['blocked']++;
                        continue;
                    }
                    $stats['eligible']++;
                    $stats['candidateids'][] = (int)$ob->id;
                    if (!self::is_live_enabled()) {
                        continue; // NO reward writes in simulation.
                    }
                    if ($stats['credited'] >= self::MAX_GRANTS ||
                            $stats['points'] + (int)$ob->points > self::MAX_POINTS) {
                        continue;
                    }
                    $result = $service->repair_automatically((int)$ob->id);
                    $stats['credited']++;
                    $stats['points'] += (int)$result['points'];
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    $stats['failids'][] = (int)$ob->id;
                    debugging('XP Auto: observation #'.(int)$ob->id.' '.get_class($e), DEBUG_DEVELOPER);
                }
            }
            set_config('xp_auto_scan_cursor', $cursor, 'local_subscriptions');
            $stats['candidateids'] = array_slice($stats['candidateids'], 0, 10);
            $stats['failids'] = array_slice($stats['failids'], 0, 10);
            set_config('xp_auto_last_summary', json_encode($stats), 'local_subscriptions');
            set_config('xp_auto_last_run', $now, 'local_subscriptions');
        } finally {
            $lock->release();
        }
        return $stats;
    }
}
