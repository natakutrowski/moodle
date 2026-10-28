<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\report;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use moodle_database;

/**
 * Read-only coupon audit from immutable purchase metadata and durable payment/refund records.
 *
 * The old promouse table is not populated by the checkout code: do not mistake
 * a coupon applied on a cart for a paid conversion or commissionable sale.
 * No database writes, no payment-provider API calls.
 */
final class CommercePromotionUsageReport {
    private const PURCHASE = 'local_subscriptions_commerce_purchase';
    private const PAYMENT = 'local_subscriptions_commerce_payment';
    private const REFUND = 'local_subscriptions_commerce_refund';
    private const ITEM = 'local_subscriptions_commerce_purchase_item';

    public function __construct(private readonly moodle_database $database) {}

    /**
     * @return array<int,array<string,mixed>> Order rows, newest first, one per order.
     */
    public function records(CommercePromotion $promotion): array {
        $code = strtoupper(trim((string)$promotion->get_code()));
        $promotionid = (int)$promotion->get_id();
        if ($code === '' && $promotionid <= 0) {
            return [];
        }
        $conditions = [];
        $params = [];
        if ($code !== '') {
            // Quoted JSON string; a second exact-match check below rejects false positives.
            $conditions[] = $this->database->sql_like('p.metadatajson', ':couponcode', false);
            $params['couponcode'] = '%' . $this->database->sql_like_escape('"' . $code . '"') . '%';
        }
        if ($promotionid > 0) {
            $conditions[] = $this->database->sql_like('p.metadatajson', ':promotionid', false);
            $params['promotionid'] = '%' . $this->database->sql_like_escape('"promotionid":' . $promotionid . ',') . '%';
        }

        $purchases = $this->database->get_records_sql(
            'SELECT p.* FROM {' . self::PURCHASE . '} p WHERE (' . implode(' OR ', $conditions) . ')'
                . ' ORDER BY p.timecreated DESC, p.id DESC',
            $params
        );
        if (!$purchases) {
            return [];
        }

        $matched = [];
        foreach ($purchases as $purchase) {
            $metadata = self::metadata($purchase->metadatajson);
            if (!self::matches($metadata, $code, $promotionid)) {
                continue;
            }
            $matched[(int)$purchase->id] = ['purchase' => $purchase, 'meta' => $metadata];
        }
        if (!$matched) {
            return [];
        }

        $payments = [];
        $products = [];
        foreach (array_chunk(array_keys($matched), 200) as $ids) {
            [$insql, $inparams] = $this->database->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'pu');
            foreach ($this->database->get_records_select(self::PAYMENT, 'purchaseid ' . $insql,
                    $inparams, 'id ASC') as $payment) {
                $payments[(int)$payment->purchaseid][] = $payment;
            }
            foreach ($this->database->get_records_select(self::ITEM, 'purchaseid ' . $insql,
                    $inparams, 'position ASC, id ASC') as $item) {
                $products[(int)$item->purchaseid][] = (string)$item->label;
            }
        }
        $refunds = [];
        $allpaymentids = [];
        foreach ($payments as $attempts) {
            foreach ($attempts as $payment) {
                $allpaymentids[] = (int)$payment->id;
            }
        }
        foreach (array_chunk($allpaymentids, 200) as $ids) {
            [$insql, $inparams] = $this->database->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'py');
            foreach ($this->database->get_records_select(self::REFUND, 'paymentid ' . $insql,
                    $inparams, 'id ASC') as $refund) {
                if (strtolower((string)$refund->status) === 'succeeded') {
                    $refunds[(int)$refund->paymentid] = ($refunds[(int)$refund->paymentid] ?? 0)
                        + max(0, (int)$refund->amountminor);
                }
            }
        }
        // Get current account names, falling back to frozen order identity.
        $users = [];
        $userids = array_values(array_unique(array_filter(array_map(
            static fn(array $v): int => (int)$v['purchase']->userid, $matched
        ))));
        foreach (array_chunk($userids, 200) as $ids) {
            [$insql, $inparams] = $this->database->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'usr');
            foreach ($this->database->get_records_select('user', 'id ' . $insql, $inparams,
                    '', 'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email,deleted') as $user) {
                $users[(int)$user->id] = $user;
            }
        }

        $results = [];
        foreach ($matched as $id => $item) {
            $purchase = $item['purchase'];
            $meta = $item['meta'];
            $identity = self::metadata($purchase->customerjson);
            $user = $users[(int)$purchase->userid] ?? null;
            $name = $user && !(int)$user->deleted ? trim(fullname($user)) : '';
            if ($name === '') {
                $name = trim((string)($identity['firstname'] ?? '') . ' ' . (string)($identity['lastname'] ?? ''));
            }
            $email = trim((string)($purchase->customeremail ?? ''));
            if ($email === '') {
                $email = trim((string)($identity['email'] ?? ''));
            }
            $state = self::financial_state(
                (int)$purchase->totalminor,
                (string)$purchase->status,
                $payments[$id] ?? [],
                $refunds
            );
            $detail = self::discount_for_code($meta, $code, $promotionid);
            $results[] = [
                'id' => $id,
                'reference' => (string)$purchase->reference,
                'name' => $name,
                'email' => $email,
                'userid' => (int)$purchase->userid,
                'products' => $products[$id] ?? [],
                'currency' => (string)$purchase->currency,
                'orderat' => (int)$purchase->timecreated,
                'paidat' => $state['paidat'],
                'status' => $state['status'],
                'grossminor' => $state['grossminor'],
                'refundedminor' => $state['refundedminor'],
                'netminor' => $state['netminor'],
                'discountminor' => $detail['amount'],
                'discountprecision' => $detail['precision'],
            ];
        }
        return $results;
    }

    /** Exact matching prevents LIKE prefilter from attributing unrelated orders. */
    public static function matches(array $metadata, string $code, int $promotionid): bool {
        foreach ((array)($metadata['promotion_adjustments'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ($promotionid > 0 && (int)($row['promotionid'] ?? 0) === $promotionid) {
                return true;
            }
            if ($code !== '' && strtoupper(trim((string)($row['code'] ?? ''))) === $code) {
                return true;
            }
        }
        foreach ((array)($metadata['promotion_codes'] ?? []) as $value) {
            if (is_string($value) && $code !== '' && strtoupper(trim($value)) === $code) {
                return true;
            }
        }
        return false;
    }

    /** @return array{amount:?int,precision:string} */
    public static function discount_for_code(array $metadata, string $code, int $promotionid): array {
        foreach ((array)($metadata['promotion_adjustments'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($promotionid > 0 && (int)($row['promotionid'] ?? 0) === $promotionid)
                || ($code !== '' && strtoupper(trim((string)($row['code'] ?? ''))) === $code)) {
                return ['amount' => max(0, (int)($row['discountminor'] ?? 0)), 'precision' => 'exact'];
            }
        }
        $codes = array_values(array_filter((array)($metadata['promotion_codes'] ?? []), 'is_string'));
        if (count($codes) === 1 && array_key_exists('cart_adjustment_discount_minor', $metadata)) {
            // Older checkouts saved only the combined adjustment, possibly including automatic promos.
            return ['amount' => max(0, (int)$metadata['cart_adjustment_discount_minor']), 'precision' => 'estimate'];
        }
        return ['amount' => null, 'precision' => 'unavailable'];
    }

    /**
     * @param \stdClass[] $payments
     * @param array<int,int> $refunds Amounts of succeeded refunds by payment attempt.
     * @return array{status:string,grossminor:int,refundedminor:int,netminor:int,paidat:?int}
     */
    public static function financial_state(int $ordertotal, string $orderstatus, array $payments, array $refunds): array {
        $paid = array_values(array_filter($payments, static fn(\stdClass $payment): bool =>
            in_array(strtolower((string)$payment->status),
                ['paid', 'completed', 'refunded', 'partially_refunded'], true)
        ));
        // An order can have failed/redirected attempts and a final successful attempt;
        // count the order once. Use the latest successful attempt, never sum retries.
        usort($paid, static fn(\stdClass $a, \stdClass $b): int =>
            ((int)($b->paidat ?? $b->timemodified ?? 0) <=> (int)($a->paidat ?? $a->timemodified ?? 0))
                ?: ((int)$b->id <=> (int)$a->id)
        );
        if (count($paid) > 1) {
            // Duplicate captured attempts are a financial anomaly, not two conversions.
            // Do not generate a commission automatically until reconciled.
            return ['status' => 'unknown', 'grossminor' => 0, 'refundedminor' => 0,
                'netminor' => 0, 'paidat' => null];
        }
        $payment = $paid[0] ?? null;
        if ($payment === null) {
            if ($ordertotal === 0 && in_array(strtolower($orderstatus),
                ['completed', 'payment_confirmed', 'fulfillment_pending'], true)) {
                return ['status' => 'free', 'grossminor' => 0, 'refundedminor' => 0,
                    'netminor' => 0, 'paidat' => null];
            }
            $attemptstatuses = array_map(static fn(\stdClass $p): string =>
                strtolower((string)$p->status), $payments);
            $state = strtolower($orderstatus);
            $reason = in_array($state, ['cancelled', 'rejected'], true) ||
                in_array('cancelled', $attemptstatuses, true) ? 'cancelled' :
                ((in_array($state, ['failed'], true) ||
                    array_intersect($attemptstatuses, ['failed', 'error'])) ? 'failed' : 'pending');
            return ['status' => $reason, 'grossminor' => 0, 'refundedminor' => 0,
                'netminor' => 0, 'paidat' => null];
        }
        $gross = max(0, (int)$payment->amountminor);
        $returned = min($gross, max(0, (int)($refunds[(int)$payment->id] ?? 0)));
        $status = 'paid';
        if ((string)$payment->status === 'refunded') {
            $returned = $gross;
            $status = 'refunded';
        } else if ($returned >= $gross && $gross > 0) {
            $status = 'refunded';
        } else if ($returned > 0) {
            $status = 'partial';

        } else if ((string)$payment->status === 'partially_refunded') {
            // The amount was not recorded locally: exclude uncertain commission.
            return ['status' => 'unknown', 'grossminor' => $gross, 'refundedminor' => 0,
                'netminor' => 0, 'paidat' => !empty($payment->paidat) ? (int)$payment->paidat : null];
        }
        return [
            'status' => $status,
            'grossminor' => $gross,
            'refundedminor' => $returned,
            'netminor' => max(0, $gross - $returned),
            'paidat' => !empty($payment->paidat) ? (int)$payment->paidat : null,
        ];
    }

    /** Deterministic integer arithmetic; percentage is expressed in hundredths of a percent. */
    public static function commission_minor(int $netminor, int $ratebasispoints): int {
        if ($netminor <= 0 || $ratebasispoints <= 0) {
            return 0;
        }
        return intdiv($netminor * min(10000, $ratebasispoints) + 5000, 10000);
    }

    private static function metadata(?string $raw): array {
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
