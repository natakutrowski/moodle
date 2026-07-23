<?php

namespace local_subscriptions\crm\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Runs CRM Commerce shadow mode for customers found in historical tables.
 *
 * This auditor is strictly read-only.
 */
final class CrmCommerceShadowAuditor {

    public function __construct(
        private readonly ?CrmCommerceShadowService $shadowservice = null
    ) {
    }

    public function audit(
        int $limit = 0
    ): CrmCommerceShadowAuditReport {
        global $DB;

        $report = new CrmCommerceShadowAuditReport();

        $userids = $this->load_customer_user_ids(
            $limit
        );

        $shadowservice = $this->shadowservice
            ?? new CrmCommerceShadowService();

        foreach ($userids as $userid) {
            $user = $DB->get_record(
                'user',
                [
                    'id' => $userid,
                ],
                'id,email'
            );

            $email = $user
                ? (string)$user->email
                : null;

            try {
                $result = $shadowservice->execute(
                    $userid,
                    $email
                );

                $report->add_result(
                    $result
                );
            } catch (\Throwable $exception) {
                debugging(
                    sprintf(
                        '[Commerce shadow audit] User %d failed: %s',
                        $userid,
                        $exception->getMessage()
                    ),
                    DEBUG_DEVELOPER
                );

                $report->add_result(
                    new CrmCommerceShadowResult(
                        new \local_subscriptions\crm\commerce\CrmCommerceCustomerSnapshot(
                            $userid,
                            [],
                            0,
                            0,
                            [],
                            [],
                            [],
                            null,
                            null,
                            \local_subscriptions\crm\commerce\CrmCommerceSnapshotSource::LEGACY_FALLBACK
                        ),
                        null,
                        true,
                        get_class($exception)
                            . ': '
                            . $exception->getMessage()
                    )
                );
            }
        }

        return $report;
    }

    /**
     * @return int[]
     */
    private function load_customer_user_ids(
        int $limit
    ): array {
        global $DB;

        $sql = "
            SELECT DISTINCT customer.userid
              FROM (
                    SELECT userid
                      FROM {user_subscription}
                     WHERE userid > 0

                    UNION

                    SELECT userid
                      FROM {subscription_digital_payment_request}
                     WHERE userid > 0
                   ) customer
          ORDER BY customer.userid ASC
        ";

        $records = $DB->get_records_sql(
            $sql,
            [],
            0,
            $limit > 0 ? $limit : 0
        );

        return array_map(
            'intval',
            array_keys($records)
        );
    }
}