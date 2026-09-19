<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/**
 * Persists the A5.7 Guest Identity state machine.
 *
 * editing -> otp_pending -> identity_locked
 * identity_locked -> editing only through explicit reset_identity().
 */
final class CommerceGuestIdentityVerificationService {
    public function __construct(
        private readonly CommerceGuestCheckoutSessionRepository $sessions,
        private readonly ?CommerceGuestCartTransferService $carts = null
    ) {}

    public static function create(): self {
        global $DB;
        return new self(
            new CommerceGuestCheckoutSessionRepository($DB),
            CommerceGuestCartTransferService::create()
        );
    }

    private function carts(): CommerceGuestCartTransferService {
        return $this->carts
            ?? CommerceGuestCartTransferService::create();
    }

    public function begin_verification(
        CommerceGuestCheckoutSession $session,
        string $email,
        string $firstname,
        string $lastname
    ): CommerceGuestCheckoutSession {
        $state = CommerceGuestIdentityVerificationState::from_session($session);

        if ($state->is_locked()) {
            throw new \coding_exception(
                'A locked Guest Checkout identity must be explicitly reset before editing.'
            );
        }

        $identity = CommerceGuestIdentityValidator::validate(
            $email,
            $firstname,
            $lastname
        );

        return $this->sessions->update_identity(
            $session,
            $session->get_user_id(),
            $identity['email'],
            $identity['firstname'],
            $identity['lastname'],
            'identity_pending',
            CommerceGuestIdentityVerificationState::otp_pending_metadata(
                $session->get_metadata()
            )
        );
    }

    /**
     * Called only after A5.7.2 has cryptographically validated the OTP.
     */
    public function mark_verified(
        CommerceGuestCheckoutSession $session,
        string $verifiedemail,
        int $now
    ): CommerceGuestCheckoutSession {
        $state = CommerceGuestIdentityVerificationState::from_session($session);

        $sessionemail =
            \core_text::strtolower(trim((string)$session->get_email()));
        $verifiedemail =
            \core_text::strtolower(trim($verifiedemail));

        // L7.3.8: make the successful OTP transition idempotent. A duplicate
        // AJAX verification can arrive after the first request has already
        // locked the exact same mailbox identity.
        if ($state->is_locked()) {
            $lockedemail = \core_text::strtolower(
                trim((string)(
                    $session->get_metadata()['identity_verified_email']
                    ?? ''
                ))
            );
            if (
                $lockedemail !== ''
                && hash_equals($lockedemail, $verifiedemail)
            ) {
                return $session;
            }
        }

        if (!$state->is_otp_pending()) {
            throw new \coding_exception(
                'Guest Checkout identity can only lock from otp_pending.'
            );
        }

        if (
            $sessionemail === ''
            || $verifiedemail === ''
            || !hash_equals($sessionemail, $verifiedemail)
        ) {
            throw new \coding_exception(
                'Verified email does not match the Guest Checkout identity.'
            );
        }

        return $this->sessions->transition(
            $session,
            $session->get_status(),
            [
                'metadatajson' =>
                    CommerceGuestIdentityVerificationState::locked_metadata(
                        $session->get_metadata(),
                        $verifiedemail,
                        $now
                    ),
            ]
        );
    }

    public function reset_identity(
        CommerceGuestCheckoutSession $session
    ): CommerceGuestCheckoutSession {
        $metadata =
            $session->get_metadata();
        $durablecart =
            $metadata['guest_cart_snapshot']
            ?? null;
        $previoususerid =
            (int)($session->get_user_id() ?? 0);

        if (
            $previoususerid > 0
            && $session->get_status() === 'provisional'
            && ($metadata['account_origin'] ?? '') === 'guest_checkout'
        ) {
            // Keep ownership of the suspended provisional Moodle account so a
            // second email on the SAME checkout can reuse it instead of trying
            // to create another checkout_<session> username.
            $metadata['identity_reset_provisional_userid'] =
                $previoususerid;
        }

        if (is_array($durablecart)) {
            $restored =
                $this->carts()
                    ->restore_anonymous(
                        $previoususerid,
                        $session->get_currency(),
                        $durablecart
                    );

            $metadata =
                array_replace(
                    $metadata,
                    [
                        'cart_transferred' => false,
                        'cart_uuid' => $restored->get_uuid(),
                        'cart_item_count' => count(
                            $restored->get_items()
                        ),
                        'identity_reset_cart_restored_at' => time(),
                    ]
                );
        }

        return $this->sessions->transition(
            $session,
            'identity_pending',
            [
                'userid' => null,
                'email' => null,
                'firstname' => null,
                'lastname' => null,
                'purchasereference' => null,
                'paymentreference' => null,
                'metadatajson' =>
                    CommerceGuestIdentityVerificationState::editing_metadata(
                        $metadata
                    ),
            ]
        );
    }
}
