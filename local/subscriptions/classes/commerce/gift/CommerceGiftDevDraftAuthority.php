<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** Domain-separated proof of a server-issued draft, bound to buyer and price. */
final class CommerceGiftDevDraftAuthority {
    public const PROOF_FIELD = 'campusfr_gift_dev_authority_v1';

    public static function proof(array $draft, string $buyeremail, string $currency,
        string $sku, int $priceid, string $keyhex): string {
        if (!CommerceGiftCommercialRuntimeGate::can_operate()) {
            throw new \DomainException('Gift checkout preparation is not enabled.');
        }
        if (preg_match('/\A[a-f0-9]{64}\z/D', $keyhex) !== 1 || $priceid < 1
            || strtoupper($sku) !== 'AVENT_2026'
            || !in_array(strtoupper($currency), ['EUR', 'RUB'], true)
            || filter_var($buyeremail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DomainException('DEV gift draft proof context is invalid.');
        }
        $intent = CommerceGiftIntentSeal::canonical_intent($draft);
        $material = json_encode([
            'context' => 'campusfr:gift:dev-draft:v1',
            'buyeremail' => strtolower(trim($buyeremail)),
            'currency' => strtoupper($currency),
            'sku' => strtoupper($sku),
            'priceid' => $priceid,
            'intent' => $intent,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $material, hex2bin($keyhex));
    }

    public static function assert_authorized(array $metadata, array $draft,
        string $buyeremail, string $currency, string $sku, int $priceid, string $keyhex): void {
        $actual = $metadata[self::PROOF_FIELD] ?? null;
        if (!is_string($actual) || preg_match('/\A[a-f0-9]{64}\z/D', $actual) !== 1) {
            throw new \DomainException('Gift draft has no trusted DEV origin.');
        }
        $expected = self::proof($draft, $buyeremail, $currency, $sku, $priceid, $keyhex);
        if (!hash_equals($expected, $actual)) {
            throw new \DomainException('Gift draft DEV origin proof does not match checkout.');
        }
    }
}
