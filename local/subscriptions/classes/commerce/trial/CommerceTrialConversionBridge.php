<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\trial;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\storefront\CommerceCourseStorefrontTargetResolver;
use local_subscriptions\trial_manager;

/**
 * Resolves the Legacy trial conversion CTA to a validated Native Storefront destination.
 *
 * J6.1 deliberately does not calculate or apply the discount to the cart yet.
 * It only establishes one authoritative server-side bridge for J6.2.
 */
final class CommerceTrialConversionBridge {
    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    public function resolve_for_user(
        int $userid,
        ?string $currency = null,
        ?string $productsku = null
    ): ?CommerceTrialConversionOffer {
        if ($userid <= 0) {
            return null;
        }

        $trial = trial_manager::user_has_active_trial($userid);
        if ($trial === null || !trial_manager::is_discount_window_open($userid)) {
            return null;
        }

        $settings = trial_manager::get_trial_settings();
        $discountpercent = max(0, min(100, (int)$settings['disc_pct']));
        $expiresat = trial_manager::discount_window_deadline($userid);
        if ($discountpercent <= 0 || $expiresat <= time()) {
            return null;
        }

        $product = $productsku !== null && trim($productsku) !== ''
            ? $this->resolve_product_by_sku($productsku, $userid)
            : null;
        $sku = $product ? strtoupper(trim((string)$product->sku)) : null;
        $params = [];
        if ($currency !== null && in_array(strtoupper($currency), ['EUR', 'RUB'], true)) {
            $params['currency'] = strtoupper($currency);
        }

        if ($sku !== null && $sku !== '') {
            $params['sku'] = $sku;
            $url = new \moodle_url(
                '/local/subscriptions/storefront_product.php',
                $params
            );
        } else {
            $url = new \moodle_url('/boutique', $params);
            $sku = null;
        }

        return new CommerceTrialConversionOffer(
            $userid,
            $discountpercent,
            $expiresat,
            $sku,
            $url
        );
    }

    public function resolve_for_course(
        int $userid,
        int $courseid,
        string $accesslevel = 'full',
        ?string $currency = null
    ): ?CommerceTrialConversionOffer {
        if ($courseid <= 0) {
            return $this->resolve_for_user($userid, $currency);
        }

        $product = CommerceCourseStorefrontTargetResolver::create()
            ->resolve_one($courseid, $accesslevel);

        return $this->resolve_for_user(
            $userid,
            $currency,
            $product ? (string)$product->sku : null
        );
    }

    private function resolve_product_by_sku(string $sku, int $userid): ?\stdClass {
        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => strtoupper(trim($sku))],
            '*',
            IGNORE_MISSING
        );

        if (
            !$this->is_available_product($product) ||
            !(new CommerceTrialProductEligibilityService($this->db))->is_eligible(
                $userid,
                (string)$product->sku
            )
        ) {
            return null;
        }

        return $product;
    }

    private function is_available_product(?\stdClass $product): bool {
        if ($product === null || strtolower((string)$product->status) !== 'active') {
            return false;
        }

        $now = time();
        if ($product->availablefrom !== null && (int)$product->availablefrom > $now) {
            return false;
        }
        if ($product->availableuntil !== null && (int)$product->availableuntil < $now) {
            return false;
        }

        return trim((string)$product->sku) !== '';
    }
}
