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
        public readonly int $lastactivity,
        public readonly int $purchasecount = 0,
        public readonly int $successfulpurchasecount = 0,
        public readonly int $bundlecount = 0,
        public readonly int $upgradecount = 0,
        public readonly int $paymentattemptcount = 0,
        public readonly int $activegrantcount = 0,
        public readonly bool $hasguesthistory = false
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
            'purchasecount' => $this->purchasecount,
            'successfulpurchasecount' => $this->successfulpurchasecount,
            'bundlecount' => $this->bundlecount,
            'upgradecount' => $this->upgradecount,
            'paymentattemptcount' => $this->paymentattemptcount,
            'activegrantcount' => $this->activegrantcount,
            'hasguesthistory' => $this->hasguesthistory,
        ];
    }
}
