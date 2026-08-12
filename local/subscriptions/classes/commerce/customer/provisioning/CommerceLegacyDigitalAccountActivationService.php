<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\provisioning;

defined('MOODLE_INTERNAL') || die();

/**
 * One-time password activation for Legacy Digital provisioned accounts.
 */
final class CommerceLegacyDigitalAccountActivationService {
    public const KEY_SCRIPT = 'local_subscriptions_legacy_digital_activation';
    public const KEY_TTL = 604800;

    public function __construct(
        private readonly \moodle_database $database
    ) {
    }

    /**
     * @return array{url:\moodle_url,expiresat:int}
     */
    public function issue_activation_url(\stdClass $user): array {
        $userid = (int)$user->id;
        if ($userid <= 0) {
            throw new \coding_exception(
                'Legacy Digital account activation requires a valid Moodle user.'
            );
        }

        delete_user_key(self::KEY_SCRIPT, $userid);
        $expiresat = time() + self::KEY_TTL;
        $key = create_user_key(
            self::KEY_SCRIPT,
            $userid,
            $userid,
            null,
            $expiresat
        );

        set_user_preference(
            'local_subscriptions_legacy_activation_issued_at',
            time(),
            $userid
        );

        return [
            'url' => new \moodle_url(
                '/local/subscriptions/legacy_account_activate.php',
                [
                    'uid' => $userid,
                    'key' => $key,
                ]
            ),
            'expiresat' => $expiresat,
        ];
    }

    public function validate(
        string $key,
        int $userid
    ): \stdClass {
        $keyrecord = validate_user_key(
            trim($key),
            self::KEY_SCRIPT,
            $userid
        );

        if ((int)$keyrecord->userid !== $userid) {
            throw new \moodle_exception('invalidkey');
        }

        $origin = (string)get_user_preferences(
            'local_subscriptions_account_origin',
            '',
            $userid
        );
        $state = (string)get_user_preferences(
            'local_subscriptions_account_state',
            '',
            $userid
        );

        if (
            $origin !== 'legacy_digital_provisioning'
            || $state !== 'activation_pending'
        ) {
            throw new \moodle_exception('invalidkey');
        }

        return $this->database->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
            ],
            '*',
            MUST_EXIST
        );
    }

    public function complete(
        string $key,
        int $userid,
        string $password,
        bool $login = true
    ): \stdClass {
        global $CFG;

        $user = $this->validate($key, $userid);

        $passworderror = '';
        if (!check_password_policy($password, $passworderror)) {
            throw new \moodle_exception(
                'commerce_guest_activation_password_invalid',
                'local_subscriptions',
                '',
                $passworderror
            );
        }

        require_once($CFG->dirroot . '/user/lib.php');

        update_internal_user_password($user, $password);

        $user->confirmed = 1;
        $user->suspended = 0;
        $user->timemodified = time();
        user_update_user($user, false, false);

        unset_user_preference('auth_forcepasswordchange', $userid);
        set_user_preference(
            'local_subscriptions_account_state',
            'ready',
            $userid
        );
        set_user_preference(
            'local_subscriptions_legacy_activated_at',
            time(),
            $userid
        );
        delete_user_key(self::KEY_SCRIPT, $userid);

        $user = $this->database->get_record(
            'user',
            ['id' => $userid],
            '*',
            MUST_EXIST
        );

        if ($login) {
            complete_user_login($user);
        }

        return $user;
    }
}
