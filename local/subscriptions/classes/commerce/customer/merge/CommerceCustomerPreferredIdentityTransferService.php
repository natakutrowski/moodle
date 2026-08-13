<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Atomically swaps login identity (username + email) from one merge source onto
 * the retained account. Names and pedagogical identity remain on the retained account.
 */
final class CommerceCustomerPreferredIdentityTransferService {
    public function __construct(private readonly moodle_database $database) {}

    /** @return array<string,mixed> */
    public function transfer(int $targetuserid, int $sourceuserid): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        if ($targetuserid <= 1 || $sourceuserid <= 1 || $targetuserid === $sourceuserid) {
            throw new \coding_exception('Preferred identity transfer requires two distinct real Moodle users.');
        }
        $target = $this->database->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);
        $source = $this->database->get_record('user', ['id' => $sourceuserid, 'deleted' => 0], '*', MUST_EXIST);

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
        ];

        if (!validate_email((string)$source->email) || trim((string)$source->username) === '') {
            throw new \moodle_exception('commerce_identity_merge_preferred_identity_invalid', 'local_subscriptions');
        }

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

        return $snapshot;
    }
}
