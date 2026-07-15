<?php

namespace local_subscriptions\crm\inbox\ai\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxAiQueueRepository {

    public function pending_thread_ids(
        int $limit,
        int $minimumage = 300
    ): array {
        global $DB;

        $threshold = time() - $minimumage;

        $sql = "
            SELECT t.id
              FROM {local_subscriptions_inbox_thread} t
             WHERE t.locallydeleted = 0
               AND t.status IN (
                    :openstatus,
                    :pendingstatus
               )
               AND t.lastmessageat <= :threshold
               AND EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_message} m
                     WHERE m.threadid = t.id
                       AND m.direction = :direction
               )
               AND NOT EXISTS (
                    SELECT 1
                      FROM {local_subscriptions_inbox_ai_result} ar
                     WHERE ar.threadid = t.id
                       AND ar.capability = :capability
                       AND ar.status IN (
                            :successstatus,
                            :partialstatus
                       )
                       AND ar.timecreated >= t.lastmessageat
               )
          ORDER BY t.lastmessageat ASC, t.id ASC
        ";

        return array_map(
            'intval',
            array_keys(
                $DB->get_records_sql(
                    $sql,
                    [
                        'openstatus' => 'open',
                        'pendingstatus' => 'pending',
                        'threshold' => $threshold,
                        'direction' => 'inbound',
                        'capability' => 'summary',
                        'successstatus' => 'success',
                        'partialstatus' => 'partial',
                    ],
                    0,
                    max(1, $limit)
                )
            )
        );
    }
}