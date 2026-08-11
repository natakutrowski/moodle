<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\lifecycle;

defined('MOODLE_INTERNAL') || die();

/**
 * Reconciles Native Grant lifecycle status from completed fulfillment states.
 */
final class CommerceGrantLifecycleReconciler {
    private const GRANT_TABLE = 'local_subs_commerce_grant';
    private const STATE_TABLE = 'local_subs_commerce_ful_state';

    public function __construct(private readonly \moodle_database $db) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function inspect(int $limit = 0): array {
        $limit = max(0, $limit);

        $sql = "SELECT g.id,
                       g.grantreference,
                       g.purchasereference,
                       g.type,
                       g.resourcekey,
                       g.status AS grantstatus,
                       s.status AS fulfillmentstatus,
                       s.handlerclass,
                       s.timecompleted
                  FROM {" . self::GRANT_TABLE . "} g
                  JOIN {" . self::STATE_TABLE . "} s
                    ON s.grantreference = g.grantreference
                 WHERE g.status = :grantstatus
                   AND s.status = :fulfillmentstatus
              ORDER BY g.id ASC";

        $records = $this->db->get_records_sql(
            $sql,
            [
                'grantstatus' => 'planned',
                'fulfillmentstatus' => 'completed',
            ],
            0,
            $limit
        );

        return array_map(static fn(\stdClass $record): array => [
            'id' => (int) $record->id,
            'grantreference' => (string) $record->grantreference,
            'purchasereference' => (string) $record->purchasereference,
            'type' => (string) $record->type,
            'resourcekey' => (string) $record->resourcekey,
            'grantstatus' => (string) $record->grantstatus,
            'fulfillmentstatus' => (string) $record->fulfillmentstatus,
            'handlerclass' => (string) $record->handlerclass,
            'timecompleted' => $record->timecompleted === null ? null : (int) $record->timecompleted,
        ], array_values($records));
    }

    /**
     * @return array{candidates:int,activated:int,skipped:int,activatedids:int[]}
     */
    public function execute(int $limit = 0, ?int $now = null): array {
        $candidates = $this->inspect($limit);
        $activatedids = [];
        $skipped = 0;
        $now ??= time();

        $transaction = $this->db->start_delegated_transaction();

        foreach ($candidates as $candidate) {
            $updated = $this->db->set_field_select(
                self::GRANT_TABLE,
                'status',
                'active',
                'id = :id AND status = :status',
                [
                    'id' => $candidate['id'],
                    'status' => 'planned',
                ]
            );

            if (!$updated) {
                $skipped++;
                continue;
            }

            $this->db->set_field(self::GRANT_TABLE, 'timemodified', $now, ['id' => $candidate['id']]);
            $activatedids[] = $candidate['id'];
        }

        $transaction->allow_commit();

        return [
            'candidates' => count($candidates),
            'activated' => count($activatedids),
            'skipped' => $skipped,
            'activatedids' => $activatedids,
        ];
    }
}
