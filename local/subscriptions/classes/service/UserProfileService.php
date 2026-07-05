<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\service\UserNoteService;
use local_subscriptions\service\UserTimelineService;

final class UserProfileService {

    public static function load(int $userid): \stdClass {
        global $DB;

        $profile = new \stdClass();

        $profile->user = $DB->get_record('user', [
            'id' => $userid,
            'deleted' => 0,
        ], '*', MUST_EXIST);

        $profile->subscriptions = $DB->get_records_sql("
            SELECT us.*, sp.name AS planname, sp.duration_key
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
             WHERE us.userid = :userid
          ORDER BY us.start_date DESC, us.id DESC
        ", ['userid' => $userid]);

        $profile->digitalpayments = $DB->get_records_sql("
            SELECT dpr.*, dp.name AS productname
              FROM {subscription_digital_payment_request} dpr
         LEFT JOIN {subscription_digital_product} dp ON dp.id = dpr.productid
             WHERE dpr.userid = :userid OR dpr.email = :email
          ORDER BY dpr.creation_date DESC, dpr.id DESC
        ", [
            'userid' => $userid,
            'email' => $profile->user->email,
        ], 0, 20);

        $profile->stats = (object)[
            'crmstatus' => self::crm_status((int)$profile->user->id, !empty($profile->user->suspended)),
            'subscriptions' => count($profile->subscriptions),
            'digitalpayments' => count($profile->digitalpayments),
            'accessiblecourses' => self::count_accessible_courses((int)$profile->user->id),
            'spent_eur' => self::sum_spent_by_currency((int)$profile->user->id, 'EUR'),
            'spent_rub' => self::sum_spent_by_currency((int)$profile->user->id, 'RUB'),
            'lastactivity' => self::last_activity((int)$profile->user->id),
        ];

        $profile->notes = UserNoteService::get_for_user($userid, 20);
        $profile->timeline = UserTimelineService::get_for_user($userid, 40);
        $profile->courses = self::get_accessible_courses($userid);

        return $profile;
    }

    private static function crm_status(int $userid, bool $usersuspended): string {
        global $DB;

        if ($usersuspended) {
            return 'suspended';
        }

        $hasactive = $DB->record_exists_select(
            'user_subscription',
            'userid = :userid AND status = :status',
            [
                'userid' => $userid,
                'status' => 'active',
            ]
        );

        if ($hasactive) {
            return 'active_customer';
        }

        $hastrial = $DB->record_exists_select(
            'user_subscription',
            'userid = :userid AND status = :status',
            [
                'userid' => $userid,
                'status' => 'trial',
            ]
        );

        if ($hastrial) {
            return 'trial';
        }

        $haspastpurchase = $DB->record_exists_select(
            'user_subscription',
            'userid = :userid AND status IN (:expired, :cancelled, :replaced)',
            [
                'userid' => $userid,
                'expired' => 'expired',
                'cancelled' => 'cancelled',
                'replaced' => 'replaced',
            ]
        );

        if ($haspastpurchase) {
            return 'former_customer';
        }

        return 'lead';
    }

    private static function get_accessible_courses(int $userid): array {
        global $DB;

        return $DB->get_records_sql("
            SELECT DISTINCT
                c.id,
                c.fullname,
                c.shortname,
                ue.timestart,
                ue.timeend,
                ue.status AS enrolstatus,
                e.enrol
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON c.id = e.courseid
            WHERE ue.userid = :userid
            AND c.id <> :siteid
            AND ue.status = 0
            AND e.status = 0
            AND (ue.timeend = 0 OR ue.timeend > :now)
        ORDER BY c.fullname ASC
        ", [
            'userid' => $userid,
            'siteid' => SITEID,
            'now' => time(),
        ]);
    }

    private static function count_accessible_courses(int $userid): int {
        global $DB;

        return (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT e.courseid)
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE ue.userid = :userid
            AND ue.status = 0
            AND e.status = 0
        ", [
            'userid' => $userid,
        ]);
    }

    private static function sum_spent_by_currency(int $userid, string $currency): float {
        global $DB;

        $subsum = (float)$DB->get_field_sql("
            SELECT COALESCE(SUM(pricepaid), 0)
            FROM {user_subscription}
            WHERE userid = :userid
            AND status IN ('active', 'expired', 'cancelled', 'replaced')
            AND currency = :currency
        ", [
            'userid' => $userid,
            'currency' => $currency,
        ]);

        $digitalsum = 0.0;

        if ($DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $digitalsum = (float)$DB->get_field_sql("
                SELECT COALESCE(SUM(price), 0)
                FROM {subscription_digital_payment_request}
                WHERE userid = :userid
                AND status IN ('paid', 'completed', 'PAID', 'COMPLETED')
                AND currency = :currency
            ", [
                'userid' => $userid,
                'currency' => $currency,
            ]);
        }

        return $subsum + $digitalsum;
    }

    private static function last_activity(int $userid): int {
        global $DB;

        $lasts = [];

        $lasts[] = (int)$DB->get_field_sql("
            SELECT COALESCE(MAX(COALESCE(last_update, creation_date)), 0)
            FROM {user_subscription}
            WHERE userid = :userid
        ", ['userid' => $userid]);

        $lasts[] = (int)$DB->get_field_sql("
            SELECT COALESCE(MAX(timecreated), 0)
            FROM {local_subscriptions_admin_log}
            WHERE targetuserid = :userid
        ", ['userid' => $userid]);

        if ($DB->get_manager()->table_exists('subscription_payment_request')) {
            $field = $DB->get_manager()->field_exists('subscription_payment_request', 'last_update')
                ? 'last_update'
                : 'creation_date';

            $lasts[] = (int)$DB->get_field_sql("
                SELECT COALESCE(MAX($field), 0)
                FROM {subscription_payment_request}
                WHERE userid = :userid
            ", ['userid' => $userid]);
        }

        if ($DB->get_manager()->table_exists('subscription_digital_payment_request')) {
            $field = $DB->get_manager()->field_exists('subscription_digital_payment_request', 'last_update')
                ? 'last_update'
                : 'creation_date';

            $lasts[] = (int)$DB->get_field_sql("
                SELECT COALESCE(MAX($field), 0)
                FROM {subscription_digital_payment_request}
                WHERE userid = :userid
            ", ['userid' => $userid]);
        }

        return max($lasts);
    }

}