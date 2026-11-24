<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G3B: converts a single explicitly requested gift draft into a checkout-owned
 * signed envelope. This adapter has no HTTP entry point. G4 must supply the
 * draft through an authenticated, server-controlled cart operation.
 *
 * The production caller uses CommerceGiftFeatureToggle::can_issue(), which is
 * hard-disabled by the G1 compile-time switch in this release.
 */
final class CommerceGiftCheckoutIntentBinder {
    public const DRAFT_FIELD = 'campusfr_gift_draft_v1';

    /** @param array<string,mixed> $metadata @return array<string,mixed> */
    public static function seal_checkout_line(
        array $metadata,
        string $reference,
        string $buyeremail,
        string $currency,
        int $position,
        string $sku,
        int $priceid,
        int $quantity,
        int $netminor,
        bool $islive,
        string $provider,
        bool $singleline,
        bool $enabled,
        string $keyhex
    ): array {
        // A signed envelope can only be minted here; never trust one received
        // from a cart, URL or browser. All ordinary lines remain byte-identical.
        if (array_key_exists(CommerceGiftIntentSeal::METADATA_FIELD, $metadata)) {
            throw new \DomainException('A checkout client cannot supply a signed gift envelope.');
        }
        if (!array_key_exists(self::DRAFT_FIELD, $metadata)) {
            return $metadata;
        }
        $draft = $metadata[self::DRAFT_FIELD];
        if (!$enabled || !CommerceGiftCommercialRuntimeGate::can_operate()
            || !$singleline || $quantity !== 1 || $netminor <= 0
            || strtoupper(trim($sku)) !== 'AVENT_2026'
            || (string)($metadata['operation'] ?? '') !== ''
            || !empty($metadata['is_trial']) || !empty($metadata['is_recurring'])
            || !is_array($draft)) {
            throw new \DomainException('Gift checkout is unavailable for this product or transaction.');
        }
        // Refuse a PSP inconsistent with the frozen Native currency. The
        // provider is an input from Native checkout, not the browser.
        CommerceGiftNativeProviderGuard::assert_checkout($currency, $provider);
        // G3D / G5B4B5 accepts only a draft proven to originate from our
        // protected test route. A browser-supplied draft is not a trusted intent.
        CommerceGiftDevDraftAuthority::assert_authorized(
            $metadata, $draft, $buyeremail, $currency, $sku, $priceid, $keyhex
        );
        $intent = CommerceGiftIntentSeal::canonical_intent($draft);
        $purchase = (object)[
            'reference' => $reference,
            'customeremail' => $buyeremail,
            'currency' => strtoupper($currency),
        ];
        $item = (object)[
            'position' => $position,
            'itemreference' => strtoupper(trim($sku)),
            'currency' => strtoupper($currency),
            'netminor' => $netminor,
            'quantity' => $quantity,
        ];
        $seal = CommerceGiftIntentSeal::sign($purchase, $item, $intent, $priceid, $keyhex);
        unset($metadata[self::DRAFT_FIELD], $metadata[CommerceGiftDevDraftAuthority::PROOF_FIELD]);
        $metadata[CommerceGiftIntentSeal::METADATA_FIELD] = [
            'intent' => $intent,
            'seal' => $seal,
        ];
        return $metadata;
    }
}
