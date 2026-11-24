<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G5B4A: gift-only checkout presentation from an immutable server-side draft.
 * This service never creates an intention, signs arbitrary client data, issues
 * a gift, sends email, changes the cart, or grants Reader access.
 */
final class CommerceGiftCheckoutPresentation {
    /** @param array<string,mixed>|null $direct */
    public static function present(?array $direct, int $viewerid, string $buyeremail,
        string $checkoutcurrency, \moodle_database $db, string $source = ''): array {
        $metadata = is_array($direct['metadata'] ?? null) ? $direct['metadata'] : [];
        if (!array_key_exists(CommerceGiftCheckoutIntentBinder::DRAFT_FIELD, $metadata)) {
            // The normal checkout is unaffected, including legacy direct purchases.
            return ['cfrgiftcheckout' => false];
        }
        if ($viewerid <= 2 || is_siteadmin($viewerid)
            || !CommerceGiftCommercialRuntimeGate::can_operate()
            || !(new CommerceGiftFeatureToggle())->can_issue()
            || !is_array($metadata[CommerceGiftCheckoutIntentBinder::DRAFT_FIELD])
            || strtoupper((string)($direct['sku'] ?? '')) !== 'AVENT_2026'
            || !in_array(strtoupper($checkoutcurrency), ['EUR', 'RUB'], true)
            || strtoupper((string)($direct['currency'] ?? '')) !== strtoupper($checkoutcurrency)
            || (int)($direct['quantity'] ?? 0) !== 1
            || (int)($direct['priceid'] ?? 0) < 1
            || filter_var($buyeremail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DomainException('Prepared gift checkout is not authorised.');
        }
        CommerceGiftCommercialRuntimeGate::assert_buyer($db, $viewerid, $buyeremail);
        $intent = CommerceGiftIntentSeal::canonical_intent($metadata[CommerceGiftCheckoutIntentBinder::DRAFT_FIELD]);
        $key = trim((string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'));
        CommerceGiftDevDraftAuthority::assert_authorized(
            $metadata, $intent, $buyeremail, strtoupper($checkoutcurrency), 'AVENT_2026', (int)$direct['priceid'], $key
        );
        $prepared = $db->get_record(CommerceGiftPreparedCheckoutService::TABLE,
            ['lineuuid' => $intent['lineuuid']], '*', IGNORE_MISSING);
        if (!$prepared) {
            // Backward compatibility: historical G3D paid pilots deliberately
            // issued a signed intention without a persisted precheckout draft.
            // This exception is display-only, never used to authorise payments.
            if ($source !== 'gift_dev' || (int)$intent['expiresat'] <= time()) {
                throw new \DomainException('Prepared gift checkout is missing.');
            }
            $recipientid = (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::RECIPIENT_SETTING);
            $recipient = $db->get_record('user', ['id' => $recipientid, 'deleted' => 0],
                'id,email,confirmed,suspended', IGNORE_MISSING);
            if (!$recipient || empty($recipient->confirmed) || !empty($recipient->suspended)
                || !hash_equals(strtolower(trim((string)$recipient->email)), (string)$intent['recipientemail'])) {
                throw new \DomainException('Historical DEV recipient has changed.');
            }
            $prepared = (object)[
                'status' => 'checkout_started', 'giftid' => null, 'buyeruserid' => $viewerid,
                'buyeremail' => strtolower(trim($buyeremail)), 'recipientemail' => $intent['recipientemail'],
                'deliverymode' => $intent['deliverymode'], 'scheduledat' => $intent['scheduledat'],
                'expiresat' => $intent['expiresat'], 'priceid' => (int)$direct['priceid'],
                'currency' => 'EUR', 'recipientname' => '',
            ];
        }
        if ((string)$prepared->status !== 'checkout_started'
            || $prepared->giftid !== null
            || (int)$prepared->buyeruserid !== $viewerid
            || !hash_equals(strtolower(trim((string)$prepared->buyeremail)), strtolower(trim($buyeremail)))
            || !hash_equals(strtolower(trim((string)$prepared->recipientemail)), (string)$intent['recipientemail'])
            || (string)$prepared->deliverymode !== (string)$intent['deliverymode']
            || (int)($prepared->scheduledat ?? 0) !== (int)($intent['scheduledat'] ?? 0)
            || (int)$prepared->expiresat !== (int)$intent['expiresat']
            || (int)$prepared->expiresat <= time()
            || (int)$prepared->priceid !== (int)$direct['priceid']
            || (string)$prepared->currency !== strtoupper($checkoutcurrency)
            || (string)$prepared->deliverymode !== 'email'
            || !filter_var($prepared->recipientemail, FILTER_VALIDATE_EMAIL)) {
            throw new \DomainException('Prepared gift checkout data has changed or expired.');
        }
        // Mask the email for the checkout page, never expose another purchase's recipient.
        $email = strtolower(trim((string)$prepared->recipientemail));
        [$local, $domain] = explode('@', $email, 2);
        $masked = \core_text::substr($local, 0, 1) . '•••@' . $domain;
        $name = trim((string)$prepared->recipientname);
        $identity = $name !== '' ? $name . ' · ' . $masked : $masked;
        $delivery = $prepared->scheduledat === null
            ? get_string('cfr_g5b4_checkout_immediate', 'local_subscriptions')
            : get_string('cfr_g5b4_checkout_scheduled', 'local_subscriptions',
                userdate((int)$prepared->scheduledat, '%d/%m/%Y %H:%M', 'Europe/Paris'));
        return [
            'cfrgiftcheckout' => true,
            'cfrgiftcheckouttitle' => get_string('cfr_g5b4_checkout_title', 'local_subscriptions'),
            'cfrgiftcheckoutrecipientlabel' => get_string('cfr_g5b4_checkout_recipient', 'local_subscriptions'),
            'cfrgiftcheckoutrecipient' => $identity,
            'cfrgiftcheckoutdelivery' => $delivery,
            'cfrgiftcheckoutaccess' => get_string('cfr_g5b4_checkout_access', 'local_subscriptions'),
        ];
    }
}
