<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Retry delays for transactional messages.
 */
final class CommerceMailRetryPolicy {

    /** @var int[] */
    private const DELAYS = [300, 1800, 7200, 43200];

    public function next_runtime(int $attemptcount, int $now): ?int {
        if ($attemptcount <= 0) {
            throw new \coding_exception('Commerce mail attempt count must be positive.');
        }

        $index = $attemptcount - 1;
        if (!array_key_exists($index, self::DELAYS)) {
            return null;
        }

        return $now + self::DELAYS[$index];
    }
}
