<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxThreadStatus;

/**
 * Reads aggregated Inbox support facts for one matched Moodle user.
 */
final class SupportInboxRepository {

    public function is_available(): bool {
        global $DB;

        $manager = $DB->get_manager();

        return
            $manager->table_exists(
                new \xmldb_table(
                    'local_subscriptions_inbox_contact'
                )
            ) &&
            $manager->table_exists(
                new \xmldb_table(
                    'local_subscriptions_inbox_thread'
                )
            );
    }

    /**
     * @return array<string,int|float|null>
     */
    public function get_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Support Inbox userid must be greater than zero.'
            );
        }

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Support Inbox timestamp must be greater than zero.'
            );
        }

        if (!$this->is_available()) {
            return $this->empty_statistics();
        }

        $sql = "
            SELECT
                COUNT(thread.id) AS conversationcount,

                COALESCE(SUM(CASE
                    WHEN thread.status = :openstatus
                    THEN 1 ELSE 0
                END), 0) AS openconversationcount,

                COALESCE(SUM(CASE
                    WHEN thread.status = :pendingstatus
                    THEN 1 ELSE 0
                END), 0) AS pendingconversationcount,

                COALESCE(SUM(CASE
                    WHEN thread.status = :resolvedstatus
                    THEN 1 ELSE 0
                END), 0) AS resolvedconversationcount,

                COALESCE(SUM(CASE
                    WHEN thread.status = :closedstatus
                    THEN 1 ELSE 0
                END), 0) AS closedconversationcount,

                COALESCE(SUM(CASE
                    WHEN thread.priority = :urgentpriority
                    THEN 1 ELSE 0
                END), 0) AS urgentconversationcount,

                COALESCE(SUM(thread.unreadcount), 0)
                    AS unreadmessagecount,

                COALESCE(SUM(CASE
                    WHEN thread.assigneduserid IS NULL
                     AND thread.assignedteamid IS NULL
                     AND thread.status IN (
                        :unassignedopen,
                        :unassignedpending
                     )
                    THEN 1 ELSE 0
                END), 0) AS unassignedactivecount,

                COALESCE(SUM(CASE
                    WHEN thread.status IN (
                        :activeopen,
                        :activepending
                    )
                    THEN 1 ELSE 0
                END), 0) AS activeconversationcount,

                MIN(CASE
                    WHEN thread.status IN (
                        :oldestopen,
                        :oldestpending
                    )
                    THEN COALESCE(
                        NULLIF(thread.lastmessageat, 0),
                        NULLIF(thread.timecreated, 0)
                    )
                    ELSE NULL
                END) AS oldestactiveat,

                MAX(thread.lastmessageat) AS lastconversationat

              FROM {local_subscriptions_inbox_contact} contact

              JOIN {local_subscriptions_inbox_thread} thread
                ON thread.contactid = contact.id
               AND thread.locallydeleted = 0

             WHERE contact.matcheduserid = :userid
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'userid' => $userid,

                'openstatus' => InboxThreadStatus::OPEN,
                'pendingstatus' => InboxThreadStatus::PENDING,
                'resolvedstatus' => InboxThreadStatus::RESOLVED,
                'closedstatus' => InboxThreadStatus::CLOSED,

                'urgentpriority' => 'urgent',

                'unassignedopen' => InboxThreadStatus::OPEN,
                'unassignedpending' => InboxThreadStatus::PENDING,

                'activeopen' => InboxThreadStatus::OPEN,
                'activepending' => InboxThreadStatus::PENDING,

                'oldestopen' => InboxThreadStatus::OPEN,
                'oldestpending' => InboxThreadStatus::PENDING,
            ]
        );

        $oldestactiveat =
            (int)($record->oldestactiveat ?? 0);

        $lastconversationat =
            (int)($record->lastconversationat ?? 0);

        return [
            'conversation_count' =>
                (int)($record->conversationcount ?? 0),

            'active_conversation_count' =>
                (int)($record->activeconversationcount ?? 0),

            'open_conversation_count' =>
                (int)($record->openconversationcount ?? 0),

            'pending_conversation_count' =>
                (int)($record->pendingconversationcount ?? 0),

            'resolved_conversation_count' =>
                (int)($record->resolvedconversationcount ?? 0),

            'closed_conversation_count' =>
                (int)($record->closedconversationcount ?? 0),

            'urgent_conversation_count' =>
                (int)($record->urgentconversationcount ?? 0),

            'unread_message_count' =>
                (int)($record->unreadmessagecount ?? 0),

            'unassigned_active_count' =>
                (int)($record->unassignedactivecount ?? 0),

            'oldest_active_at' =>
                $oldestactiveat,

            'oldest_active_age_days' =>
                $oldestactiveat > 0
                    ? max(
                        0,
                        (int)floor(
                            ($measuredat - $oldestactiveat) /
                            DAYSECS
                        )
                    )
                    : null,

            'last_conversation_at' =>
                $lastconversationat,

            'days_since_last_conversation' =>
                $lastconversationat > 0
                    ? max(
                        0,
                        (int)floor(
                            ($measuredat - $lastconversationat) /
                            DAYSECS
                        )
                    )
                    : null,
        ];
    }

    /**
     * @return array<string,int|float|null>
     */
    private function empty_statistics(): array {
        return [
            'conversation_count' => 0,
            'active_conversation_count' => 0,
            'open_conversation_count' => 0,
            'pending_conversation_count' => 0,
            'resolved_conversation_count' => 0,
            'closed_conversation_count' => 0,
            'urgent_conversation_count' => 0,
            'unread_message_count' => 0,
            'unassigned_active_count' => 0,
            'oldest_active_at' => 0,
            'oldest_active_age_days' => null,
            'last_conversation_at' => 0,
            'days_since_last_conversation' => null,
        ];
    }
}