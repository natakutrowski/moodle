<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\dto\CommerceCustomerHistory;

final class CommerceCustomerHistoryService {
    public function __construct(private readonly CommercePurchaseReadService $purchases) {
    }

    public function get(int $userid, ?string $email = null): CommerceCustomerHistory {
        return new CommerceCustomerHistory(
            $userid,
            $email,
            $this->purchases->find_by_customer($userid, $email)
        );
    }
}
