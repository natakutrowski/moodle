<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserProfileStats {

    public function __construct(
        public readonly string $crmstatus,
        public readonly int $subscriptions,
        public readonly int $digitalpayments,
        public readonly int $accessiblecourses,
        public readonly float $spent_eur,
        public readonly float $spent_rub,
        public readonly int $lastactivity
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'crmstatus' => $this->crmstatus,
            'subscriptions' => $this->subscriptions,
            'digitalpayments' => $this->digitalpayments,
            'accessiblecourses' => $this->accessiblecourses,
            'spent_eur' => $this->spent_eur,
            'spent_rub' => $this->spent_rub,
            'lastactivity' => $this->lastactivity,
        ];
    }
}