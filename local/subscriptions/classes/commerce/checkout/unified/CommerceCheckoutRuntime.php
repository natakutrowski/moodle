<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/** H1 application service orchestrating Cart -> Checkout -> Purchase -> Provider. */
final class CommerceCheckoutRuntime {
    public function __construct(
        private readonly CommerceCartService $cart,
        private readonly CommerceCheckoutSummaryBuilder $summaries,
        private readonly CommerceCheckoutPurchaseBuilder $purchases,
        private readonly CommerceCheckoutPaymentRequestBuilder $payments,
        private readonly CommercePaymentOrchestrator $orchestrator,
        private readonly CommercePaymentProviderContextFactory $contexts,
        private readonly ?CommerceCheckoutPurchasePersister $persister = null,
        private readonly ?CommerceCheckoutLegacyPaymentRequestBridge $legacybridge = null,
        private readonly ?CommerceCheckoutPaymentLaunchRecorder $launchrecorder = null,
        private readonly ?CommerceCheckoutPaymentIdentityEnricher $identityenricher = null
    ) {}

    public function prepare(CommerceCheckoutContext $context, CommerceCustomer $customer): CommerceCheckoutSnapshot {
        $cartsnapshot = $this->cart->snapshot(
            $context->get_customer_id(),
            $context->get_currency(),
            $context->get_language()
        );
        $summary = $this->summaries->build($cartsnapshot, $context);
        $purchase = $this->purchases->build($summary, $customer);
        $payment = $this->payments->build($purchase);
        return new CommerceCheckoutSnapshot($summary, $purchase, $payment);
    }

    public function launch(CommerceCheckoutContext $context, CommerceCustomer $customer): CommerceCheckoutLaunchResult {
        $snapshot = $this->prepare($context, $customer);
        $persistence = $this->persister?->persist_with_result(
            $snapshot->get_purchase_request()
        );
        $paymentattempt = $persistence?->get_payment_attempt();
        $paymentrequest = $snapshot->get_payment_request();

        if ($paymentattempt !== null && $this->identityenricher !== null) {
            $paymentrequest = $this->identityenricher->enrich(
                $paymentrequest,
                $paymentattempt
            );
        }

        $paymentrequest = $this->legacybridge !== null
            ? $this->legacybridge->persist_and_enrich($paymentrequest)
            : $paymentrequest;
        $providercontext = $this->contexts->create(
            $paymentrequest,
            $context->is_live(),
            ['checkout_engine' => 'unified', 'checkout_phase' => '7.95H4.4C']
        );
        $initialization = $this->orchestrator->initialize(
            $paymentrequest,
            $providercontext
        );
        if ($paymentattempt !== null && $this->launchrecorder !== null) {
            $this->launchrecorder->record(
                $paymentattempt,
                $initialization
            );
        }

        return new CommerceCheckoutLaunchResult(
            $snapshot,
            $initialization
        );
    }

    public function simulate(CommerceCheckoutContext $context, CommerceCustomer $customer): CommerceCheckoutLaunchResult {
        $snapshot = $this->prepare($context, $customer);
        $providercontext = $this->contexts->create(
            $snapshot->get_payment_request(),
            false,
            ['checkout_engine' => 'unified', 'checkout_phase' => '7.95H1', 'simulation' => true]
        );
        return new CommerceCheckoutLaunchResult(
            $snapshot,
            $this->orchestrator->simulate($snapshot->get_payment_request(), $providercontext)
        );
    }
}
