<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\policy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

/**
 * Determines whether a persisted Commerce purchase is expected to produce entitlements.
 *
 * The decision is based on the immutable catalogue type persisted on each purchase item.
 * Missing or unknown type metadata is treated conservatively as entitlement-bearing so a
 * broken course purchase can never be mistaken for a legitimate no-fulfillment purchase.
 */
final class CommercePurchaseEntitlementExpectationPolicy {
    private readonly CommerceProductTypeRegistry $types;

    public function __construct(?CommerceProductTypeRegistry $types = null) {
        $this->types = $types ?? CommerceProductTypeRegistry::create_default();
    }

    public function details_requires_fulfillment(CommercePurchaseDetails $purchase): bool {
        return $this->raw_items_require_fulfillment($purchase->items);
    }

    public function summary_requires_fulfillment(CommercePurchaseSummary $purchase): bool {
        return $this->metadata_items_require_fulfillment($purchase->productitems);
    }

    public function order_requires_fulfillment(CommerceOrderPresentation $order): bool {
        if ($order->items === []) {
            return true;
        }

        foreach ($order->items as $item) {
            $metadata = is_array($item->metadata ?? null) ? $item->metadata : [];
            if ($this->catalogue_type_requires_fulfillment((string)($metadata['catalogue_type'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    public function details_is_donation_only(CommercePurchaseDetails $purchase): bool {
        return $this->raw_items_are_exclusively_type($purchase->items, CommerceProductType::DONATION);
    }

    public function summary_is_donation_only(CommercePurchaseSummary $purchase): bool {
        return $this->metadata_items_are_exclusively_type($purchase->productitems, CommerceProductType::DONATION);
    }

    public function order_is_donation_only(CommerceOrderPresentation $order): bool {
        if ($order->items === []) {
            return false;
        }

        foreach ($order->items as $item) {
            $metadata = is_array($item->metadata ?? null) ? $item->metadata : [];
            if (CommerceProductType::normalise((string)($metadata['catalogue_type'] ?? '')) !== CommerceProductType::DONATION) {
                return false;
            }
        }

        return true;
    }

    /** @param \stdClass[] $items */
    private function raw_items_require_fulfillment(array $items): bool {
        if ($items === []) {
            return true;
        }

        foreach ($items as $item) {
            $metadata = $this->decode_json((string)($item->metadatajson ?? ''));
            if ($this->catalogue_type_requires_fulfillment((string)($metadata['catalogue_type'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int,array<string,mixed>> $items */
    private function metadata_items_require_fulfillment(array $items): bool {
        if ($items === []) {
            return true;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                return true;
            }
            if ($this->catalogue_type_requires_fulfillment((string)($item['catalogue_type'] ?? ''))) {
                return true;
            }
        }

        return false;
    }

    /** @param \stdClass[] $items */
    private function raw_items_are_exclusively_type(array $items, string $expectedtype): bool {
        if ($items === []) {
            return false;
        }

        $expectedtype = CommerceProductType::normalise($expectedtype);
        foreach ($items as $item) {
            $metadata = $this->decode_json((string)($item->metadatajson ?? ''));
            if (CommerceProductType::normalise((string)($metadata['catalogue_type'] ?? '')) !== $expectedtype) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int,array<string,mixed>> $items */
    private function metadata_items_are_exclusively_type(array $items, string $expectedtype): bool {
        if ($items === []) {
            return false;
        }

        $expectedtype = CommerceProductType::normalise($expectedtype);
        foreach ($items as $item) {
            if (!is_array($item)
                    || CommerceProductType::normalise((string)($item['catalogue_type'] ?? '')) !== $expectedtype) {
                return false;
            }
        }

        return true;
    }

    private function catalogue_type_requires_fulfillment(string $type): bool {
        $type = CommerceProductType::normalise($type);
        if ($type === '' || !$this->types->has($type)) {
            return true;
        }

        $capabilities = $this->types->get($type)->get_capabilities();
        return $capabilities->supports_entitlements() || $capabilities->is_expandable();
    }

    /** @return array<string,mixed> */
    private function decode_json(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
