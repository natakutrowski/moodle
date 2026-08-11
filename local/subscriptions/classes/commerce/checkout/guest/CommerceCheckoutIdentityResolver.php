<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Resolves the checkout identity without authenticating provisional accounts. */
final class CommerceCheckoutIdentityResolver {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {}

    public static function create(): self {
        global $DB;
        return new self($DB, new CommerceGuestCheckoutSessionRepository($DB));
    }

    public function resolve(string $currency): CommerceCheckoutIdentity {
        global $USER, $SESSION;

        if (isloggedin() && !isguestuser()) {
            return new CommerceCheckoutIdentity(
                (int) $USER->id,
                (string) $USER->email,
                (string) $USER->firstname,
                (string) $USER->lastname
            );
        }

        $token = trim((string) ($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        $session = $token !== '' ? $this->sessions->find_by_token($token) : null;
        if ($session === null || $session->is_expired() || $session->get_currency() !== strtoupper($currency)) {
            throw new \moodle_exception('commerce_guest_checkout_identity_required', 'local_subscriptions');
        }
        if ($session->get_status() === 'existing_account') {
            throw new \moodle_exception('commerce_guest_checkout_login_required', 'local_subscriptions');
        }
        if (!in_array($session->get_status(), ['provisional', 'payment_pending'], true) || $session->get_user_id() === null) {
            throw new \moodle_exception('commerce_guest_checkout_identity_required', 'local_subscriptions');
        }

        $user = $this->database->get_record('user', ['id' => $session->get_user_id(), 'deleted' => 0], '*', MUST_EXIST);
        return new CommerceCheckoutIdentity(
            (int) $user->id,
            (string) $user->email,
            (string) $user->firstname,
            (string) $user->lastname,
            $session
        );
    }
}
