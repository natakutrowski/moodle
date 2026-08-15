<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\readmodel;

defined('MOODLE_INTERNAL') || die();

final class CommercePurchaseSummary {
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $reference,
        public readonly string $type,
        public readonly CommercePurchaseCustomer $customer,
        public readonly array $productlabels,
        public readonly string $currency,
        public readonly int $totalminor,
        public readonly string $commercialstatus,
        public readonly string $paymentstatus,
        public readonly string $fulfillmentstatus,
        public readonly ?string $provider,
        public readonly string $source,
        public readonly int $timecreated,
        public readonly array $productitems = [],
        public readonly string $publicreference = '',
        public readonly bool $haspersonaloffer = false,
        public readonly string $personalofferuuid = '',
        public readonly string $personaloffercampaign = '',
        public readonly bool $adminclosed = false,
        public readonly int $adminclosedat = 0,
        public readonly int $adminclosedby = 0,
        public readonly string $adminclosereason = ''
    ) {
    }
}
