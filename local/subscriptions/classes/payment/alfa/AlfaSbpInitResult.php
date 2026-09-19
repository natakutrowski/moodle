<?php

namespace local_subscriptions\payment\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of an Alfa SBP dynamic QR initialisation.
 */
final class AlfaSbpInitResult {
    public function __construct(
        public readonly string $orderid,
        public readonly string $qrid,
        public readonly string $qrstatus,
        public readonly string $payload,
        public readonly ?string $renderedqr = null
    ) {
        if (trim($orderid) === '') {
            throw new \coding_exception('An Alfa SBP order id is required.');
        }
        if (trim($qrid) === '') {
            throw new \coding_exception('An Alfa SBP QR id is required.');
        }
        if (trim($payload) === '') {
            throw new \coding_exception('An Alfa SBP payload is required.');
        }
    }
}
