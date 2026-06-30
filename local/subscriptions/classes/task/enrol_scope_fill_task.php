<?php
namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/enrollib.php');
require_once($GLOBALS['CFG']->dirroot . '/enrol/manual/lib.php');

class enrol_scope_fill_task extends \core\task\scheduled_task {

    // === CONFIG TABLES/COLS (ton schéma prod confirmé) ===
    private const TBL_SUBS          = 'user_subscription';           // mdl_user_subscription
    private const COL_SUBS_ID       = 'id';
    private const COL_SUBS_USERID   = 'userid';
    private const COL_SUBS_STATUS   = 'status';
    private const COL_SUBS_START    = 'start_date';                  // int UNIX
    private const COL_SUBS_END      = 'end_date';                    // int UNIX
    private const COL_SUBS_PLANID   = 'planid';
    private const SUB_STATUS_ACTIVE = 'active';

    private const TBL_PLAN          = 'subscription_plan';           // mdl_subscription_plan
    private const COL_PLAN_ID       = 'id';
    private const COL_PLAN_SCOPEID  = 'accessscopeid';               // confirmé

    private const TBL_SCOPE         = 'subscription_access_scope';   // mdl_subscription_access_scope
    private const COL_SCOPE_ID      = 'id';
    private const COL_SCOPE_COURSES = 'course_ids';                  // CSV d’IDs

    private const DEFAULT_ROLE_SHORTNAME = 'student';                // rôle pour enrol

    public function get_name(): string {
        return get_string('task_enrol_scope_fill', 'local_subscriptions');
    }


    public function execute() {
        global $DB;

        $now = time();

        // 1) Récupérer toutes les souscriptions actives non expirées.
        $sql = "SELECT s.".self::COL_SUBS_ID."      AS subid,
                    s.".self::COL_SUBS_USERID."  AS userid,
                    s.".self::COL_SUBS_PLANID."  AS planid,
                    s.".self::COL_SUBS_START."   AS startts,
                    s.".self::COL_SUBS_END."     AS endts
                FROM {".self::TBL_SUBS."} s
                JOIN {user} u ON u.id = s.".self::COL_SUBS_USERID."
                WHERE s.".self::COL_SUBS_STATUS." = :active
                AND s.".self::COL_SUBS_END." > :now
                AND u.deleted = 0
                AND u.suspended = 0";
        $subs = $DB->get_records_sql($sql, ['active' => self::SUB_STATUS_ACTIVE, 'now' => $now]);

        if (!$subs) {
            mtrace('enrol_scope_fill: no active subscriptions to process.');
            return;
        }

        $roleid = $this->get_roleid(self::DEFAULT_ROLE_SHORTNAME);
        $countprocessed = 0;
        $countenrolled  = 0;

        foreach ($subs as $s) {
            $countprocessed++;

            $userid = (int)$s->userid;

            if (!$this->user_exists_and_active($userid)) {
                mtrace("enrol_scope_fill: user {$userid} does not exist, deleted or suspended, skip.");
                continue;
            }

            $planid = (int)$s->planid;
            $start  = (int)$s->startts ?: $now;
            $end    = (int)$s->endts   ?: 0; // 0 = illimité

            // 2) scopeid depuis plan
            $plan = $DB->get_record(self::TBL_PLAN, [self::COL_PLAN_ID => $planid], self::COL_PLAN_SCOPEID, IGNORE_MISSING);
            if (!$plan || empty($plan->{self::COL_PLAN_SCOPEID})) {
                continue;
            }
            $scopeid = (int)$plan->{self::COL_PLAN_SCOPEID};

            // 3) course_ids CSV depuis le scope
            $scope = $DB->get_record(self::TBL_SCOPE, [self::COL_SCOPE_ID => $scopeid], self::COL_SCOPE_COURSES, IGNORE_MISSING);
            if (!$scope) {
                continue;
            }
            $csv = trim((string)$scope->{self::COL_SCOPE_COURSES});
            if ($csv === '') {
                continue;
            }

            // 4) parser CSV -> ints uniques
            $courseids = $this->parse_course_ids_csv($csv);
            if (!$courseids) {
                continue;
            }

            // 5) filtrer sur courses existants
            $courseids = $this->filter_existing_courses($courseids);
            if (!$courseids) {
                continue;
            }

            // 6) pour chaque course, inscrire si pas déjà inscrit
            foreach ($courseids as $courseid) {
                if ($end && $end <= $now) {
                    // sub expirée (sécurité)
                    continue;
                }
                if ($this->is_user_enrolled_in_course($userid, $courseid)) {
                    continue;
                }
                $this->enrol_user_manual($userid, $courseid, $roleid, $start, $end);
                $countenrolled++;
            }
        }

        mtrace("enrol_scope_fill: processed={$countprocessed}, enrolled={$countenrolled}.");
    }

    // === primitives épurées ===

    private function get_roleid(string $shortname): int {
        global $DB;
        if ($role = $DB->get_record('role', ['shortname' => $shortname], 'id', IGNORE_MISSING)) {
            return (int)$role->id;
        }
        return 5; // fallback
    }

    private function parse_course_ids_csv(string $csv): array {
        $raw = preg_split('/[,\s]+/', $csv, -1, PREG_SPLIT_NO_EMPTY);
        $ids = [];
        foreach ($raw as $t) {
            if (is_numeric($t)) {
                $ids[] = (int)$t;
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function filter_existing_courses(array $ids): array {
        global $DB;
        if (!$ids) { return []; }
        list($in, $p) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $exist = $DB->get_fieldset_sql("SELECT id FROM {course} WHERE id $in", $p);
        return array_map('intval', $exist);
    }

    private function is_user_enrolled_in_course(int $userid, int $courseid): bool {
        global $DB;
        // IMPORTANT: record_exists_sql() ajoute déjà LIMIT 1, donc NE PAS mettre LIMIT dans le SQL.
        $sql = "SELECT 1
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :uid
                   AND e.courseid = :cid";
        return $DB->record_exists_sql($sql, ['uid' => $userid, 'cid' => $courseid]);
    }

    private function enrol_user_manual(int $userid, int $courseid, int $roleid, int $timestart, int $timeend): void {
        $instances = enrol_get_instances($courseid, true);
        $manual = null;
        foreach ($instances as $inst) {
            if ($inst->enrol === 'manual' && (int)$inst->status === ENROL_INSTANCE_ENABLED) {
                $manual = $inst;
                break;
            }
        }
        if (!$manual) {
            mtrace("enrol_scope_fill: no active manual enrol instance for courseid={$courseid}, skip.");
            return;
        }
        $plugin = enrol_get_plugin('manual');
        if (!$plugin instanceof \enrol_manual_plugin) {
            mtrace("enrol_scope_fill: manual enrol plugin unavailable, skip course {$courseid}.");
            return;
        }
        $plugin->enrol_user($manual, $userid, $roleid, $timestart, $timeend);
    }

    private function user_exists_and_active(int $userid): bool {
        global $DB;

        return $DB->record_exists('user', [
            'id' => $userid,
            'deleted' => 0,
            'suspended' => 0,
        ]);
    }

}
