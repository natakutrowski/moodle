<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\task\write;
defined('MOODLE_INTERNAL') || die();
final class CommerceTaskWriteResult {
    public function __construct(private readonly int $processed = 0, private readonly int $successful = 0, private readonly int $failed = 0) {}
    public function get_processed(): int { return $this->processed; }
    public function get_successful(): int { return $this->successful; }
    public function get_failed(): int { return $this->failed; }
}
