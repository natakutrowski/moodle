<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipeline;
use local_subscriptions\commerce\catalog\purchase\CommerceCatalogPaymentPipelineResult;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/** Builds and validates a Native catalogue payment without contacting a provider. */
final class CommerceCatalogPaymentSimulationService {
    public function __construct(
        private readonly CommerceCatalogPaymentPipeline $pipeline,
        private readonly CommercePaymentProviderContextFactory $contextfactory,
        private readonly CommercePaymentOrchestrator $orchestrator
    ) {
    }

    /** @param array<int, array{sku:string,quantity?:int}> $selections */
    public function simulate(
        string $reference,
        CommerceCustomer $customer,
        array $selections,
        string $currency,
        string $language,
        ?string $provider = null,
        ?string $returnurl = null,
        ?string $cancelurl = null,
        array $metadata = []
    ): array {
        $pipeline = $this->pipeline->build(
            $reference,
            $customer,
            $selections,
            $currency,
            $language,
            $provider,
            $returnurl,
            $cancelurl,
            array_merge($metadata, ['catalogue_stage' => 'provider_simulation'])
        );
        $request = $pipeline->get_payment_request();
        $context = $this->contextfactory->create($request, false, ['catalogue_source' => 'native']);

        return [
            'pipeline' => $pipeline,
            'initialization' => $this->orchestrator->simulate($request, $context),
        ];
    }
}
