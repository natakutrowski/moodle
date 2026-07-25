<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\student;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

final class StudentCommercePurchaseFactory {
    public static function create(?string $mode = null, ?bool $strict = null): StudentCommercePurchaseService {
        return new StudentCommercePurchaseService(
            CommercePurchaseSqlRepositoryFactory::create(),
            new NativeStudentCommercePurchaseMapper(),
            $mode,
            $strict
        );
    }
}
