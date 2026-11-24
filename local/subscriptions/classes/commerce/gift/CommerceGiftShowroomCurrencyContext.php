<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Buyer-scoped trusted presentation context, NOT checkout authorization.
 * The actual price and Stripe currency must always be validated through
 * CommerceProductPriceRepository and the Native frozen purchase snapshot.
 */
final class CommerceGiftShowroomCurrencyContext {
    private const KEY = 'cfr_g5b4b_showroom_currency';
    private const TTL_SECONDS = 1200;

    public static function capture(\stdClass $session, int $buyerid, string $currency, int $now): void {
        $currency = strtoupper(trim($currency));
        if ($buyerid <= 2 || $now <= 0 || !in_array($currency, ['EUR', 'RUB'], true)) {
            // Never carry an older selection across a malformed new rendering.
            unset($session->{self::KEY});
            return;
        }
        $session->{self::KEY} = (object)[
            'buyerid' => $buyerid,
            'currency' => $currency,
            'at' => $now,
        ];
    }

    /** @return array{currency:string,fromshowroom:bool} */
    public static function resolve(\stdClass $session, int $buyerid, int $now): array {
        $row = $session->{self::KEY} ?? null;
        if (!is_object($row) || $buyerid <= 2 || $now <= 0
            || (int)($row->buyerid ?? 0) !== $buyerid
            || (int)($row->at ?? 0) > $now
            || (int)($row->at ?? 0) < $now - self::TTL_SECONDS
            || !in_array((string)($row->currency ?? ''), ['EUR', 'RUB'], true)) {
            return ['currency' => 'EUR', 'fromshowroom' => false];
        }
        return ['currency' => (string)$row->currency, 'fromshowroom' => true];
    }
}
