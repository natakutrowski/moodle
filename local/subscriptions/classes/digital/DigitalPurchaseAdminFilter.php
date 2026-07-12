<?php

namespace local_subscriptions\digital;

defined('MOODLE_INTERNAL') || die();

final class DigitalPurchaseAdminFilter {

    public const ISSUE_NONE = '';
    public const ISSUE_EMAIL_ERROR = 'email_error';
    public const ISSUE_EXPIRED_TOKEN = 'expired_token';

    public static function normalize_status(string $status): string {
        return in_array($status, [
            '',
            'pending',
            'paid',
            'completed',
            'failed',
            'cancelled',
            'canceled',
        ], true) ? $status : '';
    }

    public static function normalize_issue(string $issue): string {
        return in_array($issue, [
            self::ISSUE_NONE,
            self::ISSUE_EMAIL_ERROR,
            self::ISSUE_EXPIRED_TOKEN,
        ], true) ? $issue : self::ISSUE_NONE;
    }

    public static function issue_label(string $issue): string {
        if ($issue === self::ISSUE_EMAIL_ERROR) {
            return get_string('digital_purchase_filter_issue_email_error', 'local_subscriptions');
        }

        if ($issue === self::ISSUE_EXPIRED_TOKEN) {
            return get_string('digital_purchase_filter_issue_expired_token', 'local_subscriptions');
        }

        return get_string('digital_purchase_filter_no_issue', 'local_subscriptions');
    }
}