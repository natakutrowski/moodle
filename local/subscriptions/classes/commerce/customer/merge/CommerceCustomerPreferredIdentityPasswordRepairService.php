<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Repairs pre-password-swap M13 preferred-identity merges from their audit trail.
 */
final class CommerceCustomerPreferredIdentityPasswordRepairService {
    private const AUDIT = 'local_subs_identity_merge';

    public function __construct(private readonly moodle_database $database) {}

    /** @return array<string,mixed> */
    public function repair_by_preferred_email(string $email, bool $execute = false): array {
        $email = trim(\core_text::strtolower($email));
        if (!validate_email($email)) {
            throw new \invalid_argument_exception('Invalid preferred email: ' . $email);
        }

        $match = null;
        $records = $this->database->get_records(self::AUDIT, ['status' => 'completed'], 'id DESC');
        foreach ($records as $record) {
            $result = json_decode((string)$record->resultjson, true);
            $transfer = is_array($result) && is_array($result['identitytransfer'] ?? null)
                ? $result['identitytransfer'] : null;
            if ($transfer === null) {
                continue;
            }
            if (\core_text::strtolower((string)($transfer['target_after_email'] ?? '')) === $email) {
                $match = [$record, $result, $transfer];
                break;
            }
        }
        if ($match === null) {
            throw new \moodle_exception('No completed preferred-identity merge audit found for ' . $email);
        }

        [$audit, $result, $transfer] = $match;
        if (!empty($transfer['password_swapped']) || !empty($transfer['password_repair']['completed'])) {
            return [
                'email' => $email,
                'mergeid' => (int)$audit->id,
                'status' => 'already_repaired',
                'executed' => false,
            ];
        }

        $targetuserid = (int)($transfer['targetuserid'] ?? $result['targetuserid'] ?? 0);
        $sourceuserid = (int)($transfer['sourceuserid'] ?? 0);
        if ($targetuserid <= 1 || $sourceuserid <= 1 || $targetuserid === $sourceuserid) {
            throw new \moodle_exception('Merge audit has invalid target/source user ids for ' . $email);
        }

        $target = $this->database->get_record('user', ['id' => $targetuserid, 'deleted' => 0], '*', MUST_EXIST);
        $source = $this->database->get_record('user', ['id' => $sourceuserid, 'deleted' => 0], '*', MUST_EXIST);
        if (\core_text::strtolower((string)$target->email) !== $email
            || (string)$target->username !== (string)($transfer['target_after_username'] ?? '')
            || \core_text::strtolower((string)$source->email) !== \core_text::strtolower((string)($transfer['source_after_email'] ?? ''))
            || (string)$source->username !== (string)($transfer['source_after_username'] ?? '')) {
            throw new \moodle_exception('Current Moodle identities no longer match the audited post-merge state for ' . $email);
        }
        if ((string)$target->auth !== 'manual' || (string)$source->auth !== 'manual') {
            throw new \moodle_exception('Password repair is only safe for two manual-auth Moodle accounts: ' . $email);
        }

        $summary = [
            'email' => $email,
            'mergeid' => (int)$audit->id,
            'targetuserid' => $targetuserid,
            'sourceuserid' => $sourceuserid,
            'status' => $execute ? 'repaired' : 'ready',
            'executed' => $execute,
        ];
        if (!$execute) {
            return $summary;
        }

        $transaction = $this->database->start_delegated_transaction();
        $targetpassword = (string)$target->password;
        $sourcepassword = (string)$source->password;
        $this->database->set_field('user', 'password', $sourcepassword, ['id' => $targetuserid]);
        $this->database->set_field('user', 'password', $targetpassword, ['id' => $sourceuserid]);

        // Record only metadata, never password hashes.
        $result['identitytransfer']['password_swapped'] = true;
        $result['identitytransfer']['password_repair'] = [
            'completed' => true,
            'time' => time(),
            'method' => 'post_m13_cli',
        ];
        $audit->resultjson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $audit->timemodified = time();
        $this->database->update_record(self::AUDIT, $audit);
        $transaction->allow_commit();

        return $summary;
    }
}
