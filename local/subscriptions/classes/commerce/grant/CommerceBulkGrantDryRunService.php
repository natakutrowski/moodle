<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\grant;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;

/**
 * Resolves and certifies a bulk-grant audience without mutating any access.
 */
final class CommerceBulkGrantDryRunService {
    public const SOURCE_LEGACY_PLAN = 'legacy_plan';
    public const SOURCE_NATIVE_PRODUCT = 'native_product';

    public const DECISION_ELIGIBLE = 'eligible';
    public const DECISION_ALREADY_OWNED = 'already_owned';
    public const DECISION_IDENTITY_REVIEW = 'identity_review';
    public const DECISION_ERROR = 'error';

    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @return array{
     *   source:array,
     *   target:array,
     *   summary:array{total:int,eligible:int,alreadyowned:int,identityreview:int,error:int},
     *   rows:array<int,array<string,mixed>>
     * }
     */
    public function simulate(
        string $sourcetype,
        int $sourceid,
        int $targetproductid,
        int $actoruserid,
        ?int $now = null
    ): array {
        $sourcetype = strtolower(trim($sourcetype));
        if (!in_array($sourcetype, [self::SOURCE_LEGACY_PLAN, self::SOURCE_NATIVE_PRODUCT], true)) {
            throw new \moodle_exception('commerce_bulk_grant_invalid_source_type', 'local_subscriptions');
        }
        if ($sourceid <= 0 || $targetproductid <= 0 || $actoruserid <= 0) {
            throw new \coding_exception('Bulk grant dry-run requires source, target and actor identifiers.');
        }

        $now ??= time();
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $target = $products->find_by_id($targetproductid);
        if ($target === null || !$target->is_active()) {
            throw new \moodle_exception('commerce_manual_grant_product_unavailable', 'local_subscriptions');
        }

        if ($sourcetype === self::SOURCE_LEGACY_PLAN) {
            $source = $this->legacy_source($sourceid);
            $candidates = $this->legacy_candidates($sourceid, $now);
        } else {
            $sourceproduct = $products->find_by_id($sourceid);
            if ($sourceproduct === null) {
                throw new \moodle_exception('commerce_bulk_grant_source_product_missing', 'local_subscriptions');
            }
            $source = [
                'type' => self::SOURCE_NATIVE_PRODUCT,
                'id' => $sourceid,
                'sku' => $sourceproduct->get_sku(),
                'name' => $sourceproduct->get_name(),
            ];
            $candidates = $this->native_candidates($sourceproduct->get_sku(), $sourceid, $now);
        }

        $ownership = new CommerceStorefrontOwnershipResolver($this->db);
        $grantservice = new CommerceManualProductGrantService($this->db);
        $rows = [];
        $summary = [
            'total' => 0,
            'eligible' => 0,
            'alreadyowned' => 0,
            'identityreview' => 0,
            'error' => 0,
        ];

        foreach ($candidates as $candidate) {
            $summary['total']++;
            $userid = (int)($candidate['userid'] ?? 0);
            $email = trim(\core_text::strtolower((string)($candidate['email'] ?? '')));
            $firstname = trim((string)($candidate['firstname'] ?? ''));
            $lastname = trim((string)($candidate['lastname'] ?? ''));
            $decision = self::DECISION_ELIGIBLE;
            $reason = '';
            $ownershipsource = 'none';
            $grantcount = 0;

            if ($userid <= 0) {
                $decision = self::DECISION_IDENTITY_REVIEW;
                $reason = 'missing_moodle_user';
            } else if (!validate_email($email)) {
                $decision = self::DECISION_IDENTITY_REVIEW;
                $reason = 'invalid_email';
            } else {
                $ownershipsource = $ownership->resolve_source($userid, $target->get_sku());
                if ($ownershipsource !== 'none') {
                    $decision = self::DECISION_ALREADY_OWNED;
                    $reason = 'target_already_owned';
                } else {
                    try {
                        $plan = $grantservice->plan(
                            $userid,
                            $targetproductid,
                            $actoruserid,
                            'bulk_dry_run',
                            $now
                        );
                        $grantcount = $plan->count();
                    } catch (\Throwable $exception) {
                        $decision = self::DECISION_ERROR;
                        $reason = $exception->getMessage();
                    }
                }
            }

            if ($decision === self::DECISION_ELIGIBLE) {
                $summary['eligible']++;
            } else if ($decision === self::DECISION_ALREADY_OWNED) {
                $summary['alreadyowned']++;
            } else if ($decision === self::DECISION_IDENTITY_REVIEW) {
                $summary['identityreview']++;
            } else {
                $summary['error']++;
            }

            $rows[] = [
                'userid' => $userid > 0 ? $userid : null,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'evidence' => array_values(array_unique($candidate['evidence'] ?? [])),
                'decision' => $decision,
                'reason' => $reason,
                'ownershipsource' => $ownershipsource,
                'grantcount' => $grantcount,
                'targetsku' => $target->get_sku(),
            ];
        }

        usort($rows, static function(array $left, array $right): int {
            $leftname = trim((string)$left['lastname'] . ' ' . (string)$left['firstname'] . ' ' . (string)$left['email']);
            $rightname = trim((string)$right['lastname'] . ' ' . (string)$right['firstname'] . ' ' . (string)$right['email']);
            return strcasecmp($leftname, $rightname);
        });

        return [
            'source' => $source,
            'target' => [
                'id' => $targetproductid,
                'sku' => $target->get_sku(),
                'name' => $target->get_name(),
                'type' => $target->get_type(),
            ],
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    /** @return array<string,mixed> */
    private function legacy_source(int $planid): array {
        $plan = $this->db->get_record(
            'subscription_plan',
            ['id' => $planid],
            'id,name,is_active,duration_key',
            MUST_EXIST
        );

        return [
            'type' => self::SOURCE_LEGACY_PLAN,
            'id' => (int)$plan->id,
            'name' => (string)$plan->name,
            'active' => !empty($plan->is_active),
            'durationkey' => (string)$plan->duration_key,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function legacy_candidates(int $planid, int $now): array {
        $sql = "SELECT us.id AS evidenceid, us.userid, us.status,
                       u.firstname, u.lastname, u.email
                  FROM {user_subscription} us
             LEFT JOIN {user} u ON u.id = us.userid AND u.deleted = 0
                 WHERE us.planid = :planid
                   AND us.status IN (:active, :completed)
                   AND us.start_date <= :now
                   AND (us.end_date = 0 OR us.end_date >= :now2)
              ORDER BY us.id ASC";
        $records = $this->db->get_records_sql($sql, [
            'planid' => $planid,
            'active' => 'active',
            'completed' => 'completed',
            'now' => $now,
            'now2' => $now,
        ]);

        $candidates = [];
        foreach ($records as $record) {
            $key = 'u:' . (int)$record->userid;
            if (!isset($candidates[$key])) {
                $candidates[$key] = [
                    'userid' => (int)$record->userid,
                    'firstname' => (string)($record->firstname ?? ''),
                    'lastname' => (string)($record->lastname ?? ''),
                    'email' => (string)($record->email ?? ''),
                    'evidence' => [],
                ];
            }
            $candidates[$key]['evidence'][] = 'legacy_subscription:#'
                . (int)$record->evidenceid . ':' . (string)$record->status;
        }

        return array_values($candidates);
    }

    /**
     * Native source membership is reconstructed from actual Native purchases,
     * active grants, CRM/manual root grants and compatible Legacy mappings.
     *
     * @return array<int,array<string,mixed>>
     */
    private function native_candidates(string $sku, int $productid, int $now): array {
        $candidates = [];

        $purchaseSql = 'SELECT p.id, p.reference, p.userid, p.customeremail, p.customerjson
                          FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p
                          JOIN {' . CommercePersistenceSchema::TABLE_ITEM . '} i ON i.purchaseid = p.id
                         WHERE i.itemreference = :sku
                           AND p.status IN (:completed, :paid, :succeeded)
                      ORDER BY p.id ASC';
        foreach ($this->db->get_records_sql($purchaseSql, [
            'sku' => $sku,
            'completed' => 'completed',
            'paid' => 'paid',
            'succeeded' => 'succeeded',
        ]) as $purchase) {
            $this->add_candidate(
                $candidates,
                !empty($purchase->userid) ? (int)$purchase->userid : null,
                (string)$purchase->customeremail,
                $this->customer_name((string)$purchase->customerjson),
                'native_purchase:' . (string)$purchase->reference
            );
        }

        // Direct entitlements plus CRM/manual bundle roots. The metadata LIKE
        // clause keeps the candidate query bounded instead of scanning the full ledger.
        $grantSql = 'SELECT id, grantreference, productsku, beneficiaryuserid, beneficiaryemail, metadatajson
                       FROM {local_subs_commerce_grant}
                      WHERE status IN (:active, :granted, :completed)
                        AND validfrom <= :now
                        AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)
                        AND (productsku = :sku OR metadatajson LIKE :rootpattern)
                   ORDER BY id ASC';
        foreach ($this->db->get_records_sql($grantSql, [
            'active' => 'active',
            'granted' => 'granted',
            'completed' => 'completed',
            'now' => $now,
            'now2' => $now,
            'sku' => $sku,
            'rootpattern' => '%"rootsku":"' . $sku . '"%',
        ]) as $grant) {
            $metadata = json_decode((string)$grant->metadatajson, true);
            $rootsku = is_array($metadata)
                ? strtoupper(trim((string)($metadata['rootsku'] ?? '')))
                : '';
            if (strtoupper(trim((string)$grant->productsku)) !== $sku && $rootsku !== $sku) {
                continue;
            }

            $this->add_candidate(
                $candidates,
                !empty($grant->beneficiaryuserid) ? (int)$grant->beneficiaryuserid : null,
                (string)$grant->beneficiaryemail,
                [],
                'native_grant:' . (string)$grant->grantreference
            );
        }

        foreach ($this->db->get_records('local_subs_commerce_prod_map', [
            'productid' => $productid,
        ]) as $mapping) {
            if ((string)$mapping->legacytable === 'subscription_plan') {
                foreach ($this->legacy_candidates((int)$mapping->legacyid, $now) as $candidate) {
                    $this->merge_candidate($candidates, $candidate, 'legacy_mapping:plan#' . (int)$mapping->legacyid);
                }
            } else if ((string)$mapping->legacytable === 'subscription_digital_product') {
                $sql = "SELECT pr.id, pr.userid, pr.email, pr.firstname, pr.lastname, pr.status
                          FROM {subscription_digital_payment_request} pr
                         WHERE pr.productid = :productid
                           AND pr.status IN (:paid, :completed, :succeeded)
                      ORDER BY pr.id ASC";
                foreach ($this->db->get_records_sql($sql, [
                    'productid' => (int)$mapping->legacyid,
                    'paid' => 'paid',
                    'completed' => 'completed',
                    'succeeded' => 'succeeded',
                ]) as $record) {
                    $this->add_candidate(
                        $candidates,
                        !empty($record->userid) ? (int)$record->userid : null,
                        (string)$record->email,
                        [
                            'firstname' => (string)$record->firstname,
                            'lastname' => (string)$record->lastname,
                        ],
                        'legacy_mapping:digital#' . (int)$record->id
                    );
                }
            }
        }

        return array_values($candidates);
    }

    /**
     * @param array<string,array<string,mixed>> $candidates
     * @param array{firstname?:string,lastname?:string} $name
     */
    private function add_candidate(
        array &$candidates,
        ?int $userid,
        string $email,
        array $name,
        string $evidence
    ): void {
        $email = trim(\core_text::strtolower($email));
        $resolved = $userid !== null && $userid > 0
            ? $this->db->get_record('user', ['id' => $userid, 'deleted' => 0], 'id,firstname,lastname,email', IGNORE_MISSING)
            : null;

        if (!$resolved && $email !== '') {
            $matches = $this->db->get_records('user', ['email' => $email, 'deleted' => 0], 'id ASC', 'id,firstname,lastname,email', 0, 2);
            if (count($matches) === 1) {
                $resolved = reset($matches);
                $userid = (int)$resolved->id;
            }
        }

        if ($resolved) {
            $email = (string)$resolved->email;
            $name = [
                'firstname' => (string)$resolved->firstname,
                'lastname' => (string)$resolved->lastname,
            ];
        }

        $key = $userid !== null && $userid > 0
            ? 'u:' . $userid
            : 'e:' . ($email !== '' ? $email : hash('sha256', $evidence));

        if (!isset($candidates[$key])) {
            $candidates[$key] = [
                'userid' => $userid,
                'firstname' => (string)($name['firstname'] ?? ''),
                'lastname' => (string)($name['lastname'] ?? ''),
                'email' => $email,
                'evidence' => [],
            ];
        }
        $candidates[$key]['evidence'][] = $evidence;
    }

    /**
     * @param array<string,array<string,mixed>> $candidates
     * @param array<string,mixed> $candidate
     */
    private function merge_candidate(array &$candidates, array $candidate, string $evidence): void {
        $this->add_candidate(
            $candidates,
            !empty($candidate['userid']) ? (int)$candidate['userid'] : null,
            (string)($candidate['email'] ?? ''),
            [
                'firstname' => (string)($candidate['firstname'] ?? ''),
                'lastname' => (string)($candidate['lastname'] ?? ''),
            ],
            $evidence
        );
    }

    /** @return array{firstname:string,lastname:string} */
    private function customer_name(string $json): array {
        $data = json_decode($json, true);
        return [
            'firstname' => is_array($data) ? trim((string)($data['firstname'] ?? '')) : '',
            'lastname' => is_array($data) ? trim((string)($data['lastname'] ?? '')) : '',
        ];
    }
}
