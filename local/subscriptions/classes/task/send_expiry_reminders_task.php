<?php
namespace local_subscriptions\task;

use local_subscriptions\constants\Status;
use local_subscriptions\mailer;

defined('MOODLE_INTERNAL') || die();

class send_expiry_reminders_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_send_expiry_reminders', 'local_subscriptions');
    }

    public function execute() {
        global $DB;

        $now = time();

        // Rappel global (fallback plan si vide)
        $globalcsv  = (string)(get_config('local_subscriptions','expiry_reminder_days') ?? '7');
        $globaldays = self::parse_days($globalcsv);
        if (empty($globaldays)) { $globaldays = [7]; }

        // Souscriptions actives, non récurrentes, qui expirent dans le futur
        $sql = "
            SELECT s.*, p.accessscopeid, p.duration_key,
                p.expiry_reminder_days, p.expiry_reminder_enabled
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.status = :active
            AND (p.is_recurring = 0 OR p.is_recurring IS NULL)
            AND (p.is_trial = 0 OR p.is_trial IS NULL)
            AND s.end_date IS NOT NULL
            AND s.end_date > :now
        ";

        $subs = $DB->get_records_sql($sql, ['active' => Status::ACTIVE, 'now' => $now]);

        foreach ($subs as $s) {
            // Plan qui désactive explicitement les rappels
            if (isset($s->expiry_reminder_enabled) && (int)$s->expiry_reminder_enabled === 0) {
                continue;
            }

            // Jours restants
            $daysleft = (int)ceil( ((int)$s->end_date - $now) / DAYSECS );
            if ($daysleft <= 0) { continue; }

            // Jours de rappel pour CE plan (fallback sur le global)
            $plandays = self::parse_days((string)($s->expiry_reminder_days ?? ''));
            $dayslist = !empty($plandays) ? $plandays : $globaldays;

            // Envoi uniquement si on est pile à un des J–X définis
            if (!in_array($daysleft, $dayslist, true)) { continue; }

            // Ne pas relancer si une brique QUEUED existe déjà dans le même scope
            $hasqueued = $DB->record_exists_sql("
                SELECT 1
                FROM {user_subscription} qs
                JOIN {subscription_plan} qp ON qp.id = qs.planid
                WHERE qs.userid = :u
                AND qs.status = :queued
                AND qp.accessscopeid = :scope
                AND qs.start_date >= :minstart
            ", [
                'u'        => (int)$s->userid,
                'queued'   => Status::QUEUED,
                'scope'    => (int)$s->accessscopeid,
                // tolérance d'1h pour éviter l'effet de bord à la seconde près
                'minstart' => (int)$s->end_date - 3600,
            ]);
            if ($hasqueued) { continue; }

            // Anti-doublon : accepte l'ancien format ('d7') et le nouveau ('J-7')
            $knew = 'J-'.$daysleft;
            $kold = 'd'.$daysleft;
            $dupe = $DB->record_exists_select(
                'subscription_reminder_log',
                'subscriptionid = :sid AND remind_key IN (:k1, :k2)',
                ['sid' => $s->id, 'k1' => $knew, 'k2' => $kold]
            );
            if ($dupe) { continue; }

            // Contexte destinataire & plan pour le mailer
            $user = $DB->get_record('user', ['id' => $s->userid, 'deleted' => 0], '*', MUST_EXIST);
            $plan = $DB->get_record('subscription_plan', ['id' => $s->planid], '*', MUST_EXIST);

            // Envoi : on passe désormais 'days' (entier) — le mailer gère J–X dynamiques
            mailer::dispatch(
                mailer::T_SUBSCRIPTION_EXPIRY_REM,
                [
                    'user' => $user,
                    'plan' => $plan,
                    'sub'  => $s,
                    'days' => $daysleft,     // ⬅️ nouveau (plus robuste que remindkey)
                    // 'lang' => 'fr'        // (optionnel) forcer une langue
                ]
            );

            // Log d'idempotence
            $DB->insert_record('subscription_reminder_log', (object)[
                'subscriptionid' => $s->id,
                'userid'         => $s->userid,
                'remind_key'     => $knew,  // on normalise sur 'J-X'
                'sent_at'        => $now,
            ]);
        }

        return true;
    }

    /**
     * Transforme '14, 7,3, 1' en [14,7,3,1], borne à [0..365], triée.
     */
    private static function parse_days(string $csv): array {
        $arr = array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[,\s;]+/', trim((string)$csv)))
        )));
        $arr = array_filter($arr, static fn($d) => $d >= 0 && $d <= 365);
        sort($arr);
        return $arr;
    }


}
