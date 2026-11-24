<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Future checkout-owned intent envelope. HMAC protects the persisted item
 * from client-supplied or replayed metadata. G3A does not create these seals
 * in the public checkout; a later lot must perform that privileged step.
 */
final class CommerceGiftIntentSeal {
    public const METADATA_FIELD = 'campusfr_gift_v1';

    /** @param array<string, mixed> $intent @return array<string, mixed> */
    public static function canonical_intent(array $intent): array {
        $expected = ['lineuuid', 'deliverymode', 'recipientemail', 'scheduledat', 'expiresat'];
        $keys = array_keys($intent);
        sort($keys);
        $sortedexpected = $expected;
        sort($sortedexpected);
        if ($keys !== $sortedexpected) {
            throw new \DomainException('Gift intent fields are incomplete or unrecognised.');
        }
        $uuid = (string)$intent['lineuuid'];
        $mode = (string)$intent['deliverymode'];
        $email = $intent['recipientemail'];
        if (preg_match('/\A[a-f0-9]{32}\z/D', $uuid) !== 1) {
            throw new \DomainException('Gift line reference is invalid.');
        }
        if (!in_array($mode, ['email', 'link'], true)) {
            throw new \DomainException('Gift delivery mode is invalid.');
        }
        if ($email !== null) {
            if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new \DomainException('Gift recipient email is invalid.');
            }
            $email = strtolower(trim($email));
        }
        if (($mode === 'email' && $email === null) || ($mode === 'link' && $email !== null)) {
            throw new \DomainException('Gift delivery mode and recipient mismatch.');
        }
        foreach (['scheduledat', 'expiresat'] as $datekey) {
            if ($intent[$datekey] !== null && (!is_int($intent[$datekey]) || $intent[$datekey] <= 0)) {
                throw new \DomainException('Gift schedule must contain an integer timestamp.');
            }
        }
        if ($intent['scheduledat'] !== null && $intent['expiresat'] !== null
            && $intent['expiresat'] <= $intent['scheduledat']) {
            throw new \DomainException('Gift expiry must be later than scheduled delivery.');
        }
        return [
            'lineuuid' => $uuid,
            'deliverymode' => $mode,
            'recipientemail' => $email,
            'scheduledat' => $intent['scheduledat'],
            'expiresat' => $intent['expiresat'],
        ];
    }

    /**
     * Context is based entirely on immutable Native checkout snapshot fields.
     * It is available when checkout freezes the request, before DB item IDs exist.
     */
    public static function sign(
        \stdClass $purchase,
        \stdClass $item,
        array $intent,
        int $priceid,
        string $secretkeyhex
    ): string {
        $canonical = self::canonical_intent($intent);
        if (preg_match('/\A[a-f0-9]{64}\z/D', $secretkeyhex) !== 1 || $priceid <= 0) {
            throw new \DomainException('Gift signer is unconfigured or price identifier is invalid.');
        }
        $reference = trim((string)($purchase->reference ?? ''));
        $buyeremail = strtolower(trim((string)($purchase->customeremail ?? '')));
        $currency = strtoupper((string)($purchase->currency ?? ''));
        $itemcurrency = strtoupper((string)($item->currency ?? ''));
        $sku = strtoupper(trim((string)($item->itemreference ?? '')));
        if ($reference === '' || filter_var($buyeremail, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/\A[A-Z]{3}\z/D', $currency) !== 1
            || $currency !== $itemcurrency
            || preg_match('/\A[A-Z0-9_\-]{2,128}\z/D', $sku) !== 1
            || !isset($item->position, $item->netminor, $item->quantity)
            || (int)$item->position < 0 || (int)$item->netminor <= 0 || (int)$item->quantity !== 1) {
            throw new \DomainException('Gift checkout binding fields are invalid.');
        }
        $binding = [
            'version' => 1,
            'purchasereference' => $reference,
            'buyeremail' => $buyeremail,
            'currency' => $currency,
            'position' => (int)$item->position,
            'sku' => $sku,
            'priceid' => $priceid,
            'netminor' => (int)$item->netminor,
            'quantity' => (int)$item->quantity,
            'intent' => $canonical,
        ];
        $json = json_encode($binding, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', 'campusfr:gifts:intent:v1:' . $json, hex2bin($secretkeyhex));
    }

    public static function verify(
        \stdClass $purchase,
        \stdClass $item,
        array $envelope,
        int $priceid,
        string $secretkeyhex
    ): array {
        if (array_keys($envelope) !== ['intent', 'seal']
            || !is_array($envelope['intent'])
            || !is_string($envelope['seal'])
            || preg_match('/\A[a-f0-9]{64}\z/D', $envelope['seal']) !== 1) {
            throw new \DomainException('Gift intent envelope format is invalid.');
        }
        $intent = self::canonical_intent($envelope['intent']);
        $expected = self::sign($purchase, $item, $intent, $priceid, $secretkeyhex);
        if (!hash_equals($expected, $envelope['seal'])) {
            throw new \DomainException('Gift intent signature does not match the paid item.');
        }
        return $intent;
    }
}
