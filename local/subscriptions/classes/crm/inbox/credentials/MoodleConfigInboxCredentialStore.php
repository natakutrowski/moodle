<?php

namespace local_subscriptions\crm\inbox\credentials;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\contracts\InboxCredentialStoreInterface;
use local_subscriptions\crm\inbox\exception\InboxConfigurationException;

final class MoodleConfigInboxCredentialStore implements
    InboxCredentialStoreInterface {

    public function has(string $credentialkey): bool {
        $credentials = $this->all();

        if (!isset($credentials[$credentialkey])) {
            return false;
        }

        $credential = $credentials[$credentialkey];

        return is_array($credential)
            && trim((string)($credential['username'] ?? '')) !== ''
            && trim((string)($credential['password'] ?? '')) !== '';
    }

    public function get_username(
        string $credentialkey
    ): string {
        return $this->get_value(
            $credentialkey,
            'username'
        );
    }

    public function get_password(
        string $credentialkey
    ): string {
        return $this->get_value(
            $credentialkey,
            'password'
        );
    }

    private function get_value(
        string $credentialkey,
        string $field
    ): string {
        $credentials = $this->all();

        if (!isset($credentials[$credentialkey])) {
            throw new InboxConfigurationException(
                'crm_inbox_credential_missing',
                'local_subscriptions',
                '',
                $credentialkey
            );
        }

        $credential = $credentials[$credentialkey];

        if (!is_array($credential)) {
            throw new InboxConfigurationException(
                'crm_inbox_credential_invalid',
                'local_subscriptions',
                '',
                $credentialkey
            );
        }

        $value = trim(
            (string)($credential[$field] ?? '')
        );

        if ($value === '') {
            throw new InboxConfigurationException(
                'crm_inbox_credential_field_missing',
                'local_subscriptions',
                '',
                (object)[
                    'key' => $credentialkey,
                    'field' => $field,
                ]
            );
        }

        return $value;
    }

    private function all(): array {
        global $CFG;

        $credentials =
            $CFG->local_subscriptions_inbox_credentials
                ?? [];

        return is_array($credentials)
            ? $credentials
            : [];
    }
}