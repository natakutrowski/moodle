<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceMapper;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;

/** Production composition root for the unified checkout runtime. */
final class CommerceCheckoutRuntimeFactory {
    public static function create(): CommerceCheckoutRuntime {
        $commerce = CommerceRuntimeFactory::create();
        $payments = new CommercePaymentRepository($GLOBALS['DB']);

        return new CommerceCheckoutRuntime(
            CommerceCartRuntimeFactory::create(),
            new CommerceCheckoutSummaryBuilder(new CommerceCheckoutValidator()),
            new CommerceCheckoutPurchaseBuilder(),
            new CommerceCheckoutPaymentRequestBuilder(),
            $commerce->payment_orchestration(),
            $commerce->payment_contexts(),
            new CommerceCheckoutPurchasePersister(
                CommercePurchaseSqlRepositoryFactory::create(),
                new CommercePurchasePersistenceMapper(),
                $payments
            ),
            new CommerceCheckoutLegacyPaymentRequestBridge($GLOBALS['DB']),
            new CommerceCheckoutPaymentLaunchRecorder($payments),
            new CommerceCheckoutPaymentIdentityEnricher()
        );
    }
}
