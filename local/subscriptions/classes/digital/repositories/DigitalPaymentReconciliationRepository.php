<?php

namespace local_subscriptions\digital\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;

/**
 * Persistence layer for digital payment reconciliation.
 */
final class DigitalPaymentReconciliationRepository {

    /**
     * Returns pending payment requests eligible for reconciliation.
     *
     * @return \stdClass[]
     */
    public function find_pending_candidates(
        int $mintime,
        int $maxtime,
        int $limit
    ): array {
        global $DB;

        $limit = max(
            1,
            min(
                100,
                $limit
            )
        );

        $sql = "
            SELECT *
              FROM {" .
                product_manager::TABLE_PAYMENT_REQUEST .
                "}
             WHERE status = :status
               AND sessionid IS NOT NULL
               AND sessionid <> ''
               AND creation_date <= :mintime
               AND creation_date >= :maxtime
          ORDER BY creation_date DESC, id DESC
        ";

        return
            $DB->get_records_sql(
                $sql,
                [
                    'status' => 'pending',
                    'mintime' => $mintime,
                    'maxtime' => $maxtime,
                ],
                0,
                $limit
            );
    }

    public function find_by_id(
        int $paymentrequestid,
        int $strictness = MUST_EXIST
    ): ?\stdClass {
        global $DB;

        return
            $DB->get_record(
                product_manager::TABLE_PAYMENT_REQUEST,
                [
                    'id' =>
                        $paymentrequestid,
                ],
                '*',
                $strictness
            );
    }

    /**
     * Stores the latest provider state only while the request is pending.
     */
    public function record_pending_provider_status(
        int $paymentrequestid,
        string $message,
        int $time
    ): bool {
        global $DB;

        $sql = "
            UPDATE {" .
                product_manager::TABLE_PAYMENT_REQUEST .
                "}
               SET last_error = :lasterror,
                   last_update = :lastupdate
             WHERE id = :id
               AND status = :pendingstatus
        ";

        return
            $DB->execute(
                $sql,
                [
                    'lasterror' =>
                        $this->sanitize_message(
                            $message
                        ),
                    'lastupdate' => $time,
                    'id' =>
                        $paymentrequestid,
                    'pendingstatus' =>
                        'pending',
                ]
            );
    }

    /**
     * Marks a request failed only while it is still pending.
     */
    public function mark_failed_if_pending(
        int $paymentrequestid,
        string $message,
        int $time
    ): bool {
        global $DB;

        $sql = "
            UPDATE {" .
                product_manager::TABLE_PAYMENT_REQUEST .
                "}
               SET status = :failedstatus,
                   last_error = :lasterror,
                   last_update = :lastupdate
             WHERE id = :id
               AND status = :pendingstatus
        ";

        return
            $DB->execute(
                $sql,
                [
                    'failedstatus' =>
                        'failed',
                    'lasterror' =>
                        $this->sanitize_message(
                            $message
                        ),
                    'lastupdate' => $time,
                    'id' =>
                        $paymentrequestid,
                    'pendingstatus' =>
                        'pending',
                ]
            );
    }

    /**
     * Records a reconciliation exception without changing the status.
     */
    public function record_reconciliation_error(
        int $paymentrequestid,
        string $message,
        int $time
    ): bool {
        return
            $this->record_pending_provider_status(
                $paymentrequestid,
                '[cron_reconcile] ' .
                $message,
                $time
            );
    }

    /**
     * Records one manual provider check.
     *
     * The method does not overwrite a paid/completed status.
     */
    public function record_manual_check(
        int $paymentrequestid,
        string $message,
        int $time
    ): bool {
        global $DB;

        $sql = "
            UPDATE {" .
                product_manager::TABLE_PAYMENT_REQUEST .
                "}
               SET last_attempt = :lastattempt,
                   attempts = attempts + 1,
                   last_update = :lastupdate,
                   last_error = :lasterror
             WHERE id = :id
               AND status NOT IN (:paidstatus, :completedstatus)
        ";

        return
            $DB->execute(
                $sql,
                [
                    'lastattempt' => $time,
                    'lastupdate' => $time,
                    'lasterror' =>
                        $message !== ''
                            ? $this->sanitize_message(
                                $message
                            )
                            : null,
                    'id' =>
                        $paymentrequestid,
                    'paidstatus' => 'paid',
                    'completedstatus' =>
                        'completed',
                ]
            );
    }

    private function sanitize_message(
        string $message
    ): string {
        $message = trim($message);

        if ($message === '') {
            return '';
        }

        $patterns = [
            '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
            '/Basic\s+[A-Za-z0-9+\/=]+/i',
            '/sk_(?:live|test)_[A-Za-z0-9]+/i',
            '/([?&](?:token|access_token|refresh_token|password|secret|api_key)=)[^&\s]+/i',
            '/((?:token|access_token|refresh_token|password|secret|api_key)\s*[=:]\s*)[^\s,;]+/i',
        ];

        $replacements = [
            'Bearer [redacted]',
            'Basic [redacted]',
            '[redacted Stripe key]',
            '$1[redacted]',
            '$1[redacted]',
        ];

        $sanitized =
            preg_replace(
                $patterns,
                $replacements,
                $message
            );

        $sanitized ??= $message;

        if (
            \core_text::strlen($sanitized) >
            2000
        ) {
            $sanitized =
                \core_text::substr(
                    $sanitized,
                    0,
                    2000
                ) .
                '…';
        }

        return $sanitized;
    }
}