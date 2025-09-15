<?php
namespace local_subscriptions\task;

class cleanup_login_tokens_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('task_cleanup_login_tokens', 'local_subscriptions');
    }

    public function execute() {
        global $DB;

        $now = time();
        // Purge les jetons expirés
        $sql = "UPDATE {subscription_payment_request}
                   SET login_token = NULL,
                       login_token_expires = NULL,
                       last_update = :now
                 WHERE login_token_expires IS NOT NULL
                   AND login_token_expires < :now";
        $params = ['now' => $now];

        $count = $DB->execute($sql, $params);

        mtrace('[local_subscriptions] cleanup_login_tokens_task executed');
    }
}
