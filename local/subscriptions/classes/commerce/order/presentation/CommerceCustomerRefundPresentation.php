<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;

/**
 * Customer-facing refund summary derived from successful Native refund records.
 *
 * Payment capture and refund are deliberately kept as two distinct facts: an
 * order can remain historically paid while also being partially or fully
 * refunded. Customer surfaces therefore render the refund alongside, rather
 * than replacing, the original payment state.
 */
final class CommerceCustomerRefundPresentation {
    /**
     * @return array{
     *     hasrefund:bool,
     *     isfull:bool,
     *     ispartial:bool,
     *     status:string,
     *     refundedminor:int,
     *     totalminor:int
     * }
     */
    public static function resolve(
        CommercePurchaseDetails $details,
        CommercePaymentRefundRepository $refunds
    ): array {
        $refundedminor = 0;

        foreach ($details->payments as $payment) {
            $paymentid = (int)($payment->id ?? 0);
            if ($paymentid <= 0) {
                continue;
            }

            $paymentrefund = $refunds->successful_refunded_amount_minor($paymentid);
            if ($paymentrefund <= 0) {
                continue;
            }

            $paymentamount = max(0, (int)($payment->amountminor ?? 0));
            $refundedminor += $paymentamount > 0
                ? min($paymentamount, $paymentrefund)
                : $paymentrefund;
        }

        return self::from_amounts(
            max(0, (int)$details->summary->totalminor),
            $refundedminor
        );
    }

    /**
     * Pure classification helper kept public so the customer contract can be
     * unit-tested without a payment provider or persistence fixture.
     *
     * @return array{
     *     hasrefund:bool,
     *     isfull:bool,
     *     ispartial:bool,
     *     status:string,
     *     refundedminor:int,
     *     totalminor:int
     * }
     */
    public static function from_amounts(int $totalminor, int $refundedminor): array {
        $totalminor = max(0, $totalminor);
        $refundedminor = max(0, $refundedminor);
        if ($totalminor > 0) {
            $refundedminor = min($totalminor, $refundedminor);
        }

        $hasrefund = $refundedminor > 0;
        $isfull = $hasrefund && $totalminor > 0 && $refundedminor >= $totalminor;

        return [
            'hasrefund' => $hasrefund,
            'isfull' => $isfull,
            'ispartial' => $hasrefund && !$isfull,
            'status' => $isfull ? 'refunded' : ($hasrefund ? 'partially_refunded' : ''),
            'refundedminor' => $refundedminor,
            'totalminor' => $totalminor,
        ];
    }
}
