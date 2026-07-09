<?php

namespace local_subscriptions\crm\intelligence\reports;

defined('MOODLE_INTERNAL') || die();

final class CrmFunnelReport {

    public function __construct(
        public readonly int $users,
        public readonly int $trials,
        public readonly int $customers,
        public readonly int $digitalCustomers,
        public readonly int $expiredCustomers
    ) {
    }

    public function trial_conversion_rate(): float {
        if ($this->trials <= 0) {
            return 0.0;
        }

        return round(($this->customers / $this->trials) * 100, 2);
    }

    public function to_object(): \stdClass {
        return (object)[
            'users' => $this->users,
            'trials' => $this->trials,
            'customers' => $this->customers,
            'digitalcustomers' => $this->digitalCustomers,
            'expiredcustomers' => $this->expiredCustomers,
            'trialconversionrate' => $this->trial_conversion_rate(),
        ];
    }
}