<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Resolves the provisional Guest Checkout account owned by the current browser session. */
final class CommerceProvisionalGuestAccountContext {
    /**
     * @return array{session:CommerceGuestCheckoutSession,reference:string,activationurl:\moodle_url}|null
     */
    public static function resolve(?string $purchasereference = null): ?array {
        global $DB, $SESSION;

        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        if ($token === '') {
            return null;
        }

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $purchasereference === null || trim($purchasereference) === ''
            ? $repository->find_by_token($token)
            : $repository->find_by_purchase_reference(trim($purchasereference));

        if ($session === null || !hash_equals($session->get_token(), $token)) {
            return null;
        }

        $reference = trim((string)($session->get_purchase_reference() ?? ''));
        $metadata = $session->get_metadata();
        if ($reference === ''
                || ($metadata['account_origin'] ?? '') !== 'guest_checkout'
                || !empty($metadata['password_set_at'])
                || (int)($session->get_user_id() ?? 0) <= 0) {
            return null;
        }

        return [
            'session' => $session,
            'reference' => $reference,
            'activationurl' => new \moodle_url('/local/subscriptions/guest_account_activation_start.php', [
                'reference' => $reference,
            ]),
        ];
    }
}
