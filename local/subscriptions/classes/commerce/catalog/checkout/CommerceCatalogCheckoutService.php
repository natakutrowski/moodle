<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipeline;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/** Explicit Native checkout boundary. Remote execution must be requested deliberately. */
final class CommerceCatalogCheckoutService {
    public function __construct(
        private readonly CommerceCatalogPaymentPipeline $pipeline,
        private readonly CommercePaymentProviderContextFactory $contextfactory,
        private readonly CommercePaymentOrchestrator $orchestrator
    ) {
    }

    /** @param array<int, array{sku:string,quantity?:int}> $selections */
    public function initialize(
        string $reference,
        CommerceCustomer $customer,
        array $selections,
        string $currency,
        string $language,
        ?string $provider,
        string $returnurl,
        string $cancelurl,
        bool $live,
        bool $execute = false,
        array $metadata = []
    ): CommerceCatalogCheckoutResult {
        $pipeline = $this->pipeline->build(
            $reference, $customer, $selections, $currency, $language, $provider,
            $returnurl, $cancelurl, array_merge($metadata, ['catalogue_stage' => 'checkout'])
        );
        $request = $pipeline->get_payment_request();
        $context = $this->contextfactory->create($request, $live, ['catalogue_source' => 'native']);
        $initialization = $execute
            ? $this->orchestrator->initialize($request, $context)
            : $this->orchestrator->simulate($request, $context);

        return new CommerceCatalogCheckoutResult($pipeline, $initialization);
    }
}
