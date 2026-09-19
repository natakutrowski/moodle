<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;

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
        private readonly ?CommerceCheckoutPaymentIdentityEnricher $identityenricher = null,
        private readonly ?CommerceCheckoutSeatReservationCoordinator $seatreservations = null
    ) {}

    public function prepare(
        CommerceCheckoutContext $context,
        CommerceCustomer $customer,
        ?string $reference = null
    ): CommerceCheckoutSnapshot {
        $metadata = $context->get_metadata();
        $directpurchase =
            CommercePurchaseFlow::is_direct(
                (string)($metadata['purchase_flow'] ?? '')
            )
                ? ($metadata['direct_purchase'] ?? null)
                : null;

        if (is_array($directpurchase)) {
            $cartsnapshot =
                $this->cart->direct_snapshot(
                    $context->get_customer_id(),
                    $context->get_currency(),
                    $context->get_language(),
                    (string)($directpurchase['sku'] ?? ''),
                    (int)($directpurchase['priceid'] ?? 0),
                    (int)($directpurchase['quantity'] ?? 1),
                    (array)($directpurchase['metadata'] ?? []),
                    null,
                    isset($directpurchase['cartuuid'])
                        ? (string)$directpurchase['cartuuid']
                        : null,
                    \local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService::DEFAULT_TTL
                );
        } else {
            $cartsnapshot = $this->cart->snapshot(
                $context->get_customer_id(),
                $context->get_currency(),
                $context->get_language()
            );
        }

        $this->seatreservations?->extend_snapshot(
            $cartsnapshot,
            time(),
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );

        $summary = $this->summaries->build($cartsnapshot, $context);
        $purchase = $this->purchases->build($summary, $customer, $reference);
        $payment = $this->payments->build($purchase);
        return new CommerceCheckoutSnapshot($summary, $purchase, $payment);
    }

    public function launch(CommerceCheckoutContext $context, CommerceCustomer $customer): CommerceCheckoutLaunchResult {
        $resumereference = trim((string)(
            $context->get_metadata()['resume_purchase_reference'] ?? ''
        ));
        $snapshot = $this->prepare(
            $context,
            $customer,
            $resumereference !== '' ? $resumereference : null
        );


        try {
            $persistence = $this->persister?->persist_with_result(
                $snapshot->get_purchase_request()
            );
        } catch (CommerceInterruptedCheckoutResumeMismatchException $exception) {
            // The customer's cart changed since the interrupted checkout.
            // Never force it onto the old immutable purchase; create a fresh one.
            $snapshot = $this->prepare($context, $customer, null);
            $persistence = $this->persister?->persist_with_result(
                $snapshot->get_purchase_request()
            );
        }
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
        $this->seatreservations?->extend_snapshot(
            $snapshot->get_summary()->get_cart_snapshot(),
            time(),
            CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            'payment'
        );

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
