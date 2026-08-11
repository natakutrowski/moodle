<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\tracking;

defined('MOODLE_INTERNAL') || die();

/** Builds and validates signed, local-only customer action tracking URLs. */
final class CommerceTrackedActionUrl {
    private const ACTIONS = [
        'postpayment_view_order',
        'postpayment_open_course',
        'postpayment_open_resources',
        'postpayment_my_orders',
        'postpayment_retry',
        'order_download_invoice',
        'order_contact_support',
        'order_open_course',
        'order_download_file',
        'order_print',
    ];

    public static function build(
        string $reference,
        string $action,
        string $source,
        \moodle_url|string $destination
    ): \moodle_url {
        $destination = self::normalise_destination($destination);
        self::assert_action($action);
        self::assert_local_destination($destination);

        return new \moodle_url('/local/subscriptions/commerce_action.php', [
            'reference' => $reference,
            'action' => $action,
            'source' => clean_param($source, PARAM_ALPHANUMEXT),
            'destination' => $destination,
            'signature' => self::signature($reference, $action, $source, $destination),
        ]);
    }

    public static function validate(
        string $reference,
        string $action,
        string $source,
        string $destination,
        string $signature
    ): void {
        $destination = self::normalise_destination($destination);
        self::assert_action($action);
        if (!hash_equals(self::signature($reference, $action, $source, $destination), $signature)) {
            throw new \moodle_exception('commerce_tracking_invalid', 'local_subscriptions');
        }
    }

    public static function allowed_actions(): array {
        return self::ACTIONS;
    }


    private static function normalise_destination(\moodle_url|string $destination): string {
        global $CFG;
        if ($destination instanceof \moodle_url) {
            return $destination->out_as_local_url(false);
        }
        $destination = trim($destination);
        self::assert_local_destination($destination);
        $sitehost = parse_url((string)$CFG->wwwroot, PHP_URL_HOST);
        $parts = parse_url($destination);
        if ($parts !== false && !empty($parts['host']) && strcasecmp((string)$parts['host'], (string)$sitehost) === 0) {
            $destination = (string)($parts['path'] ?? '/');
            if (!empty($parts['query'])) {
                $destination .= '?' . $parts['query'];
            }
            if (!empty($parts['fragment'])) {
                $destination .= '#' . $parts['fragment'];
            }
        }
        self::assert_local_destination($destination);
        return $destination;
    }

    private static function signature(string $reference, string $action, string $source, string $destination): string {
        global $CFG;
        $secret = (string)($CFG->passwordsaltmain ?? $CFG->siteidentifier ?? 'moodle');
        return hash_hmac('sha256', implode('|', [$reference, $action, $source, $destination]), $secret);
    }

    private static function assert_action(string $action): void {
        if (!in_array($action, self::ACTIONS, true)) {
            throw new \moodle_exception('commerce_tracking_invalid', 'local_subscriptions');
        }
    }

    private static function assert_local_destination(string $destination): void {
        global $CFG;
        $parts = parse_url($destination);
        if ($parts === false) {
            throw new \moodle_exception('commerce_tracking_invalid', 'local_subscriptions');
        }
        if (!empty($parts['host'])) {
            $sitehost = parse_url((string)$CFG->wwwroot, PHP_URL_HOST);
            if ($sitehost === null || strcasecmp((string)$parts['host'], (string)$sitehost) !== 0) {
                throw new \moodle_exception('commerce_tracking_invalid', 'local_subscriptions');
            }
        } elseif (!str_starts_with($destination, '/')) {
            throw new \moodle_exception('commerce_tracking_invalid', 'local_subscriptions');
        }
    }
}
