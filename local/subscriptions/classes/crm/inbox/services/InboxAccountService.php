<?php

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;
use local_subscriptions\crm\inbox\exception\InboxConfigurationException;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\validation\InboxAccountValidator;

final class InboxAccountService {

    private readonly InboxAccountValidator $validator;

    public function __construct(
        private readonly InboxAccountRepository $repository,
        private readonly InboxCredentialStoreInterface $credentials,
        ?InboxAccountValidator $validator = null
    ) {
        $this->validator =
            $validator ??
            new InboxAccountValidator($credentials);
    }

    public function configure_ovh_support_account(
        bool $enabled = false
    ): InboxAccount {
        return $this->configure_ovh_account(
            'CampusFR Support',
            'support@campusfr.fr',
            'support_ovh',
            $enabled
        );
    }

    public function configure_ovh_account(
        string $name,
        string $email,
        string $credentialkey,
        bool $enabled = false
    ): InboxAccount {
        return $this->repository->upsert(
            $name,
            $email,
            'imap_smtp',
            $credentialkey,
            [
                'imap' => [
                    'host' => 'ssl0.ovh.net',
                    'port' => 993,
                    'encryption' => 'ssl',
                    'validatecertificate' => true,
                    'folder' => 'INBOX',
                ],
                'smtp' => [
                    'host' => 'ssl0.ovh.net',
                    'port' => 465,
                    'encryption' => 'ssl',
                ],
                'folders' => [
                    'inbox' => 'INBOX',
                    'archive' => '',
                    'trash' => '',
                    'sent' => '',
                    'drafts' => '',
                ],
                'sync' => [
                    'batchsize' => 50,
                    'history' => 'all',
                    'intervalminutes' => 15,
                    'attachments' => true,
                    'folders' => [
                        'inbox',
                        'sent',
                    ],
                ],
                'retention' => [
                    'closed_days' => 730,
                    'locally_deleted_days' => 30,
                    'sync_logs_days' => 90,
                ],
            ],
            $enabled
        );
    }

    public function assert_ready(
        InboxAccount $account
    ): void {
        if (!$account->enabled) {
            throw new InboxConfigurationException(
                'crm_inbox_account_disabled',
                'local_subscriptions'
            );
        }

        $result = $this->validator->validate(
            $account
        );

        if ($result->is_valid()) {
            return;
        }

        throw new InboxConfigurationException(
            'crm_inbox_account_validation_failed',
            'local_subscriptions',
            '',
            implode(' ', $result->errors)
        );
    }
}