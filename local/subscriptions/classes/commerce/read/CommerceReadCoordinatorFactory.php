<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\policy\CommerceReadCoordinator;
use local_subscriptions\commerce\read\policy\CommerceReadPolicy;
use local_subscriptions\commerce\read\shadow\CommerceReadShadowComparator;

final class CommerceReadCoordinatorFactory {
    public static function create(): CommerceReadCoordinator {
        return new CommerceReadCoordinator(
            CommerceReadServiceFactory::create_purchase_service(),
            new CommerceReadPolicy(),
            new CommerceReadShadowComparator()
        );
    }
}
