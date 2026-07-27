<?php

namespace local_subscriptions\commerce\catalog\purchase;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\service\CommerceBundleExpander;
use local_subscriptions\commerce\catalog\service\CommerceProductResolver;
use local_subscriptions\commerce\purchase\CommerceCustomer;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;

/**
 * Builds provider-independent purchase requests directly from Native SKUs.
 */
final class CommerceCatalogPurchaseRequestFactory {

    public function __construct(
        private readonly CommerceProductResolver $resolver,
        private readonly CommerceBundleExpander $bundleexpander,
        private readonly CommerceCatalogPurchaseItemFactory $itemfactory
    ) {
    }

    /**
     * @param array<int, array{sku:string,quantity?:int}> $selections
     */
    public function create(
        string $reference,
        CommerceCustomer $customer,
        array $selections,
        string $currency,
        string $language,
        ?string $provider = null,
        ?string $returnurl = null,
        ?string $cancelurl = null,
        array $metadata = []
    ): CommercePurchaseRequest {
        if ($selections === []) {
            throw new \coding_exception('A Native catalogue purchase requires at least one SKU selection.');
        }

        $quantities = [];
        foreach ($selections as $selection) {
            $sku = strtoupper(trim((string)($selection['sku'] ?? '')));
            $quantity = (int)($selection['quantity'] ?? 1);
            if ($sku === '' || $quantity <= 0) {
                throw new \coding_exception('A Native catalogue selection requires a SKU and positive quantity.');
            }

            foreach ($this->bundleexpander->expand($sku, $quantity) as $expanded) {
                $childsku = $expanded->get_product()->get_sku();
                $quantities[$childsku] = ($quantities[$childsku] ?? 0) + $expanded->get_quantity();
            }
        }

        $items = [];
        foreach ($quantities as $sku => $quantity) {
            $resolved = $this->resolver->resolve_for_purchase(
                $sku,
                $currency,
                $language,
                $provider
            );
            $items[] = $this->itemfactory->create($resolved, $quantity);
        }

        return new CommercePurchaseRequest(
            $reference,
            $customer,
            $items,
            preferredprovider: $provider,
            returnurl: $returnurl,
            cancelurl: $cancelurl,
            metadata: array_merge(
                $metadata,
                [
                    'catalogue_source' => 'native',
                    'catalogue_selections' => $selections,
                    'catalogue_expanded_skus' => array_keys($quantities),
                ]
            ),
            createdat: time()
        );
    }
}
