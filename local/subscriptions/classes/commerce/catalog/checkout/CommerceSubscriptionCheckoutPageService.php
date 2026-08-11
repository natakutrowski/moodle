<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceLegacyProductMapRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\constants\Operation;
use local_subscriptions\constants\Status;
use local_subscriptions\domain\SubscriptionAdvisor;

/**
 * Native UI boundary for the historical Subscription checkout page.
 *
 * Legacy Subscription rules remain encapsulated behind this boundary until their
 * dedicated Native replacement is delivered. Catalogue identity and pricing are
 * resolved exclusively from Native Commerce tables.
 */
final class CommerceSubscriptionCheckoutPageService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceLegacyProductMapRepository $legacymap,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices
    ) {
    }

    public function prepare(
        int $userid,
        int $effectiveuserid,
        int $planid,
        string $currency
    ): CommerceSubscriptionCheckoutPageResult {
        $currency = strtoupper(trim($currency));
        $plan = $this->db->get_record(
            'subscription_plan',
            ['id' => $planid],
            '*',
            MUST_EXIST
        );

        try {
            $options = $effectiveuserid
                ? SubscriptionAdvisor::advise_options($effectiveuserid, $planid, $currency)
                : [];
        } catch (\moodle_exception $exception) {
            if ($exception->errorcode === 'plan_inactive' && $exception->module === 'local_subscriptions') {
                throw new CommerceSubscriptionCheckoutPlanInactiveException((int)$plan->accessscopeid);
            }
            throw $exception;
        }

        if (empty($plan->is_active)) {
            throw new CommerceSubscriptionCheckoutPlanInactiveException((int)$plan->accessscopeid);
        }

        $alreadycovered = $effectiveuserid > 0
            && SubscriptionAdvisor::user_has_higher_or_equal_access_for_plan($effectiveuserid, $planid);

        [$currentsubscription, $currentplan] = $this->find_current_scope_subscription(
            $userid,
            (int)$plan->accessscopeid
        );

        [$usedcurrency, $baseprice, $requestedavailable] = $this->resolve_native_price($planid, $currency);

        require_once(__DIR__ . '/../../../trial_manager.php');
        $discountopen = $userid > 0
            && \local_subscriptions\trial_manager::is_discount_window_open($userid);
        $discountpercent = max(0, min(100,
            (int)(get_config('local_subscriptions', 'trial_discount_percent') ?? 15)
        ));
        $discountdeadline = $discountopen
            ? (int)\local_subscriptions\trial_manager::discount_window_deadline($userid)
            : 0;
        $applydiscount = $discountopen && $discountpercent > 0;
        $finalpurchaseprice = $applydiscount
            ? round($baseprice * (100 - $discountpercent) / 100, 2)
            : $baseprice;

        if ($effectiveuserid <= 0 || $options === []) {
            $options = [[
                'key' => Operation::PURCHASE_NEW,
                'label' => get_string('option_purchase_new', 'local_subscriptions'),
                'amount' => $finalpurchaseprice,
                'currency' => $usedcurrency,
                'ref_subid' => null,
            ]];
        }

        return new CommerceSubscriptionCheckoutPageResult(
            $plan,
            $currentsubscription,
            $currentplan,
            $options,
            $currency,
            $usedcurrency,
            $baseprice,
            $requestedavailable,
            $discountopen,
            $discountpercent,
            $discountdeadline,
            $finalpurchaseprice,
            $alreadycovered
        );
    }

    /** @return array{0:?\stdClass,1:?\stdClass} */
    private function find_current_scope_subscription(int $userid, int $scopeid): array {
        if ($userid <= 0) {
            return [null, null];
        }

        $subscription = $this->db->get_record_sql(
            "SELECT s.*
               FROM {user_subscription} s
               JOIN {subscription_plan} p ON p.id = s.planid
              WHERE s.userid = :userid
                AND s.status = :status
                AND p.accessscopeid = :scopeid
           ORDER BY s.end_date DESC, s.id DESC",
            [
                'userid' => $userid,
                'status' => Status::ACTIVE,
                'scopeid' => $scopeid,
            ],
            IGNORE_MULTIPLE
        );

        if (!$subscription) {
            return [null, null];
        }

        $plan = $this->db->get_record('subscription_plan', ['id' => (int)$subscription->planid]);
        return [$subscription, $plan ?: null];
    }

    /** @return array{0:string,1:float,2:bool} */
    private function resolve_native_price(int $planid, string $requestedcurrency): array {
        $productid = $this->legacymap->find_product_id('subscription_plan', $planid);
        if ($productid === null) {
            throw new \RuntimeException('No Native Commerce product maps to Subscription plan #' . $planid . '.');
        }

        $product = $this->products->find_by_id($productid);
        if ($product === null || !$product->is_active()) {
            throw new \RuntimeException('The mapped Native Commerce product is missing or inactive.');
        }

        $available = $this->prices->find_by_product_sku($product->get_sku(), true);
        if ($available === []) {
            throw new \RuntimeException('The mapped Native Commerce product has no active price.');
        }

        $selected = null;
        foreach ($available as $price) {
            if ($price->get_currency() === $requestedcurrency && $price->get_provider() === null) {
                $selected = $price;
                break;
            }
            if ($price->get_currency() === $requestedcurrency && $selected === null) {
                $selected = $price;
            }
        }

        $requestedavailable = $selected !== null;
        $selected ??= $available[0];

        return [
            $selected->get_currency(),
            round($selected->get_amount_minor() / 100, 2),
            $requestedavailable,
        ];
    }
}
