<?php

namespace local_subscriptions\commerce\fulfillment\postaction;

defined('MOODLE_INTERNAL') || die();

/**
 * Result of a non-critical post-fulfillment action.
 */
final class CommercePostFulfillmentActionResult {

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly string $actionkey,
        private readonly string $status,
        private readonly ?string $message = null,
        private readonly array $metadata = []
    ) {
        if (trim($actionkey) === '') {
            throw new \coding_exception(
                'A Commerce post-fulfillment action key cannot be empty.'
            );
        }

        if (!in_array($status, [
            self::STATUS_COMPLETED,
            self::STATUS_SKIPPED,
            self::STATUS_FAILED,
        ], true)) {
            throw new \coding_exception(
                'Unsupported Commerce post-fulfillment status: ' . $status
            );
        }
    }

    public function get_action_key(): string {
        return $this->actionkey;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_message(): ?string {
        return $this->message;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function is_successful(): bool {
        return $this->status !== self::STATUS_FAILED;
    }
}
