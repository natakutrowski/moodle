<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

final class DashboardActivityService {

    public static function load(int $limit = 8): array {
        global $DB;

        return $DB->get_records_sql("
            SELECT l.*, u.firstname, u.lastname, u.email,
                   tu.firstname AS targetfirstname,
                   tu.lastname AS targetlastname,
                   tu.email AS targetemail
              FROM {local_subscriptions_admin_log} l
         LEFT JOIN {user} u ON u.id = l.actorid
         LEFT JOIN {user} tu ON tu.id = l.targetuserid
          ORDER BY l.timecreated DESC, l.id DESC
        ", [], 0, $limit);
    }
}