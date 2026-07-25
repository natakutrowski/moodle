<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\shadow;

defined('MOODLE_INTERNAL') || die();

final class CommerceReadShadowComparison {
    /** @param CommerceReadDifference[] $differences */
    public function __construct(public readonly array $differences) {
    }

    public function highest_severity(): ?string {
        $order = [
            CommerceReadDifference::INFO => 1,
            CommerceReadDifference::EXPECTED => 2,
            CommerceReadDifference::WARNING => 3,
            CommerceReadDifference::CRITICAL => 4,
        ];
        $highest = null;
        $score = 0;

        foreach ($this->differences as $difference) {
            $current = $order[$difference->severity] ?? 0;
            if ($current > $score) {
                $score = $current;
                $highest = $difference->severity;
            }
        }

        return $highest;
    }
}
