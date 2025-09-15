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

        // 1) ACTIVER les queued arrivées à start_date
        $queued = $DB->get_records_select('user_subscription',
            "status = 'queued' AND start_date IS NOT NULL AND start_date <= :now",
            ['now' => $now]
        );
        foreach ($queued as $q) {
            $q->status      = 'active';
            $q->last_update = $now;
            $DB->update_record('user_subscription', $q);

            // (Ré)inscriptions idempotentes via TON manager
            \local_subscriptions\subscription_manager::enrol_user_to_courses(
                (int)$q->userid, (int)$q->planid, (int)$q->start_date, (int)$q->end_date
            );

            // Mail d’activation (optionnel)
            if (class_exists('\local_subscriptions\mailer')) {
                $user = $DB->get_record('user', ['id'=>$q->userid, 'deleted'=>0], 'id,username,firstname,lastname,email', MUST_EXIST);
                $plan = $DB->get_record('subscription_plan', ['id'=>$q->planid], '*', MUST_EXIST);
                \local_subscriptions\mailer::send_subscription_activated($user, $plan, $q);
            }
        }

        // 2) EXPIRER les actives dépassées + SUSPENDRE les cours
        $expired = $DB->get_records_select('user_subscription',
            "status = 'active' AND end_date IS NOT NULL AND end_date < :now",
            ['now' => $now]
        );
        foreach ($expired as $sub) {
            // Suspendre via TON manager
            \local_subscriptions\subscription_manager::suspend_user_in_plan_courses(
                (int)$sub->userid, (int)$sub->planid
            );

            // Marquer comme expirée
            $sub->status      = 'expired';
            $sub->last_update = $now;
            $DB->update_record('user_subscription', $sub);

            // Mail d’expiration UNIQUEMENT s’il n’y a PAS de brique suivante déjà en file
            $hasnext = $DB->record_exists_select('user_subscription',
                "userid = :u AND planid = :p AND status = 'queued' AND start_date > :now",
                ['u'=>$sub->userid, 'p'=>$sub->planid, 'now'=>$now]
            );
            if (!$hasnext && class_exists('\local_subscriptions\mailer')) {
                $user = $DB->get_record('user', ['id'=>$sub->userid, 'deleted'=>0], '*', MUST_EXIST);
                $plan = $DB->get_record('subscription_plan', ['id'=>$sub->planid], '*', MUST_EXIST);
                \local_subscriptions\mailer::send_subscription_expired($user, $plan, $sub);
            }
        }
    }

}
