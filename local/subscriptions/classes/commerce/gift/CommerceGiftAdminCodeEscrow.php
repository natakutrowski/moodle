<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Admin-only encrypted escrow for recently sent gift and OTP codes.
 * Secret bytes never enter the Commerce mail row, its preview or its logs.
 * Capture is best effort AFTER confirmed mail delivery and journaling.
 */
final class CommerceGiftAdminCodeEscrow {
    public const INVITATION = 'invitation';
    public const VERIFICATION = 'verification';
    private const MAIL_TABLE = 'local_subs_commerce_mail';
    private const MAX_FILE = 8192;

    private static function kind(string $kind): void {
        if (!in_array($kind, [self::INVITATION, self::VERIFICATION], true)) {
            throw new \InvalidArgumentException('Invalid secret category.');
        }
    }

    private static function root(): string {
        global $CFG;
        $dataroot = realpath((string)($CFG->dataroot ?? ''));
        $webroot = realpath((string)($CFG->dirroot ?? ''));
        if (!$dataroot || !$webroot || str_starts_with($dataroot . '/', $webroot . '/')) {
            throw new \DomainException('Private storage unavailable.');
        }
        $path = $dataroot . '/local_subscriptions_private/commerce_gift_admin_codes';
        if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
            throw new \RuntimeException('Private mail-code directory unavailable.');
        }
        if (is_link($path) || !is_writable($path)) {
            throw new \RuntimeException('Unsafe mail-code directory.');
        }
        @chmod($path, 0700);
        return $path;
    }

    private static function file(int $mailid, string $kind): string {
        self::kind($kind);
        if ($mailid <= 0) { throw new \InvalidArgumentException('Invalid mail row.'); }
        return self::root() . '/mail_' . $mailid . '_' . $kind . '.json';
    }

    private static function key(): string {
        global $CFG;
        $hex = (string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex');
        $dbsecret = (string)($CFG->dbpass ?? '');
        if (!preg_match('/\A[0-9a-f]{64}\z/D', $hex) || $dbsecret === ''
            || !function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
            throw new \DomainException('Gift-code encryption prerequisites unavailable.');
        }
        return hash_hkdf('sha256', hex2bin($hex) . "\0" . $dbsecret, 32,
            'campusfr:commerce:gift-admin-secret:v1');
    }

    /** @return array{gift: \stdClass, row: \stdClass} */
    private static function evidence(int $giftid, int $mailid, string $kind): array {
        global $DB;
        self::kind($kind);
        $mail = $DB->get_record(self::MAIL_TABLE, ['id' => $mailid], '*', MUST_EXIST);
        $expectedtype = $kind === self::INVITATION ? 'gift_invitation' : 'gift_verification_otp';
        if ((string)$mail->status !== 'sent' || (string)$mail->mailtype !== $expectedtype
            || (int)($mail->purchaseid ?? 0) <= 0) {
            throw new \DomainException('Mail event is not a confirmed gift message.');
        }
        $ctx = json_decode((string)$mail->contextjson, true);
        $slot = $kind === self::INVITATION ? 'gift_history' : 'gift_verification_history';
        if (!is_array($ctx) || (int)($ctx[$slot]['giftid'] ?? 0) !== $giftid) {
            throw new \DomainException('Mail/gift reference mismatch.');
        }
        $gift = $DB->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => $giftid, 'sourceproductsku' => 'AVENT_2026'], '*', MUST_EXIST);
        if ((int)$mail->purchaseid !== (int)$gift->purchaseid
            || !hash_equals(strtolower(trim((string)$gift->intendedrecipientemail)),
                strtolower(trim((string)$mail->recipientemail)))) {
            throw new \DomainException('Gift recipient or purchase mismatch.');
        }
        (new CommerceGiftPaidSafety($DB))->assert_still_paid($gift);
        if ($kind === self::VERIFICATION) {
            $otp = $DB->get_record('local_subs_commerce_gift_signup', ['giftid' => $giftid], '*', IGNORE_MISSING);
            if (!$otp || (string)$otp->status !== 'sent' || (int)$otp->expiresat <= time()) {
                throw new \DomainException('Gift email challenge expired or consumed.');
            }
            $expectedkey = sprintf('gift-verification:%d:%d:%d:%d', $giftid,
                (int)$otp->id, (int)$otp->windowstart, (int)$otp->sendcount);
            if (!hash_equals($expectedkey, (string)$mail->idempotencykey)) {
                throw new \DomainException('Mail entry does not match current OTP send.');
            }
        } else if (!empty($gift->claimedat) || !empty($gift->activatedat)) {
            throw new \DomainException('Gift invitation already redeemed.');
        }
        return ['gift' => $gift, 'row' => $mail];
    }

    /** Opportunistically erase expired encrypted files; bounded to avoid SMTP latency spikes. */
    private static function cleanup(): void {
        $files = glob(self::root() . '/mail_*_*.json') ?: [];
        $checked = 0;
        foreach ($files as $file) {
            if (++$checked > 40) { break; }
            if (!is_file($file) || is_link($file) || filesize($file) > self::MAX_FILE) { continue; }
            $outer = json_decode((string)file_get_contents($file), true);
            if (is_array($outer) && (int)($outer['expires'] ?? PHP_INT_MAX) <= time()) {
                @unlink($file);
            }
        }
    }

    /** Never raises an SMTP error: caller catches any exception AFTER mail has been accepted. */
    public static function capture(int $giftid, int $mailid, string $kind, string $code): void {
        global $DB;
        self::kind($kind);
        if ($kind === self::INVITATION && !preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $code)) {
            throw new \InvalidArgumentException('Invalid invitation code format.');
        }
        if ($kind === self::VERIFICATION && !preg_match('/\A[0-9]{6}\z/D', $code)) {
            throw new \InvalidArgumentException('Invalid verification code format.');
        }
        self::evidence($giftid, $mailid, $kind);
        if ($kind === self::INVITATION) {
            $token = $DB->get_record(CommerceGiftSchema::TABLE_TOKEN,
                ['giftid' => $giftid, 'tokenhash' => CommerceGiftToken::fingerprint($code)], '*', MUST_EXIST);
            if (!empty($token->usedat) || !empty($token->revokedat)
                || (int)$token->expiresat <= time()) {
                throw new \DomainException('Invitation no longer valid.');
            }
            $expires = (int)$token->expiresat;
        } else {
            $otp = $DB->get_record('local_subs_commerce_gift_signup', ['giftid' => $giftid], '*', MUST_EXIST);
            if (!password_verify($code, (string)$otp->codehash)) {
                throw new \DomainException('Verification code is not current.');
            }
            $expires = (int)$otp->expiresat;
        }
        // For safety, even long-lived gift claims are revealable for at most 48 hours.
        $expires = min($expires, time() + ($kind === self::INVITATION ? 48 * HOURSECS : 10 * MINSECS));
        if ($expires <= time()) { return; }
        $path = self::file($mailid, $kind);
        self::cleanup();
        if (is_link($path) || file_exists($path)) { return; }
        $plaintext = json_encode(['v' => 1, 'mailid' => $mailid, 'giftid' => $giftid,
            'kind' => $kind, 'expires' => $expires, 'code' => $code], JSON_THROW_ON_ERROR);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA,
            $iv, $tag, 'cfr:gift:mail:' . $mailid . ':' . $kind, 16);
        if (!is_string($cipher)) { throw new \RuntimeException('Encryption failed.'); }
        $body = json_encode(['v' => 1, 'expires' => $expires, 'iv' => base64_encode($iv),
            'tag' => base64_encode($tag), 'cipher' => base64_encode($cipher)], JSON_THROW_ON_ERROR);
        $tmp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (file_put_contents($tmp, $body, LOCK_EX) !== strlen($body)) {
            @unlink($tmp);
            throw new \RuntimeException('Private encrypted escrow write failed.');
        }
        @chmod($tmp, 0600);
        if (!rename($tmp, $path)) { @unlink($tmp); throw new \RuntimeException('Private escrow publication failed.'); }
    }

    /** Admin can reveal only the CURRENT unexpired, unused code tied to this sent mail. */
    public static function reveal(int $mailid, string $kind): ?string {
        global $DB, $USER;
        self::kind($kind);
        if (!is_siteadmin((int)($USER->id ?? 0))) {
            throw new \required_capability_exception(\context_system::instance(),
                'moodle/site:config', 'nopermissions', '');
        }
        $path = self::file($mailid, $kind);
        if (!is_file($path) || is_link($path) || filesize($path) > self::MAX_FILE) { return null; }
        $body = json_decode((string)file_get_contents($path), true);
        if (!is_array($body) || ($body['v'] ?? null) !== 1) { return null; }
        if ((int)($body['expires'] ?? 0) <= time()) { @unlink($path); return null; }
        $iv = base64_decode((string)($body['iv'] ?? ''), true);
        $tag = base64_decode((string)($body['tag'] ?? ''), true);
        $cipher = base64_decode((string)($body['cipher'] ?? ''), true);
        if (!is_string($iv) || strlen($iv) !== 12 || !is_string($tag) || strlen($tag) !== 16
            || !is_string($cipher)) { return null; }
        $clear = openssl_decrypt($cipher, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA,
            $iv, $tag, 'cfr:gift:mail:' . $mailid . ':' . $kind);
        if (!is_string($clear)) { return null; }
        $entry = json_decode($clear, true);
        if (!is_array($entry) || (int)($entry['mailid'] ?? 0) !== $mailid
            || ($entry['kind'] ?? '') !== $kind || (int)($entry['expires'] ?? 0) <= time()
            || (int)($entry['expires'] ?? 0) !== (int)$body['expires']) { return null; }
        $giftid = (int)($entry['giftid'] ?? 0);
        $code = (string)($entry['code'] ?? '');
        try {
            self::evidence($giftid, $mailid, $kind);
            if ($kind === self::INVITATION) {
                if (!preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $code)) { return null; }
                $token = $DB->get_record(CommerceGiftSchema::TABLE_TOKEN,
                    ['giftid' => $giftid, 'tokenhash' => CommerceGiftToken::fingerprint($code)], '*', IGNORE_MISSING);
                if (!$token || !empty($token->usedat) || !empty($token->revokedat)
                    || (int)$token->expiresat <= time()) { return null; }
            } else {
                if (!preg_match('/\A[0-9]{6}\z/D', $code)) { return null; }
                $otp = $DB->get_record('local_subs_commerce_gift_signup', ['giftid' => $giftid], '*', IGNORE_MISSING);
                if (!$otp || !password_verify($code, (string)$otp->codehash)) { return null; }
            }
        } catch (\Throwable $e) {
            return null;
        }
        // Auditable read; no code/email is logged. Fail closed if event cannot be recorded.
        $DB->insert_record(CommerceGiftSchema::TABLE_EVENT, (object)[
            'giftid' => $giftid, 'eventtype' => 'admin_code_revealed',
            'actoruserid' => (int)$USER->id, 'occurredat' => time(),
            'idempotencykey' => hash('sha256', 'cfr:gift:admin-code-view:' . bin2hex(random_bytes(16))),
            'detailjson' => json_encode(['mailid' => $mailid, 'kind' => $kind], JSON_THROW_ON_ERROR),
        ]);
        return $code;
    }
}
