<?php

namespace local_subscriptions\commerce\catalog\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequestFactory;
use local_subscriptions\commerce\purchase\CommerceCustomer;

/**
 * Converts Native catalogue selections into a provider-independent payment request.
 *
 * No provider is contacted and no payment request row is persisted here.
 */
final class CommerceCatalogPaymentPipeline {
    public function __construct(
        private readonly CommerceCatalogPurchasePreparationService $preparationservice,
        private readonly CommercePaymentRequestFactory $paymentrequestfactory
    ) {
    }

    /**
     * @param array<int, array{sku:string,quantity?:int}> $selections
     */
    public function build(
        string $reference,
        CommerceCustomer $customer,
        array $selections,
        string $currency,
        string $language,
        ?string $provider = null,
        ?string $returnurl = null,
        ?string $cancelurl = null,
        array $metadata = []
    ): CommerceCatalogPaymentPipelineResult {
        $preparation = $this->preparationservice->prepare(
            $reference,
            $customer,
            $selections,
            $currency,
            $language,
            $provider,
            $returnurl,
            $cancelurl,
            array_merge($metadata, ['catalogue_stage' => 'payment_request'])
        );

        return new CommerceCatalogPaymentPipelineResult(
            $preparation,
            $this->paymentrequestfactory->create($preparation)
        );
    }
}
