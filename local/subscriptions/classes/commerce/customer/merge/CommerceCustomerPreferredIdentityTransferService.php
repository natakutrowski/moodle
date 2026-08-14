<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Atomically swaps email + username from one merge source onto the retained account.
 * The password owner is explicit and independent from the preferred login identity.
 * Names and pedagogical identity remain on the retained account.
 */
final class CommerceCustomerPreferredIdentityTransferService {
    public function __construct(private readonly moodle_database $database) {}

    /** @return array<string,mixed> */
    public function transfer(int $targetuserid, int $sourceuserid, ?int $passwordowneruserid = null): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        if ($targetuserid <= 1 || $sourceuserid <= 1 || $targetuserid === $sourceuserid) {
            throw new \coding_exception('Preferred identity transfer requires two distinct real Moodle users.');
        }
        $target = $this->database->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);
        $source = $this->database->get_record('user', ['id' => $sourceuserid, 'deleted' => 0], '*', MUST_EXIST);

        // Preserve the historical/safest behaviour by default: changing email + username does
        // not change the password of the retained Moodle account unless the admin asks for it.
        $passwordowneruserid ??= $targetuserid;
        if (!in_array($passwordowneruserid, [$targetuserid, $sourceuserid], true)) {
            throw new \moodle_exception('commerce_identity_merge_preferred_password_invalid', 'local_subscriptions');
        }

        $snapshot = [
            'targetuserid' => $targetuserid,
            'sourceuserid' => $sourceuserid,
            'target_before_email' => (string)$target->email,
            'target_before_username' => (string)$target->username,
            'source_before_email' => (string)$source->email,
            'source_before_username' => (string)$source->username,
            'target_after_email' => (string)$source->email,
            'target_after_username' => (string)$source->username,
            'source_after_email' => (string)$target->email,
            'source_after_username' => (string)$target->username,
            'password_owner_userid' => $passwordowneruserid,
            'password_swapped' => false,
        ];

        if (!validate_email((string)$source->email) || trim((string)$source->username) === '') {
            throw new \moodle_exception('commerce_identity_merge_preferred_identity_invalid', 'local_subscriptions');
        }

        $swappassword = $passwordowneruserid === $sourceuserid;
        if ($swappassword && ((string)$target->auth !== 'manual' || (string)$source->auth !== 'manual')) {
            throw new \moodle_exception('commerce_identity_merge_preferred_password_manual_only', 'local_subscriptions');
        }

        // Never expose either hash in the returned/audited snapshot.
        $targetpassword = $swappassword ? (string)$target->password : null;
        $sourcepassword = $swappassword ? (string)$source->password : null;

        $nonce = substr(hash('sha256', $targetuserid . ':' . $sourceuserid . ':' . microtime(true)), 0, 12);
        $source->username = 'merge-swap-' . $sourceuserid . '-' . $nonce;
        $source->email = 'merge-swap-' . $sourceuserid . '-' . $nonce . '@example.invalid';
        user_update_user($source, false, false);

        $target->username = $snapshot['target_after_username'];
        $target->email = $snapshot['target_after_email'];
        user_update_user($target, false, false);

        $source = $this->database->get_record('user', ['id' => $sourceuserid], '*', MUST_EXIST);
        $source->username = $snapshot['source_after_username'];
        $source->email = $snapshot['source_after_email'];
        user_update_user($source, false, false);

        if ($swappassword) {
            // user_update_user() expects clear-text passwords when a password is supplied. Use
            // direct DB writes because these values are already Moodle password hashes.
            $this->database->set_field('user', 'password', $sourcepassword, ['id' => $targetuserid]);
            $this->database->set_field('user', 'password', $targetpassword, ['id' => $sourceuserid]);
            $snapshot['password_swapped'] = true;
        }

        return $snapshot;
    }
}
