<?php
namespace local_subscriptions\task;

class cleanup_login_tokens_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_cleanup_login_tokens', 'local_subscriptions');
    }

    public function execute() {
        global $DB;

        $now = time();

        // (facultatif) comptage pour le log
        $candidates = $DB->count_records_select(
            'subscription_payment_request',
            'login_token_expires IS NOT NULL AND login_token_expires < :now',
            ['now' => $now]
        );

        // Nettoyage des jetons expirés
        $sql = "UPDATE {subscription_payment_request}
                   SET login_token = NULL,
                       login_token_expires = NULL
                 WHERE login_token_expires IS NOT NULL
                   AND login_token_expires < :now";
        $ok = $DB->execute($sql, ['now' => $now]);

        mtrace('[local_subscriptions] cleanup_login_tokens_task — candidates=' .
            $candidates . ', status=' . ($ok ? 'OK' : 'FAIL'));
    }
}
