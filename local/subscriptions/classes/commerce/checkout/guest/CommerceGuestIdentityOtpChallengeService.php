<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * Issues and validates six-digit Guest Checkout identity OTP challenges.
 *
 * OTP hashes and throttling counters live in Guest Checkout metadata, avoiding
 * a schema upgrade while keeping raw codes out of persistent storage.
 */
final class CommerceGuestIdentityOtpChallengeService {
    public const CODE_TTL = 600;
    public const RESEND_DELAY = 60;
    public const SEND_WINDOW = 3600;
    public const MAX_SENDS_PER_WINDOW = 5;
    public const MAX_VERIFY_ATTEMPTS = 5;

    public function __construct(
        private readonly CommerceGuestCheckoutSessionRepository $sessions,
        private readonly CommerceGuestIdentityVerificationService $verification,
        private readonly CommerceGuestCheckoutService $checkout
    ) {}

    public static function create(): self {
        global $DB;
        $sessions = new CommerceGuestCheckoutSessionRepository($DB);

        return new self(
            $sessions,
            new CommerceGuestIdentityVerificationService($sessions),
            CommerceGuestCheckoutService::create()
        );
    }

    /**
     * @return array{status:string,session:CommerceGuestCheckoutSession,retryafter:int,expiresin:int}
     */
    public function issue(
        CommerceGuestCheckoutSession $session,
        string $email,
        string $firstname,
        string $lastname,
        string $language,
        ?int $now = null
    ): array {
        $now ??= time();
        $metadata = $session->get_metadata();

        // H12.9-A5.7.3.5: counters produced by the pre-delivery-aware OTP
        // implementation must not lock a customer out. Once this contract
        // marker is absent, discard those legacy counters exactly once.
        if (!in_array(($metadata['identity_otp_delivery_contract'] ?? ''), ['delivered_only_v1', 'delivered_only_v2'], true)) {
            unset(
                $metadata['identity_otp_sent_at'],
                $metadata['identity_otp_send_window_started_at'],
                $metadata['identity_otp_send_count'],
                $metadata['identity_otp_hash'],
                $metadata['identity_otp_challenge_id'],
                $metadata['identity_otp_expires_at'],
                $metadata['identity_otp_attempts']
            );
            $metadata['identity_otp_delivery_contract'] = 'delivered_only_v1';
            $session = $this->sessions->transition(
                $session,
                $session->get_status(),
                ['metadatajson' => $metadata]
            );
        }

        $lastsent = (int)($metadata['identity_otp_sent_at'] ?? 0);
        $requestedidentity = CommerceGuestIdentityValidator::validate(
            $email,
            $firstname,
            $lastname
        );
        $sameemail =
            $session->get_email() !== null
            && hash_equals(
                \core_text::strtolower(trim((string)$session->get_email())),
                \core_text::strtolower($requestedidentity['email'])
            );

        if (
            $sameemail
            && $lastsent > 0
            && ($lastsent + self::RESEND_DELAY) > $now
        ) {
            // The OTP proves mailbox ownership, not the spelling of a person's
            // name. Keep the live challenge while allowing name corrections
            // before verification.
            $session = $this->sessions->update_identity(
                $session,
                $session->get_user_id(),
                $requestedidentity['email'],
                $requestedidentity['firstname'],
                $requestedidentity['lastname'],
                $session->get_status(),
                $metadata
            );

            return [
                'status' => 'wait',
                'session' => $session,
                'retryafter' => ($lastsent + self::RESEND_DELAY) - $now,
                'expiresin' => max(
                    0,
                    (int)($metadata['identity_otp_expires_at'] ?? 0) - $now
                ),
            ];
        }

        $windowstarted = (int)($metadata['identity_otp_send_window_started_at'] ?? 0);
        $sendcount = (int)($metadata['identity_otp_send_count'] ?? 0);

        if (
            $windowstarted <= 0
            || ($windowstarted + self::SEND_WINDOW) <= $now
        ) {
            $windowstarted = $now;
            $sendcount = 0;
        }

        if ($sendcount >= self::MAX_SENDS_PER_WINDOW) {
            return [
                'status' => 'rate_limited',
                'session' => $session,
                'retryafter' => max(1, ($windowstarted + self::SEND_WINDOW) - $now),
                'expiresin' => 0,
            ];
        }

        $session = $this->verification->begin_verification(
            $session,
            $requestedidentity['email'],
            $requestedidentity['firstname'],
            $requestedidentity['lastname']
        );

        $code = str_pad(
            (string)random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
        $challengeid = bin2hex(random_bytes(16));

        // H12.9-A5.7.3.6:
        // An OTP email is interactive, not a normal retryable transactional
        // message. Cancel any older queued OTP for this Guest Checkout before
        // activating a new challenge, otherwise cron could later deliver a
        // stale code that can never verify.
        $mailprefix =
            'guest-identity-otp:'
            . $session->get_id()
            . ':';
        (new CommerceMailQueueRepository())->cancel_queued_by_prefix(
            CommerceMailType::GUEST_IDENTITY_OTP,
            $mailprefix,
            'Superseded by a newer Guest Checkout OTP challenge.',
            $now
        );

        // Persist the exact challenge before delivery. This guarantees that a
        // successfully delivered code is already the authoritative code even
        // if the HTTP request is interrupted immediately after SMTP accepts it.
        $metadata = $session->get_metadata();
        $metadata['identity_otp_delivery_contract'] = 'delivered_only_v2';
        $metadata['identity_otp_challenge_id'] = $challengeid;
        $metadata['identity_otp_hash'] = password_hash(
            $code,
            PASSWORD_DEFAULT
        );
        $metadata['identity_otp_expires_at'] =
            $now + self::CODE_TTL;
        $metadata['identity_otp_sent_at'] = 0;
        $metadata['identity_otp_attempts'] = 0;
        $metadata['identity_otp_send_window_started_at'] =
            $windowstarted;
        $metadata['identity_otp_send_count'] =
            $sendcount;

        $session = $this->sessions->transition(
            $session,
            $session->get_status(),
            ['metadatajson' => $metadata]
        );

        if (!$this->send_code(
            $session,
            $code,
            $challengeid,
            $language
        )) {
            // No retry is allowed for OTP delivery. Remove the active secret
            // immediately so a failed delivery cannot leave a phantom code.
            $metadata = $session->get_metadata();
            unset(
                $metadata['identity_otp_challenge_id'],
                $metadata['identity_otp_hash'],
                $metadata['identity_otp_expires_at'],
                $metadata['identity_otp_sent_at'],
                $metadata['identity_otp_attempts']
            );

            $session = $this->sessions->transition(
                $session,
                $session->get_status(),
                ['metadatajson' => $metadata]
            );

            return [
                'status' => 'delivery_failed',
                'session' => $session,
                'retryafter' => 0,
                'expiresin' => 0,
            ];
        }

        // Only a confirmed immediate delivery consumes the resend quota.
        $metadata = $session->get_metadata();
        $metadata['identity_otp_sent_at'] = $now;
        $metadata['identity_otp_send_count'] =
            $sendcount + 1;

        $session = $this->sessions->transition(
            $session,
            $session->get_status(),
            ['metadatajson' => $metadata]
        );

        return [
            'status' => 'sent',
            'session' => $session,
            'retryafter' => self::RESEND_DELAY,
            'expiresin' => self::CODE_TTL,
        ];
    }

    /**
     * @return array{
     *   status:string,
     *   session:CommerceGuestCheckoutSession,
     *   attemptsremaining:int,
     *   requireslogin:bool,
     *   paymentready:bool
     * }
     */
    public function verify(
        CommerceGuestCheckoutSession $session,
        string $code,
        ?int $now = null
    ): array {
        $now ??= time();
        $metadata = $session->get_metadata();
        $state = CommerceGuestIdentityVerificationState::from_session($session);

        if (!$state->is_otp_pending()) {
            return $this->verification_result(
                'not_pending',
                $session,
                0
            );
        }

        $hash = trim((string)($metadata['identity_otp_hash'] ?? ''));
        $expiresat = (int)($metadata['identity_otp_expires_at'] ?? 0);
        $attempts = (int)($metadata['identity_otp_attempts'] ?? 0);

        if ($hash === '' || $expiresat <= 0 || $expiresat < $now) {
            return $this->verification_result(
                'expired',
                $session,
                0
            );
        }

        if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
            return $this->verification_result(
                'attempts_exhausted',
                $session,
                0
            );
        }

        $code = trim($code);

        if (
            !preg_match('/^\d{6}$/', $code)
            || !password_verify($code, $hash)
        ) {
            $attempts++;
            $metadata['identity_otp_attempts'] = $attempts;
            $session = $this->sessions->transition(
                $session,
                $session->get_status(),
                ['metadatajson' => $metadata]
            );

            return $this->verification_result(
                $attempts >= self::MAX_VERIFY_ATTEMPTS
                    ? 'attempts_exhausted'
                    : 'invalid',
                $session,
                max(0, self::MAX_VERIFY_ATTEMPTS - $attempts)
            );
        }

        // Only after proving possession of the mailbox do we resolve whether
        // this is a new/provisional identity or an existing CampusFR account.
        $resolved = $this->checkout->identify(
            $session,
            (string)$session->get_email(),
            (string)$session->get_first_name(),
            (string)$session->get_last_name(),
            true
        );

        $resolved = $this->verification->mark_verified(
            $resolved,
            (string)$session->get_email(),
            $now
        );

        return $this->verification_result(
            'verified',
            $resolved,
            self::MAX_VERIFY_ATTEMPTS
        );
    }

    /**
     * @return array{
     *   status:string,
     *   session:CommerceGuestCheckoutSession,
     *   attemptsremaining:int,
     *   requireslogin:bool,
     *   paymentready:bool
     * }
     */
    private function verification_result(
        string $status,
        CommerceGuestCheckoutSession $session,
        int $attemptsremaining
    ): array {
        $requireslogin = $session->get_status() === 'existing_account';
        $paymentready =
            $status === 'verified'
            && !$requireslogin
            && $session->get_user_id() !== null;

        return [
            'status' => $status,
            'session' => $session,
            'attemptsremaining' => $attemptsremaining,
            'requireslogin' => $requireslogin,
            'paymentready' => $paymentready,
        ];
    }

    private function send_code(
        CommerceGuestCheckoutSession $session,
        string $code,
        string $challengeid,
        string $language
    ): bool {
        global $PAGE;

        // Commerce templates are rendered by the queue processor before the
        // transport is invoked, so AJAX checkout requests need a page context
        // here, before process_ids().
        $PAGE->set_context(\context_system::instance());
        $name = trim(
            (string)$session->get_first_name()
            . ' '
            . (string)$session->get_last_name()
        );

        $request = new CommerceMailRequest(
            CommerceMailType::GUEST_IDENTITY_OTP,
            new CommerceMailRecipient(
                (string)$session->get_email(),
                $name
            ),
            new CommerceMailContext([
                'code' => $code,
                'name' => $name,
                'expiresminutes' => (int)(self::CODE_TTL / 60),
            ]),
            clean_param($language, PARAM_LANG),
            CommerceMailIdempotencyKey::normalise(
                'guest-identity-otp:'
                . $session->get_id()
                . ':'
                . $challengeid
            )
        );

        $record = CommerceMailRuntime::queue_service()->queue(
            $request,
            1
        );

        // OTP is interactive checkout traffic: do not wait for cron.
        $result = CommerceMailRuntime::processor()->process_ids([
            (int)$record->id,
        ]);

        $sent =
            (int)($result['sent'] ?? 0) === 1;

        if (!$sent) {
            $repository =
                new CommerceMailQueueRepository();
            $persisted =
                $repository->find_by_id(
                    (int)$record->id
                );

            if (
                $persisted !== null
                && (string)$persisted->status
                    === \local_subscriptions\commerce\mail\CommerceMailStatus::QUEUED
            ) {
                $repository->mark_cancelled(
                    (int)$persisted->id,
                    'Interactive Guest Checkout OTP delivery failed; delayed retry is forbidden.'
                );
            }
        }

        return $sent;
    }
}
