<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\services\InboxManualSyncService;
use local_subscriptions\crm\inbox\services\InboxSyncRuntimeFactory;
use local_subscriptions\subscription_config;
use moodle_url;

final class InboxSyncAction extends
    AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::INBOX_SYNC;
    }

    public function capability(): string {
        return Capabilities::MANAGE_INBOX;
    }

    public function execute(
        array $payload
    ): CommandActionResult {
        try {
            $runtime = (
                new InboxSyncRuntimeFactory()
            )->create_runtime();

            $summary = (
                new InboxManualSyncService(
                    $runtime->accounts,
                    $runtime->sync
                )
            )->sync_enabled_accounts();

            if (
                (int)$summary['accountcount'] === 0
            ) {
                return CommandActionResult::error(
                    get_string(
                        'command_inbox_sync_no_accounts',
                        'local_subscriptions'
                    )
                );
            }

            $message = get_string(
                'command_inbox_sync_success',
                'local_subscriptions',
                (object)[
                    'fetched' =>
                        (int)$summary['fetched'],

                    'created' =>
                        (int)$summary['created'],

                    'skipped' =>
                        (int)$summary['skipped'],

                    'errors' =>
                        (int)$summary['errors'],
                ]
            );

            if (!empty($summary['hasmore'])) {
                $message .= ' ' .
                    get_string(
                        'command_inbox_sync_has_more',
                        'local_subscriptions'
                    );
            }

            return CommandActionResult::success(
                $message,
                (
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page()
                    )
                )->out(false),
                $summary
            );
        } catch (\Throwable $exception) {
            debugging(
                'Manual CRM Inbox sync failed: ' .
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );

            return CommandActionResult::error(
                get_string(
                    'command_inbox_sync_failed',
                    'local_subscriptions'
                )
            );
        }
    }
}