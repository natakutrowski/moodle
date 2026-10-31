<?php
// CampusFR remember-this-device token manager.
// GPL v3 or later.
namespace auth_campusremember;

defined('MOODLE_INTERNAL') || die();

final class manager {
    private const TABLE = 'auth_campusremember_tokens';
    private const COOKIE = '__Host-CampusFRRemember';
    private const LIFETIME = 30 * DAYSECS;

    private static function web_request(): bool {
        if ((defined('CLI_SCRIPT') && CLI_SCRIPT) ||
                (defined('AJAX_SCRIPT') && AJAX_SCRIPT) ||
                (defined('WS_SERVER') && WS_SERVER) ||
                (defined('NO_MOODLE_COOKIES') && NO_MOODLE_COOKIES) ||
                !is_https() || headers_sent()) {
            return false;
        }
        return true;
    }

    private static function is_eligible(\stdClass $user): bool {
        global $CFG;
        // Never auto-sign in guest, admin, suspended, deleted or unconfirmed users.
        if (empty($user->id) || (int)$user->id === (int)($CFG->siteguest ?? 0) ||
                !empty($user->deleted) || !empty($user->suspended) || empty($user->confirmed)) {
            return false;
        }
        if (!in_array($user->auth, ['email', 'manual'], true) || is_siteadmin($user->id)) {
            return false;
        }
        return !has_capability('moodle/site:config', \context_system::instance(), $user->id);
    }

    /** Return [selector, rawvalidator] or null, never store cookie plaintext. */
    private static function cookie_parts(): ?array {
        $value = $_COOKIE[self::COOKIE] ?? '';
        if (!is_string($value) || !preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/D', $value, $parts)) {
            return null;
        }
        return [$parts[1], $parts[2]];
    }

    private static function set_cookie(string $value, int $expires): void {
        if (!self::web_request()) {
            return;
        }
        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function forget_cookie(): void {
        self::set_cookie('', time() - DAYSECS);
        unset($_COOKIE[self::COOKIE]);
    }

    /** Normal manual login, after Moodle has validated password and login CSRF token. */
    public static function on_login(\core\event\user_loggedin $event): void {
        global $USER, $DB;
        // Any successful normal-form login replaces the persistent identity of this device.
        if (!self::web_request() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ||
                !preg_match('~/login/index\.php$~', $_SERVER['SCRIPT_NAME'] ?? '') ||
                empty($_POST['logintoken']) || (int)$event->userid !== (int)$USER->id) {
            return;
        }
        // Revoke an existing token even when the option is NOT checked, or when
        // a different person logs into a previously remembered shared device.
        if ($old = self::cookie_parts()) {
            $DB->delete_records(self::TABLE, ['selector' => $old[0]]);
            self::forget_cookie();
        }
        if (($_POST['campusremember'] ?? '') !== '1' || !self::is_eligible($USER)) {
            return;
        }
        $passwordhash = $DB->get_field('user', 'password', ['id' => $USER->id]);
        if (!is_string($passwordhash) || $passwordhash === '') {
            return;
        }
        // Avoid indefinite growth of expired token records for this account.
        $DB->delete_records_select(self::TABLE, 'userid = ? AND expires < ?', [$USER->id, time()]);
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $now = time();
        $DB->insert_record(self::TABLE, (object)[
            'userid' => $USER->id,
            'selector' => $selector,
            'tokenhash' => hash('sha256', $validator),
            'pwfingerprint' => hash('sha256', $passwordhash),
            'timecreated' => $now,
            'lastused' => $now,
            'expires' => $now + self::LIFETIME,
        ]);
        self::set_cookie($selector . ':' . $validator, $now + self::LIFETIME);
    }

    /** Log the user in from an opt-in, unexpired browser cookie. */
    public static function try_restore(): void {
        global $CFG, $DB;
        if (!self::web_request() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' ||
                (isloggedin() && !isguestuser())) {
            return;
        }
        $parts = self::cookie_parts();
        if ($parts === null) {
            return;
        }
        [$selector, $validator] = $parts;
        $record = $DB->get_record(self::TABLE, ['selector' => $selector]);
        if (!$record || (int)$record->expires <= time() ||
                !hash_equals($record->tokenhash, hash('sha256', $validator))) {
            if ($record && ((int)$record->expires <= time() ||
                    hash_equals($record->tokenhash, hash('sha256', $validator)))) {
                $DB->delete_records(self::TABLE, ['id' => $record->id]);
            }
            self::forget_cookie();
            return;
        }
        $user = \get_complete_user_data('id', $record->userid);
        $pw = $DB->get_field('user', 'password', ['id' => $record->userid]);
        if (!$user || !is_string($pw) ||
                !hash_equals($record->pwfingerprint, hash('sha256', $pw)) ||
                !self::is_eligible($user)) {
            $DB->delete_records(self::TABLE, ['id' => $record->id]);
            self::forget_cookie();
            return;
        }
        // A normal Moodle login starts a new session and regenerates its session ID.
        \complete_user_login($user);
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());
        $DB->set_field(self::TABLE, 'lastused', time(), ['id' => $record->id]);
        // Stay on the originally requested protected URL, when safe.
        $requesturi = $_SERVER['REQUEST_URI'] ?? '';
        // CampusFR: replace Moodle's default dashboard.
        if (is_string($requesturi) &&
                preg_match('~^/my/(?:index\.php)?(?:\?|$)~', $requesturi)) {
            \redirect(new \moodle_url('/local/subscriptions/mon_campus.php'));
        }
        if (is_string($requesturi) && preg_match('~^/(?!/)[^\r\n]*$~D', $requesturi) &&
                !preg_match('~^/login/(?:index|logout)\.php(?:\?|$)~', $requesturi)) {
            \redirect(new \moodle_url($CFG->wwwroot . $requesturi));
        }
        \redirect(new \moodle_url('/local/subscriptions/mon_campus.php'));
    }

    /** Called before the Moodle logout, including from non-standard logout flows. */
    public static function logout_this_device(): void {
        global $DB;
        if (!self::web_request()) {
            return;
        }
        if ($parts = self::cookie_parts()) {
            // Only this device's opaque selector is revoked, no password/user exposed.
            $DB->delete_records(self::TABLE, ['selector' => $parts[0]]);
        }
        self::forget_cookie();
    }

    /** Covers manual password changes and 'forgot password' resets. */
    public static function on_password_change(\core\event\user_password_updated $event): void {
        global $DB;
        if (!empty($event->relateduserid)) {
            $DB->delete_records(self::TABLE, ['userid' => $event->relateduserid]);
        }
    }

    public static function on_user_deleted(\core\event\user_deleted $event): void {
        global $DB;
        if (!empty($event->objectid)) {
            $DB->delete_records(self::TABLE, ['userid' => $event->objectid]);
        }
    }
}
