<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\campaign;

defined('MOODLE_INTERNAL') || die();

/**
 * Global, email-based suppression for generic Mail Studio marketing campaigns.
 * Do not use for receipts, payment notices, account activation or course access.
 */
final class CommerceMarketingOptoutService {
    private const TABLE = 'local_subs_mail_optout';
    private const SECRET_CONFIG = 'commerce_mail_optout_hmac_secret';

    public function __construct(private readonly \moodle_database $db) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public static function canonical_email(string $email): string {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            throw new \invalid_parameter_exception('Invalid marketing email address.');
        }
        return $email;
    }

    public function is_suppressed(string $email): bool {
        return $this->db->record_exists(self::TABLE, ['email' => self::canonical_email($email)]);
    }

    /** Returns true only on first registration. Oppositions remain effective indefinitely. */
    public function suppress(string $email, string $source = 'unsubscribe_link'): bool {
        $email = self::canonical_email($email);
        if ($this->is_suppressed($email)) {
            return false;
        }
        $source = substr(trim($source), 0, 40);
        if ($source === '') {
            $source = 'unsubscribe_link';
        }
        try {
            $this->db->insert_record(self::TABLE, (object)[
                'email' => $email,
                'source' => $source,
                'timecreated' => time(),
            ]);
            return true;
        } catch (\dml_write_exception $exception) {
            // Two concurrent unsubscribe requests may collide on the unique email index.
            if ($this->is_suppressed($email)) {
                return false;
            }
            throw $exception;
        }
    }

    /**
     * Opaque-looking recipient ID + signed token. No email address is exposed in URLs.
     * The secret is generated in db/upgrade.php; fail closed if the migration was missed.
     */
    public function token_for(int $recipientid, string $email): string {
        if ($recipientid <= 0) {
            throw new \invalid_parameter_exception('Invalid campaign recipient ID.');
        }
        $secret = (string)get_config('local_subscriptions', self::SECRET_CONFIG);
        if ($secret === '' && defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            // PHPUnit creates tables from install.xml without running db/upgrade.php.
            // Isolated test databases therefore need their own test-only secret.
            $secret = bin2hex(random_bytes(32));
            set_config(self::SECRET_CONFIG, $secret, 'local_subscriptions');
        }
        if (strlen($secret) !== 64 || !ctype_xdigit($secret)) {
            throw new \coding_exception('Marketing unsubscribe secret missing. Run Moodle upgrade first.');
        }
        return hash_hmac('sha256', $recipientid . ':' . self::canonical_email($email), $secret);
    }

    public function url_for(int $recipientid, string $email): \moodle_url {
        return new \moodle_url('/local/subscriptions/marketing_unsubscribe.php', [
            'r' => $recipientid,
            't' => $this->token_for($recipientid, $email),
        ]);
    }

    /** Returns address only when the URL is authentic and references a frozen recipient. */
    public function email_from_signed_link(int $recipientid, string $token): ?string {
        if ($recipientid <= 0 || preg_match('/^[0-9a-f]{64}$/D', $token) !== 1) {
            return null;
        }
        $recipient = $this->db->get_record('local_subs_mail_campaign_recipient',
            ['id' => $recipientid], 'id,email', IGNORE_MISSING);
        if (!$recipient) {
            return null;
        }
        $email = self::canonical_email((string)$recipient->email);
        if (!hash_equals($this->token_for($recipientid, $email), $token)) {
            return null;
        }
        return $email;
    }
}
