<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;

/**
 * Central policy for the remote folders that form the professional mailbox.
 */
final class InboxSyncFolderPolicy {

    private const BASELINE = [
        'inbox',
        'sent',
    ];

    private const ALLOWED = [
        'inbox',
        'sent',
        'drafts',
        'archive',
        'trash',
    ];

    /**
     * @return string[]
     */
    public function folder_types(
        InboxAccount $account
    ): array {
        $configured =
            $account->configuration['sync']['folders']
            ?? [];

        if (!is_array($configured)) {
            $configured = [];
        }

        $folders = [];

        foreach ($configured as $type) {
            $type = trim((string)$type);

            if (
                $type !== ''
                && in_array($type, self::ALLOWED, true)
            ) {
                $folders[] = $type;
            }
        }

        $resolved = $account->configuration['folders']
            ?? [];

        if (!is_array($resolved)) {
            $resolved = [];
        }

        foreach (self::BASELINE as $type) {
            if (
                trim((string)($resolved[$type] ?? '')) !== ''
                && !in_array($type, $folders, true)
            ) {
                $folders[] = $type;
            }
        }

        return array_values(
            array_unique($folders)
        );
    }
}
