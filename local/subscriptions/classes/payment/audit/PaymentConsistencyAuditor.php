<?php

namespace local_subscriptions\payment\audit;

defined('MOODLE_INTERNAL') || die();

/**
 * Vérifie la cohérence des paiements et de leurs objets métier.
 *
 * Ce service est strictement en lecture seule.
 */
final class PaymentConsistencyAuditor {

    /**
     * Durée après laquelle un paiement pending est considéré ancien.
     */
    private const STALE_PENDING_SECONDS =
        48 * HOURSECS;

    /**
     * Lance l’audit complet.
     *
     * @return PaymentConsistencyReport
     */
    public function audit(): PaymentConsistencyReport {
        global $DB;

        $report = new PaymentConsistencyReport();

        if (
            !$DB->get_manager()->table_exists(
                new \xmldb_table(
                    'subscription_payment_request'
                )
            )
        ) {
            $report->add_issue(
                'error',
                'missing_subscription_payment_table',
                'Table subscription_payment_request absente.'
            );

            return $report;
        }

        $this->audit_subscription_payments(
            $report
        );

        if (
            $DB->get_manager()->table_exists(
                new \xmldb_table(
                    'subscription_digital_payment_request'
                )
            )
        ) {
            $this->audit_digital_payments(
                $report
            );
        }

        return $report;
    }

    /**
     * Audit des paiements d’abonnement.
     *
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_subscription_payments(
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        $table = 'subscription_payment_request';
        $columns = $DB->get_columns($table);

        $total = $DB->count_records($table);

        $report->set_counter(
            'subscription_payment_requests',
            $total
        );

        $this->count_statuses(
            $table,
            'subscription',
            $report
        );

        if (isset($columns['creation_date'])) {
            $this->audit_stale_pending(
                $table,
                'subscription',
                'creation_date',
                $report
            );
        }

        if (isset($columns['last_error'])) {
            $this->audit_success_with_error(
                $table,
                'subscription',
                $report
            );
        }

        if (isset($columns['sessionid'])) {
            $this->audit_duplicate_value(
                $table,
                'sessionid',
                'subscription',
                $report
            );
        }

        if (isset($columns['transactionid'])) {
            $this->audit_duplicate_value(
                $table,
                'transactionid',
                'subscription',
                $report
            );
        }

        if (
            isset($columns['subscriptionid'])
        ) {
            $this->audit_broken_reference(
                $table,
                'subscriptionid',
                'user_subscription',
                'id',
                'subscription',
                $report
            );

            $this->audit_success_without_reference(
                $table,
                'subscriptionid',
                'subscription',
                $report
            );
        }

        if (
            isset($columns['reference_subscription_id'])
        ) {
            $this->audit_broken_reference(
                $table,
                'reference_subscription_id',
                'user_subscription',
                'id',
                'subscription_reference',
                $report
            );
        }
    }

    /**
     * Audit des paiements digitaux.
     *
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_digital_payments(
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        $table =
            'subscription_digital_payment_request';

        $columns = $DB->get_columns($table);

        $report->set_counter(
            'digital_payment_requests',
            $DB->count_records($table)
        );

        $this->count_statuses(
            $table,
            'digital',
            $report
        );

        if (isset($columns['creation_date'])) {
            $this->audit_stale_pending(
                $table,
                'digital',
                'creation_date',
                $report
            );
        }

        if (isset($columns['last_error'])) {
            $this->audit_success_with_error(
                $table,
                'digital',
                $report
            );
        }

        if (isset($columns['sessionid'])) {
            $this->audit_duplicate_value(
                $table,
                'sessionid',
                'digital',
                $report
            );
        }

        if (isset($columns['transactionid'])) {
            $this->audit_duplicate_value(
                $table,
                'transactionid',
                'digital',
                $report
            );
        }

        if (
            isset($columns['purchaseid']) &&
            $DB->get_manager()->table_exists(
                new \xmldb_table(
                    'subscription_digital_purchase'
                )
            )
        ) {
            $this->audit_broken_reference(
                $table,
                'purchaseid',
                'subscription_digital_purchase',
                'id',
                'digital',
                $report
            );

            $this->audit_success_without_reference(
                $table,
                'purchaseid',
                'digital',
                $report
            );
        }
    }

    /**
     * Compte les statuts présents.
     *
     * @param string $table
     * @param string $prefix
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function count_statuses(
        string $table,
        string $prefix,
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        $sql = "
            SELECT
                status,
                COUNT(1) AS total
              FROM {{$table}}
          GROUP BY status
        ";

        $records = $DB->get_records_sql($sql);

        foreach ($records as $record) {
            $status = strtolower(
                trim((string)$record->status)
            );

            if ($status === '') {
                $status = 'empty';
            }

            $report->set_counter(
                $prefix . '_status_' . $status,
                (int)$record->total
            );

            if (
                in_array(
                    $status,
                    [
                        'error',
                        'canceled',
                        'cancelled',
                    ],
                    true
                )
            ) {
                $report->add_issue(
                    'warning',
                    'legacy_or_terminal_status',
                    sprintf(
                        '%s paiement(s) %s avec le statut "%s".',
                        (int)$record->total,
                        $prefix,
                        $status
                    ),
                    [
                        'table' => $table,
                        'status' => $status,
                        'count' => (int)$record->total,
                    ]
                );
            }
        }
    }

    /**
     * Recherche les paiements pending anciens.
     *
     * @param string $table
     * @param string $prefix
     * @param string $datefield
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_stale_pending(
        string $table,
        string $prefix,
        string $datefield,
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        $threshold =
            time() - self::STALE_PENDING_SECONDS;

        $sql = "
            SELECT id, {$datefield}
              FROM {{$table}}
             WHERE status = :status
               AND {$datefield} > 0
               AND {$datefield} < :threshold
          ORDER BY {$datefield} ASC
        ";

        $records = $DB->get_records_sql(
            $sql,
            [
                'status' => 'pending',
                'threshold' => $threshold,
            ]
        );

        foreach ($records as $record) {
            $report->add_issue(
                'warning',
                'stale_pending_payment',
                sprintf(
                    'Paiement %s #%d pending depuis plus de 48 heures.',
                    $prefix,
                    (int)$record->id
                ),
                [
                    'table' => $table,
                    'id' => (int)$record->id,
                    'created_at' =>
                        (int)$record->{$datefield},
                ]
            );
        }
    }

    /**
     * Recherche les paiements réussis conservant une erreur.
     *
     * @param string $table
     * @param string $prefix
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_success_with_error(
        string $table,
        string $prefix,
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        [$insql, $params] =
            $DB->get_in_or_equal(
                [
                    'paid',
                    'completed',
                    'success',
                    'succeeded',
                ],
                SQL_PARAMS_NAMED,
                'successfulstatus'
            );

        $sql = "
            SELECT id, status, last_error
              FROM {{$table}}
             WHERE status {$insql}
               AND last_error IS NOT NULL
               AND " .
                $DB->sql_compare_text(
                    'last_error'
                ) .
                " <> :emptyerror
        ";

        $params['emptyerror'] = '';

        $records = $DB->get_records_sql(
            $sql,
            $params
        );

        foreach ($records as $record) {
            $report->add_issue(
                'warning',
                'successful_payment_with_error',
                sprintf(
                    'Paiement %s #%d réussi mais last_error est encore renseigné.',
                    $prefix,
                    (int)$record->id
                ),
                [
                    'table' => $table,
                    'id' => (int)$record->id,
                    'status' =>
                        (string)$record->status,
                ]
            );
        }
    }

    /**
     * Recherche les doublons non vides d’une colonne.
     *
     * @param string $table
     * @param string $field
     * @param string $prefix
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_duplicate_value(
        string $table,
        string $field,
        string $prefix,
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        $sql = "
            SELECT
                {$field},
                COUNT(1) AS total
              FROM {{$table}}
             WHERE {$field} IS NOT NULL
               AND {$field} <> :emptyvalue
          GROUP BY {$field}
            HAVING COUNT(1) > 1
        ";

        $records = $DB->get_records_sql(
            $sql,
            ['emptyvalue' => '']
        );

        foreach ($records as $record) {
            $value = (string)$record->{$field};

            $report->add_issue(
                'error',
                'duplicate_provider_reference',
                sprintf(
                    'La référence %s "%s" est utilisée par %d paiements %s.',
                    $field,
                    $value,
                    (int)$record->total,
                    $prefix
                ),
                [
                    'table' => $table,
                    'field' => $field,
                    'value' => $value,
                    'count' =>
                        (int)$record->total,
                ]
            );
        }
    }

    /**
     * Recherche les références vers un objet inexistant.
     *
     * @param string $sourcetable
     * @param string $sourcefield
     * @param string $targettable
     * @param string $targetfield
     * @param string $prefix
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_broken_reference(
        string $sourcetable,
        string $sourcefield,
        string $targettable,
        string $targetfield,
        string $prefix,
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        $sql = "
            SELECT source.id,
                   source.{$sourcefield}
              FROM {{$sourcetable}} source
         LEFT JOIN {{$targettable}} target
                ON target.{$targetfield} =
                   source.{$sourcefield}
             WHERE source.{$sourcefield} IS NOT NULL
               AND source.{$sourcefield} > 0
               AND target.{$targetfield} IS NULL
        ";

        $records = $DB->get_records_sql($sql);

        foreach ($records as $record) {
            $report->add_issue(
                'error',
                'broken_payment_reference',
                sprintf(
                    'Paiement %s #%d : référence %s=%d introuvable.',
                    $prefix,
                    (int)$record->id,
                    $sourcefield,
                    (int)$record->{$sourcefield}
                ),
                [
                    'table' => $sourcetable,
                    'id' => (int)$record->id,
                    'field' => $sourcefield,
                    'target_table' => $targettable,
                    'target_id' =>
                        (int)$record->{$sourcefield},
                ]
            );
        }
    }

    /**
     * Recherche les paiements réussis sans objet métier lié.
     *
     * @param string $table
     * @param string $referencefield
     * @param string $prefix
     * @param PaymentConsistencyReport $report
     * @return void
     */
    private function audit_success_without_reference(
        string $table,
        string $referencefield,
        string $prefix,
        PaymentConsistencyReport $report
    ): void {
        global $DB;

        [$insql, $params] =
            $DB->get_in_or_equal(
                [
                    'paid',
                    'completed',
                    'success',
                    'succeeded',
                ],
                SQL_PARAMS_NAMED,
                'successfulstatus'
            );

        $sql = "
            SELECT id, status
              FROM {{$table}}
             WHERE status {$insql}
               AND (
                    {$referencefield} IS NULL
                    OR {$referencefield} = 0
               )
        ";

        $records = $DB->get_records_sql(
            $sql,
            $params
        );

        foreach ($records as $record) {
            $report->add_issue(
                'error',
                'successful_payment_without_business_object',
                sprintf(
                    'Paiement %s #%d réussi sans objet métier lié.',
                    $prefix,
                    (int)$record->id
                ),
                [
                    'table' => $table,
                    'id' => (int)$record->id,
                    'status' =>
                        (string)$record->status,
                    'reference_field' =>
                        $referencefield,
                ]
            );
        }
    }
}