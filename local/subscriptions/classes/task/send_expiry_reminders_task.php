<?php
namespace local_subscriptions\task;

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
          SELECT s.*, p.accessscopeid
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
           WHERE s.status = 'active'
             AND p.is_recurring = 0
             AND s.end_date IS NOT NULL
             AND s.end_date >= :now";
        $subs = $DB->get_records_sql($sql, ['now' => $now]);

        require_once($CFG->dirroot . '/local/subscriptions/classes/domain/SubscriptionAdvisor.php');

        foreach ($subs as $s) {
            $delta = (int)$s->end_date - $now;
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
                    AND qs.status = 'queued'
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
