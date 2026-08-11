<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use moodle_database;

/**
 * Read-only certification of customer identity consistency across Native Commerce.
 *
 * The certifier never attempts to repair data. It is designed to be run before and
 * after the production reconciliation batch so both reports can be archived.
 */
final class CommerceCustomerIdentityCertificationService {
    private const TABLE_GRANT = 'local_subs_commerce_grant';
    private const TABLE_DIGITAL_ACCESS = 'local_subs_commerce_dig_access';
    private const TABLE_GUEST = 'local_subs_commerce_guest';
    private const TABLE_LEGACY_DIGITAL = 'subscription_digital_payment_request';

    public function __construct(private readonly moodle_database $database) {}

    public function certify(?string $email = null, int $samplelimit = 10): CommerceCustomerIdentityCertificationReport {
        global $CFG;

        $samplelimit = max(1, min(100, $samplelimit));
        $emailfilter = $email !== null ? $this->normalise_email($email) : '';
        $report = new CommerceCustomerIdentityCertificationReport();

        $purchases = $this->load_purchases($emailfilter);
        $purchasebyreference = [];
        foreach ($purchases as $purchase) {
            $purchasebyreference[(string)$purchase->reference] = $purchase;
        }

        $grants = $this->group_by_purchase_reference($this->database->get_records(
            self::TABLE_GRANT,
            null,
            'id ASC',
            'id,purchasereference,beneficiaryuserid,beneficiaryemail'
        ));
        $accesses = $this->group_by_purchase_reference($this->database->get_records(
            self::TABLE_DIGITAL_ACCESS,
            null,
            'id ASC',
            'id,purchasereference,beneficiaryuserid,beneficiaryemail'
        ));
        $guests = $this->group_by_purchase_reference($this->database->get_records(
            self::TABLE_GUEST,
            null,
            'id ASC',
            'id,purchasereference,userid,email'
        ));

        $usercache = [];
        $emailusercache = [];
        $metrics = [
            'purchases_scanned' => count($purchases),
            'purchases_linked' => 0,
            'purchases_unresolved' => 0,
            'unresolved_matchable' => 0,
            'unresolved_without_account' => 0,
            'unresolved_ambiguous' => 0,
            'linked_user_missing' => 0,
            'linked_email_changed' => 0,
            'partial_children' => 0,
            'conflicting_children' => 0,
            'legacy_partial' => 0,
            'reverse_partial' => 0,
            'orphan_grants' => 0,
            'orphan_digital_accesses' => 0,
            'orphan_guest_sessions' => 0,
        ];
        $samples = [];

        foreach ($purchases as $purchase) {
            $reference = (string)$purchase->reference;
            $purchaseemail = $this->normalise_email((string)$purchase->customeremail);
            $userid = empty($purchase->userid) ? null : (int)$purchase->userid;

            if ($userid === null) {
                $metrics['purchases_unresolved']++;
                if ($purchaseemail !== '') {
                    $matches = $this->find_users_by_email($purchaseemail, (int)$CFG->mnet_localhost_id, $emailusercache);
                    if (count($matches) === 1) {
                        $metrics['unresolved_matchable']++;
                        $this->add_sample($samples, 'unresolved_matchable', $reference . ' <' . $purchaseemail . '>', $samplelimit);
                    } else if ($matches === []) {
                        $metrics['unresolved_without_account']++;
                    } else {
                        $metrics['unresolved_ambiguous']++;
                        $this->add_sample($samples, 'unresolved_ambiguous', $reference . ' <' . $purchaseemail . '>', $samplelimit);
                    }
                }

                $metrics['reverse_partial'] += $this->count_reverse_partial(
                    $reference,
                    $purchaseemail,
                    $grants,
                    $accesses,
                    $guests,
                    $samples,
                    $samplelimit
                );
                continue;
            }

            $metrics['purchases_linked']++;
            $user = $this->get_user($userid, $usercache);
            if ($user === null) {
                $metrics['linked_user_missing']++;
                $this->add_sample($samples, 'linked_user_missing', $reference . ' -> user #' . $userid, $samplelimit);
            } else if ($purchaseemail !== '' && $purchaseemail !== $this->normalise_email((string)$user->email)) {
                $metrics['linked_email_changed']++;
                $this->add_sample(
                    $samples,
                    'linked_email_changed',
                    $reference . ' <' . $purchaseemail . '> -> user #' . $userid . ' <' . $this->normalise_email((string)$user->email) . '>',
                    $samplelimit
                );
            }

            foreach ($grants[$reference] ?? [] as $grant) {
                $this->inspect_child_identity(
                    $grant,
                    'beneficiaryuserid',
                    'beneficiaryemail',
                    $userid,
                    $purchaseemail,
                    $reference,
                    'grant',
                    $metrics,
                    $samples,
                    $samplelimit
                );
            }
            foreach ($accesses[$reference] ?? [] as $access) {
                $this->inspect_child_identity(
                    $access,
                    'beneficiaryuserid',
                    'beneficiaryemail',
                    $userid,
                    $purchaseemail,
                    $reference,
                    'digital access',
                    $metrics,
                    $samples,
                    $samplelimit
                );
            }
            foreach ($guests[$reference] ?? [] as $guest) {
                $this->inspect_child_identity(
                    $guest,
                    'userid',
                    'email',
                    $userid,
                    $purchaseemail,
                    $reference,
                    'guest session',
                    $metrics,
                    $samples,
                    $samplelimit
                );
            }

            if ((string)($purchase->legacyfamily ?? '') === 'digital' && !empty($purchase->legacyid)) {
                $legacy = $this->database->get_record(
                    self::TABLE_LEGACY_DIGITAL,
                    ['id' => (int)$purchase->legacyid],
                    'id,userid,email',
                    IGNORE_MISSING
                );
                if ($legacy !== false && $purchaseemail !== ''
                        && $purchaseemail === $this->normalise_email((string)$legacy->email)) {
                    $legacyuserid = empty($legacy->userid) ? null : (int)$legacy->userid;
                    if ($legacyuserid === null || $legacyuserid !== $userid) {
                        $metrics['legacy_partial']++;
                        $this->add_sample(
                            $samples,
                            'legacy_partial',
                            $reference . ' -> legacy digital #' . (int)$legacy->id,
                            $samplelimit
                        );
                    }
                }
            }
        }

        if ($emailfilter === '') {
            $metrics['orphan_grants'] = $this->count_orphans($grants, $purchasebyreference, $samples, 'orphan_grants', $samplelimit);
            $metrics['orphan_digital_accesses'] = $this->count_orphans($accesses, $purchasebyreference, $samples, 'orphan_digital_accesses', $samplelimit);
            $metrics['orphan_guest_sessions'] = $this->count_orphans($guests, $purchasebyreference, $samples, 'orphan_guest_sessions', $samplelimit);
        }

        foreach ($metrics as $key => $value) {
            $report->set_metric($key, $value);
        }

        $this->build_findings($report, $metrics, $samples, $emailfilter);
        return $report;
    }

    /** @return \stdClass[] */
    private function load_purchases(string $emailfilter): array {
        if ($emailfilter === '') {
            return $this->database->get_records(
                CommercePersistenceSchema::TABLE_PURCHASE,
                null,
                'id ASC',
                'id,reference,userid,customeremail,legacyfamily,legacyid'
            );
        }

        $emailcondition = $this->database->sql_equal('customeremail', ':customeremail', false);
        return $this->database->get_records_sql(
            'SELECT id,reference,userid,customeremail,legacyfamily,legacyid
               FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '}
              WHERE ' . $emailcondition . '
           ORDER BY id ASC',
            ['customeremail' => $emailfilter]
        );
    }

    /** @param array<int,\stdClass> $records @return array<string,array<int,\stdClass>> */
    private function group_by_purchase_reference(array $records): array {
        $grouped = [];
        foreach ($records as $record) {
            $reference = trim((string)($record->purchasereference ?? ''));
            if ($reference === '') {
                continue;
            }
            $grouped[$reference][] = $record;
        }
        return $grouped;
    }

    /** @param array<int,?\stdClass> $cache */
    private function get_user(int $userid, array &$cache): ?\stdClass {
        if (!array_key_exists($userid, $cache)) {
            $record = $this->database->get_record('user', ['id' => $userid, 'deleted' => 0], 'id,email', IGNORE_MISSING);
            $cache[$userid] = $record === false ? null : $record;
        }
        return $cache[$userid];
    }

    /** @param array<string,array<int,\stdClass>> $cache @return array<int,\stdClass> */
    private function find_users_by_email(string $email, int $mnethostid, array &$cache): array {
        if (!array_key_exists($email, $cache)) {
            $emailcondition = $this->database->sql_equal('email', ':email', false);
            $cache[$email] = $this->database->get_records_sql(
                'SELECT id,email
                   FROM {user}
                  WHERE ' . $emailcondition . '
                    AND deleted = 0
                    AND mnethostid = :mnethostid
               ORDER BY id ASC',
                ['email' => $email, 'mnethostid' => $mnethostid],
                0,
                2
            );
        }
        return $cache[$email];
    }

    /**
     * Only rows representing the same person as the buyer are checked. A gift or
     * another beneficiary with a different email is deliberately left alone.
     *
     * @param array<string,int> $metrics
     * @param array<string,string[]> $samples
     */
    private function inspect_child_identity(
        \stdClass $record,
        string $useridfield,
        string $emailfield,
        int $purchaseuserid,
        string $purchaseemail,
        string $reference,
        string $kind,
        array &$metrics,
        array &$samples,
        int $samplelimit
    ): void {
        $childemail = $this->normalise_email((string)($record->{$emailfield} ?? ''));
        if ($purchaseemail === '' || $childemail !== $purchaseemail) {
            return;
        }

        $childuserid = empty($record->{$useridfield}) ? null : (int)$record->{$useridfield};
        if ($childuserid === null) {
            $metrics['partial_children']++;
            $this->add_sample($samples, 'partial_children', $reference . ' -> ' . $kind . ' #' . (int)$record->id, $samplelimit);
            return;
        }
        if ($childuserid !== $purchaseuserid) {
            $metrics['conflicting_children']++;
            $this->add_sample(
                $samples,
                'conflicting_children',
                $reference . ' -> ' . $kind . ' #' . (int)$record->id . ' user #' . $childuserid . ' != #' . $purchaseuserid,
                $samplelimit
            );
        }
    }

    /**
     * Count children already linked to a user while the same-email purchase is not.
     *
     * @param array<string,array<int,\stdClass>> $grants
     * @param array<string,array<int,\stdClass>> $accesses
     * @param array<string,array<int,\stdClass>> $guests
     * @param array<string,string[]> $samples
     */
    private function count_reverse_partial(
        string $reference,
        string $purchaseemail,
        array $grants,
        array $accesses,
        array $guests,
        array &$samples,
        int $samplelimit
    ): int {
        if ($purchaseemail === '') {
            return 0;
        }

        $count = 0;
        foreach ([
            ['records' => $grants[$reference] ?? [], 'userid' => 'beneficiaryuserid', 'email' => 'beneficiaryemail', 'kind' => 'grant'],
            ['records' => $accesses[$reference] ?? [], 'userid' => 'beneficiaryuserid', 'email' => 'beneficiaryemail', 'kind' => 'digital access'],
            ['records' => $guests[$reference] ?? [], 'userid' => 'userid', 'email' => 'email', 'kind' => 'guest session'],
        ] as $group) {
            foreach ($group['records'] as $record) {
                if ($this->normalise_email((string)($record->{$group['email']} ?? '')) !== $purchaseemail) {
                    continue;
                }
                if (!empty($record->{$group['userid']})) {
                    $count++;
                    $this->add_sample(
                        $samples,
                        'reverse_partial',
                        $reference . ' <- ' . $group['kind'] . ' #' . (int)$record->id . ' user #' . (int)$record->{$group['userid']},
                        $samplelimit
                    );
                }
            }
        }
        return $count;
    }

    /** @param array<string,array<int,\stdClass>> $grouped @param array<string,\stdClass> $purchases @param array<string,string[]> $samples */
    private function count_orphans(
        array $grouped,
        array $purchases,
        array &$samples,
        string $samplekey,
        int $samplelimit
    ): int {
        $count = 0;
        foreach ($grouped as $reference => $records) {
            if (isset($purchases[$reference])) {
                continue;
            }
            $count += count($records);
            foreach ($records as $record) {
                $this->add_sample($samples, $samplekey, $reference . ' -> row #' . (int)$record->id, $samplelimit);
            }
        }
        return $count;
    }

    /** @param array<string,int> $metrics @param array<string,string[]> $samples */
    private function build_findings(
        CommerceCustomerIdentityCertificationReport $report,
        array $metrics,
        array $samples,
        string $emailfilter
    ): void {
        $scope = $emailfilter === '' ? 'all Native purchases' : 'buyer email ' . $emailfilter;
        $report->add(new CommerceCustomerIdentityCertificationFinding(
            'identity.scope',
            CommerceCustomerIdentityCertificationFinding::OK,
            'Certification scope',
            $metrics['purchases_scanned'] . ' purchase(s) scanned — ' . $scope . '.'
        ));

        $this->add_count_finding(
            $report,
            'identity.unresolved_matchable',
            $metrics['unresolved_matchable'],
            CommerceCustomerIdentityCertificationFinding::WARNING,
            'Unresolved purchases with one exact Moodle match',
            'These purchases are safe candidates for K1 reconciliation.',
            $samples['unresolved_matchable'] ?? []
        );
        $this->add_count_finding(
            $report,
            'identity.unresolved_ambiguous',
            $metrics['unresolved_ambiguous'],
            CommerceCustomerIdentityCertificationFinding::ERROR,
            'Ambiguous unresolved buyer identities',
            'No automatic reconciliation is safe while more than one Moodle account owns the email.',
            $samples['unresolved_ambiguous'] ?? []
        );
        $report->add(new CommerceCustomerIdentityCertificationFinding(
            'identity.unresolved_without_account',
            CommerceCustomerIdentityCertificationFinding::OK,
            'Unresolved purchases without Moodle account',
            $metrics['unresolved_without_account'] . ' purchase(s). This is a valid Guest/legacy state and is not blocking.'
        ));
        $this->add_count_finding(
            $report,
            'identity.linked_user_missing',
            $metrics['linked_user_missing'],
            CommerceCustomerIdentityCertificationFinding::ERROR,
            'Purchases linked to a missing/deleted Moodle user',
            'A persisted userid must resolve to an active local identity.',
            $samples['linked_user_missing'] ?? []
        );
        $this->add_count_finding(
            $report,
            'identity.linked_email_changed',
            $metrics['linked_email_changed'],
            CommerceCustomerIdentityCertificationFinding::WARNING,
            'Historical buyer email differs from current account email',
            'This can be legitimate after an email change; the historical Commerce email is intentionally preserved.',
            $samples['linked_email_changed'] ?? []
        );
        $this->add_count_finding(
            $report,
            'identity.partial_children',
            $metrics['partial_children'],
            CommerceCustomerIdentityCertificationFinding::ERROR,
            'Partially reconciled Native resources',
            'Purchase userid is set but same-email grant/access/Guest rows are still missing their userid.',
            $samples['partial_children'] ?? []
        );
        $this->add_count_finding(
            $report,
            'identity.conflicting_children',
            $metrics['conflicting_children'],
            CommerceCustomerIdentityCertificationFinding::ERROR,
            'Conflicting Native resource identities',
            'Same-email child rows point to a different userid than their purchase.',
            $samples['conflicting_children'] ?? []
        );
        $this->add_count_finding(
            $report,
            'identity.legacy_partial',
            $metrics['legacy_partial'],
            CommerceCustomerIdentityCertificationFinding::ERROR,
            'Legacy digital source not aligned with reconciled Native purchase',
            'During the transition, same-email Legacy digital payment requests must carry the same userid as Native.',
            $samples['legacy_partial'] ?? []
        );
        $this->add_count_finding(
            $report,
            'identity.reverse_partial',
            $metrics['reverse_partial'],
            CommerceCustomerIdentityCertificationFinding::WARNING,
            'Resources linked while parent purchase remains unresolved',
            'These historical partial states should be reviewed before the Legacy layer is retired.',
            $samples['reverse_partial'] ?? []
        );

        if ($emailfilter === '') {
            foreach ([
                ['key' => 'identity.orphan_grants', 'metric' => 'orphan_grants', 'label' => 'Orphan Commerce grants', 'samples' => 'orphan_grants'],
                ['key' => 'identity.orphan_accesses', 'metric' => 'orphan_digital_accesses', 'label' => 'Orphan digital accesses', 'samples' => 'orphan_digital_accesses'],
                ['key' => 'identity.orphan_guests', 'metric' => 'orphan_guest_sessions', 'label' => 'Orphan Guest sessions', 'samples' => 'orphan_guest_sessions'],
            ] as $definition) {
                $this->add_count_finding(
                    $report,
                    $definition['key'],
                    $metrics[$definition['metric']],
                    CommerceCustomerIdentityCertificationFinding::ERROR,
                    $definition['label'],
                    'purchasereference does not resolve to a Native purchase.',
                    $samples[$definition['samples']] ?? []
                );
            }
        }
    }

    /** @param string[] $samples */
    private function add_count_finding(
        CommerceCustomerIdentityCertificationReport $report,
        string $key,
        int $count,
        string $failureseverity,
        string $label,
        string $explanation,
        array $samples
    ): void {
        $severity = $count > 0 ? $failureseverity : CommerceCustomerIdentityCertificationFinding::OK;
        $detail = $count . ' row(s). ' . $explanation;
        if ($samples !== []) {
            $detail .= ' Samples: ' . implode('; ', $samples) . '.';
        }
        $report->add(new CommerceCustomerIdentityCertificationFinding($key, $severity, $label, $detail));
    }

    /** @param array<string,string[]> $samples */
    private function add_sample(array &$samples, string $key, string $value, int $limit): void {
        $samples[$key] ??= [];
        if (count($samples[$key]) < $limit) {
            $samples[$key][] = $value;
        }
    }

    private function normalise_email(string $email): string {
        return \core_text::strtolower(trim($email));
    }
}
