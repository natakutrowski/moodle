<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\action;

defined('MOODLE_INTERNAL') || die();

/** Builds the purchase command service with its Moodle persistence dependencies. */
final class CommercePurchaseActionServiceFactory {
    public static function create(): CommercePurchaseActionService {
        global $DB;

        return new CommercePurchaseActionService($DB);
    }
}
