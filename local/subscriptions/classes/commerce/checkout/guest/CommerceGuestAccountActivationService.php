<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Issues and consumes one-time account activation links for paid Guest Checkout accounts. */
final class CommerceGuestAccountActivationService {
    public const KEY_SCRIPT = 'local_subscriptions_guest_account_activation';
    public const KEY_TTL = 172800;

    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {}

    public function issue_activation_url(CommerceGuestCheckoutSession $session): \moodle_url {
        $userid = $session->get_user_id();
        if ($userid === null || $userid <= 0) {
            throw new \coding_exception('Guest account activation requires a resolved Moodle user.');
        }

        $metadata = $session->get_metadata();
        if (($metadata['account_origin'] ?? '') !== 'guest_checkout') {
            throw new \coding_exception('Only provisional Guest Checkout accounts can use this activation flow.');
        }

        delete_user_key(self::KEY_SCRIPT, $userid);
        $validuntil = time() + self::KEY_TTL;
        $key = create_user_key(self::KEY_SCRIPT, $userid, $session->get_id(), null, $validuntil);

        $metadata['activation_link_issued_at'] = time();
        $metadata['activation_link_expires_at'] = $validuntil;
        $this->sessions->transition($session, $session->get_status(), [
            'metadatajson' => $metadata,
        ]);

        $activationurl = new \moodle_url('/local/subscriptions/guest_account_activate.php', [
            'uid' => $userid,
            'sessionid' => $session->get_id(),
            'key' => $key,
            'reference' => (string)($session->get_purchase_reference() ?? ''),
        ]);
        $activationurl->set_anchor('activation');

        return $activationurl;
    }

    /** @return array{user:\stdClass,session:CommerceGuestCheckoutSession} */
    public function validate(string $key, int $userid, int $sessionid): array {
        $keyrecord = validate_user_key(trim($key), self::KEY_SCRIPT, $sessionid);
        if ((int)$keyrecord->userid !== $userid) {
            throw new \moodle_exception('invalidkey');
        }

        $session = $this->sessions->require_by_id($sessionid);
        if ((int)($session->get_user_id() ?? 0) !== $userid) {
            throw new \moodle_exception('invalidkey');
        }

        $metadata = $session->get_metadata();
        if (($metadata['account_origin'] ?? '') !== 'guest_checkout') {
            throw new \moodle_exception('invalidkey');
        }

        $user = $this->database->get_record('user', [
            'id' => $userid,
            'deleted' => 0,
        ], '*', MUST_EXIST);

        return ['user' => $user, 'session' => $session];
    }

    public function complete(
        string $key,
        int $userid,
        int $sessionid,
        string $password,
        bool $login = true
    ): CommerceGuestCheckoutSession {
        global $CFG;

        $validated = $this->validate($key, $userid, $sessionid);
        $user = $validated['user'];
        $session = $validated['session'];

        $passworderror = '';
        if (!check_password_policy($password, $passworderror)) {
            throw new \moodle_exception('commerce_guest_activation_password_invalid', 'local_subscriptions', '', $passworderror);
        }

        require_once($CFG->libdir . '/moodlelib.php');
        require_once($CFG->dirroot . '/local/subscriptions/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        $previoususername = (string)$user->username;
        if (str_starts_with($previoususername, 'checkout_')) {
            $user->username = local_subscriptions_generate_unique_username(
                (string)$user->firstname,
                (string)$user->lastname,
                (string)$user->email
            );
            user_update_user($user, false, false);
        }

        update_internal_user_password($user, $password);
        unset_user_preference('auth_forcepasswordchange', $userid);
        delete_user_key(self::KEY_SCRIPT, $userid);

        $metadata = $session->get_metadata();
        $metadata['account_state'] = 'ready';
        $metadata['password_set_at'] = time();
        if ($previoususername !== (string)$user->username) {
            $metadata['provisional_username'] = $previoususername;
            $metadata['final_username'] = (string)$user->username;
            $metadata['username_finalised_at'] = time();
        }
        $metadata['activation_requires_password_reset'] = false;
        unset($metadata['activation_link_expires_at']);

        $session = $this->sessions->transition($session, 'active', [
            'expiresat' => 0,
            'metadatajson' => $metadata,
        ]);

        if ($login) {
            complete_user_login($this->database->get_record('user', ['id' => $userid], '*', MUST_EXIST));
        }

        return $session;
    }
}
