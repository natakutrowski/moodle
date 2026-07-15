<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxUserMatchRepository;
use local_subscriptions\crm\inbox\services\InboxContactReconciliationService;
use local_subscriptions\crm\inbox\services\InboxUserMatcher;

final class reconcile_crm_inbox_contacts_task
    extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_reconcile_crm_inbox_contacts',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        $contacts = new InboxContactRepository();
        $matches = new InboxUserMatchRepository();

        $service =
            new InboxContactReconciliationService(
                $contacts,
                $matches,
                new InboxUserMatcher(
                    $contacts,
                    $matches
                )
            );

        $result = $service->reconcile_pending(
            500
        );

        mtrace(sprintf(
            '[CRM Inbox] Contacts reconciled=%d errors=%d',
            $result['processed'],
            $result['errors']
        ));
    }
}