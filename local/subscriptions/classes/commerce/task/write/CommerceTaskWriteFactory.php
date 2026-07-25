<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\task\write;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\command\CommerceCommandFactory;
use local_subscriptions\commerce\command\policy\CommerceWritePolicy;
final class CommerceTaskWriteFactory {
    public static function create(): CommerceTaskWriteCoordinator {
        return new CommerceTaskWriteCoordinator(CommerceCommandFactory::create_purchase_service(), new CommerceWritePolicy());
    }
}
