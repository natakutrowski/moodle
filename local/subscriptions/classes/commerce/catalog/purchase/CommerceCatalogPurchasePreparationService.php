<?php

namespace local_subscriptions\commerce\catalog\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparation;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparationOrchestrator;

/**
 * Builds and business-prepares a purchase directly from Native catalogue SKUs.
 *
 * This service is provider-independent and has no persistence side effect.
 */
final class CommerceCatalogPurchasePreparationService {

    public function __construct(
        private readonly CommerceCatalogPurchaseRequestFactory $requestfactory,
        private readonly CommercePurchasePreparationOrchestrator $orchestrator
    ) {
    }

    /**
     * @param array<int, array{sku:string,quantity?:int}> $selections
     */
    public function prepare(
        string $reference,
        CommerceCustomer $customer,
        array $selections,
        string $currency,
        string $language,
        ?string $provider = null,
        ?string $returnurl = null,
        ?string $cancelurl = null,
        array $metadata = []
    ): CommercePurchasePreparation {
        $request = $this->requestfactory->create(
            $reference,
            $customer,
            $selections,
            $currency,
            $language,
            $provider,
            $returnurl,
            $cancelurl,
            array_merge($metadata, ['catalogue_stage' => 'preparation'])
        );

        return $this->orchestrator->prepare($request);
    }
}
