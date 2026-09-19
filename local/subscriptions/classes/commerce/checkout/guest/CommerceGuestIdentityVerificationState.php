<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/**
 * A5.7.1 Guest Checkout identity-verification state.
 *
 * The state deliberately lives in Guest Checkout metadata so this phase does
 * not require a schema upgrade. OTP secrets themselves are NOT stored here;
 * A5.7.2 owns challenge persistence and delivery.
 */
final class CommerceGuestIdentityVerificationState {
    public const EDITING = 'editing';
    public const OTP_PENDING = 'otp_pending';
    public const IDENTITY_LOCKED = 'identity_locked';

    private const METADATA_STATE = 'identity_verification_state';
    private const METADATA_LOCKED_AT = 'identity_locked_at';
    private const METADATA_VERIFIED_EMAIL = 'identity_verified_email';

    private function __construct(
        private readonly string $state,
        private readonly ?string $verifiedemail,
        private readonly ?int $lockedat
    ) {}

    public static function from_session(
        CommerceGuestCheckoutSession $session
    ): self {
        $metadata = $session->get_metadata();
        $state = trim((string)($metadata[self::METADATA_STATE] ?? self::EDITING));

        if (!in_array($state, [
            self::EDITING,
            self::OTP_PENDING,
            self::IDENTITY_LOCKED,
        ], true)) {
            $state = self::EDITING;
        }

        $verifiedemail = trim((string)($metadata[self::METADATA_VERIFIED_EMAIL] ?? ''));
        $lockedat = (int)($metadata[self::METADATA_LOCKED_AT] ?? 0);

        return new self(
            $state,
            $verifiedemail !== '' ? \core_text::strtolower($verifiedemail) : null,
            $lockedat > 0 ? $lockedat : null
        );
    }

    public function get_state(): string {
        return $this->state;
    }

    public function is_editing(): bool {
        return $this->state === self::EDITING;
    }

    public function is_otp_pending(): bool {
        return $this->state === self::OTP_PENDING;
    }

    public function is_locked(): bool {
        return $this->state === self::IDENTITY_LOCKED;
    }

    public function get_verified_email(): ?string {
        return $this->verifiedemail;
    }

    public function get_locked_at(): ?int {
        return $this->lockedat;
    }

    /** @return array<string, mixed> */
    public static function editing_metadata(array $metadata): array {
        unset(
            $metadata[self::METADATA_LOCKED_AT],
            $metadata[self::METADATA_VERIFIED_EMAIL],
            $metadata['identity_otp_challenge_id'],
            $metadata['identity_otp_hash'],
            $metadata['identity_otp_expires_at'],
            $metadata['identity_otp_sent_at'],
            $metadata['identity_otp_attempts'],
            $metadata['identity_otp_send_window_started_at'],
            $metadata['identity_otp_send_count'],
            $metadata['identity_otp_delivery_contract']
        );
        $metadata[self::METADATA_STATE] = self::EDITING;
        return $metadata;
    }

    /** @return array<string, mixed> */
    public static function otp_pending_metadata(array $metadata): array {
        unset(
            $metadata[self::METADATA_LOCKED_AT],
            $metadata[self::METADATA_VERIFIED_EMAIL],
            $metadata['identity_otp_challenge_id'],
            $metadata['identity_otp_hash'],
            $metadata['identity_otp_expires_at'],
            $metadata['identity_otp_sent_at'],
            $metadata['identity_otp_attempts'],
            $metadata['identity_otp_send_window_started_at'],
            $metadata['identity_otp_send_count'],
            $metadata['identity_otp_delivery_contract']
        );
        $metadata[self::METADATA_STATE] = self::OTP_PENDING;
        return $metadata;
    }

    /** @return array<string, mixed> */
    public static function locked_metadata(
        array $metadata,
        string $verifiedemail,
        int $now
    ): array {
        unset(
            $metadata['identity_otp_challenge_id'],
            $metadata['identity_otp_hash'],
            $metadata['identity_otp_expires_at'],
            $metadata['identity_otp_sent_at'],
            $metadata['identity_otp_attempts']
        );
        $metadata[self::METADATA_STATE] = self::IDENTITY_LOCKED;
        $metadata[self::METADATA_VERIFIED_EMAIL] =
            \core_text::strtolower(trim($verifiedemail));
        $metadata[self::METADATA_LOCKED_AT] = $now;
        return $metadata;
    }
}
