<?php

namespace local_subscriptions\crm\work\intelligence\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemStatus;

/**
 * Loads existing Work Items that may duplicate a suggestion.
 */
final class WorkItemDuplicateRepository {

    /**
     * Return active Work Items for the same target user.
     */
    public function find_candidates(
        ?int $targetuserid,
        string $type,
        int $limit = 50
    ): array {
        global $DB;

        $limit = max(1, min(200, $limit));

        [$statussql, $params] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'duplicate_status'
            );

        $conditions = [
            "item.status {$statussql}",
        ];

        if ($targetuserid !== null) {
            $conditions[] =
                'item.targetuserid = :duplicate_targetuserid';

            $params['duplicate_targetuserid'] =
                $targetuserid;
        }

        $conditions[] =
            'item.type = :duplicate_type';

        $params['duplicate_type'] = $type;

        return array_values($DB->get_records_sql(
            "SELECT
                    item.id,
                    item.reference,
                    item.title,
                    item.description,
                    item.type,
                    item.priority,
                    item.status,
                    item.targetuserid,
                    item.assignedteamid,
                    item.timecreated,
                    item.timemodified
               FROM {local_subscriptions_work_item} item
              WHERE " . implode(
                  ' AND ',
                  $conditions
              ) . "
           ORDER BY item.timemodified DESC,
                    item.id DESC",
            $params,
            0,
            $limit
        ));
    }

    /**
     * Find an active Work Item already linked to a recommendation.
     */
    public function find_linked_recommendation_item(
        int $recommendationid
    ): ?\stdClass {
        global $DB;

        [$statussql, $params] =
            $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'linked_status'
            );

        $params['objecttype'] =
            'recommendation';

        $params['objectid'] =
            $recommendationid;

        $record = $DB->get_record_sql(
            "SELECT item.*
               FROM {local_subscriptions_work_item} item
               JOIN {local_subscriptions_work_link} link
                 ON link.itemid = item.id
              WHERE link.objecttype = :objecttype
                AND link.objectid = :objectid
                AND item.status {$statussql}
           ORDER BY item.id DESC",
            $params,
            IGNORE_MULTIPLE
        );

        return $record ?: null;
    }
}