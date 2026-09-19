<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\merchant;

use local_subscriptions\commerce\legal\entity\CommerceLegalEntityRegistry;

defined('MOODLE_INTERNAL') || die();

/**
 * V1 seller routing policy.
 *
 * RU/BY market => Russian legal entity.
 * Rest of world, including unknown ZZ => French legal entity.
 * Currency and payment provider are deliberately not seller identities and do not drive V1 routing.
 */
final class CommerceMerchantResolver {
    public const RULE_RU_BY = 'market_ru_by';
    public const RULE_ROW = 'market_row';
    public const RULE_UNKNOWN_FALLBACK_FR = 'market_unknown_fallback_fr';

    public function __construct(
        private readonly ?CommerceLegalEntityRegistry $registry = null
    ) {
    }

    public function resolve(CommerceMerchantResolutionContext $context): CommerceMerchantResolutionResult {
        $country = $context->get_market_country();
        $registry = $this->registry ?? new CommerceLegalEntityRegistry();

        if (in_array($country, ['RU', 'BY'], true)) {
            return new CommerceMerchantResolutionResult(
                $registry->get(CommerceLegalEntityRegistry::RU_MAIN),
                $country,
                self::RULE_RU_BY
            );
        }

        if ($country === 'ZZ') {
            return new CommerceMerchantResolutionResult(
                $registry->get(CommerceLegalEntityRegistry::FR_MAIN),
                $country,
                self::RULE_UNKNOWN_FALLBACK_FR
            );
        }

        return new CommerceMerchantResolutionResult(
            $registry->get(CommerceLegalEntityRegistry::FR_MAIN),
            $country,
            self::RULE_ROW
        );
    }
}
