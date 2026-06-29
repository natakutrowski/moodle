<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminLog;
use local_subscriptions\service\UserNoteService;

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
            'subscriptions' => count($profile->subscriptions),
            'digitalpayments' => count($profile->digitalpayments),
        ];

        $profile->adminlogs = AdminLog::get_for_user($userid, 20);
        $profile->notes = UserNoteService::get_for_user($userid, 20);

        return $profile;
    }
}