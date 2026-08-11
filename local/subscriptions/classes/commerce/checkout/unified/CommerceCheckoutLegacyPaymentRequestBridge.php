<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\legacy\LegacyPaymentRequestContext;
use local_subscriptions\constants\Operation;
use local_subscriptions\constants\Status;

/**
 * Persists the temporary Legacy transaction mirror required by current gateways.
 *
 * The Native Purchase remains the source of truth. This record only exists so
 * Stripe/Alfa Legacy gateways can initialize and update their provider session.
 */
final class CommerceCheckoutLegacyPaymentRequestBridge {
    private const TABLE = LegacyPaymentRequestContext::TABLE_SUBSCRIPTION;

    public function __construct(private readonly \moodle_database $db) {
    }

    public function persist_and_enrich(CommercePaymentRequest $request): CommercePaymentRequest {
        if (!$request->requires_payment()) {
            return $request;
        }

        $token = hash('sha256', 'commerce-checkout:' . $request->get_reference());
        $record = $this->db->get_record(self::TABLE, ['retry_token' => $token], '*', IGNORE_MISSING);

        if (!$record) {
            $record = $this->build_record($request, $token);
            $record->id = $this->db->insert_record(self::TABLE, $record);
        } else {
            $this->assert_compatible($record, $request);
        }

        return $this->with_metadata($request, [
            'legacy_payment_request_id' => (int)$record->id,
            'legacy_payment_request_table' => self::TABLE,
            'legacy_payment_context' => LegacyPaymentRequestContext::CONTEXT_COMMERCE_TRANSACTION,
            'legacy_order_number_prefix' => 'commerce',
            'legacy_language' => $this->resolve_language($request),
            'legacy_mode' => 'payment',
            'commerce_purchase_reference' => $request->get_reference(),
            'commerce_transaction_mirror' => true,
        ]);
    }

    private function build_record(CommercePaymentRequest $request, string $token): \stdClass {
        $customer = $request->get_customer();
        $major = $request->get_amount_minor() / 100;
        $metadata = $request->get_metadata();
        $listminor = max(
            $request->get_amount_minor(),
            (int)($metadata['cart_subtotal_minor'] ?? $request->get_amount_minor())
        );
        $discountminor = max(0, $listminor - $request->get_amount_minor());
        $now = time();

        return (object)[
            'planid' => null,
            'userid' => $customer->get_user_id(),
            'email' => $customer->get_email(),
            'firstname' => $customer->get_first_name(),
            'lastname' => $customer->get_last_name(),
            'phone' => null,
            'phone_country' => null,
            'subscriptionid' => null,
            'currency' => $request->get_currency(),
            'price' => $major,
            'amount_minor' => $request->get_amount_minor(),
            'created_ip' => getremoteaddr() ?: null,
            'created_useragent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'http_referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'payment_provider' => (string)$request->get_preferred_provider(),
            'sessionid' => null,
            'status' => Status::PENDING,
            'transactionid' => null,
            'payment_link' => null,
            'response_json' => null,
            'creation_date' => $now,
            'last_update' => $now,
            'payment_date' => null,
            'expiration_date' => null,
            'emailsent' => 0,
            'attempts' => 0,
            'last_attempt' => null,
            'last_error' => null,
            'retry_token' => $token,
            'retry_expires' => null,
            'reminder_stage' => 0,
            'reminder1_at' => null,
            'reminder2_at' => null,
            'login_token' => null,
            'login_token_expires' => null,
            'operation' => Operation::PURCHASE_NEW,
            'reference_subscription_id' => null,
            'locked_list_price' => $listminor / 100,
            'locked_discount_percent' => $listminor > 0
                ? (int)round(($discountminor * 100) / $listminor)
                : 0,
            'locked_discount_amount' => $discountminor / 100,
            'locked_discount_reason' => $this->promotion_reason($metadata),
            'locked_final_price' => $major,
            'locked_at' => $now,
        ];
    }

    private function assert_compatible(\stdClass $record, CommercePaymentRequest $request): void {
        $same = (int)$record->amount_minor === $request->get_amount_minor()
            && strtoupper((string)$record->currency) === $request->get_currency()
            && strtolower((string)$record->payment_provider) === strtolower((string)$request->get_preferred_provider())
            && (int)($record->userid ?? 0) === (int)($request->get_customer()->get_user_id() ?? 0)
            && strtolower(trim((string)$record->email)) === strtolower(trim($request->get_customer()->get_email()));

        if (!$same) {
            throw new \RuntimeException('The existing Legacy transaction mirror does not match the Commerce payment request.');
        }
    }

    private function with_metadata(CommercePaymentRequest $request, array $extra): CommercePaymentRequest {
        return new CommercePaymentRequest(
            $request->get_reference(),
            $request->get_customer(),
            $request->get_lines(),
            $request->get_currency(),
            $request->get_amount_minor(),
            $request->get_preferred_provider(),
            $request->get_return_url(),
            $request->get_cancel_url(),
            array_merge($request->get_metadata(), $extra),
            $request->get_created_at()
        );
    }

    private function resolve_language(CommercePaymentRequest $request): string {
        $metadata = $request->get_metadata();
        $customer = $request->get_customer();
        $language = (string)($metadata['language'] ?? $customer->get_metadata()['language'] ?? current_language());
        return trim($language) !== '' ? trim($language) : 'fr';
    }

    private function promotion_reason(array $metadata): ?string {
        $codes = array_values(array_filter((array)($metadata['promotion_codes'] ?? []), 'is_string'));
        return $codes === [] ? null : implode(',', $codes);
    }
}
