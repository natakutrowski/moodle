<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\payment\Provider;

/**
 * Gestion centralisée de l'essai (7 jours par défaut).
 * Lot 1 : pas d'emails, pas d'UX, pas de paiement ici.
 */
class trial_manager {

    /** ID du plan d’essai depuis les réglages. */
    public static function get_trial_planid(): int {
        return (int) (get_config('local_subscriptions','trial_plan_id') ?? 0);
    }

    /** Réglages d’essai (jours, % réduction, fenêtre). */
    public static function get_trial_settings(): array {
        return [
            'days'       => (int)(get_config('local_subscriptions','trial_duration_days') ?? 7),
            'disc_pct'   => (int)(get_config('local_subscriptions','trial_discount_percent') ?? 15),
            'disc_hours' => (int)(get_config('local_subscriptions','trial_discount_hours') ?? 72),
        ];
    }

    /**
     * Démarrer un essai pour un utilisateur :
     * - crée la ligne user_subscription pour le plan "is_trial"
     * - inscrit l'utilisateur aux cours du scope
     * - force le rôle trialstudent (retire student si présent)
     *
     * @return int|null ID de la souscription créée
     * @throws \moodle_exception si plan d’essai absent
     */
    public static function start_trial(int $userid): ?int {
        global $DB;

        $planid = self::get_trial_planid();
        if (!$planid) {
            throw new \moodle_exception('missing_trial_plan', 'local_subscriptions');
        }

        $now = time();
        $cfg = self::get_trial_settings();

        $start = $now;
        $end   = $now + max(1, $cfg['days']) * DAYSECS;

        // Devise par défaut : première devise du plan, sinon 'EUR'.
        $currency = self::get_plan_default_currency($planid);

        // Création de la souscription d'essai (PSP-agnostique)
        $result = subscription_manager::create_or_extend_subscription(
            $userid,
            $planid,
            Provider::TRIAL,                               // provider générique pour l'essai
            'trial:' . $userid . ':' . $start,     // transaction technique
            $start,
            $end,
            0.0,                                   // pricepaid (essai gratuit)
            $currency,                             // ex. 'EUR'
            $now,
            false,                                 // allowupdate
            0,                                     // discount_percent
            null,                                  // discount_reason
            0.00                                   // discount_amount
        );

        // Inscriptions aux cours du scope
        subscription_manager::enrol_user_to_courses($userid, $planid, $start, $end);

        // Rôle trialstudent au niveau des cours du scope
        self::force_role_trialstudent($userid, $planid);

        return !empty($result['subscription']->id) ? (int)$result['subscription']->id : null;
    }

    /** Lit une devise par défaut pour le plan, sinon 'EUR'. */
    public static function get_plan_default_currency(int $planid): string {
        global $DB;
        $cur = $DB->get_field('subscription_plan_price', 'currency', ['planid' => $planid], IGNORE_MISSING);
        return $cur ? strtoupper($cur) : 'EUR';
    }   


    /** Applique le rôle trialstudent sur tous les cours du scope (et retire student si présent). */
    public static function force_role_trialstudent(int $userid, int $planid): void {
        global $DB, $CFG;

        $scope = subscription_manager::get_access_scope_from_planid($planid);
        if (!$scope || empty($scope->course_ids)) { return; }

        $trialroleid   = (int)$DB->get_field('role','id',['shortname'=>'trialstudent'], IGNORE_MISSING);
        $studentroleid = (int)$DB->get_field('role','id',['shortname'=>'student'], IGNORE_MISSING);
        if (!$trialroleid) { return; }

        require_once($CFG->dirroot.'/lib/accesslib.php');

        $courseids = array_values(array_unique(array_map('intval',
            preg_split('/[,\;\s]+/', (string)$scope->course_ids, -1, PREG_SPLIT_NO_EMPTY)))
        );

        foreach ($courseids as $cid) {
            $ctx = \context_course::instance($cid);
            if ($studentroleid) { role_unassign($studentroleid, $userid, $ctx->id); }
            if (!user_has_role_assignment($userid, $trialroleid, $ctx->id)) {
                role_assign($trialroleid, $userid, $ctx->id);
            }
        }
    }

    /** Retourne la souscription d’essai ACTIVE (encore valide) de l’utilisateur, sinon null. */
    public static function user_has_active_trial(int $userid): ?\stdClass {
        global $DB;
        $planid = self::get_trial_planid();
        if (!$planid) { return null; }

        $sub = $DB->get_record('user_subscription', [
            'userid'=>$userid, 'planid'=>$planid, 'status'=>Status::ACTIVE
        ], '*', IGNORE_MISSING);

        if (!$sub) { return null; }
        if ((int)$sub->end_date <= time()) { return null; }
        return $sub;
    }

    /** Vrai si l’utilisateur est dans la fenêtre de réduction (ex: 72h après le début de l’essai). */
    public static function is_discount_window_open(int $userid): bool {
        $trial = self::user_has_active_trial($userid);
        if (!$trial) { return false; }
        $hours = max(1, (int)(get_config('local_subscriptions','trial_discount_hours') ?? 72));
        return (time() - (int)$trial->start_date) <= ($hours * HOURSECS);
    }

    /** Timestamp (unix) de fin de fenêtre de réduction, ou 0 si pas d’essai. */
    public static function discount_window_deadline(int $userid): int {
        $trial = self::user_has_active_trial($userid);
        if (!$trial) { return 0; }
        $hours = max(1, (int)(get_config('local_subscriptions','trial_discount_hours') ?? 72));
        return ((int)$trial->start_date) + ($hours * HOURSECS);
    }

    /** True si ce plan est marqué comme plan d’essai. */
    public static function is_trial_planid(int $planid): bool {
        global $DB;
        if (!$planid) { return false; }
        // Flag explicite
        $flag = $DB->get_field('subscription_plan', 'is_trial', ['id'=>$planid], IGNORE_MISSING);
        if ((string)$flag === '1' || (int)$flag === 1) { return true; }
        // Et par sécurité, égal au réglage trial_plan_id
        $cfg = (int)(get_config('local_subscriptions','trial_plan_id') ?? 0);
        return $cfg > 0 && $cfg === $planid;
    }

    /** Applique le rôle "student" et retire "trialstudent" sur tous les cours du scope. */
    public static function force_role_student(int $userid, int $planid): void {
        global $DB, $CFG;
        $scope = subscription_manager::get_access_scope_from_planid($planid);
        if (!$scope || empty($scope->course_ids)) { return; }

        $studentroleid = (int)$DB->get_field('role','id',['shortname'=>'student'], IGNORE_MISSING);
        $trialroleid   = (int)$DB->get_field('role','id',['shortname'=>'trialstudent'], IGNORE_MISSING);
        if (!$studentroleid) { return; }

        require_once($CFG->dirroot.'/lib/accesslib.php');

        $courseids = array_values(array_unique(array_map('intval',
            preg_split('/[,\;\s]+/', (string)$scope->course_ids, -1, PREG_SPLIT_NO_EMPTY)))
        );

        foreach ($courseids as $cid) {
            $ctx = \context_course::instance($cid);
            if ($trialroleid) { role_unassign($trialroleid, $userid, $ctx->id); }
            if (!user_has_role_assignment($userid, $studentroleid, $ctx->id)) {
                role_assign($studentroleid, $userid, $ctx->id);
            }
        }
    }


}
