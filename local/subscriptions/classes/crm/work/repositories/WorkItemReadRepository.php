<?php

namespace local_subscriptions\crm\work\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemStatus;
use local_subscriptions\crm\work\dto\WorkItemCriteria;
use local_subscriptions\crm\work\dto\WorkItemListResult;

final class WorkItemReadRepository {

    public function search(
        WorkItemCriteria $criteria,
        int $viewerid
    ): WorkItemListResult {
        global $DB;

        $where = ['1 = 1'];
        $params = [];

        if ($criteria->query !== '') {
            $where[] = '(' .
                $DB->sql_like('LOWER(item.reference)', ':workqref', false) .
                ' OR ' .
                $DB->sql_like('LOWER(item.title)', ':workqtitle', false) .
                ')';
            $needle = '%' . \core_text::strtolower($criteria->query) . '%';
            $params['workqref'] = $needle;
            $params['workqtitle'] = $needle;
        }

        if ($criteria->status !== '') {
            $where[] = 'item.status = :workstatus';
            $params['workstatus'] = $criteria->status;
        }

        if ($criteria->priority !== '') {
            $where[] = 'item.priority = :workpriority';
            $params['workpriority'] = $criteria->priority;
        }

        if ($criteria->type !== '') {
            $where[] = 'item.type = :worktype';
            $params['worktype'] = $criteria->type;
        }

        if ($criteria->assigneduserid > 0) {
            $where[] = 'item.assigneduserid = :workassigneduser';
            $params['workassigneduser'] = $criteria->assigneduserid;
        }

        if ($criteria->assignedteamid > 0) {
            $where[] = 'item.assignedteamid = :workassignedteam';
            $params['workassignedteam'] = $criteria->assignedteamid;
        }

        if ($criteria->targetuserid > 0) {
            $where[] = 'item.targetuserid = :worktargetuser';
            $params['worktargetuser'] = $criteria->targetuserid;
        }

        if ($criteria->mineonly) {
            $where[] = 'item.assigneduserid = :workmine';
            $params['workmine'] = $viewerid;
        }

        if ($criteria->unassignedonly) {
            $where[] = 'item.assigneduserid IS NULL';
            $where[] = 'item.assignedteamid IS NULL';
        }

        if ($criteria->overdueonly) {
            [$activesql, $activeparams] = $DB->get_in_or_equal(
                WorkItemStatus::active(),
                SQL_PARAMS_NAMED,
                'workoverdueactive'
            );
            $where[] = 'item.dueat IS NOT NULL';
            $where[] = 'item.dueat > 0';
            $where[] = 'item.dueat < :worknow';
            $where[] = 'item.status ' . $activesql;
            $params['worknow'] = time();
            $params += $activeparams;
        }

        $wheresql = implode(' AND ', $where);

        $fromsql = "
              FROM {local_subscriptions_work_item} item
         LEFT JOIN {user} assignee
                ON assignee.id = item.assigneduserid
         LEFT JOIN {user} target
                ON target.id = item.targetuserid
         LEFT JOIN {local_subscriptions_work_team} team
                ON team.id = item.assignedteamid
             WHERE {$wheresql}
        ";

        $total = (int)$DB->count_records_sql(
            'SELECT COUNT(item.id) ' . $fromsql,
            $params
        );

        $items = array_values($DB->get_records_sql(
            "SELECT item.*,
                    assignee.firstname AS assigneefirstname,
                    assignee.lastname AS assigneelastname,
                    assignee.firstnamephonetic AS assigneefirstnamephonetic,
                    assignee.lastnamephonetic AS assigneelastnamephonetic,
                    assignee.middlename AS assigneemiddlename,
                    assignee.alternatename AS assigneealternatename,
                    target.firstname AS targetfirstname,
                    target.lastname AS targetlastname,
                    target.firstnamephonetic AS targetfirstnamephonetic,
                    target.lastnamephonetic AS targetlastnamephonetic,
                    target.middlename AS targetmiddlename,
                    target.alternatename AS targetalternatename,
                    team.name AS teamname
               {$fromsql}
           ORDER BY
                    CASE item.priority
                        WHEN 'critical' THEN 1
                        WHEN 'urgent' THEN 2
                        WHEN 'high' THEN 3
                        WHEN 'normal' THEN 4
                        ELSE 5
                    END,
                    CASE WHEN item.dueat IS NULL OR item.dueat = 0
                         THEN 1 ELSE 0 END,
                    item.dueat ASC,
                    item.timemodified DESC,
                    item.id DESC",
            $params,
            $criteria->page * $criteria->perpage,
            $criteria->perpage
        ));

        return new WorkItemListResult(
            $criteria,
            $items,
            $total,
            $this->get_teams(),
            $this->get_assignees()
        );
    }

    public function get_detail(int $itemid): \stdClass {
        global $DB;

        $item = $DB->get_record_sql(
            "SELECT item.*,
                    assignee.firstname AS assigneefirstname,
                    assignee.lastname AS assigneelastname,
                    assignee.firstnamephonetic AS assigneefirstnamephonetic,
                    assignee.lastnamephonetic AS assigneelastnamephonetic,
                    assignee.middlename AS assigneemiddlename,
                    assignee.alternatename AS assigneealternatename,
                    target.firstname AS targetfirstname,
                    target.lastname AS targetlastname,
                    target.firstnamephonetic AS targetfirstnamephonetic,
                    target.lastnamephonetic AS targetlastnamephonetic,
                    target.middlename AS targetmiddlename,
                    target.alternatename AS targetalternatename,
                    creator.firstname AS creatorfirstname,
                    creator.lastname AS creatorlastname,
                    creator.firstnamephonetic AS creatorfirstnamephonetic,
                    creator.lastnamephonetic AS creatorlastnamephonetic,
                    creator.middlename AS creatormiddlename,
                    creator.alternatename AS creatoralternatename,
                    team.name AS teamname
               FROM {local_subscriptions_work_item} item
          LEFT JOIN {user} assignee ON assignee.id = item.assigneduserid
          LEFT JOIN {user} target ON target.id = item.targetuserid
               JOIN {user} creator ON creator.id = item.createdby
          LEFT JOIN {local_subscriptions_work_team} team
                 ON team.id = item.assignedteamid
              WHERE item.id = :itemid",
            ['itemid' => $itemid],
            MUST_EXIST
        );

        $repository = new WorkItemRepository();
        $item->comments = $repository->get_comments($itemid);
        $item->links = $repository->get_links($itemid);
        $item->history = $repository->get_history($itemid);
        $item->children = $repository->get_children($itemid);

        return $item;
    }

    public function get_teams(bool $enabledonly = true): array {
        global $DB;

        $conditions = $enabledonly ? ['enabled' => 1] : [];

        return array_values($DB->get_records(
            'local_subscriptions_work_team',
            $conditions,
            'name ASC, id ASC'
        ));
    }

    public function get_assignees(): array {
        global $DB;

        return array_values($DB->get_records_sql(
            "SELECT DISTINCT u.id, u.firstname, u.lastname,
                    u.firstnamephonetic, u.lastnamephonetic,
                    u.middlename, u.alternatename
               FROM {user} u
               JOIN {local_subscriptions_work_team_member} member
                 ON member.userid = u.id
              WHERE u.deleted = 0
                AND u.suspended = 0
           ORDER BY u.lastname ASC, u.firstname ASC"
        ));
    }

    public function get_user_summary(int $userid, int $limit = 5): \stdClass {
        global $DB;

        $summary = (object)[
            'totalcount' => (int)$DB->count_records(
                'local_subscriptions_work_item',
                ['targetuserid' => $userid]
            ),
            'activecount' => $this->count_user_items_by_statuses(
                $userid,
                WorkItemStatus::active()
            ),
            'blockedcount' => (int)$DB->count_records(
                'local_subscriptions_work_item',
                ['targetuserid' => $userid, 'status' => WorkItemStatus::BLOCKED]
            ),
            'urgentcount' => $this->count_user_urgent_active($userid),
            'overduecount' => $this->count_user_overdue_active($userid),
        ];

        $summary->recent = array_values($DB->get_records_sql(
            "SELECT *
               FROM {local_subscriptions_work_item}
              WHERE targetuserid = :userid
           ORDER BY timemodified DESC, id DESC",
            ['userid' => $userid],
            0,
            $limit
        ));

        return $summary;
    }

    private function count_user_items_by_statuses(int $userid, array $statuses): int {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal(
            $statuses,
            SQL_PARAMS_NAMED,
            'workuserstatus'
        );
        $params['userid'] = $userid;

        return (int)$DB->count_records_sql(
            "SELECT COUNT(id)
               FROM {local_subscriptions_work_item}
              WHERE targetuserid = :userid
                AND status {$insql}",
            $params
        );
    }

    private function count_user_urgent_active(int $userid): int {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal(
            WorkItemStatus::active(),
            SQL_PARAMS_NAMED,
            'workuserurgentstatus'
        );
        $params['userid'] = $userid;
        $params['urgent'] = 'urgent';
        $params['critical'] = 'critical';

        return (int)$DB->count_records_sql(
            "SELECT COUNT(id)
               FROM {local_subscriptions_work_item}
              WHERE targetuserid = :userid
                AND priority IN (:urgent, :critical)
                AND status {$insql}",
            $params
        );
    }

    private function count_user_overdue_active(int $userid): int {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal(
            WorkItemStatus::active(),
            SQL_PARAMS_NAMED,
            'workuseroverduestatus'
        );
        $params['userid'] = $userid;
        $params['now'] = time();

        return (int)$DB->count_records_sql(
            "SELECT COUNT(id)
               FROM {local_subscriptions_work_item}
              WHERE targetuserid = :userid
                AND dueat > 0
                AND dueat < :now
                AND status {$insql}",
            $params
        );
    }
}