<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\command;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\command\service\CommercePurchaseCommandService;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFactory;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyRepository;
use local_subscriptions\commerce\idempotency\CommerceIdempotencyService;

final class CommerceCommandFactory {
    public static function create_purchase_service(): CommercePurchaseCommandService {
        return new CommercePurchaseCommandService(
            CommerceDualWriteFactory::create(),
            new CommerceIdempotencyService(
                new CommerceIdempotencyRepository()
            )
        );
    }
}
