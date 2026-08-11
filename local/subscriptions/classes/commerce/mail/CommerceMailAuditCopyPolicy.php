<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves the optional independent audit copy for Commerce transactional mail.
 */
final class CommerceMailAuditCopyPolicy {

    public function is_enabled_for(string $mailtype): bool {
        if (!get_config('local_subscriptions', 'commerce_mail_audit_copy_enabled')) {
            return false;
        }

        $address = $this->get_address();
        if ($address === '' || !validate_email($address)) {
            return false;
        }

        return in_array(CommerceMailType::normalise($mailtype), $this->get_types(), true);
    }

    public function get_address(): string {
        return strtolower(trim((string)get_config(
            'local_subscriptions',
            'commerce_mail_audit_copy_address'
        )));
    }

    /** @return string[] */
    public function get_types(): array {
        $configured = get_config(
            'local_subscriptions',
            'commerce_mail_audit_copy_types'
        );

        if (is_array($configured)) {
            $values = array_keys(array_filter($configured));
        } else {
            $values = preg_split('/[\s,;]+/', trim((string)$configured)) ?: [];
        }

        $types = [];
        foreach ($values as $value) {
            $value = strtolower(trim((string)$value));
            if ($value !== '' && in_array($value, CommerceMailType::all(), true)) {
                $types[] = $value;
            }
        }

        return array_values(array_unique($types));
    }

    public function include_attachment(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'commerce_mail_audit_copy_include_attachment'
        );
    }
}
