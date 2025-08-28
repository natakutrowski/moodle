<?php
namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

class expire_user_enrolments_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_expire_enrolments', 'local_subscriptions');
    }

    public function execute() {
        global $DB;

        $now = time();
        // On cible les souscriptions actives dont la fin est passée.
        $sql = "SELECT id, userid, planid, end_date, status
                  FROM {user_subscription}
                 WHERE status = 'active' AND end_date > 0 AND end_date < :now";
        $subs = $DB->get_records_sql($sql, ['now' => $now]);

        if (!$subs) { return; }

        require_once($GLOBALS['CFG']->dirroot.'/local/subscriptions/classes/subscription_manager.php');

        foreach ($subs as $sub) {
            // Suspendre dans tous les cours du plan.
            \local_subscriptions\subscription_manager::suspend_user_in_plan_courses(
                (int)$sub->userid, (int)$sub->planid
            );

            // Marquer la souscription comme expirée.
            $sub->status = 'expired';
            $sub->last_update = $now;
            $DB->update_record('user_subscription', $sub);

            // (Optionnel) envoyer un petit email "abonnement expiré"
            // → facile à ajouter via ton mailer plus tard si tu veux.
        }
    }
}
