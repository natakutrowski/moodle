<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\merchandising;

defined('MOODLE_INTERNAL') || die();

/** Resolves an active, honest comparison price for a Native Storefront price. */
final class CommerceStorefrontPromotionResolver {
    /** @return array{compareamountminor:int,discountpercentage:int,end:?int}|null */
    public function resolve(
        CommerceStorefrontMerchandising $merchandising,
        string $currency,
        int $saleamountminor,
        ?int $now = null
    ): ?array {
        $promotion = $merchandising->get_promotion($currency);
        if ($promotion === null || $saleamountminor < 0) {
            return null;
        }

        $now ??= time();
        $start = $promotion['start'] ?? null;
        $end = $promotion['end'] ?? null;
        $compareamountminor = (int)($promotion['compareamountminor'] ?? 0);

        if (($start !== null && $now < $start)
            || ($end !== null && $now > $end)
            || $compareamountminor <= $saleamountminor) {
            return null;
        }

        $discount = (int)round((1 - ($saleamountminor / $compareamountminor)) * 100);
        if ($discount <= 0 || $discount >= 100) {
            return null;
        }

        return [
            'compareamountminor' => $compareamountminor,
            'discountpercentage' => $discount,
            'end' => $end,
        ];
    }
}
