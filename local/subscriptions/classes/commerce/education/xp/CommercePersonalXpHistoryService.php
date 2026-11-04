<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\repositories\LevelUpXpRepository;

/**
 * Read-only, user-scoped Level Up XP history.
 * The total is read from the official Level Up world state, never reconstructed from logs.
 */
final class CommercePersonalXpHistoryService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly LevelUpXpRepository $levelup
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB, new LevelUpXpRepository());
    }

    /** @return array<string,mixed> */
    public function get_for_user(int $userid, int $page = 0): array {
        if ($userid <= 1) {
            throw new \InvalidArgumentException('A real learner ID is required');
        }

        $available = false;
        $total = 0;
        try {
            $available = $this->levelup->is_available();
            if ($available) {
                $stats = $this->levelup->get_statistics($userid, time());
                $total = (int)($stats['total_xp'] ?? 0);
            }
        } catch (\Throwable) {
            // Do not block history if Level Up's world-state service is unavailable.
        }

        $manager = $this->db->get_manager();
        $haslogs = $manager->table_exists(new \xmldb_table('block_xp_logs'));
        if (!$haslogs) {
            return [
                'totalpoints' => $total, 'rewardcount' => 0, 'hasrewards' => false,
                'rows' => [], 'page' => 0, 'xpavailable' => $available,
            ];
        }

        $params = ['userid' => $userid];
        $condition = 'userid = :userid AND points > 0';
        $count = (int)$this->db->count_records_select('block_xp_logs', $condition, $params);
        $limit = CommercePedagogicalXpHistoryService::PAGE_SIZE;
        $maxpage = $count ? intdiv($count - 1, $limit) : 0;
        $page = max(0, min($page, $maxpage));

        // ctx/cm are only used for safely resolving existing, learner-visible activities.
        $logs = $this->db->get_records_sql(
            "SELECT l.id, l.points, l.timerecorded, l.envid,
                    cm.id AS cmid, cm.course AS courseid
               FROM {block_xp_logs} l
          LEFT JOIN {context} ctx ON ctx.id = l.envid AND ctx.contextlevel = :contextmodule
          LEFT JOIN {course_modules} cm ON cm.id = ctx.instanceid
              WHERE l.userid = :userid AND l.points > 0
           ORDER BY l.timerecorded DESC, l.id DESC",
            ['contextmodule' => CONTEXT_MODULE, 'userid' => $userid], $page * $limit, $limit
        );

        $modinfos = [];
        $rows = [];
        foreach ($logs as $log) {
            $title = get_string('commerce_points_history_personal_other', 'local_subscriptions');
            $activityurl = '';
            $courseid = (int)($log->courseid ?? 0);
            $cmid = (int)($log->cmid ?? 0);
            if ($courseid > SITEID && $cmid > 0) {
                try {
                    if (!array_key_exists($courseid, $modinfos)) {
                        $modinfos[$courseid] = get_fast_modinfo($courseid, $userid);
                    }
                    $cm = $modinfos[$courseid]->get_cm($cmid);
                    if ($cm->uservisible) {
                        $title = format_string((string)$cm->name, true, [
                            'context' => \context_module::instance($cmid),
                        ]);
                        $activityurl = $cm->url ? $cm->url->out(false) : '';
                    }
                } catch (\Throwable) {
                    // Do not reveal deleted, inaccessible or hidden module metadata.
                }
            }
            $rows[] = [
                'date' => userdate((int)$log->timerecorded,
                    get_string('strftimedatetime', 'langconfig')),
                'source' => $title,
                'points' => (int)$log->points,
                'hasactivityurl' => $activityurl !== '',
                'activityurl' => $activityurl,
            ];
        }

        return [
            'totalpoints' => $total,
            'rewardcount' => $count,
            'hasrewards' => $rows !== [],
            'rows' => $rows,
            'page' => $page,
            'xpavailable' => $available,
        ];
    }
}
