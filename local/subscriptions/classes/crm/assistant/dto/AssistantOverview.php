<?php

namespace local_subscriptions\crm\assistant\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregated CRM Assistant counters.
 */
final class AssistantOverview {

    public function __construct(
        public readonly int $active,
        public readonly int $critical,
        public readonly int $urgent,
        public readonly int $accepted,
        public readonly int $crossdomain,
        public readonly int $users
    ) {
    }

    public function total_attention(): int {
        return $this->critical + $this->urgent;
    }

    public function to_object(): \stdClass {
        return (object)[
            'active' => $this->active,
            'critical' => $this->critical,
            'urgent' => $this->urgent,
            'accepted' => $this->accepted,
            'crossdomain' => $this->crossdomain,
            'users' => $this->users,
            'totalattention' =>
                $this->total_attention(),
        ];
    }
}