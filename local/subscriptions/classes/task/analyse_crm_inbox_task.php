<?php

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiQueueRepository;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository;
use local_subscriptions\crm\inbox\ai\services\InboxAiPanelService;
use local_subscriptions\crm\inbox\ai\services\InboxAiQuotaService;
use local_subscriptions\crm\inbox\ai\services\InboxAiRuntimeFactory;

final class analyse_crm_inbox_task
    extends scheduled_task {

    public function get_name(): string {
        return get_string(
            'task_analyse_crm_inbox',
            'local_subscriptions'
        );
    }

    public function execute(): void {
        if (
            !get_config(
                'local_subscriptions',
                'inbox_ai_automatic_analysis'
            )
        ) {
            mtrace(
                '[CRM Inbox AI] Automatic analysis disabled.'
            );

            return;
        }

        $quota = new InboxAiQuotaService(
            new InboxAiUsageRepository()
        );

        $service = new InboxAiPanelService(
            new InboxAiRuntimeFactory()
        );

        $queue =
            new InboxAiQueueRepository();

        foreach (
            $queue->pending_thread_ids(20)
            as $threadid
        ) {
            if (
                !$quota->can_consume(
                    null,
                    4
                )
            ) {
                mtrace(
                    '[CRM Inbox AI] Global quota reached.'
                );

                break;
            }

            try {
                $service->analyse(
                    $threadid,
                    'fr',
                    null,
                    false
                );

                mtrace(
                    '[CRM Inbox AI] Analysed thread ' .
                    $threadid
                );
            } catch (\Throwable $exception) {
                mtrace(
                    '[CRM Inbox AI] Thread ' .
                    $threadid .
                    ' failed (' .
                    get_class($exception) .
                    ').'
                );

                debugging(
                    'CRM Inbox AI automatic analysis failed ' .
                    'for thread #' .
                    $threadid .
                    ': ' .
                    $exception->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }
    }
}