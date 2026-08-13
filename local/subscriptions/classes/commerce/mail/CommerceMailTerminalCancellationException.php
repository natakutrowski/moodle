<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Signals a deterministic mail cancellation that must never be retried.
 *
 * This is intentionally distinct from transport/rendering failures: the
 * message is no longer valid to send, so retrying would only create noise.
 */
final class CommerceMailTerminalCancellationException extends \RuntimeException {
    public function __construct(
        private readonly string $reason,
        string $message
    ) {
        parent::__construct($message);
    }

    public function get_reason(): string {
        return $this->reason;
    }
}
