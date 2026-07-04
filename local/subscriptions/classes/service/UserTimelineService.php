<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
final class UserTimelineService {

    public static function get_for_user(int $userid, int $limit = 40): array {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid], 'id,email', MUST_EXIST);

        $items = [];

        $logs = $DB->get_records(
            'local_subscriptions_admin_log',
            ['targetuserid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        );

        $seenlogids = [];

        foreach ($logs as $log) {
            if ((string)$log->action === AdminEvents::USER_NOTE_ADDED) {
                continue;
            }

            $seenlogids[(int)$log->id] = true;

            $items[] = (object)[
                'type' => 'admin_log',
                'icon' => self::icon_for_action((string)$log->action),
                'timecreated' => (int)$log->timecreated,
                'actorid' => (int)$log->actorid,
                'title' => self::label_for_action((string)$log->action),
                'body' => self::details_to_text($log->details ?? ''),
                'objecttype' => $log->objecttype ?? null,
                'detailsraw' => self::details_to_array($log->details ?? ''),
                'action' => (string)$log->action,
                'raw' => $log,
            ];
        }

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

        $digitalpayments = $DB->get_records_sql("
            SELECT dpr.*, dp.name AS productname
            FROM {subscription_digital_payment_request} dpr
        LEFT JOIN {subscription_digital_product} dp ON dp.id = dpr.productid
            WHERE dpr.userid = :userid OR dpr.email = :email
        ORDER BY dpr.creation_date DESC, dpr.id DESC
        ", [
            'userid' => $userid,
            'email' => $user->email,
        ], 0, $limit);

        $purchaseids = array_map(function($payment) {
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
                $items[] = (object)[
                    'type' => 'admin_log',
                    'icon' => self::icon_for_action((string)$log->action),
                    'timecreated' => (int)$log->timecreated,
                    'actorid' => (int)$log->actorid,
                    'title' => self::label_for_action((string)$log->action),
                    'body' => self::details_to_text($log->details ?? ''),
                    'objecttype' => $log->objecttype ?? null,
                    'detailsraw' => self::details_to_array($log->details ?? ''),
                    'action' => (string)$log->action,
                    'raw' => $log,
                ];

                if (!empty($seenlogids[(int)$log->id])) {
                    continue;
                }
                $seenlogids[(int)$log->id] = true;
            }
        }


        foreach ($digitalpayments as $payment) {
            $status = strtoupper((string)($payment->status ?? ''));

            $items[] = (object)[
                'type' => 'digital_purchase',
                'icon' => $status === 'PAID' || $status === 'COMPLETED' ? '📦' : '🧾',
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

        usort($items, function($a, $b) {
            if ($a->timecreated === $b->timecreated) {
                return 0;
            }
            return $a->timecreated > $b->timecreated ? -1 : 1;
        });

        return array_slice($items, 0, $limit);
    }

    private static function icon_for_action(string $action): string {
        if (str_starts_with($action, 'email.')) {
            return '✉️';
        }

        if (str_contains($action, 'password')) {
            return '🔑';
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