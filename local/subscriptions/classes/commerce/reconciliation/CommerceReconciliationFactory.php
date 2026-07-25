<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\reconciliation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\command\CommerceCommandFactory;

final class CommerceReconciliationFactory {
    public static function create(): CommerceReconciliationService {
        return new CommerceReconciliationService(
            CommerceCommandFactory::create_purchase_service(),
            new CommerceReconciliationPolicy()
        );
    }
}
