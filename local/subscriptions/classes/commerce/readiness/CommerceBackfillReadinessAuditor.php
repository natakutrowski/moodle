<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\readiness;

use local_subscriptions\commerce\certification\CommerceCertificationReport;
use local_subscriptions\commerce\migration\CommerceLegacyMigrationFactory;
use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepositoryFactory;
use moodle_database;

/**
 * Read-only and batched backfill readiness auditor for 7.95F8C.
 */
final class CommerceBackfillReadinessAuditor {
    private const DETAIL_LIMIT = 25;
    private const DIFFERENCE_LIMIT = 50;
    private const MISSING_ID_LIMIT = 100;

    public function __construct(private readonly moodle_database $db) {
    }

    public function audit(string $family = 'all', int $batchsize = 100): CommerceCertificationReport {
        global $CFG;

        $report = new CommerceCertificationReport('7.95F8C');
        $batchsize = max(1, min(1000, $batchsize));
        $report->add_inventory('batch_size', $batchsize);
        $report->add_inventory('readonly', true);
        $report->add_inventory('detail_limit', self::DETAIL_LIMIT);

        $required = [
            'cli/commerce/migration/migrate_legacy_commerce_purchases.php',
            'cli/commerce/migration/audit_commerce_native_backfill.php',
            'classes/commerce/migration/CommerceLegacyPurchaseMigrator.php',
            'classes/commerce/migration/CommerceLegacySnapshotFactory.php',
            'classes/commerce/migration/CommerceLegacyNativeComparator.php',
        ];
        $root = $CFG->dirroot . '/local/subscriptions';
        $missing = array_values(array_filter($required, static fn(string $file): bool => !is_file($root . '/' . $file)));
        $report->add_inventory('missing_tooling_files', $missing);
        if ($missing !== []) {
            $report->add_issue('blocking', 'missing_backfill_tooling', 'Backfill tooling is incomplete.', ['files' => $missing]);
            return $report;
        }

        $registry = CommerceLegacyMigrationFactory::create_source_registry();
        $availablefamilies = $registry->get_families();
        $families = $family === 'all' ? $availablefamilies : [strtolower(trim($family))];
        foreach ($families as $candidate) {
            if (!in_array($candidate, $availablefamilies, true)) {
                $report->add_issue('blocking', 'unknown_legacy_family', 'Unknown Legacy Commerce family.', [
                    'requested' => $candidate,
                    'available' => $availablefamilies,
                ]);
                return $report;
            }
        }
        $report->add_inventory('families', $families);

        $repository = CommercePurchaseSqlRepositoryFactory::create();
        $snapshotfactory = new CommerceLegacySnapshotFactory();
        $comparator = new CommerceLegacyNativeComparator();
        $summaries = [];
        $missingdetails = [];
        $differencedetails = [];
        $invaliddetails = [];

        foreach ($families as $currentfamily) {
            $source = $registry->get($currentfamily);
            $legacytotal = $source->count();
            $nativetotal = $this->db->count_records(CommercePersistenceSchema::TABLE_PURCHASE, [
                'legacyfamily' => $currentfamily,
            ]);
            $missingnative = 0;
            $different = 0;
            $invalid = 0;
            $afterid = 0;
            $familymissingids = [];
            $familydifferences = [];
            $familyinvalid = [];

            do {
                $ids = $source->get_ids($afterid, $batchsize);
                if ($ids === []) {
                    break;
                }
                foreach ($ids as $id) {
                    try {
                        $legacy = $source->get_by_id($id);
                        if ($legacy === null) {
                            $invalid++;
                            $this->append_limited($familyinvalid, [
                                'legacy_id' => (int)$id,
                                'reason' => 'Legacy source returned no record.',
                            ], self::DETAIL_LIMIT);
                            continue;
                        }

                        $expected = $snapshotfactory->create($legacy);
                        $actual = $repository->find_by_legacy_reference($currentfamily, $id);
                        $comparison = $comparator->compare($expected, $actual);

                        if ($comparison->get_status() === 'missing_native') {
                            $missingnative++;
                            if (count($familymissingids) < self::MISSING_ID_LIMIT) {
                                $familymissingids[] = (int)$id;
                            }
                            continue;
                        }

                        if (!$comparison->is_equal()) {
                            $materialdifferences = $this->flatten_differences($comparison->get_differences());
                            if ($materialdifferences === []) {
                                continue;
                            }

                            $different++;
                            $actualpurchase = $actual?->get_purchase();
                            $actualrecord = $actualpurchase?->to_record();
                            $nativerow = $this->db->get_record(
                                CommercePersistenceSchema::TABLE_PURCHASE,
                                ['legacyfamily' => $currentfamily, 'legacyid' => (int)$id],
                                'id, purchaseuuid, reference, status, currency, totalminor',
                                IGNORE_MISSING
                            );
                            $this->append_limited($familydifferences, [
                                'legacy_family' => $currentfamily,
                                'legacy_id' => (int)$id,
                                'native_purchase_id' => $nativerow ? (int)$nativerow->id : null,
                                'purchase_uuid' => $actualrecord->purchaseuuid ?? ($nativerow->purchaseuuid ?? null),
                                'purchase_reference' => $actualrecord->reference ?? ($nativerow->reference ?? null),
                                'comparison_status' => $comparison->get_status(),
                                'differences' => $materialdifferences,
                            ], self::DETAIL_LIMIT);
                        }
                    } catch (\Throwable $exception) {
                        $invalid++;
                        $this->append_limited($familyinvalid, [
                            'legacy_id' => (int)$id,
                            'exception' => get_class($exception),
                            'message' => $exception->getMessage(),
                        ], self::DETAIL_LIMIT);
                    }
                }
                $afterid = max($ids);
            } while (true);

            $summaries[$currentfamily] = [
                'legacy_total' => $legacytotal,
                'native_total' => $nativetotal,
                'missing_native' => $missingnative,
                'different_snapshots' => $different,
                'invalid_legacy' => $invalid,
            ];
            $missingdetails[$currentfamily] = [
                'count' => $missingnative,
                'legacy_ids' => $familymissingids,
                'truncated' => $missingnative > count($familymissingids),
            ];
            $differencedetails[$currentfamily] = [
                'count' => $different,
                'records' => $familydifferences,
                'truncated' => $different > count($familydifferences),
            ];
            $invaliddetails[$currentfamily] = [
                'count' => $invalid,
                'records' => $familyinvalid,
                'truncated' => $invalid > count($familyinvalid),
            ];

            if ($invalid > 0) {
                $report->add_issue('blocking', 'invalid_legacy_records', 'Legacy records cannot be transformed into Native snapshots.', [
                    'family' => $currentfamily,
                    'count' => $invalid,
                    'records' => $familyinvalid,
                    'truncated' => $invalid > count($familyinvalid),
                ]);
            }
            if ($different > 0) {
                $report->add_issue('blocking', 'different_native_snapshots', 'Native snapshots differ from their Legacy source.', [
                    'family' => $currentfamily,
                    'count' => $different,
                    'records' => $familydifferences,
                    'truncated' => $different > count($familydifferences),
                ]);
            }
            if ($missingnative > 0) {
                $report->add_issue('important', 'backfill_required', 'Legacy purchases still require Native backfill.', [
                    'family' => $currentfamily,
                    'count' => $missingnative,
                    'legacy_ids' => $familymissingids,
                    'truncated' => $missingnative > count($familymissingids),
                ]);
            }
        }
        $report->add_inventory('family_summaries', $summaries);
        $report->add_inventory('missing_native_details', $missingdetails);
        $report->add_inventory('different_snapshot_details', $differencedetails);
        $report->add_inventory('invalid_legacy_details', $invaliddetails);

        $orphans = [
            'items' => $this->orphan_count(CommercePersistenceSchema::TABLE_ITEM),
            'payments' => $this->orphan_count(CommercePersistenceSchema::TABLE_PAYMENT),
            'fulfillments' => $this->orphan_count(CommercePersistenceSchema::TABLE_FULFILLMENT),
        ];
        $report->add_inventory('orphan_children', $orphans);
        if (array_sum($orphans) > 0) {
            $report->add_issue('blocking', 'orphan_native_children', 'Native Commerce child records reference missing purchases.', $orphans);
        }

        return $report;
    }

    /**
     * Convert section-level comparator output into readable field paths.
     *
     * @param array<string, mixed> $differences
     * @return array<int, array<string, mixed>>
     */
    private function flatten_differences(array $differences): array {
        $result = [];
        foreach ($differences as $section => $values) {
            $expected = $values['expected'] ?? null;
            $actual = $values['actual'] ?? null;
            $this->walk_difference((string)$section, $expected, $actual, $result);
            if (count($result) >= self::DIFFERENCE_LIMIT) {
                break;
            }
        }
        $result = array_values(array_filter(
            $result,
            fn(array $difference): bool => !$this->is_ignored_runtime_metadata_path((string)($difference['path'] ?? ''))
        ));

        return array_slice($result, 0, self::DIFFERENCE_LIMIT);
    }

    /**
     * Native lifecycle metadata is expected to enrich a migrated purchase after import.
     * It must not make the Legacy/Native business snapshot fail certification.
     */
    private function is_ignored_runtime_metadata_path(string $path): bool {
        if (!str_starts_with($path, 'purchase.metadatajson.')) {
            return false;
        }

        $key = substr($path, strlen('purchase.metadatajson.'));
        foreach (['fulfillment_', 'runtime_', 'migration_', 'audit_'] as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, array<string, mixed>> $result */
    private function walk_difference(string $path, mixed $expected, mixed $actual, array &$result): void {
        if (count($result) >= self::DIFFERENCE_LIMIT) {
            return;
        }
        if (is_array($expected) && is_array($actual)) {
            $keys = array_unique(array_merge(array_keys($expected), array_keys($actual)));
            foreach ($keys as $key) {
                $nextpath = $path . '.' . (string)$key;
                $this->walk_difference(
                    $nextpath,
                    array_key_exists($key, $expected) ? $expected[$key] : null,
                    array_key_exists($key, $actual) ? $actual[$key] : null,
                    $result
                );
                if (count($result) >= self::DIFFERENCE_LIMIT) {
                    return;
                }
            }
            return;
        }
        if ($expected !== $actual) {
            $result[] = [
                'path' => $path,
                'expected' => $expected,
                'actual' => $actual,
            ];
        }
    }

    /** @param array<int, mixed> $target */
    private function append_limited(array &$target, mixed $value, int $limit): void {
        if (count($target) < $limit) {
            $target[] = $value;
        }
    }

    private function orphan_count(string $table): int {
        return (int)$this->db->count_records_sql(
            'SELECT COUNT(1) FROM {' . $table . '} child
              LEFT JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} purchase ON purchase.id = child.purchaseid
             WHERE purchase.id IS NULL'
        );
    }
}
