<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\currency;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartRuntimeFactory;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;

/** Rebuilds a session cart with authoritative prices from another currency. */
final class CommerceCartCurrencySwitchService {
    public static function create(): self {
        global $DB;

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($DB, $hydrator);

        return new self(
            new CommerceSessionCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceProductPriceRepository($DB, $hydrator, $products),
            $products,
            new CommerceProductTranslationRepository($DB, $hydrator, $products)
        );
    }

    public function __construct(
        private readonly CommerceSessionCartRepository $repository,
        private readonly CommerceCartSessionKeyResolver $keys,
        private readonly CommerceCartFactory $factory,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductTranslationRepository $translations
    ) {
    }

    public function switch(
        int $customerid,
        string $sourcecurrency,
        string $targetcurrency,
        string $language
    ): CommerceCartCurrencySwitchResult {
        $sourcecurrency = strtoupper(trim($sourcecurrency));
        $targetcurrency = strtoupper(trim($targetcurrency));
        if ($sourcecurrency === $targetcurrency) {
            $snapshot = CommerceCartRuntimeFactory::create()->snapshot($customerid, $sourcecurrency, $language);
            return new CommerceCartCurrencySwitchResult($snapshot);
        }

        $sourcekey = $this->keys->resolve($customerid, $sourcecurrency);
        $source = $this->repository->find($sourcekey)
            ?? $this->factory->create($customerid, $sourcecurrency);

        $items = [];
        $removed = [];
        foreach ($source->get_items() as $item) {
            $targetprice = $this->find_target_price($item->get_product_sku(), $targetcurrency);
            if ($targetprice === null || $targetprice->get_id() === null) {
                $sku = $item->get_product_sku();
                $removed[] = [
                    'sku' => $sku,
                    'label' => $this->resolve_product_label($sku, $language),
                ];
                continue;
            }
            $items[] = new CommerceCartItem(
                $item->get_product_sku(),
                (int)$targetprice->get_id(),
                $item->get_quantity(),
                $this->currency_safe_metadata($item->get_metadata())
            );
        }

        $metadata = $source->get_metadata();
        $target = $this->factory->create($customerid, $targetcurrency, $metadata)->with_items($items);
        $targetkey = $this->keys->resolve($customerid, $targetcurrency);
        $this->repository->save($targetkey, $target);

        $runtime = CommerceCartRuntimeFactory::create();
        $snapshot = $runtime->snapshot($customerid, $targetcurrency, $language);
        $promotionremoved = false;
        if (isset($metadata['promotion_code']) && $this->has_promotion_rejection($snapshot)) {
            unset($metadata['promotion_code']);
            $target = $target->with_metadata($metadata);
            $this->repository->save($targetkey, $target);
            $snapshot = $runtime->snapshot($customerid, $targetcurrency, $language);
            $promotionremoved = true;
        }

        $this->repository->delete($sourcekey);

        return new CommerceCartCurrencySwitchResult($snapshot, $this->unique_removed_items($removed), $promotionremoved);
    }


    private function resolve_product_label(string $sku, string $language): string {
        $translation = $this->translations->find($sku, $language);
        if ($translation !== null && trim($translation->get_name()) !== '') {
            return trim($translation->get_name());
        }

        $product = $this->products->find_by_sku($sku);
        if ($product !== null && trim($product->get_name()) !== '') {
            return trim($product->get_name());
        }

        return get_string('commerce_cart_currency_removed_item_fallback', 'local_subscriptions');
    }

    /**
     * @param array<int, array{sku:string,label:string}> $items
     * @return array<int, array{sku:string,label:string}>
     */
    private function unique_removed_items(array $items): array {
        $unique = [];
        foreach ($items as $item) {
            $unique[$item['sku']] = $item;
        }
        return array_values($unique);
    }

    private function find_target_price(string $sku, string $currency): ?\local_subscriptions\commerce\catalog\domain\CommerceProductPrice {
        $fallback = null;
        foreach ($this->prices->find_by_product_sku($sku, true) as $price) {
            if ($price->get_currency() !== $currency) {
                continue;
            }
            if ($price->get_provider() === null || trim((string)$price->get_provider()) === '') {
                return $price;
            }
            $fallback ??= $price;
        }
        return $fallback;
    }

    private function currency_safe_metadata(array $metadata): array {
        foreach (array_keys($metadata) as $key) {
            if (str_contains(strtolower((string)$key), 'amountminor')) {
                unset($metadata[$key]);
            }
        }
        return $metadata;
    }

    private function has_promotion_rejection(\local_subscriptions\commerce\cart\domain\CommerceCartSnapshot $snapshot): bool {
        foreach ($snapshot->get_messages() as $message) {
            if (str_starts_with($message->get_code(), 'promotion_')) {
                return true;
            }
        }
        return false;
    }
}
