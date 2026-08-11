<?php

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/** Immutable half-open statistics period: start <= timestamp < end. */
final class CommerceStatisticsPeriod {
    public function __construct(
        private readonly int $start,
        private readonly int $end
    ) {
        if ($start < 0 || $end <= $start) {
            throw new \coding_exception('Commerce statistics period must have a positive duration.');
        }
    }

    public static function custom(int $start, int $end): self {
        return new self($start, $end);
    }

    public static function last_days(int $days, ?int $now = null): self {
        if ($days < 1) {
            throw new \coding_exception('Statistics day count must be greater than zero.');
        }
        $now ??= time();
        return new self($now - ($days * DAYSECS), $now);
    }

    public function start(): int {
        return $this->start;
    }

    public function end(): int {
        return $this->end;
    }

    public function duration(): int {
        return $this->end - $this->start;
    }

    public function previous(): self {
        return new self($this->start - $this->duration(), $this->start);
    }

    public function contains(int $timestamp): bool {
        return $timestamp >= $this->start && $timestamp < $this->end;
    }
}
