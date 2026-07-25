<?php

namespace local_subscriptions\commerce\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler;

/**
 * Builds the new Commerce representation from historical digital purchases.
 *
 * This factory is read-only and does not modify legacy records.
 */
final class DigitalPurchaseFactory {

    /**
     * @param \stdClass $paymentrequest Record from subscription_digital_payment_request.
     * @param \stdClass|null $product Record from subscription_digital_product.
     */
    public static function from_legacy_records(
        \stdClass $paymentrequest,
        ?\stdClass $product = null
    ): DigitalPurchase {
        self::validate_payment_request($paymentrequest);

        $purchaseid = (int)$paymentrequest->id;
        $productid = (int)$paymentrequest->productid;

        $currency = strtoupper(
            self::first_non_empty([
                $paymentrequest->currency ?? null,
            ], 'EUR')
        );

        $amountminor = self::resolve_amount_minor($paymentrequest);

        $itemname = self::first_non_empty([
            $product->name ?? null,
            $product->slug ?? null,
        ], 'Digital product #' . $productid);

        $itemreference = self::first_non_empty([
            !empty($product->slug)
                ? 'digital-product:' . $product->slug
                : null,
        ], 'digital-product:' . $productid);

        $item = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            $itemreference,
            $itemname,
            $productid,
            [
                'legacy_table' => 'subscription_digital_product',
                'legacy_id' => $productid,
                'product_id' => $productid,
                'slug' => self::nullable_string($product->slug ?? null),
                'enabled' => isset($product->enabled)
                    ? !empty($product->enabled)
                    : null,
            ]
        );

        $payment = new CommercePayment(
            $amountminor,
            $currency,
            LegacyCommerceStatusMapper::payment_status(
                $paymentrequest->status ?? null
            ),
            self::nullable_string(
                $paymentrequest->payment_provider ?? null
            ),
            self::nullable_string(
                $paymentrequest->transactionid ?? null
            ),
            $purchaseid,
            self::nullable_positive_int(
                $paymentrequest->payment_date ?? null
            ),
            [
                'legacy_table' => 'subscription_digital_payment_request',
                'is_free_product' =>
                    isset($product->price)
                    && (float)$product->price <= 0,

                'is_complimentary' => !empty(
                    $paymentrequest->is_complimentary
                        ?? false
                ),                
                'session_id' => self::nullable_string(
                    $paymentrequest->sessionid ?? null
                ),
                'buyer_language' => self::nullable_string(
                    $paymentrequest->buyer_lang ?? null
                ),
                'email_sent' => (int)($paymentrequest->emailsent ?? 0),
                'receipt_sent' => (int)($paymentrequest->receipt_sent ?? 0),
                'attempts' => (int)($paymentrequest->attempts ?? 0),
                'last_error' => self::nullable_string(
                    $paymentrequest->last_error ?? null
                ),
            ]
        );

        return new DigitalPurchase(
            'digital:' . $purchaseid,
            $item,
            $payment,
            self::nullable_positive_int($paymentrequest->userid ?? null),
            self::nullable_string($paymentrequest->email ?? null),
            self::resolve_purchase_status(
                $paymentrequest,
                $payment
            ),
            $purchaseid,
            $productid,
            self::nullable_string(
                $paymentrequest->download_token ?? null
            ),
            self::nullable_positive_int(
                $paymentrequest->download_token_expires ?? null
            ),
            self::nullable_positive_int(
                $paymentrequest->creation_date ?? null
            ),
            self::nullable_positive_int(
                $paymentrequest->last_update ?? null
            ),
            [
                'legacy_table' => 'subscription_digital_payment_request',
                'legacy_status' => self::nullable_string($paymentrequest->status ?? null),
                'product_id' => $productid,
                'download_token' => self::nullable_string($paymentrequest->download_token ?? null),
                'download_token_expires' => self::nullable_positive_int($paymentrequest->download_token_expires ?? null),
                'firstname' => self::nullable_string(
                    $paymentrequest->firstname ?? null
                ),
                'lastname' => self::nullable_string(
                    $paymentrequest->lastname ?? null
                ),
                'expiration_date' => self::nullable_positive_int(
                    $paymentrequest->expiration_date ?? null
                ),
                'locked_list_price' => (float)(
                    $paymentrequest->locked_list_price ?? 0
                ),
                'locked_discount_percent' => (int)(
                    $paymentrequest->locked_discount_percent ?? 0
                ),
                'locked_discount_amount' => (float)(
                    $paymentrequest->locked_discount_amount ?? 0
                ),
                'locked_discount_reason' => self::nullable_string(
                    $paymentrequest->locked_discount_reason ?? null
                ),
                'locked_final_price' => (float)(
                    $paymentrequest->locked_final_price ?? 0
                ),
            ],
            self::build_fulfillments(
                $paymentrequest,
                $product
            )
        );
    }

    /**
     * Resolve the Native lifecycle from the actual Digital delivery state.
     */
    private static function resolve_purchase_status(
        \stdClass $paymentrequest,
        CommercePayment $payment
    ): string {
        $status = strtolower(trim((string)($paymentrequest->status ?? '')));

        if (
            in_array($status, ['paid', 'completed'], true)
            && !empty($paymentrequest->download_token)
        ) {
            return CommercePurchaseStatus::FULFILLED;
        }

        if ($payment->is_successful()) {
            return CommercePurchaseStatus::FULFILLMENT_PENDING;
        }

        return LegacyCommerceStatusMapper::purchase_status($status);
    }

    /**
     * Reconstruct one deterministic Digital delivery fulfillment from the
     * current Legacy truth. This records observed delivery and never generates
     * or refreshes a download token.
     *
     * @return CommerceFulfillmentOperation[]
     */
    private static function build_fulfillments(
        \stdClass $paymentrequest,
        ?\stdClass $product
    ): array {
        $status = strtolower(trim((string)($paymentrequest->status ?? '')));
        $paymentsuccessful = in_array($status, ['paid', 'completed'], true);

        if (!$paymentsuccessful) {
            return [];
        }

        $purchaseid = (int)$paymentrequest->id;
        $productid = (int)$paymentrequest->productid;
        $delivered = !empty($paymentrequest->download_token);

        return [new CommerceFulfillmentOperation(
            'digital:' . $purchaseid . ':delivery',
            DigitalDownloadFulfillmentHandler::KEY,
            [
                'persistence_status' => $delivered ? 'completed' : 'pending',
                'projection_source' => 'legacy_observation',
                'historical_fulfillment_inferred' => true,
                'legacy_digital_purchase_id' => $purchaseid,
                'legacy_status' => $status,
                'product_id' => $productid,
                'slug' => self::nullable_string($product->slug ?? null),
                'delivery_mode' => 'download',
                'download_token_present' => $delivered,
                'download_token_expires' => self::nullable_positive_int(
                    $paymentrequest->download_token_expires ?? null
                ),
                'email_sent' => (int)($paymentrequest->emailsent ?? 0),
                'receipt_sent' => (int)($paymentrequest->receipt_sent ?? 0),
            ]
        )];
    }

    private static function validate_payment_request(
        \stdClass $paymentrequest
    ): void {
        if (empty($paymentrequest->id)) {
            throw new \coding_exception('The legacy digital purchase has no identifier.');
        }

        if (empty($paymentrequest->productid)) {
            throw new \coding_exception('The legacy digital purchase has no product identifier.');
        }
    }

    private static function resolve_amount_minor(
        \stdClass $paymentrequest
    ): int {
        if (
            isset($paymentrequest->amount_minor)
            && (int)$paymentrequest->amount_minor > 0
        ) {
            return (int)$paymentrequest->amount_minor;
        }

        $amount = $paymentrequest->locked_final_price
            ?? $paymentrequest->price
            ?? 0;

        return max(0, (int)round(((float)$amount) * 100));
    }

    private static function first_non_empty(
        array $values,
        string $default = ''
    ): string {
        foreach ($values as $value) {
            if ($value !== null && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        return $default;
    }

    private static function nullable_string(mixed $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }

    private static function nullable_positive_int(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }
}