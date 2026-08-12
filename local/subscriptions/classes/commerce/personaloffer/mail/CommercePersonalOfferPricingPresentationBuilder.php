<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\mail;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\currency\CurrencyFormatter;

/** Builds deterministic, server-authoritative multi-currency Personal Offer presentation data. */
final class CommercePersonalOfferPricingPresentationBuilder {
    /**
     * @param array<string,array{regularminor:int,offerminor:int}> $available
     * @return array<string,mixed>
     */
    public static function build(array $available, string $preferredcurrency): array {
        if ($available === []) {
            throw new \coding_exception('Personal Offer pricing presentation requires at least one currency.');
        }

        $preferredcurrency = strtoupper(trim($preferredcurrency));
        if (!isset($available[$preferredcurrency])) {
            $preferredcurrency = isset($available['EUR'])
                ? 'EUR'
                : (isset($available['RUB']) ? 'RUB' : (string)array_key_first($available));
        }

        // Stable commercial display order, independent from recipient language.
        $orderedcurrencies = [];
        foreach (['EUR', 'RUB'] as $currency) {
            if (isset($available[$currency])) {
                $orderedcurrencies[] = $currency;
            }
        }
        foreach (array_keys($available) as $currency) {
            if (!in_array($currency, $orderedcurrencies, true)) {
                $orderedcurrencies[] = $currency;
            }
        }

        $flags = ['EUR' => '🇪🇺', 'RUB' => '🇷🇺', 'USD' => '🇺🇸', 'GBP' => '🇬🇧'];
        $cards = [];
        foreach ($orderedcurrencies as $currency) {
            $regularminor = (int)$available[$currency]['regularminor'];
            $offerminor = (int)$available[$currency]['offerminor'];
            $discountminor = max(0, $regularminor - $offerminor);
            $discountpercent = $regularminor > 0
                ? (int)round(($discountminor * 100) / $regularminor)
                : 0;
            $format = static fn(int $minor): string => CurrencyFormatter::format($minor / 100, $currency);

            $cards[] = [
                'currency' => $currency,
                'flag' => $flags[$currency] ?? '',
                'regularminor' => $regularminor,
                'offerminor' => $offerminor,
                'discountminor' => $discountminor,
                'discountpercent' => $discountpercent,
                'regularformatted' => $format($regularminor),
                'offerformatted' => $format($offerminor),
                'discountformatted' => $format($discountminor),
                'discountpercentformatted' => $discountpercent > 0 ? $discountpercent . ' %' : '',
                'hasregularprice' => $regularminor > $offerminor,
                'hasdiscountpercent' => $discountpercent > 0,
            ];
        }

        $primary = null;
        foreach ($cards as $card) {
            if ($card['currency'] === $preferredcurrency) {
                $primary = $card;
                break;
            }
        }
        $primary ??= $cards[0];

        return [
            'currency' => $primary['currency'],
            'regularminor' => $primary['regularminor'],
            'offerminor' => $primary['offerminor'],
            'discountminor' => $primary['discountminor'],
            'discountpercent' => $primary['discountpercent'],
            'regularformatted' => $primary['regularformatted'],
            'offerformatted' => $primary['offerformatted'],
            'discountformatted' => $primary['discountformatted'],
            'discountpercentformatted' => $primary['discountpercentformatted'],
            'prices' => $cards,
        ];
    }
}
