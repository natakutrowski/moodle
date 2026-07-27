<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\postaction;

defined('MOODLE_INTERNAL') || die();

/** Result of one non-critical Native post-fulfillment action. */
final class CommerceNativePostFulfillmentActionResult {
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly string $actionkey,
        private readonly string $status,
        private readonly ?string $message = null,
        private readonly array $payload = []
    ) {
        if (trim($this->actionkey) === '') {
            throw new \coding_exception('A Native post-fulfillment action key cannot be empty.');
        }
        if (!in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_SKIPPED, self::STATUS_FAILED], true)) {
            throw new \coding_exception('Invalid Native post-fulfillment action status.');
        }
    }

    public function get_action_key(): string { return $this->actionkey; }
    public function get_status(): string { return $this->status; }
    public function get_message(): ?string { return $this->message; }
    public function get_payload(): array { return $this->payload; }
    public function is_successful(): bool { return $this->status !== self::STATUS_FAILED; }
}
