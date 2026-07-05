<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminFormatter;

final class UserTimelineService {

    public static function get_for_user(int $userid, int $limit = 40): array {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], 'id,email', MUST_EXIST);

        $items = [];
        $seenlogids = [];

        self::add_admin_logs($items, $seenlogids, $userid, $limit);
        self::add_notes($items, $userid, $limit);
        self::add_subscription_payments($items, $user, $limit);
        self::add_subscriptions($items, $userid, $limit);
        self::add_digital_purchases($items, $seenlogids, $user, $limit);

        usort($items, static function($a, $b) {
            return (int)$b->timecreated <=> (int)$a->timecreated;
        });

        return array_slice($items, 0, $limit);
    }

    private static function add_admin_logs(array &$items, array &$seenlogids, int $userid, int $limit): void {
        global $DB;

        $logs = $DB->get_records(
            'local_subscriptions_admin_log',
            ['targetuserid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        );

        foreach ($logs as $log) {
            if ((string)$log->action === AdminEvents::USER_NOTE_ADDED) {
                continue;
            }

            $seenlogids[(int)$log->id] = true;
            $items[] = self::admin_log_item($log);
        }
    }

    private static function add_notes(array &$items, int $userid, int $limit): void {
        global $DB;

        $notes = $DB->get_records(
            'local_subscriptions_user_note',
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        );

        foreach ($notes as $note) {
            $items[] = (object)[
                'type' => 'note',
                'icon' => '📝',
                'timecreated' => (int)$note->timecreated,
                'actorid' => (int)$note->authorid,
                'title' => get_string('crm_timeline_note_added', 'local_subscriptions'),
                'body' => $note->note,
                'raw' => $note,
            ];
        }
    }

    private static function add_subscription_payments(array &$items, \stdClass $user, int $limit): void {
        global $DB;

        $payments = $DB->get_records_sql("
            SELECT pr.*, sp.name AS planname
              FROM {subscription_payment_request} pr
         LEFT JOIN {subscription_plan} sp ON sp.id = pr.planid
             WHERE pr.userid = :userid OR pr.email = :email
          ORDER BY COALESCE(pr.payment_date, pr.creation_date) DESC, pr.id DESC
        ", [
            'userid' => (int)$user->id,
            'email' => $user->email,
        ], 0, $limit);

        foreach ($payments as $pr) {
            $status = strtoupper((string)($pr->status ?? ''));

            $items[] = (object)[
                'type' => 'subscription_payment',
                'icon' => in_array($status, ['PAID', 'COMPLETED'], true) ? '💳' : '🧾',
                'timecreated' => !empty($pr->payment_date) ? (int)$pr->payment_date : (int)$pr->creation_date,
                'actorid' => 0,
                'title' => in_array($status, ['PAID', 'COMPLETED'], true)
                    ? get_string('crm_timeline_course_purchase_paid', 'local_subscriptions')
                    : get_string('crm_timeline_payment_request', 'local_subscriptions'),
                'body' => '',
                'objecttype' => 'payment_request',
                'detailsraw' => [
                    'paymentrequestid' => (int)$pr->id,
                    'subscriptionid' => (int)($pr->subscriptionid ?? 0),
                    'planid' => (int)($pr->planid ?? 0),
                    'plan' => $pr->planname ?: '-',
                    'status' => $status ?: '-',
                    'operation' => $pr->operation ?? '',
                    'price' => self::payment_amount($pr),
                    'currency' => $pr->currency ?? '',
                    'provider' => $pr->payment_provider ?? '',
                    'transactionid' => $pr->transactionid ?? '',
                    'email' => $pr->email ?? '',
                ],
                'raw' => $pr,
            ];
        }
    }

    private static function add_subscriptions(array &$items, int $userid, int $limit): void {
        global $DB;

        $subscriptions = $DB->get_records_sql("
            SELECT us.*, sp.name AS planname
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
             WHERE us.userid = :userid
          ORDER BY us.creation_date DESC, us.id DESC
        ", ['userid' => $userid], 0, $limit);

        foreach ($subscriptions as $sub) {
            $items[] = (object)[
                'type' => 'subscription_snapshot',
                'icon' => self::subscription_icon($sub),
                'timecreated' => !empty($sub->creation_date) ? (int)$sub->creation_date : (int)$sub->start_date,
                'actorid' => 0,
                'title' => self::subscription_snapshot_title($sub),
                'body' => '',
                'objecttype' => 'subscription',
                'detailsraw' => [
                    'subscriptionid' => (int)$sub->id,
                    'plan' => $sub->planname ?: '-',
                    'status' => $sub->status ?? '-',
                    'start' => AdminFormatter::date((int)($sub->start_date ?? 0)),
                    'end' => AdminFormatter::subscription_end((int)($sub->end_date ?? 0)),
                    'price' => AdminFormatter::price($sub->pricepaid ?? 0, $sub->currency ?? ''),
                    'provider' => $sub->payment_provider ?? '',
                    'transactionid' => $sub->transactionid ?? '',
                ],
                'raw' => $sub,
            ];
        }
    }

    private static function add_digital_purchases(array &$items, array &$seenlogids, \stdClass $user, int $limit): void {
        global $DB;

        $digitalpayments = $DB->get_records_sql("
            SELECT dpr.*, dp.name AS productname
              FROM {subscription_digital_payment_request} dpr
         LEFT JOIN {subscription_digital_product} dp ON dp.id = dpr.productid
             WHERE dpr.userid = :userid OR dpr.email = :email
          ORDER BY dpr.creation_date DESC, dpr.id DESC
        ", [
            'userid' => (int)$user->id,
            'email' => $user->email,
        ], 0, $limit);

        $purchaseids = array_map(static function($payment) {
            return (int)$payment->id;
        }, $digitalpayments);

        if ($purchaseids) {
            [$insql, $inparams] = $DB->get_in_or_equal($purchaseids, SQL_PARAMS_NAMED);

            $digitallogs = $DB->get_records_select(
                'local_subscriptions_admin_log',
                "objecttype = :objecttype AND objectid $insql",
                ['objecttype' => 'digital_purchase'] + $inparams,
                'timecreated DESC, id DESC',
                '*',
                0,
                $limit
            );

            foreach ($digitallogs as $log) {
                if (!empty($seenlogids[(int)$log->id])) {
                    continue;
                }

                $seenlogids[(int)$log->id] = true;
                $items[] = self::admin_log_item($log);
            }
        }

        foreach ($digitalpayments as $payment) {
            $status = strtoupper((string)($payment->status ?? ''));

            $items[] = (object)[
                'type' => 'digital_purchase',
                'icon' => in_array($status, ['PAID', 'COMPLETED'], true) ? '📦' : '🧾',
                'timecreated' => !empty($payment->payment_date) ? (int)$payment->payment_date : (int)$payment->creation_date,
                'actorid' => 0,
                'title' => get_string('crm_timeline_digital_purchase', 'local_subscriptions'),
                'body' => '',
                'objecttype' => 'digital_purchase',
                'detailsraw' => [
                    'productid' => (int)$payment->productid,
                    'product' => $payment->productname ?: '-',
                    'status' => $status ?: '-',
                    'price' => $payment->price ?? 0,
                    'currency' => $payment->currency ?? '',
                    'email' => $payment->email ?? '',
                ],
                'raw' => $payment,
            ];
        }
    }

    private static function admin_log_item(\stdClass $log): \stdClass {
        return (object)[
            'type' => 'admin_log',
            'icon' => self::icon_for_action((string)$log->action),
            'timecreated' => (int)$log->timecreated,
            'actorid' => (int)$log->actorid,
            'title' => self::label_for_action((string)$log->action),
            'body' => '',
            'objecttype' => $log->objecttype ?? null,
            'detailsraw' => self::details_to_array($log->details ?? ''),
            'action' => (string)$log->action,
            'logid' => (int)$log->id,
            'raw' => $log,
        ];
    }

    private static function payment_amount(\stdClass $pr): float {
        if (isset($pr->locked_final_price) && (float)$pr->locked_final_price > 0) {
            return (float)$pr->locked_final_price;
        }

        if (isset($pr->amount_minor) && (int)$pr->amount_minor > 0) {
            return round(((int)$pr->amount_minor) / 100, 2);
        }

        return (float)($pr->price ?? 0);
    }

    private static function subscription_snapshot_title(\stdClass $sub): string {
        $status = strtolower((string)($sub->status ?? ''));

        if ($status === 'trial') {
            return get_string('crm_timeline_trial_started', 'local_subscriptions');
        }

        return get_string('crm_timeline_subscription_created', 'local_subscriptions');
    }

    private static function subscription_icon(\stdClass $sub): string {
        $status = strtolower((string)($sub->status ?? ''));

        if ($status === 'trial') {
            return '🎁';
        }

        if ($status === 'deleted' || $status === 'cancelled') {
            return '🗑️';
        }

        return '📚';
    }

    private static function icon_for_action(string $action): string {
        if (str_starts_with($action, 'email.')) {
            return '✉️';
        }

        if (str_contains($action, 'password')) {
            return '🔑';
        }

        if (str_contains($action, 'trial')) {
            return '🎁';
        }

        if (str_contains($action, 'subscription')) {
            return '📚';
        }

        if (str_contains($action, 'payment')) {
            return '💳';
        }

        if (str_contains($action, 'digital')) {
            return '📦';
        }

        return '⚙️';
    }

    private static function label_for_action(string $action): string {
        $map = [
            'email.receipt.sent' => 'crm_timeline_email_receipt_sent',
            'email.subscription_access.sent' => 'crm_timeline_email_access_sent',
            'email.welcome.sent' => 'crm_timeline_email_welcome_sent',
        ];

        if (!empty($map[$action])) {
            return get_string($map[$action], 'local_subscriptions');
        }

        $key = 'adminlog_' . str_replace('.', '_', $action);

        if (get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }

        return $action;
    }

    private static function details_to_text(?string $json): string {
        if (empty($json)) {
            return '';
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded) || !$decoded) {
            return '';
        }

        $parts = [];

        foreach ($decoded as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } else if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $parts[] = s($key) . ': ' . s((string)$value);
        }

        return implode('<br>', $parts);
    }

    private static function details_to_array(?string $json): array {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}