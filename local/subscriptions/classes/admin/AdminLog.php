<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminFormatter;

final class AdminLog {

    public static function log(
        string $action,
        ?int $targetuserid = null,
        ?string $objecttype = null,
        ?int $objectid = null,
        array $details = []
    ): void {
        global $DB, $USER;

        if (!AdminEvents::exists($action)) {
            debugging('Unknown CampusFR admin event: ' . $action, DEBUG_DEVELOPER);
        }

        $record = (object)[
            'actorid' => (int)$USER->id,
            'targetuserid' => $targetuserid,
            'action' => $action,
            'objecttype' => $objecttype,
            'objectid' => $objectid,
            'details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ipaddress' => getremoteaddr(),
            'timecreated' => time(),
        ];

        $DB->insert_record('local_subscriptions_admin_log', $record);
    }

    public static function get_for_user(int $userid, int $limit = 20): array {
        global $DB;

        return $DB->get_records(
            'local_subscriptions_admin_log',
            ['targetuserid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        );
    }

    public static function subscriptionCreatedManual(\stdClass $subscription, ?\stdClass $plan = null): void {
        self::log(
            AdminEvents::SUBSCRIPTION_CREATED_MANUAL,
            (int)$subscription->userid,
            'subscription',
            (int)$subscription->id,
            self::subscription_details($subscription, $plan)
        );
    }

    public static function subscriptionUpdated(\stdClass $subscription, ?\stdClass $plan = null, array $changes = []): void {
        $details = self::subscription_details($subscription, $plan);

        if ($changes) {
            $details['changes'] = $changes;
        }

        self::log(
            AdminEvents::SUBSCRIPTION_UPDATED,
            (int)$subscription->userid,
            'subscription',
            (int)$subscription->id,
            $details
        );
    }

    public static function subscriptionDeleted(\stdClass $subscription, ?\stdClass $plan = null): void {
        self::log(
            AdminEvents::SUBSCRIPTION_DELETED,
            (int)$subscription->userid,
            'subscription',
            (int)$subscription->id,
            self::subscription_details($subscription, $plan)
        );
    }

    public static function subscriptionCreatedAuto(
        \stdClass $subscription,
        ?\stdClass $plan = null,
        ?\stdClass $pr = null
    ): void {
        $details = self::subscription_details($subscription, $plan);

        if ($pr) {
            $details['paymentrequest'] = '#' . (int)$pr->id;
            $details['provider'] = $pr->payment_provider ?? ($pr->provider ?? '-');
            $details['transactionid'] = $pr->transactionid ?? '-';
        }

        self::log(
            AdminEvents::SUBSCRIPTION_CREATED_AUTO,
            (int)$subscription->userid,
            'subscription',
            (int)$subscription->id,
            $details
        );
    }

    public static function subscriptionExtended(
        \stdClass $subscription,
        ?\stdClass $plan = null,
        array $changes = []
    ): void {
        $details = self::subscription_details($subscription, $plan);

        if ($changes) {
            $details['changes'] = $changes;
        }

        self::log(
            AdminEvents::SUBSCRIPTION_EXTENDED,
            (int)$subscription->userid,
            'subscription',
            (int)$subscription->id,
            $details
        );
    }

    public static function emailReceiptSent(
        int $userid,
        ?int $subscriptionid = null,
        ?int $prid = null,
        array $details = []
    ): void {
        self::log(
            AdminEvents::EMAIL_RECEIPT_SENT,
            $userid,
            $subscriptionid ? 'subscription' : 'user',
            $subscriptionid ?: $userid,
            [
                'subscriptionid' => $subscriptionid,
                'paymentrequest' => $prid ? '#' . $prid : '-',
            ] + $details
        );
    }

    public static function emailSubscriptionAccessSent(
        int $userid,
        ?int $subscriptionid = null,
        array $details = []
    ): void {
        self::log(
            AdminEvents::EMAIL_SUBSCRIPTION_ACCESS_SENT,
            $userid,
            $subscriptionid ? 'subscription' : 'user',
            $subscriptionid ?: $userid,
            [
                'subscriptionid' => $subscriptionid,
            ] + $details
        );
    }

    public static function emailWelcomeSent(
        int $userid,
        ?int $subscriptionid = null,
        array $details = []
    ): void {
        self::log(
            AdminEvents::EMAIL_WELCOME_SENT,
            $userid,
            $subscriptionid ? 'subscription' : 'user',
            $subscriptionid ?: $userid,
            [
                'subscriptionid' => $subscriptionid,
                'emailtype' => 'welcome',
            ] + $details
        );
    }

    public static function emailCustomSent(
        int $userid,
        string $subject,
        string $body,
        array $details = []
    ): void {
        self::log(
            AdminEvents::EMAIL_CUSTOM_SENT,
            $userid,
            'email',
            $userid,
            [
                'subject' => $subject,
                'body' => $body,
                'emailtype' => 'custom',
            ] + $details
        );
    }

    private static function subscription_details(\stdClass $subscription, ?\stdClass $plan = null): array {
        return [
            'plan' => $plan->name ?? ($subscription->planname ?? $subscription->planid ?? '-'),
            'status' => $subscription->status ?? '-',
            'start' => AdminFormatter::date((int)($subscription->start_date ?? 0)),
            'end' => AdminFormatter::subscription_end((int)($subscription->end_date ?? 0)),
            'price' => AdminFormatter::price($subscription->pricepaid ?? 0, $subscription->currency ?? ''),
        ];
    }

}