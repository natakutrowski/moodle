<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

/**
 * Explicit, targeted repair of one divergent Native purchase projection.
 *
 * This service only repairs the main purchase row. It deliberately refuses
 * missing purchases, duplicate links, identity changes and child-aggregate
 * differences. The caller must perform the operation inside a transaction and
 * verify the projection again before committing.
 *
 * @package local_subscriptions
 */
final class CommerceTargetedNativeRepairService {
    /** Fields that may be synchronised from the current Legacy projection. */
    private const MUTABLE_FIELDS = [
        'userid',
        'customeremail',
        'status',
        'currency',
        'subtotalminor',
        'discountminor',
        'totalminor',
        'customerjson',
        'snapshotjson',
        'metadatajson',
        'snapshotversion',
        'timemodified',
    ];

    /** Identity fields that must never be changed by this repair tool. */
    private const IDENTITY_FIELDS = [
        'purchaseuuid',
        'reference',
        'type',
        'legacyfamily',
        'legacyid',
        'timecreated',
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceLegacyPurchaseMigrator $migrator
    ) {
    }

    /**
     * Analyse one Legacy/Native pair without writing.
     *
     * @return array<string, mixed>
     */
    public function inspect(string $family, int $legacyid): array {
        $summary = $this->migrator->migrate_batch($family, [$legacyid], false);
        $results = $summary->get_results();
        if (count($results) !== 1) {
            throw new \RuntimeException('Expected exactly one migration result.');
        }

        $result = reset($results);
        $status = $result->get_status();
        $base = [
            'family' => $family,
            'legacyid' => $legacyid,
            'status' => $status,
            'repairable' => false,
            'changes' => [],
            'issues' => [],
        ];

        foreach ($result->get_issues() as $issue) {
            $context = method_exists($issue, 'get_context') ? $issue->get_context() : [];
            $base['issues'][] = [
                'code' => $issue->get_code(),
                'message' => $issue->get_message(),
                'severity' => $issue->get_severity(),
                'context' => $context,
            ];
        }

        if ($result->is_successful()) {
            $base['message'] = 'Native projection is already healthy; no repair is needed.';
            return $base;
        }

        $expected = null;
        $actual = null;
        foreach ($base['issues'] as $issue) {
            if (($issue['code'] ?? '') !== 'native_snapshot_mismatch') {
                continue;
            }
            $purchase = $issue['context']['differences']['purchase'] ?? null;
            if (is_array($purchase) && isset($purchase['expected'], $purchase['actual']) &&
                    is_array($purchase['expected']) && is_array($purchase['actual'])) {
                $expected = $purchase['expected'];
                $actual = $purchase['actual'];
                break;
            }
        }

        if ($expected === null || $actual === null) {
            $base['message'] = 'The anomaly is not a supported main-purchase snapshot mismatch.';
            return $base;
        }

        foreach (self::IDENTITY_FIELDS as $field) {
            if (($expected[$field] ?? null) !== ($actual[$field] ?? null)) {
                $base['message'] = 'Repair refused because immutable purchase identity differs: ' . $field;
                return $base;
            }
        }

        $changes = [];
        foreach (self::MUTABLE_FIELDS as $field) {
            if (($expected[$field] ?? null) !== ($actual[$field] ?? null)) {
                $changes[$field] = [
                    'before' => $actual[$field] ?? null,
                    'after' => $expected[$field] ?? null,
                ];
            }
        }

        // Refuse hidden/unsupported differences instead of partially repairing.
        $known = array_merge(self::IDENTITY_FIELDS, self::MUTABLE_FIELDS);
        foreach (array_unique(array_merge(array_keys($expected), array_keys($actual))) as $field) {
            if (!in_array($field, $known, true) && (($expected[$field] ?? null) !== ($actual[$field] ?? null))) {
                $base['message'] = 'Repair refused because unsupported field differs: ' . $field;
                return $base;
            }
        }

        if ($changes === []) {
            $base['message'] = 'No supported mutable difference was found.';
            return $base;
        }

        $base['repairable'] = true;
        $base['changes'] = $changes;
        $base['expected'] = $expected;
        $base['actual'] = $actual;
        $base['message'] = 'Target is eligible for a controlled main-purchase repair.';
        return $base;
    }

    /**
     * Apply one inspected repair. Caller controls the transaction.
     *
     * @param array<string, mixed> $inspection
     * @return int Native purchase database id.
     */
    public function apply(array $inspection): int {
        if (empty($inspection['repairable']) || !isset($inspection['expected'])) {
            throw new \RuntimeException('The requested repair is not eligible.');
        }

        $family = (string)$inspection['family'];
        $legacyid = (int)$inspection['legacyid'];
        $records = $this->db->get_records('local_subscriptions_commerce_purchase', [
            'legacyfamily' => $family,
            'legacyid' => $legacyid,
        ]);
        if (count($records) !== 1) {
            throw new \RuntimeException('Expected exactly one Native purchase for the Legacy link.');
        }

        $record = reset($records);
        $expected = $inspection['expected'];
        foreach (self::MUTABLE_FIELDS as $field) {
            if (!array_key_exists($field, $expected)) {
                continue;
            }
            $value = $expected[$field];
            if (in_array($field, ['customerjson', 'snapshotjson', 'metadatajson'], true)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            }
            $record->{$field} = $value;
        }
        $this->db->update_record('local_subscriptions_commerce_purchase', $record);
        return (int)$record->id;
    }
}
