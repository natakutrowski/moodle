<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\reconciliation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/**
 * Reconciles account-less Native Commerce purchases with an existing Moodle user.
 *
 * Matching is intentionally conservative: an account is linked only when one and
 * only one non-deleted local Moodle identity owns the exact buyer email address.
 */
final class CommerceCustomerIdentityReconciliationService {
    private const TABLE_GRANT = 'local_subs_commerce_grant';
    private const TABLE_DIGITAL_ACCESS = 'local_subs_commerce_dig_access';
    private const TABLE_GUEST = 'local_subs_commerce_guest';
    private const TABLE_LEGACY_DIGITAL = 'subscription_digital_payment_request';

    public function __construct(private readonly moodle_database $database) {}

    /**
     * Reconcile every unresolved Native purchase matching the supplied Moodle user.
     *
     * @return CommerceCustomerIdentityReconciliationResult[]
     */
    public function reconcile_user(int $userid, bool $execute = true): array {
        global $CFG;

        $user = $this->database->get_record('user', [
            'id' => $userid,
            'deleted' => 0,
            'mnethostid' => (int)$CFG->mnet_localhost_id,
        ], 'id,email', IGNORE_MISSING);
        if ($user === false) {
            return [];
        }

        $email = $this->normalise_email((string)$user->email);
        if ($email === '') {
            return [];
        }

        $emailcondition = $this->database->sql_equal('customeremail', ':customeremail', false);
        $records = $this->database->get_records_sql(
            'SELECT *
               FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '}
              WHERE userid IS NULL
                AND customeremail IS NOT NULL
                AND customeremail <> \'\'
                AND ' . $emailcondition . '
           ORDER BY id ASC',
            ['customeremail' => $email]
        );

        $results = [];
        foreach ($records as $record) {
            $results[] = $this->reconcile_purchase_record($record, $user, $execute);
        }
        return $results;
    }

    /**
     * Reconcile unresolved purchases in deterministic ascending id order.
     *
     * @return CommerceCustomerIdentityReconciliationResult[]
     */
    public function reconcile_batch(int $limit = 0, bool $execute = false, ?string $email = null, int $offset = 0): array {
        $params = [];
        $where = [
            'userid IS NULL',
            'customeremail IS NOT NULL',
            "customeremail <> ''",
        ];
        if ($email !== null && $this->normalise_email($email) !== '') {
            $where[] = $this->database->sql_equal('customeremail', ':customeremail', false);
            $params['customeremail'] = $this->normalise_email($email);
        }

        $records = $this->database->get_records_sql(
            'SELECT *
               FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '}
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY id ASC',
            $params,
            max(0, $offset),
            max(0, $limit)
        );

        $results = [];
        foreach ($records as $record) {
            $emailvalue = $this->normalise_email((string)$record->customeremail);
            if ($emailvalue === '') {
                $results[] = $this->result(CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED, $record);
                continue;
            }

            $users = $this->find_users_by_email($emailvalue);
            if ($users === []) {
                $results[] = $this->result(CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND, $record);
                continue;
            }
            if (count($users) !== 1) {
                $results[] = $this->result(
                    CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS,
                    $record,
                    null,
                    array_map('intval', array_keys($users))
                );
                continue;
            }

            $results[] = $this->reconcile_purchase_record($record, reset($users), $execute);
        }
        return $results;
    }


    /** Count unresolved purchases, optionally restricted to one buyer email. */
    public function count_unresolved(?string $email = null): int {
        $params = [];
        $where = [
            'userid IS NULL',
            'customeremail IS NOT NULL',
            "customeremail <> ''",
        ];

        if ($email !== null && $this->normalise_email($email) !== '') {
            $where[] = $this->database->sql_equal('customeremail', ':customeremail', false);
            $params['customeremail'] = $this->normalise_email($email);
        }

        return (int)$this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '}
              WHERE ' . implode(' AND ', $where),
            $params
        );
    }

    /** Build a read-only impact preview for one unresolved purchase. */
    public function preview_purchase(int $purchaseid): CommerceCustomerIdentityReconciliationPreview {
        $result = $this->reconcile_purchase($purchaseid, false);
        if (
            $result->status !== CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED
            || $result->userid === null
            || $result->purchaseid === null
        ) {
            return new CommerceCustomerIdentityReconciliationPreview($result);
        }

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $result->purchaseid],
            '*',
            MUST_EXIST
        );
        $email = $this->normalise_email((string)$purchase->customeremail);

        return new CommerceCustomerIdentityReconciliationPreview(
            $result,
            1,
            $this->count_beneficiaries(self::TABLE_GRANT, (string)$purchase->reference, $email),
            $this->count_beneficiaries(self::TABLE_DIGITAL_ACCESS, (string)$purchase->reference, $email),
            $this->count_guest_sessions((string)$purchase->reference, $email),
            $this->count_legacy_digital_source($purchase, $email)
        );
    }

    /**
     * Inspect or reconcile one unresolved purchase by its Native Commerce id.
     *
     * This method deliberately re-evaluates the Moodle identity at execution time.
     */
    public function reconcile_purchase(int $purchaseid, bool $execute = false): CommerceCustomerIdentityReconciliationResult {
        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => $purchaseid],
            '*',
            IGNORE_MISSING
        );

        if ($purchase === false) {
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED,
                null,
                null,
                null,
                null
            );
        }

        if (!empty($purchase->userid)) {
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_UNCHANGED,
                (int)$purchase->id,
                (string)$purchase->reference,
                $this->normalise_email((string)$purchase->customeremail) ?: null,
                (int)$purchase->userid
            );
        }

        $email = $this->normalise_email((string)$purchase->customeremail);
        if ($email === '') {
            return $this->result(CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED, $purchase);
        }

        $users = $this->find_users_by_email($email);
        if ($users === []) {
            return $this->result(CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND, $purchase);
        }
        if (count($users) !== 1) {
            return $this->result(
                CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS,
                $purchase,
                null,
                array_map('intval', array_keys($users))
            );
        }

        return $this->reconcile_purchase_record($purchase, reset($users), $execute);
    }

    /**
     * Explicitly reconcile one projected Legacy Digital purchase to a selected
     * Moodle user, even when the historical buyer email differs from the
     * Moodle account email.
     *
     * This is an administrator-confirmed cross-source path and is never used by
     * automatic email reconciliation.
     */
    public function reconcile_legacy_digital_to_user(
        int $legacyid,
        int $userid,
        bool $execute = false
    ): CommerceCustomerIdentityReconciliationResult {
        global $CFG;

        if ($legacyid <= 0 || $userid <= 1) {
            throw new \invalid_parameter_exception(
                'A positive Legacy Digital id and Moodle userid are required.'
            );
        }

        $user = $this->database->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
                'mnethostid' => (int)$CFG->mnet_localhost_id,
            ],
            'id,email',
            MUST_EXIST
        );

        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            [
                'legacyfamily' => 'digital',
                'legacyid' => $legacyid,
            ],
            '*',
            IGNORE_MISSING
        );

        if ($purchase === false) {
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND,
                null,
                null,
                null,
                $userid
            );
        }

        $legacy = $this->database->get_record(
            self::TABLE_LEGACY_DIGITAL,
            ['id' => $legacyid],
            '*',
            MUST_EXIST
        );

        if (
            (!empty($purchase->userid) && (int)$purchase->userid !== $userid)
            || (!empty($legacy->userid) && (int)$legacy->userid !== $userid)
        ) {
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS,
                (int)$purchase->id,
                (string)$purchase->reference,
                $this->normalise_email((string)$purchase->customeremail) ?: null,
                null
            );
        }

        $email = $this->normalise_email((string)$purchase->customeremail);
        if ($email === '') {
            $email = $this->normalise_email((string)$legacy->email);
        }

        if (!$execute) {
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED,
                (int)$purchase->id,
                (string)$purchase->reference,
                $email !== '' ? $email : null,
                $userid
            );
        }

        $transaction = $this->database->start_delegated_transaction();

        $current = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => (int)$purchase->id],
            '*',
            MUST_EXIST
        );
        $currentlegacy = $this->database->get_record(
            self::TABLE_LEGACY_DIGITAL,
            ['id' => $legacyid],
            '*',
            MUST_EXIST
        );

        if (
            (!empty($current->userid) && (int)$current->userid !== $userid)
            || (!empty($currentlegacy->userid) && (int)$currentlegacy->userid !== $userid)
        ) {
            $transaction->allow_commit();
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS,
                (int)$current->id,
                (string)$current->reference,
                $email !== '' ? $email : null,
                null
            );
        }

        $now = time();

        if (empty($current->userid)) {
            $this->database->update_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                (object)[
                    'id' => (int)$current->id,
                    'userid' => $userid,
                    'timemodified' => $now,
                ]
            );
        }

        $grantsupdated = $email !== ''
            ? $this->update_beneficiaries(
                self::TABLE_GRANT,
                (string)$current->reference,
                $email,
                $userid,
                $now
            )
            : 0;

        $digitalaccessupdated = $email !== ''
            ? $this->update_beneficiaries(
                self::TABLE_DIGITAL_ACCESS,
                (string)$current->reference,
                $email,
                $userid,
                $now
            )
            : 0;

        $guestsessionsupdated = $email !== ''
            ? $this->update_guest_sessions(
                (string)$current->reference,
                $email,
                $userid,
                $now
            )
            : 0;

        $legacyrecordsupdated = 0;
        if (empty($currentlegacy->userid)) {
            $this->database->update_record(
                self::TABLE_LEGACY_DIGITAL,
                (object)[
                    'id' => $legacyid,
                    'userid' => $userid,
                    'last_update' => $now,
                ]
            );
            $legacyrecordsupdated = 1;
        }

        $transaction->allow_commit();

        return new CommerceCustomerIdentityReconciliationResult(
            CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED,
            (int)$current->id,
            (string)$current->reference,
            $email !== '' ? $email : null,
            $userid,
            $grantsupdated,
            $digitalaccessupdated,
            $guestsessionsupdated,
            $legacyrecordsupdated
        );
    }

    private function reconcile_purchase_record(
        \stdClass $purchase,
        \stdClass $user,
        bool $execute
    ): CommerceCustomerIdentityReconciliationResult {
        $email = $this->normalise_email((string)$purchase->customeremail);
        if ($email === '' || $email !== $this->normalise_email((string)$user->email)) {
            return $this->result(CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED, $purchase);
        }

        if (!$execute) {
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED,
                (int)$purchase->id,
                (string)$purchase->reference,
                $email,
                (int)$user->id
            );
        }

        $transaction = $this->database->start_delegated_transaction();
        $current = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['id' => (int)$purchase->id],
            '*',
            MUST_EXIST
        );

        if (!empty($current->userid)) {
            $transaction->allow_commit();
            return new CommerceCustomerIdentityReconciliationResult(
                CommerceCustomerIdentityReconciliationResult::STATUS_UNCHANGED,
                (int)$current->id,
                (string)$current->reference,
                $this->normalise_email((string)$current->customeremail) ?: null,
                (int)$current->userid
            );
        }

        if ($this->normalise_email((string)$current->customeremail) !== $email) {
            $transaction->allow_commit();
            return $this->result(CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED, $current);
        }

        $now = time();
        $this->database->update_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'id' => (int)$current->id,
            'userid' => (int)$user->id,
            'timemodified' => $now,
        ]);

        $grantsupdated = $this->update_beneficiaries(
            self::TABLE_GRANT,
            (string)$current->reference,
            $email,
            (int)$user->id,
            $now
        );
        $digitalaccessupdated = $this->update_beneficiaries(
            self::TABLE_DIGITAL_ACCESS,
            (string)$current->reference,
            $email,
            (int)$user->id,
            $now
        );
        $guestsessionsupdated = $this->update_guest_sessions(
            (string)$current->reference,
            $email,
            (int)$user->id,
            $now
        );
        $legacyrecordsupdated = $this->update_legacy_digital_source($current, $email, (int)$user->id, $now);

        $transaction->allow_commit();

        return new CommerceCustomerIdentityReconciliationResult(
            CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED,
            (int)$current->id,
            (string)$current->reference,
            $email,
            (int)$user->id,
            $grantsupdated,
            $digitalaccessupdated,
            $guestsessionsupdated,
            $legacyrecordsupdated
        );
    }

    /** @return array<int, \stdClass> */
    private function find_users_by_email(string $email): array {
        global $CFG;
        $emailcondition = $this->database->sql_equal('email', ':email', false);
        return $this->database->get_records_sql(
            'SELECT id,email
               FROM {user}
              WHERE ' . $emailcondition . '
                AND deleted = 0
                AND mnethostid = :mnethostid
           ORDER BY id ASC',
            [
                'email' => $email,
                'mnethostid' => (int)$CFG->mnet_localhost_id,
            ],
            0,
            2
        );
    }

    private function count_beneficiaries(string $table, string $purchasereference, string $email): int {
        $emailcondition = $this->database->sql_equal('beneficiaryemail', ':beneficiaryemail', false);
        return (int)$this->database->count_records_sql(
            'SELECT COUNT(1) FROM {' . $table . '}
              WHERE purchasereference = :purchasereference
                AND beneficiaryuserid IS NULL
                AND ' . $emailcondition,
            ['purchasereference' => $purchasereference, 'beneficiaryemail' => $email]
        );
    }

    private function count_guest_sessions(string $purchasereference, string $email): int {
        $emailcondition = $this->database->sql_equal('email', ':email', false);
        return (int)$this->database->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::TABLE_GUEST . '}
              WHERE purchasereference = :purchasereference
                AND userid IS NULL
                AND ' . $emailcondition,
            ['purchasereference' => $purchasereference, 'email' => $email]
        );
    }

    private function count_legacy_digital_source(\stdClass $purchase, string $email): int {
        if ((string)($purchase->legacyfamily ?? '') !== 'digital' || empty($purchase->legacyid)) {
            return 0;
        }
        $emailcondition = $this->database->sql_equal('email', ':email', false);
        return (int)$this->database->count_records_sql(
            'SELECT COUNT(1) FROM {' . self::TABLE_LEGACY_DIGITAL . '}
              WHERE id = :legacyid
                AND userid IS NULL
                AND ' . $emailcondition,
            ['legacyid' => (int)$purchase->legacyid, 'email' => $email]
        );
    }

    private function update_beneficiaries(
        string $table,
        string $purchasereference,
        string $email,
        int $userid,
        int $now
    ): int {
        $emailcondition = $this->database->sql_equal('beneficiaryemail', ':beneficiaryemail', false);
        $params = [
            'userid' => $userid,
            'now' => $now,
            'purchasereference' => $purchasereference,
            'beneficiaryemail' => $email,
        ];
        $count = $this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . $table . '}
              WHERE purchasereference = :purchasereference
                AND beneficiaryuserid IS NULL
                AND ' . $emailcondition,
            [
                'purchasereference' => $purchasereference,
                'beneficiaryemail' => $email,
            ]
        );
        if ($count > 0) {
            $this->database->execute(
                'UPDATE {' . $table . '}
                    SET beneficiaryuserid = :userid,
                        timemodified = :now
                  WHERE purchasereference = :purchasereference
                    AND beneficiaryuserid IS NULL
                    AND ' . $emailcondition,
                $params
            );
        }
        return (int)$count;
    }

    private function update_guest_sessions(string $purchasereference, string $email, int $userid, int $now): int {
        $emailcondition = $this->database->sql_equal('email', ':email', false);
        $count = $this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::TABLE_GUEST . '}
              WHERE purchasereference = :purchasereference
                AND userid IS NULL
                AND ' . $emailcondition,
            ['purchasereference' => $purchasereference, 'email' => $email]
        );
        if ($count > 0) {
            $this->database->execute(
                'UPDATE {' . self::TABLE_GUEST . '}
                    SET userid = :userid,
                        timemodified = :now
                  WHERE purchasereference = :purchasereference
                    AND userid IS NULL
                    AND ' . $emailcondition,
                [
                    'userid' => $userid,
                    'now' => $now,
                    'purchasereference' => $purchasereference,
                    'email' => $email,
                ]
            );
        }
        return (int)$count;
    }

    private function update_legacy_digital_source(\stdClass $purchase, string $email, int $userid, int $now): int {
        if ((string)($purchase->legacyfamily ?? '') !== 'digital' || empty($purchase->legacyid)) {
            return 0;
        }
        if (!$this->database->record_exists(self::TABLE_LEGACY_DIGITAL, ['id' => (int)$purchase->legacyid])) {
            return 0;
        }

        $emailcondition = $this->database->sql_equal('email', ':email', false);
        $count = $this->database->count_records_sql(
            'SELECT COUNT(1)
               FROM {' . self::TABLE_LEGACY_DIGITAL . '}
              WHERE id = :legacyid
                AND userid IS NULL
                AND ' . $emailcondition,
            ['legacyid' => (int)$purchase->legacyid, 'email' => $email]
        );
        if ($count > 0) {
            $this->database->execute(
                'UPDATE {' . self::TABLE_LEGACY_DIGITAL . '}
                    SET userid = :userid,
                        last_update = :now
                  WHERE id = :legacyid
                    AND userid IS NULL
                    AND ' . $emailcondition,
                [
                    'userid' => $userid,
                    'now' => $now,
                    'legacyid' => (int)$purchase->legacyid,
                    'email' => $email,
                ]
            );
        }
        return (int)$count;
    }

    private function result(
        string $status,
        \stdClass $purchase,
        ?int $userid = null,
        array $candidateuserids = []
    ): CommerceCustomerIdentityReconciliationResult {
        return new CommerceCustomerIdentityReconciliationResult(
            $status,
            (int)$purchase->id,
            (string)$purchase->reference,
            $this->normalise_email((string)$purchase->customeremail) ?: null,
            $userid,
            candidateuserids: $candidateuserids
        );
    }

    private function normalise_email(string $email): string {
        return \core_text::strtolower(trim($email));
    }
}
