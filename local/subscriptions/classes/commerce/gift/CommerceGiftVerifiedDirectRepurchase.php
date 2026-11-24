<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;

/**
 * Narrow exception to the normal already-owned cart refusal.
 * This does not grant the buyer Reader access, bypass price validation,
 * or change the ownership policy for ordinary/persisted carts.
 *
 * An immutable precheckout record AND its HMAC proof are required. The proof
 * binds product, price, currency, beneficiary, buyer and schedule; the exact
 * Native checkout still validates and signs the purchased gift later.
 */
final class CommerceGiftVerifiedDirectRepurchase {
    public static function allows(CommerceCart $cart, string $sku, int $priceid,
        int $quantity, array $metadata, \moodle_database $db): bool {
        if ($sku !== 'AVENT_2026' || $priceid <= 0 || $quantity !== 1
            || !in_array($cart->get_currency(), ['EUR', 'RUB'], true)
            || ($cart->get_metadata()['purchase_flow'] ?? '') !== 'direct'
            || (string)($metadata['operation'] ?? '') !== ''
            || !empty($metadata['is_trial']) || !empty($metadata['is_recurring'])
            || array_key_exists(CommerceGiftIntentSeal::METADATA_FIELD, $metadata)
            || !CommerceGiftCommercialRuntimeGate::can_operate()
            || !(new CommerceGiftFeatureToggle())->can_issue()) {
            return false;
        }
        $draft = $metadata[CommerceGiftCheckoutIntentBinder::DRAFT_FIELD] ?? null;
        if (!is_array($draft)) {
            return false;
        }
        try {
            $intent = CommerceGiftIntentSeal::canonical_intent($draft);
            if ($intent['deliverymode'] !== 'email'
                || !is_string($intent['recipientemail'])
                || $intent['expiresat'] === null
                || $intent['expiresat'] <= time()
                || $cart->get_customer_id() <= 2) {
                return false;
            }
            // Do not treat a buyer-controlled mail address as an identity.
            $buyer = $db->get_record('user',
                ['id' => $cart->get_customer_id(), 'deleted' => 0],
                'id,email,confirmed,suspended', IGNORE_MISSING);
            if (!$buyer || empty($buyer->confirmed) || !empty($buyer->suspended)) {
                return false;
            }
            CommerceGiftCommercialRuntimeGate::assert_buyer(
                $db, (int)$buyer->id, (string)$buyer->email);

            // Prevent replay of an otherwise valid proof after a draft was paid,
            // cancelled or replaced; keep the recipient/schedule immutable.
            $record = $db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
                ['lineuuid' => $intent['lineuuid']], '*', IGNORE_MISSING);
            if (!$record || (string)$record->status !== 'checkout_started'
                || $record->giftid !== null
                || (int)$record->buyeruserid !== (int)$buyer->id
                || (int)$record->priceid !== $priceid
                || (string)$record->currency !== $cart->get_currency()
                || !hash_equals(strtolower(trim((string)$record->buyeremail)),
                    strtolower(trim((string)$buyer->email)))
                || !hash_equals(strtolower(trim((string)$record->recipientemail)),
                    (string)$intent['recipientemail'])
                || (string)$record->deliverymode !== 'email'
                || (int)($record->scheduledat ?? 0) !== (int)($intent['scheduledat'] ?? 0)
                || (int)$record->expiresat !== (int)$intent['expiresat']) {
                return false;
            }
            CommerceGiftCommercialRuntimeGate::assert_recipient(
                $db, (int)$buyer->id, (string)$buyer->email, $intent['recipientemail']);
            $key = trim((string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'));
            CommerceGiftDevDraftAuthority::assert_authorized(
                $metadata, $intent, (string)$buyer->email, $cart->get_currency(),
                $sku, $priceid, $key
            );
            return true;
        } catch (\Throwable $e) {
            // Fail closed; no raw token, HMAC, email or record emitted.
            return false;
        }
    }
}
