<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;

/**
 * Adds the persisted Native payment identity before any provider call.
 *
 * Legacy identifiers may still exist internally while the provider adapters are
 * being migrated, but they must no longer be exposed in provider metadata or
 * browser return URLs.
 */
final class CommerceCheckoutPaymentIdentityEnricher {
    public function enrich(
        CommercePaymentRequest $request,
        CommercePaymentAttempt $attempt
    ): CommercePaymentRequest {
        $paymentid = $attempt->get_id();

        if ($paymentid === null) {
            throw new \RuntimeException(
                'A persisted Commerce payment attempt is required before provider initialization.'
            );
        }

        $identity = [
            'commerce_payment_id' => $paymentid,
            'commerce_purchase_uuid' => $attempt->get_purchase_uuid(),
            'commerce_payment_sequence' => $attempt->get_sequence(),
        ];

        return new CommercePaymentRequest(
            $request->get_reference(),
            $request->get_customer(),
            $request->get_lines(),
            $request->get_currency(),
            $request->get_amount_minor(),
            $request->get_preferred_provider(),
            $this->append_identity($request->get_return_url(), $identity),
            $this->append_identity($request->get_cancel_url(), $identity),
            array_merge($request->get_metadata(), $identity),
            $request->get_created_at()
        );
    }

    private function append_identity(?string $url, array $identity): ?string {
        if ($url === null || trim($url) === '') {
            return $url;
        }

        $moodleurl = new \moodle_url($url);
        $moodleurl->params([
            'paymentid' => $identity['commerce_payment_id'],
            'purchaseuuid' => $identity['commerce_purchase_uuid'],
        ]);

        return $moodleurl->out(false);
    }
}
