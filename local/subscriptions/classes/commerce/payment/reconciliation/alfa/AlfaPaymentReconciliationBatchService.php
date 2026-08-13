<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\Provider;
use moodle_database;

/** Finds stale Alfa payments and safely reconciles only provider-confirmed deposits. */
final class AlfaPaymentReconciliationBatchService {
    public function __construct(
        private readonly moodle_database $database,
        private readonly AlfaPaymentReconciliationEngineInterface $engine
    ) {
    }

    public static function create(moodle_database $database): self {
        return new self($database, AlfaPaymentReconciliationService::create($database));
    }

    /**
     * @return array<string,int>
     */
    public function run(int $limit = 20, int $minage = 300, int $maxage = 172800): array {
        $limit = max(1, min(100, $limit));
        $minage = max(60, $minage);
        $maxage = max($minage, $maxage);
        $now = time();

        $result = [
            'candidates' => 0,
            'checked' => 0,
            'reconciled' => 0,
            'provider_pending' => 0,
            'blocked' => 0,
            'already_complete' => 0,
            'failed' => 0,
        ];

        foreach ($this->candidate_payment_ids($limit, $now - $minage, $now - $maxage) as $paymentid) {
            $result['candidates']++;
            try {
                $inspection = $this->engine->inspect_payment($paymentid);
                $result['checked']++;

                if ($inspection->alreadycomplete) {
                    $result['already_complete']++;
                    continue;
                }
                if (!$inspection->reconcilable) {
                    if (!$inspection->providerpaid) {
                        $result['provider_pending']++;
                    } else {
                        $result['blocked']++;
                        mtrace('[Alfa reconciliation] BLOCKED ' . $inspection->purchasereference
                            . ' blockers=' . implode(',', $inspection->blockers));
                    }
                    continue;
                }

                $after = $this->engine->reconcile_payment($paymentid);
                if ($after->alreadycomplete) {
                    $result['reconciled']++;
                    mtrace('[Alfa reconciliation] RECONCILED ' . $after->purchasereference
                        . ' payment=' . $after->paymentid
                        . ' order=' . $after->providerorderid);
                } else {
                    $result['failed']++;
                    mtrace('[Alfa reconciliation] INCOMPLETE ' . $after->purchasereference);
                }
            } catch (\Throwable $exception) {
                $result['failed']++;
                mtrace('[Alfa reconciliation] ERROR payment=' . $paymentid . ' ' . $exception->getMessage());
            }
        }

        return $result;
    }

    /** @return int[] */
    private function candidate_payment_ids(int $limit, int $before, int $after): array {
        $sql = 'SELECT pay.id
                  FROM {' . CommercePersistenceSchema::TABLE_PAYMENT . '} pay
                  JOIN {' . CommercePersistenceSchema::TABLE_PURCHASE . '} pur ON pur.id = pay.purchaseid
                 WHERE pay.provider = :provider
                   AND pay.status IN (:redirected, :pending)
                   AND pur.status = :purchasestatus
                   AND pay.timecreated <= :before
                   AND pay.timecreated >= :after
                   AND (pay.providerorderid IS NOT NULL OR pay.providerreference IS NOT NULL)
              ORDER BY pay.timecreated ASC, pay.id ASC';

        $records = $this->database->get_records_sql($sql, [
            'provider' => Provider::ALFA,
            'redirected' => CommercePaymentAttemptStatus::REDIRECTED,
            'pending' => CommercePaymentAttemptStatus::PENDING,
            'purchasestatus' => 'payment_pending',
            'before' => $before,
            'after' => $after,
        ], 0, $limit);

        return array_map('intval', array_keys($records));
    }
}
