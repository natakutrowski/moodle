<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\read\repository\NativeEntitlementReadRepository;
use local_subscriptions\commerce\read\repository\NativeFulfillmentReadRepository;
use local_subscriptions\commerce\read\repository\NativePaymentReadRepository;
use local_subscriptions\commerce\read\repository\NativePurchaseReadRepository;
use local_subscriptions\commerce\read\service\CommerceCustomerHistoryService;
use local_subscriptions\commerce\read\service\CommerceEntitlementReadService;
use local_subscriptions\commerce\read\service\CommerceFulfillmentReadService;
use local_subscriptions\commerce\read\service\CommercePaymentReadService;
use local_subscriptions\commerce\read\service\CommercePurchaseReadService;
use local_subscriptions\commerce\read\service\CommerceReadModelMapper;

/** Composition root for I10C native read services. */
final class CommerceReadServiceFactory {
    public static function create_purchase_service(): CommercePurchaseReadService {
        global $DB;

        return new CommercePurchaseReadService(
            new NativePurchaseReadRepository($DB),
            new NativePaymentReadRepository($DB),
            new NativeFulfillmentReadRepository($DB),
            new NativeEntitlementReadRepository($DB),
            new CommerceReadModelMapper()
        );
    }

    public static function create_payment_service(): CommercePaymentReadService {
        return new CommercePaymentReadService(self::create_purchase_service());
    }

    public static function create_fulfillment_service(): CommerceFulfillmentReadService {
        return new CommerceFulfillmentReadService(self::create_purchase_service());
    }

    public static function create_entitlement_service(): CommerceEntitlementReadService {
        return new CommerceEntitlementReadService(self::create_purchase_service());
    }

    public static function create_customer_history_service(): CommerceCustomerHistoryService {
        return new CommerceCustomerHistoryService(self::create_purchase_service());
    }
}
