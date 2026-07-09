<?php

namespace local_subscriptions\crm\intelligence\trends;

defined('MOODLE_INTERNAL') || die();

final class CrmScoreTrend {

    public function __construct(
        public readonly int $current,
        public readonly ?int $previous,
        public readonly int $delta
    ) {
    }

    public function direction(): string {
        if ($this->delta > 0) {
            return 'up';
        }

        if ($this->delta < 0) {
            return 'down';
        }

        return 'stable';
    }

    public function to_object(): \stdClass {
        return (object)[
            'current' => $this->current,
            'previous' => $this->previous,
            'delta' => $this->delta,
            'direction' => $this->direction(),
        ];
    }
}