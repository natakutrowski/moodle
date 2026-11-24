<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\MoodleCommerceMailTransport;
use local_subscriptions\commerce\mail\template\CommerceGuestIdentityOtpTemplate;

/**
 * G5B3B DEV: email-verified gift recipient enrollment. No public runtime.
 * The invitation's *fingerprint* alone is stored in the Moodle session.
 * Six-digit verification codes are stored only as password_hash digests.
 */
final class CommerceGiftRecipientSignupService {
    private const TABLE = 'local_subs_commerce_gift_signup';
    private const SESSION_TTL = 1200;
    private const OTP_TTL = 600;
    private const MAX_SENDS = 3;
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly \moodle_database $db) {}

    private function gate(): void {
        if (!CommerceGiftCommercialRuntimeGate::can_operate()
            || !(new CommerceGiftFeatureToggle())->can_redeem()) {
            throw new \DomainException('Gift onboarding is not enabled for this DEV test.');
        }
    }

    /** @return array{giftid:int,email:string,account_exists:bool,allow_registration:bool} */
    public function begin(string $rawcode): array {
        global $SESSION;
        $this->gate();
        $state = (new CommerceGiftRecipientOnboardingInspector($this->db))->inspect($rawcode);
        // No bearer invitation secret in the session, form, URL, logs or database.
        $SESSION->cfr_gift_signup = (object)[
            'giftid' => (int)$state['giftid'],
            'fingerprint' => CommerceGiftToken::fingerprint($rawcode),
            'nonce' => bin2hex(random_bytes(16)),
            'expires' => time() + self::SESSION_TTL,
        ];
        return $state;
    }

    /** @return array{giftid:int,email:string,account_exists:bool,allow_registration:bool} */
    public function current(): array {
        global $SESSION;
        $this->gate();
        $stored = $SESSION->cfr_gift_signup ?? null;
        if (!is_object($stored) || (int)($stored->expires ?? 0) <= time()
                || !preg_match('/\A[a-f0-9]{64}\z/D', (string)($stored->fingerprint ?? ''))
                || !preg_match('/\A[a-f0-9]{32}\z/D', (string)($stored->nonce ?? ''))) {
            throw new \DomainException('Gift session has expired. Enter your invitation code again.');
        }
        $token = $this->db->get_record(CommerceGiftSchema::TABLE_TOKEN,
            ['tokenhash' => (string)$stored->fingerprint, 'giftid' => (int)$stored->giftid], '*', MUST_EXIST);
        $gift = $this->db->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => (int)$stored->giftid], '*', MUST_EXIST);
        CommerceGiftActivationPolicy::assert_claimable($gift, $token, time());
        CommerceGiftActivationPolicy::reader_right($gift);
        (new CommerceGiftPaidSafety($this->db))->assert_still_paid($gift);
        if ($gift->deliverymode !== CommerceGiftRecipientPolicy::MODE_EMAIL
                || filter_var((string)$gift->intendedrecipientemail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DomainException('This invitation does not support new-account enrollment.');
        }
        $email = strtolower(trim((string)$gift->intendedrecipientemail));
        if (hash_equals($email, strtolower(trim((string)$gift->buyeremail)))) {
            throw new \DomainException('The buyer cannot be the gift recipient.');
        }
        $existing = $this->db->record_exists_sql('SELECT 1 FROM {user} WHERE deleted = 0 AND LOWER(email) = :mail',
            ['mail' => $email]);
        return ['giftid' => (int)$gift->id, 'email' => $email, 'account_exists' => $existing,
            'allow_registration' => $gift->intendedrecipientuserid === null];
    }

    /** @return array{sent:bool,expiresin:int} */
    public function send_email_verification(): array {
        global $SESSION;
        $state = $this->current();
        if ($state['account_exists'] || !$state['allow_registration']) {
            throw new \DomainException('New-account registration is not available for this gift.');
        }
        $giftid = $state['giftid'];
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('signup:' . $giftid, 10);
        if (!$lock) { throw new \RuntimeException('Gift verification lock unavailable.'); }
        try {
            $now = time();
            $row = $this->db->get_record(self::TABLE, ['giftid' => $giftid], '*', IGNORE_MISSING);
            if ($row && (string)$row->status === 'sending' && (int)$row->expiresat > $now) {
                throw new \DomainException('A verification email is already being sent.');
            }
            if ($row && (string)$row->status === 'sent' && (int)$row->expiresat > $now) {
                throw new \DomainException('A verification code is already active.');
            }
            if ($row && (string)$row->status === 'review' && (int)$row->expiresat > $now) {
                throw new \DomainException('Email delivery requires manual review.');
            }
            $windowstart = ($row && $now - (int)$row->windowstart < 3600) ? (int)$row->windowstart : $now;
            $sendcount = ($row && $windowstart === (int)$row->windowstart) ? (int)$row->sendcount : 0;
            if ($sendcount >= self::MAX_SENDS) {
                throw new \DomainException('Verification resend limit reached.');
            }
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $data = (object)[
                'giftid' => $giftid, 'sessionhash' => hash('sha256', (string)$SESSION->cfr_gift_signup->nonce),
                'codehash' => password_hash($code, PASSWORD_DEFAULT), 'status' => 'sending',
                'sentat' => null, 'expiresat' => $now + self::OTP_TTL, 'attempts' => 0,
                'windowstart' => $windowstart, 'sendcount' => $sendcount + 1,
                'timemodified' => $now,
            ];
            if ($row) { $data->id = (int)$row->id; $this->db->update_record(self::TABLE, $data); }
            else { $data->timecreated = $now; $this->db->insert_record(self::TABLE, $data); }
            // Delivery occurs synchronously. Never place the OTP in Mail Studio's persistent JSON queue.
            $language = in_array(current_language(), ['fr', 'en', 'ru'], true) ? current_language() : 'fr';
            $email = $state['email'];
            try {
                // Interactive OTP: reuse the real Checkout OTP renderer, but send
                // synchronously. No bearer code is written to the Commerce outbox.
                global $PAGE;
                $PAGE->set_context(\context_system::instance());
                $copy = self::verification_copy($language, $code, $email);
                (new MoodleCommerceMailTransport())->send(new CommerceMailMessage(
                    new CommerceMailRecipient($email, 'CampusFR'), $copy['subject'],
                    $copy['html'], $copy['text']));
                $this->db->set_field(self::TABLE, 'status', 'sent', ['giftid' => $giftid]);
                $this->db->set_field(self::TABLE, 'sentat', time(), ['giftid' => $giftid]);
                // G6C5E: persist a non-dispatchable, redacted Commerce history entry.
                // A journal failure must NOT cause a second OTP email or revoke a sent OTP.
                try {
                    $mailrow = (new CommerceGiftVerificationMailJournal($this->db))->record_sent(
                        $giftid, $email, $language, $copy['subject']);
                    // Save an encrypted administrator-viewable copy, distinct from the mail log.
                    try {
                        CommerceGiftAdminCodeEscrow::capture($giftid, (int)$mailrow->id,
                            CommerceGiftAdminCodeEscrow::VERIFICATION, $code);
                    } catch (\Throwable $escrowerror) {
                        debugging('Gift OTP admin escrow unavailable for gift #' . $giftid, DEBUG_DEVELOPER);
                    }
                } catch (\Throwable $journalerror) {
                    debugging('Gift verification mail history unavailable for gift #' . $giftid, DEBUG_DEVELOPER);
                }
                return ['sent' => true, 'expiresin' => self::OTP_TTL];
            } catch (\Throwable $e) {
                $this->db->set_field(self::TABLE, 'status', 'review', ['giftid' => $giftid]);
                throw new \RuntimeException('Verification email delivery could not be confirmed.');
            }
        } finally { $lock->release(); }
    }

    /**
     * Render the same branded six-digit OTP email used by Commerce Guest Checkout.
     * The gift-specific wording is selected via the purpose context, while the
     * authenticated payment/checkout flow keeps its original wording.
     * Raw codes are never queued, logged or stored in the Commerce mail history.
     *
     * @return array{subject:string,html:string,text:string}
     */
    public static function verification_copy(string $lang, string $code, string $email = 'preview@example.test'): array {
        if (!preg_match('/\A[0-9]{6}\z/D', $code)) {
            throw new \InvalidArgumentException('A verification email requires exactly six digits.');
        }
        $language = in_array($lang, ['fr', 'en', 'ru'], true) ? $lang : 'fr';
        $request = new CommerceMailRequest(
            CommerceMailType::GUEST_IDENTITY_OTP,
            new CommerceMailRecipient($email, 'CampusFR'),
            new CommerceMailContext([
                'code' => $code,
                'name' => '',
                'expiresminutes' => self::OTP_TTL / 60,
                'purpose' => 'gift',
            ]),
            $language,
            'gift-verification-direct-render'
        );
        $message = (new CommerceGuestIdentityOtpTemplate())->render($request);
        return ['subject' => $message->get_subject(),
            'html' => $message->get_html(), 'text' => $message->get_text()];
    }

    /** @return array{created:bool,activated:bool,userid:int} */
    public function verify_create_and_claim(string $otp, string $first, string $last, string $pass, string $confirm): array {
        global $CFG, $SESSION;
        $state = $this->current();
        if ($state['account_exists'] || !$state['allow_registration']) { throw new \DomainException('New-account registration is not permitted.'); }
        if (!preg_match('/\A[0-9]{6}\z/D', $otp)) { throw new \DomainException('Invalid verification code.'); }
        $first = trim($first); $last = trim($last);
        // Reject markup, rather than silently persisting cleaned-up names.
        if (clean_param($first, PARAM_NOTAGS) !== $first
                || clean_param($last, PARAM_NOTAGS) !== $last) {
            throw new \DomainException('Names must not contain HTML.');
        }
        if (\core_text::strlen($first) < 2 || \core_text::strlen($first) > 80
                || \core_text::strlen($last) < 2 || \core_text::strlen($last) > 80
                || preg_match('/[\x00-\x1f\x7f]/u', $first . $last)) {
            throw new \DomainException('Invalid recipient names.');
        }
        if ($pass !== $confirm) { throw new \DomainException('Passwords do not match.'); }
        $policyerror = '';
        if (!check_password_policy($pass, $policyerror)) { throw new \DomainException('Password does not meet Moodle policy.'); }
        $lock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
            ->get_lock('signup:' . $state['giftid'], 10);
        if (!$lock) { throw new \RuntimeException('Gift signup lock unavailable.'); }
        try {
            // Always recheck eligibility and mailbox ownership under the gift signup lock.
            $state = $this->current();
            if ($state['account_exists']) { throw new \DomainException('This address already has an account.'); }
            $row = $this->db->get_record(self::TABLE, ['giftid' => $state['giftid']], '*', MUST_EXIST);
            if ((string)$row->status !== 'sent' || (int)$row->expiresat <= time()
                    || !hash_equals((string)$row->sessionhash, hash('sha256', (string)$SESSION->cfr_gift_signup->nonce))
                    || (int)$row->attempts >= self::MAX_ATTEMPTS) {
                throw new \DomainException('Email verification is no longer valid.');
            }
            $this->db->set_field(self::TABLE, 'attempts', (int)$row->attempts + 1, ['id' => (int)$row->id]);
            if (!password_verify($otp, (string)$row->codehash)) {
                throw new \DomainException('Invalid email verification code.');
            }
            // Reject accounts previously created via another gift/session and prevent race on the same email.
            $emaillock = lock_config::get_lock_factory('local_subscriptions_commerce_gift_claim')
                ->get_lock('email:' . hash('sha256', $state['email']), 10);
            if (!$emaillock) { throw new \RuntimeException('Email registration lock unavailable.'); }
            try {
                if ($this->db->record_exists_sql('SELECT 1 FROM {user} WHERE deleted = 0 AND LOWER(email) = :mail',
                        ['mail' => $state['email']])) {
                    throw new \DomainException('This address already has a CampusFR account.');
                }
                require_once($CFG->dirroot . '/user/lib.php');
                $user = (object)[
                    'auth' => 'manual', 'confirmed' => 1, 'suspended' => 0,
                    'mnethostid' => (int)$CFG->mnet_localhost_id,
                    'username' => 'gift_' . bin2hex(random_bytes(12)),
                    'email' => $state['email'], 'firstname' => $first, 'lastname' => $last,
                    'password' => $pass, 'lang' => current_language(),
                ];
                $userid = (int)user_create_user($user, true, false);
                $this->db->update_record(self::TABLE, (object)[
                    'id' => (int)$row->id, 'status' => 'consumed', 'codehash' => null,
                    'timemodified' => time(),
                ]);
            } finally { $emaillock->release(); }
        } finally { $lock->release(); }
        // Start a genuine authenticated Moodle session before using the native gift grant engine.
        // Preserve only the nonreversible fingerprint in local PHP memory, prior
        // to session regeneration performed by complete_user_login().
        $hash = (string)$SESSION->cfr_gift_signup->fingerprint;
        $giftid = $state['giftid'];
        complete_user_login($this->db->get_record('user', ['id' => $userid], '*', MUST_EXIST));
        unset($SESSION->cfr_gift_signup);
        try {
            (new CommerceGiftClaimService($this->db))->claim_verified_fingerprint($hash);
            (new CommerceGiftReaderActivationService($this->db))->activate($giftid);
            return ['created' => true, 'activated' => true, 'userid' => $userid];
        } catch (\Throwable $e) {
            // The account was safely created and verified, but the gift may need retry.
            return ['created' => true, 'activated' => false, 'userid' => $userid];
        }
    }

    public function claim_existing(): bool {
        global $USER, $SESSION;
        $state = $this->current();
        if (!isloggedin() || isguestuser() || (int)$USER->id <= 2 || empty($USER->confirmed)) {
            throw new \DomainException('Sign in with your existing confirmed CampusFR account.');
        }
        if (!hash_equals($state['email'], strtolower(trim((string)$USER->email)))) {
            throw new \DomainException('This invitation belongs to another email address.');
        }
        (new CommerceGiftClaimService($this->db))->claim_verified_fingerprint((string)$SESSION->cfr_gift_signup->fingerprint);
        (new CommerceGiftReaderActivationService($this->db))->activate($state['giftid']);
        unset($SESSION->cfr_gift_signup);
        return true;
    }
}
