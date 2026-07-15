<?php

namespace local_subscriptions\crm\inbox\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\domain\InboxAccount;

final class InboxAccountValidator {

    private const ALLOWED_ENCRYPTIONS = [
        'ssl',
        'tls',
        'none',
    ];

    public function __construct(
        private readonly InboxCredentialStoreInterface $credentials
    ) {
    }

    public function validate(
        InboxAccount $account
    ): InboxAccountValidationResult {
        $errors = [];
        $warnings = [];

        if (
            trim($account->email) === '' ||
            !validate_email($account->email)
        ) {
            $errors[] = get_string(
                'crm_inbox_validation_invalid_email',
                'local_subscriptions'
            );
        }

        if (trim($account->provider) === '') {
            $errors[] = get_string(
                'crm_inbox_validation_provider_missing',
                'local_subscriptions'
            );
        }

        if (
            $account->credentialkey === null ||
            trim($account->credentialkey) === ''
        ) {
            $errors[] = get_string(
                'crm_inbox_account_no_credential',
                'local_subscriptions'
            );
        } else if (
            !$this->credentials->has(
                $account->credentialkey
            )
        ) {
            $errors[] = get_string(
                'crm_inbox_credential_missing',
                'local_subscriptions',
                $account->credentialkey
            );
        }

        $imap = $account->configuration['imap']
            ?? null;

        if (!is_array($imap)) {
            $errors[] = get_string(
                'crm_inbox_imap_configuration_missing',
                'local_subscriptions'
            );
        } else {
            $this->validate_endpoint(
                'IMAP',
                $imap,
                993,
                $errors,
                $warnings
            );

            $folder = trim(
                (string)($imap['folder'] ?? '')
            );

            if ($folder === '') {
                $warnings[] = get_string(
                    'crm_inbox_validation_inbox_folder_missing',
                    'local_subscriptions'
                );
            }
        }

        $smtp = $account->configuration['smtp']
            ?? null;

        if (!is_array($smtp)) {
            $errors[] = get_string(
                'crm_inbox_validation_smtp_missing',
                'local_subscriptions'
            );
        } else {
            $this->validate_endpoint(
                'SMTP',
                $smtp,
                465,
                $errors,
                $warnings
            );
        }

        $sync = $account->configuration['sync']
            ?? [];

        if (!is_array($sync)) {
            $errors[] = get_string(
                'crm_inbox_validation_sync_missing',
                'local_subscriptions'
            );
        } else {
            $batchsize = (int)(
                $sync['batchsize'] ?? 0
            );

            if (
                $batchsize < 1 ||
                $batchsize > 200
            ) {
                $errors[] = get_string(
                    'crm_inbox_validation_batchsize',
                    'local_subscriptions'
                );
            }

            $interval = (int)(
                $sync['intervalminutes'] ?? 0
            );

            if (
                $interval < 5 ||
                $interval > 1440
            ) {
                $errors[] = get_string(
                    'crm_inbox_validation_interval',
                    'local_subscriptions'
                );
            }
        }

        $folders = $account->configuration['folders']
            ?? [];

        if (!is_array($folders)) {
            $warnings[] = get_string(
                'crm_inbox_validation_folders_missing',
                'local_subscriptions'
            );
        } else {
            foreach (
                ['inbox', 'sent', 'trash']
                as $requiredfolder
            ) {
                if (
                    trim(
                        (string)(
                            $folders[$requiredfolder]
                            ?? ''
                        )
                    ) === ''
                ) {
                    $warnings[] = get_string(
                        'crm_inbox_validation_folder_missing',
                        'local_subscriptions',
                        $requiredfolder
                    );
                }
            }
        }

        return new InboxAccountValidationResult(
            array_values(array_unique($errors)),
            array_values(array_unique($warnings))
        );
    }

    private function validate_endpoint(
        string $label,
        array $configuration,
        int $defaultport,
        array &$errors,
        array &$warnings
    ): void {
        $host = trim(
            (string)($configuration['host'] ?? '')
        );

        if ($host === '') {
            $errors[] = get_string(
                'crm_inbox_validation_host_missing',
                'local_subscriptions',
                $label
            );
        }

        $port = (int)(
            $configuration['port']
            ?? $defaultport
        );

        if ($port < 1 || $port > 65535) {
            $errors[] = get_string(
                'crm_inbox_validation_port_invalid',
                'local_subscriptions',
                $label
            );
        }

        $encryption = \core_text::strtolower(
            trim(
                (string)(
                    $configuration['encryption']
                    ?? ''
                )
            )
        );

        if (
            !in_array(
                $encryption,
                self::ALLOWED_ENCRYPTIONS,
                true
            )
        ) {
            $errors[] = get_string(
                'crm_inbox_validation_encryption_invalid',
                'local_subscriptions',
                $label
            );
        }

        if ($encryption === 'none') {
            $warnings[] = get_string(
                'crm_inbox_validation_unencrypted',
                'local_subscriptions',
                $label
            );
        }
    }
}