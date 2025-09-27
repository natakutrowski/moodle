<?php
namespace local_subscriptions\task;

use local_subscriptions\constants\Status;

defined('MOODLE_INTERNAL') || die();

class send_expiry_reminders_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_send_expiry_reminders', 'local_subscriptions');
    }

    public function execute() {
        global $DB, $CFG;

        $now = time();
        $targets = ['d30' => 30 * DAYSECS, 'd7' => 7 * DAYSECS, 'd1' => 1 * DAYSECS];

        // Souscriptions actives, sur plans non récurrents, qui expirent bientôt
        $sql = "
          SELECT s.*, p.accessscopeid, p.duration_key
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.status = '".Status::ACTIVE."'
             AND p.is_recurring = 0
             AND s.end_date IS NOT NULL
             AND s.end_date >= :now";
        $subs = $DB->get_records_sql($sql, ['now' => $now]);

        require_once($CFG->dirroot . '/local/subscriptions/classes/domain/SubscriptionAdvisor.php');

        foreach ($subs as $s) {
            $delta = (int)$s->end_date - $now;

            // Durée totale de ce plan en secondes (via ton Advisor)
            $durTotSec = \local_subscriptions\domain\SubscriptionAdvisor::duration_to_seconds((string)($s->duration_key ?? '1year'));

            // Plans "lifetime" ou durée nulle → jamais de rappel
            if ($durTotSec <= 0 || (isset($s->duration_key) && $s->duration_key === 'lifetime')) {
                continue;
            }

            // Début effectif de la souscription (fallback si start_date manquant)
            $startTs = (int)($s->start_date ?? 0);
            if ($startTs <= 0) {
                // Si start absent, on l’estime par rapport à end_date et la durée plan
                $startTs = max(0, (int)$s->end_date - $durTotSec);
            }

            // Temps écoulé depuis le début (borné à [0, durée])
            $elapsedSec = max(0, min($durTotSec, $now - $startTs));

            // Seuil "2/3 de la durée"
            $twoThirdsSec = (int)floor($durTotSec * 2 / 3);

            // Si on n’a pas encore atteint 2/3 du plan → on ne déclenche **aucun** rappel
            if ($elapsedSec < $twoThirdsSec) {
                continue;
            }

            foreach ($targets as $key => $sec) {
                if (abs($delta - $sec) > DAYSECS) {
                    continue;
                }
                // Skip uniquement s’il existe une souscription QUEUED dans le même scope
                $hasqueued = $DB->record_exists_sql("
                    SELECT 1
                    FROM {user_subscription} qs
                    JOIN {subscription_plan} qp ON qp.id = qs.planid
                    WHERE qs.userid = :u
                    AND qs.status = '".Status::QUEUED."'
                    AND qp.accessscopeid = :scope
                    AND qs.start_date >= :minstart
                ", [
                    'u'        => (int)$s->userid,
                    'scope'    => (int)$s->accessscopeid,
                    // petite tolérance négative pour éviter les effets de bord d’arrondi
                    'minstart' => (int)$s->end_date - 3600,
                ]);

                if ($hasqueued) {
                    // il y a déjà une brique en file pour ce scope → pas de relance
                    continue;
                }

                // Anti-doublon
                if ($DB->record_exists('subscription_reminder_log', ['subscriptionid' => $s->id, 'remind_key' => $key])) {
                    continue;
                }

                if (class_exists('\local_subscriptions\mailer')) {
                    // On récupère juste le user et le plan pour alimenter le mailer
                    $user = $DB->get_record('user', ['id'=>$s->userid, 'deleted'=>0], '*', MUST_EXIST);
                    $plan = $DB->get_record('subscription_plan', ['id'=>$s->planid], '*', MUST_EXIST);
                    \local_subscriptions\mailer::send_subscription_expiry_reminder($user, $plan, $s, $key);
                }

                $DB->insert_record('subscription_reminder_log', (object)[
                    'subscriptionid' => $s->id,
                    'userid'         => $s->userid,
                    'remind_key'     => $key,
                    'sent_at'        => time(),
                ]);
            }
        }
    }
}
