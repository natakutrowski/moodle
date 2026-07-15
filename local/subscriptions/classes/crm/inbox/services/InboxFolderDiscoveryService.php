<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxConnectorInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;

final class InboxFolderDiscoveryService {

    public function __construct(
        private readonly InboxConnectorInterface $connector,
        private readonly InboxFolderResolver $resolver,
        private readonly InboxAccountRepository $accounts
    ) {
    }

    public function discover(
        InboxAccount $account,
        bool $persist = true
    ): array {
        $folders = $this->connector->list_folders(
            $account
        );

        $configured = $account->configuration['folders']
            ?? [];

        $resolved = $this->resolver->resolve(
            $folders,
            is_array($configured)
                ? $configured
                : []
        );

        if ($persist) {
            $configuration =
                $account->configuration;

            $configuration['folders'] =
                array_merge(
                    is_array($configured)
                        ? $configured
                        : [],
                    $resolved
                );

            if (!isset($configuration['imap'])) {
                $configuration['imap'] = [];
            }

            $configuration['imap']['folder'] =
                $resolved['inbox'] ?? 'INBOX';

            $this->accounts->update_configuration(
                $account->id,
                $configuration
            );
        }

        return [
            'folders' => $folders,
            'resolved' => $resolved,
            'missing' =>
                $this->resolver->missing_required(
                    $folders,
                    $resolved
                ),
        ];
    }
}