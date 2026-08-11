<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartMessage;
use local_subscriptions\commerce\cart\domain\CommerceCartOperationResult;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\ownership\CommerceCartOwnershipGateway;
use local_subscriptions\commerce\cart\ownership\CommerceBundlePurchaseEligibilityService;
use local_subscriptions\commerce\cart\ownership\CommerceNullCartOwnershipGateway;
use local_subscriptions\commerce\cart\repository\CommerceCartRepository;
use local_subscriptions\commerce\trial\CommerceTrialCartPricingService;

/** Application service owning all mutations of the active cart. */
final class CommerceCartService {
    private readonly CommerceCartOwnershipGateway $ownership;

    public function __construct(
        private readonly CommerceCartRepository $repository,
        private readonly CommerceCartSessionKeyResolver $keys,
        private readonly CommerceCartFactory $factory,
        private readonly CommerceCartCalculator $calculator,
        private readonly ?CommerceCartCatalogGateway $catalog = null,
        ?CommerceCartOwnershipGateway $ownership = null,
        private readonly ?CommerceCartUpgradePricingService $upgrades = null,
        private readonly ?CommerceBundlePurchaseEligibilityService $bundleeligibility = null,
        private readonly ?CommerceTrialCartPricingService $trialpricing = null
    ) {
        $this->ownership = $ownership ?? new CommerceNullCartOwnershipGateway();
    }

    public function open(int $customerid, string $currency): CommerceCart {
        $key = $this->keys->resolve($customerid, $currency);
        $cart = $this->repository->find($key);
        if ($cart !== null) {
            return $cart;
        }
        $cart = $this->factory->create($customerid, $currency);
        $this->repository->save($key, $cart);
        return $cart;
    }

    public function save(CommerceCart $cart): void {
        $this->repository->save(
            $this->keys->resolve($cart->get_customer_id(), $cart->get_currency()),
            $cart
        );
    }

    public function add_product(
        int $customerid,
        string $currency,
        string $language,
        string $productsku,
        int $priceid,
        int $quantity = 1,
        array $metadata = [],
        ?int $at = null
    ): CommerceCartOperationResult {
        $cart = $this->open($customerid, $currency);
        $sku = strtoupper(trim($productsku));

        $operation = strtolower(trim((string)($metadata['operation'] ?? '')));
        $isupgrade = $operation === 'upgrade';

        if ($isupgrade) {
            if ($quantity !== 1 || $customerid <= 0 || $this->upgrades === null) {
                return new CommerceCartOperationResult($cart, false, [
                    new CommerceCartMessage('upgrade_not_eligible', CommerceCartMessage::LEVEL_WARNING, ['productsku' => $sku]),
                ]);
            }
            try {
                $metadata = array_replace($metadata, $this->upgrades->canonical_metadata(
                    $customerid,
                    $sku,
                    $cart->get_currency(),
                    isset($metadata['targetplanid']) ? (int)$metadata['targetplanid'] : null
                ));

                // Trial can stack after the differential upgrade price. Keep
                // operation=upgrade while adding server-authoritative Trial data.
                if ($this->trialpricing !== null) {
                    try {
                        $trialmetadata = $this->trialpricing->canonical_metadata(
                            $customerid,
                            $sku,
                            $cart->get_currency(),
                            $at
                        );
                        unset($trialmetadata['operation']);
                        $metadata = array_replace($metadata, $trialmetadata);
                    } catch (\Throwable) {
                        // The upgrade remains valid without a Trial benefit.
                    }
                }
            } catch (\Throwable) {
                return new CommerceCartOperationResult($cart, false, [
                    new CommerceCartMessage('upgrade_not_eligible', CommerceCartMessage::LEVEL_WARNING, ['productsku' => $sku]),
                ]);
            }
        } else {
            // Trial pricing is a server-side customer entitlement, not a
            // browser-selected operation. Eligible course products are
            // automatically marked with canonical Trial metadata.
            if (
                $quantity === 1
                && $customerid > 0
                && $this->trialpricing !== null
            ) {
                try {
                    $metadata = array_replace(
                        $metadata,
                        $this->trialpricing->canonical_metadata(
                            $customerid,
                            $sku,
                            $cart->get_currency(),
                            $at
                        )
                    );
                } catch (\Throwable) {
                    // Normal products and non-eligible courses continue with
                    // their standard price and metadata.
                }
            }

            if ($this->ownership->owns($customerid, $sku)) {
                return new CommerceCartOperationResult($cart, false, [
                    new CommerceCartMessage(
                        'already_owned',
                        CommerceCartMessage::LEVEL_NOTICE,
                        ['productsku' => $sku]
                    ),
                ]);
            }
        }

        $bundleeligibility = $this->bundleeligibility?->evaluate($customerid, $sku);
        if ($bundleeligibility !== null && $bundleeligibility->is_fully_owned()) {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('bundle_all_owned', CommerceCartMessage::LEVEL_WARNING, [
                    'productsku' => $sku,
                    'ownedcount' => $bundleeligibility->get_owned_count(),
                    'componentcount' => $bundleeligibility->get_component_count(),
                ]),
            ]);
        }

        $quote = $this->require_catalog()->quote($sku, $priceid, $cart->get_currency(), $language, $at);
        $key = $quote->get_product_sku() . ':' . $quote->get_price_id();
        $existing = $cart->get_item($key);
        $targetquantity = $existing === null ? $quantity : $existing->get_quantity() + $quantity;

        if ($existing !== null && !$quote->get_quantity_policy()->allows($targetquantity)) {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('already_in_cart', CommerceCartMessage::LEVEL_NOTICE, ['productsku' => $sku]),
            ]);
        }

        $quote->get_quantity_policy()->assert_allowed($targetquantity);
        $items = $cart->get_items();
        $replacement = $existing === null
            ? new CommerceCartItem($sku, $priceid, $targetquantity, $metadata)
            : $existing->with_quantity($targetquantity);
        $items = $this->replace_item($items, $replacement);
        $cart = $cart->with_items($items);
        $this->save($cart);

        $messages = [];
        if ($bundleeligibility !== null && $bundleeligibility->is_partially_owned()) {
            $messages[] = new CommerceCartMessage('bundle_partial_owned', CommerceCartMessage::LEVEL_WARNING, [
                'productsku' => $sku,
                'ownedcount' => $bundleeligibility->get_owned_count(),
                'componentcount' => $bundleeligibility->get_component_count(),
            ]);
        }

        return new CommerceCartOperationResult($cart, true, $messages);
    }

    public function update_quantity(
        int $customerid,
        string $currency,
        string $language,
        string $productsku,
        int $priceid,
        int $quantity,
        ?int $at = null
    ): CommerceCartOperationResult {
        $cart = $this->open($customerid, $currency);
        $key = strtoupper(trim($productsku)) . ':' . $priceid;
        $existing = $cart->get_item($key);
        if ($existing === null) {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('item_not_found', CommerceCartMessage::LEVEL_WARNING, ['key' => $key]),
            ]);
        }

        $quote = $this->require_catalog()->quote(
            $existing->get_product_sku(),
            $existing->get_price_id(),
            $cart->get_currency(),
            $language,
            $at
        );
        $quote->get_quantity_policy()->assert_allowed($quantity);
        if ($quantity === $existing->get_quantity()) {
            return new CommerceCartOperationResult($cart, false);
        }

        $cart = $cart->with_items($this->replace_item($cart->get_items(), $existing->with_quantity($quantity)));
        $this->save($cart);
        return new CommerceCartOperationResult($cart, true);
    }

    public function remove_product(
        int $customerid,
        string $currency,
        string $productsku,
        int $priceid
    ): CommerceCartOperationResult {
        $cart = $this->open($customerid, $currency);
        $key = strtoupper(trim($productsku)) . ':' . $priceid;
        if ($cart->get_item($key) === null) {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('item_not_found', CommerceCartMessage::LEVEL_WARNING, ['key' => $key]),
            ]);
        }
        $items = array_values(array_filter(
            $cart->get_items(),
            static fn(CommerceCartItem $item): bool => $item->get_key() !== $key
        ));
        $cart = $cart->with_items($items);
        $this->save($cart);
        return new CommerceCartOperationResult($cart, true);
    }

    public function apply_promotion_code(
        int $customerid,
        string $currency,
        string $code
    ): CommerceCartOperationResult {
        $cart = $this->open($customerid, $currency);
        $normalised = strtoupper(trim($code));
        if ($normalised === '') {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('promotion_code_required', CommerceCartMessage::LEVEL_WARNING),
            ]);
        }
        $metadata = $cart->get_metadata();
        if (($metadata['promotion_code'] ?? null) === $normalised) {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('promotion_already_applied', CommerceCartMessage::LEVEL_NOTICE),
            ]);
        }

        // A manual code is persisted only after a complete server-side calculation accepts it.
        // This prevents rejected codes from leaking into the cart summary or the session state.
        $candidate = $cart->with_metadata(array_replace($metadata, ['promotion_code' => $normalised]));
        $snapshot = $this->calculator->calculate($candidate, current_language());
        $rejections = array_values(array_filter(
            $snapshot->get_messages(),
            static fn(CommerceCartMessage $message): bool => str_starts_with($message->get_code(), 'promotion_')
                && $message->get_level() !== CommerceCartMessage::LEVEL_NOTICE
        ));

        if ($rejections !== []) {
            return new CommerceCartOperationResult($cart, false, $rejections);
        }

        $hasmanualadjustment = false;
        foreach ($snapshot->get_promotion_adjustments() as $adjustment) {
            if ($adjustment->get_code() === $normalised) {
                $hasmanualadjustment = true;
                break;
            }
        }
        if (!$hasmanualadjustment) {
            return new CommerceCartOperationResult($cart, false, [
                new CommerceCartMessage('promotion_no_eligible_product', CommerceCartMessage::LEVEL_WARNING),
            ]);
        }

        $this->save($candidate);
        return new CommerceCartOperationResult($candidate, true, [
            new CommerceCartMessage('promotion_code_saved', CommerceCartMessage::LEVEL_NOTICE),
        ]);
    }

    public function remove_promotion_code(int $customerid, string $currency): CommerceCartOperationResult {
        $cart = $this->open($customerid, $currency);
        $metadata = $cart->get_metadata();
        if (!array_key_exists('promotion_code', $metadata)) {
            return new CommerceCartOperationResult($cart, false);
        }
        unset($metadata['promotion_code']);
        $cart = $cart->with_metadata($metadata);
        $this->save($cart);
        return new CommerceCartOperationResult($cart, true, [
            new CommerceCartMessage('promotion_removed', CommerceCartMessage::LEVEL_NOTICE),
        ]);
    }

    public function snapshot(int $customerid, string $currency, string $language, ?int $at = null): CommerceCartSnapshot {
        return $this->calculator->calculate($this->open($customerid, $currency), $language, $at);
    }

    public function clear(int $customerid, string $currency): void {
        $this->repository->delete($this->keys->resolve($customerid, $currency));
    }

    public function clear_cart(int $customerid, string $currency): CommerceCartOperationResult {
        $cart = $this->open($customerid, $currency);
        if ($cart->is_empty()) {
            return new CommerceCartOperationResult($cart, false);
        }
        $cart = $cart->with_items([]);
        $this->save($cart);
        return new CommerceCartOperationResult($cart, true);
    }

    /** @param CommerceCartItem[] $items */
    private function replace_item(array $items, CommerceCartItem $replacement): array {
        $result = [];
        $replaced = false;
        foreach ($items as $item) {
            if ($item->get_key() === $replacement->get_key()) {
                $result[] = $replacement;
                $replaced = true;
            } else {
                $result[] = $item;
            }
        }
        if (!$replaced) {
            $result[] = $replacement;
        }
        return $result;
    }

    private function require_catalog(): CommerceCartCatalogGateway {
        if ($this->catalog === null) {
            throw new \coding_exception('Cart mutations require a Commerce cart catalogue gateway.');
        }
        return $this->catalog;
    }
}
