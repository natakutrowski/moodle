<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\crm\automation\AutomationTriggerKeys;

final class UserProfileTimelineBuilder {

    public function build_for_user(\stdClass $user, int $limit = 40): array {
        $repository = new UserProfileRepository();

        $events = [];
        $seenlogids = [];

        $this->add_admin_logs($events, $seenlogids, $repository->get_admin_logs((int)$user->id, $limit));
        $this->add_notes($events, $repository->get_user_notes((int)$user->id, $limit));
        $this->add_automation_history(
            $events,
            $repository->get_automation_history_for_timeline((int)$user->id, $limit)
        );
        $this->add_subscription_payments(
            $events,
            $repository->get_subscription_payments_for_timeline((int)$user->id, (string)$user->email, $limit)
        );
        $this->add_subscriptions($events, $repository->get_subscriptions_for_timeline((int)$user->id, $limit));

        $digitalpurchases = $repository->get_digital_purchases_for_timeline((int)$user->id, (string)$user->email, $limit);
        $purchaseids = array_map(static fn($purchase): int => (int)$purchase->id, $digitalpurchases);

        $this->add_digital_logs(
            $events,
            $seenlogids,
            $repository->get_digital_purchase_logs($purchaseids, $limit)
        );

        $this->add_digital_purchases($events, $digitalpurchases);

        usort($events, static function(UserProfileTimelineEvent $a, UserProfileTimelineEvent $b): int {
            return $b->timecreated <=> $a->timecreated;
        });

        return array_slice($events, 0, $limit);
    }

    public function to_legacy_objects(array $events): array {
        return array_map(static function(UserProfileTimelineEvent $event): \stdClass {
            return $event->to_object();
        }, $events);
    }

    private function add_admin_logs(array &$events, array &$seenlogids, array $logs): void {
        foreach ($logs as $log) {
            if ((string)$log->action === AdminEvents::USER_NOTE_ADDED) {
                continue;
            }

            $seenlogids[(int)$log->id] = true;

            $details = $this->details_to_array($log->details ?? '');

            $events[] = new UserProfileTimelineEvent(
                $this->type_for_admin_action((string)$log->action),
                (int)$log->timecreated,
                $this->label_for_action((string)$log->action),
                '',
                $this->icon_for_action((string)$log->action),
                $this->importance_for_admin_action((string)$log->action),
                null,
                [
                    'objecttype' => $log->objecttype ?? null,
                    'detailsraw' => $details,
                    'action' => (string)$log->action,
                    'logid' => (int)$log->id,
                    'actorid' => (int)$log->actorid,
                    'rawtype' => 'admin_log',
                ]
            );
        }
    }

    private function add_digital_logs(array &$events, array &$seenlogids, array $logs): void {
        foreach ($logs as $log) {
            if (!empty($seenlogids[(int)$log->id])) {
                continue;
            }

            $seenlogids[(int)$log->id] = true;

            $details = $this->details_to_array($log->details ?? '');

            $events[] = new UserProfileTimelineEvent(
                $this->type_for_admin_action((string)$log->action),
                (int)$log->timecreated,
                $this->label_for_action((string)$log->action),
                '',
                $this->icon_for_action((string)$log->action),
                $this->importance_for_admin_action((string)$log->action),
                null,
                [
                    'objecttype' => $log->objecttype ?? null,
                    'detailsraw' => $details,
                    'action' => (string)$log->action,
                    'logid' => (int)$log->id,
                    'actorid' => (int)$log->actorid,
                    'rawtype' => 'admin_log',
                ]
            );
        }
    }

    private function add_notes(array &$events, array $notes): void {
        foreach ($notes as $note) {
            $events[] = new UserProfileTimelineEvent(
                'note_added',
                (int)$note->timecreated,
                get_string('crm_timeline_note_added', 'local_subscriptions'),
                (string)$note->note,
                $this->note_icon((string)($note->type ?? 'general')),
                'normal',
                null,
                [
                    'actorid' => (int)$note->authorid,
                    'rawtype' => 'note',
                    'notetype' => (string)($note->type ?? 'general'),
                ]
            );
        }
    }

    private function note_icon(string $type): string {
        return match ($type) {
            'followup' => '📌',
            'payment' => '💳',
            'access' => '🔐',
            'sensitive' => '⚠️',
            default => '📝',
        };
    }

    private function add_subscription_payments(array &$events, array $payments): void {
        foreach ($payments as $payment) {
            $status = strtoupper((string)($payment->status ?? ''));

            $paid = in_array($status, ['PAID', 'COMPLETED'], true);

            $events[] = new UserProfileTimelineEvent(
                $paid ? 'subscription_payment_paid' : 'subscription_payment_request',
                !empty($payment->payment_date) ? (int)$payment->payment_date : (int)$payment->creation_date,
                $paid
                    ? get_string('crm_timeline_course_purchase_paid', 'local_subscriptions')
                    : get_string('crm_timeline_payment_request', 'local_subscriptions'),
                '',
                $paid ? '💳' : '🧾',
                $paid ? 'medium' : 'normal',
                null,
                [
                    'objecttype' => 'payment_request',
                    'detailsraw' => [
                        'paymentrequestid' => (int)$payment->id,
                        'subscriptionid' => (int)($payment->subscriptionid ?? 0),
                        'planid' => (int)($payment->planid ?? 0),
                        'plan' => $payment->planname ?: '-',
                        'status' => $status ?: '-',
                        'operation' => $payment->operation ?? '',
                        'price' => $this->payment_amount($payment),
                        'currency' => $payment->currency ?? '',
                        'provider' => $payment->payment_provider ?? '',
                        'transactionid' => $payment->transactionid ?? '',
                        'email' => $payment->email ?? '',
                    ],
                    'actorid' => 0,
                    'rawtype' => 'subscription_payment',
                ]
            );
        }
    }

    private function add_subscriptions(array &$events, array $subscriptions): void {
        foreach ($subscriptions as $subscription) {
            $status = strtolower((string)($subscription->status ?? ''));

            $events[] = new UserProfileTimelineEvent(
                $status === 'trial' ? 'trial_started' : 'subscription_created',
                !empty($subscription->creation_date) ? (int)$subscription->creation_date : (int)$subscription->start_date,
                $status === 'trial'
                    ? get_string('crm_timeline_trial_started', 'local_subscriptions')
                    : get_string('crm_timeline_subscription_created', 'local_subscriptions'),
                '',
                $this->subscription_icon($subscription),
                $status === 'trial' ? 'medium' : 'medium',
                null,
                [
                    'objecttype' => 'subscription',
                    'detailsraw' => [
                        'subscriptionid' => (int)$subscription->id,
                        'plan' => $subscription->planname ?: '-',
                        'status' => $subscription->status ?? '-',
                        'start' => AdminFormatter::date((int)($subscription->start_date ?? 0)),
                        'end' => AdminFormatter::subscription_end((int)($subscription->end_date ?? 0)),
                        'price' => AdminFormatter::price($subscription->pricepaid ?? 0, $subscription->currency ?? ''),
                        'provider' => $subscription->payment_provider ?? '',
                        'transactionid' => $subscription->transactionid ?? '',
                    ],
                    'actorid' => 0,
                    'rawtype' => 'subscription_snapshot',
                ]
            );
        }
    }

    private function add_digital_purchases(array &$events, array $purchases): void {
        foreach ($purchases as $purchase) {
            $status = strtoupper((string)($purchase->status ?? ''));

            $paid = in_array($status, ['PAID', 'COMPLETED'], true);

            $events[] = new UserProfileTimelineEvent(
                $paid ? 'digital_purchase_paid' : 'digital_purchase_created',
                !empty($purchase->payment_date) ? (int)$purchase->payment_date : (int)$purchase->creation_date,
                get_string('crm_timeline_digital_purchase', 'local_subscriptions'),
                '',
                $paid ? '📦' : '🧾',
                $paid ? 'medium' : 'normal',
                null,
                [
                    'objecttype' => 'digital_purchase',
                    'detailsraw' => [
                        'productid' => (int)$purchase->productid,
                        'product' => $purchase->productname ?: '-',
                        'status' => $status ?: '-',
                        'price' => $purchase->price ?? 0,
                        'currency' => $purchase->currency ?? '',
                        'email' => $purchase->email ?? '',
                    ],
                    'actorid' => 0,
                    'rawtype' => 'digital_purchase',
                ]
            );
        }
    }

    private function type_for_admin_action(string $action): string {
        return match ($action) {
            AdminEvents::EMAIL_CUSTOM_SENT,
            AdminEvents::EMAIL_PASSWORD_RESET_NOTICE_SENT,
            AdminEvents::EMAIL_WELCOME_SENT,
            AdminEvents::EMAIL_RECEIPT_SENT,
            AdminEvents::EMAIL_SUBSCRIPTION_ACCESS_SENT => 'email_sent',

            AdminEvents::USER_PASSWORD_UPDATED => 'password_reset',
            AdminEvents::USER_SUSPENDED => 'user_suspended',
            AdminEvents::USER_REACTIVATED => 'user_reactivated',

            AdminEvents::SUBSCRIPTION_CREATED,
            AdminEvents::SUBSCRIPTION_CREATED_MANUAL,
            AdminEvents::SUBSCRIPTION_CREATED_AUTO => 'subscription_created',

            AdminEvents::SUBSCRIPTION_UPDATED,
            AdminEvents::SUBSCRIPTION_STATUS_UPDATED,
            AdminEvents::SUBSCRIPTION_DATES_UPDATED => 'subscription_updated',

            AdminEvents::SUBSCRIPTION_EXTENDED => 'subscription_extended',
            AdminEvents::SUBSCRIPTION_DELETED => 'subscription_deleted',

            AdminEvents::DIGITAL_PURCHASE_CREATED => 'digital_purchase_created',
            AdminEvents::DIGITAL_PURCHASE_PAID => 'digital_purchase_paid',
            AdminEvents::DIGITAL_PURCHASE_FAILED => 'digital_purchase_failed',
            AdminEvents::DIGITAL_LINK_RESENT => 'digital_link_resent',
            AdminEvents::DIGITAL_TOKEN_REGENERATED => 'digital_token_regenerated',
            AdminEvents::DIGITAL_TOKEN_EXTENDED => 'digital_token_extended',
            AdminEvents::DIGITAL_PROVIDER_CHECKED => 'digital_provider_checked',

            AdminEvents::PAYMENT_REQUEST_CREATED => 'payment_request_created',
            AdminEvents::PAYMENT_REQUEST_PAID => 'payment_request_paid',
            AdminEvents::PAYMENT_REQUEST_FAILED => 'payment_request_failed',
            AdminEvents::PAYMENT_REQUEST_CANCELLED => 'payment_request_cancelled',

            AdminEvents::TRIAL_STARTED => 'trial_started',
            AdminEvents::TRIAL_EXPIRED => 'trial_expired',

            default => 'admin_log',
        };
    }

    private function importance_for_admin_action(string $action): string {
        return match ($action) {
            AdminEvents::USER_SUSPENDED,
            AdminEvents::DIGITAL_PURCHASE_FAILED,
            AdminEvents::PAYMENT_REQUEST_FAILED,
            AdminEvents::PAYMENT_REQUEST_CANCELLED => 'high',

            AdminEvents::DIGITAL_PURCHASE_PAID,
            AdminEvents::PAYMENT_REQUEST_PAID,
            AdminEvents::SUBSCRIPTION_CREATED,
            AdminEvents::SUBSCRIPTION_CREATED_MANUAL,
            AdminEvents::SUBSCRIPTION_CREATED_AUTO,
            AdminEvents::TRIAL_STARTED => 'medium',

            default => 'normal',
        };
    }

    private function icon_for_action(string $action): string {
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

        if (str_contains($action, 'suspended')) {
            return '⛔';
        }

        if (str_contains($action, 'reactivated')) {
            return '✅';
        }

        return '⚙️';
    }

    private function label_for_action(string $action): string {
        $map = [
            AdminEvents::EMAIL_RECEIPT_SENT => 'crm_timeline_email_receipt_sent',
            AdminEvents::EMAIL_SUBSCRIPTION_ACCESS_SENT => 'crm_timeline_email_access_sent',
            AdminEvents::EMAIL_WELCOME_SENT => 'crm_timeline_email_welcome_sent',
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

    private function subscription_icon(\stdClass $subscription): string {
        $status = strtolower((string)($subscription->status ?? ''));

        if ($status === 'trial') {
            return '🎁';
        }

        if ($status === 'deleted' || $status === 'cancelled') {
            return '🗑️';
        }

        return '📚';
    }

    private function payment_amount(\stdClass $payment): float {
        if (isset($payment->locked_final_price) && (float)$payment->locked_final_price > 0) {
            return (float)$payment->locked_final_price;
        }

        if (isset($payment->amount_minor) && (int)$payment->amount_minor > 0) {
            return round(((int)$payment->amount_minor) / 100, 2);
        }

        return (float)($payment->price ?? 0);
    }

    private function details_to_array(?string $json): array {
        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function add_automation_history(array &$events, array $history): void {
        foreach ($history as $entry) {
            $events[] = new UserProfileTimelineEvent(
                'automation_' . (string)$entry->triggerkey,
                (int)$entry->timecreated,
                $this->automation_title((string)$entry->triggerkey),
                (string)($entry->message ?? ''),
                $this->automation_icon((string)$entry->triggerkey, (string)$entry->status),
                $entry->status === 'failed' ? 'high' : 'normal',
                null,
                [
                    'objecttype' => 'automation',
                    'detailsraw' => [
                        'rule' => (string)$entry->rulekey,
                        'trigger' => (string)$entry->triggerkey,
                        'status' => (string)$entry->status,
                        'entitytype' => (string)($entry->entitytype ?? ''),
                        'entityid' => (int)($entry->entityid ?? 0),
                    ],
                    'actorid' => 0,
                    'rawtype' => 'automation_history',
                ]
            );
        }
    }

    private function automation_icon(string $triggerkey, string $status): string {
        if ($status === 'failed') {
            return '⚠️';
        }

        return match ($triggerkey) {
            'trial_expired' => '🎁',
            'payment_failed' => '💳',
            'digital_purchase_paid' => '📦',
            'subscription_expired' => '📚',
            'note_added' => '📝',
            'tag_added', 'tag_removed' => '🏷️',
            default => '⚙️',
        };
    }

    private function automation_title(string $triggerkey): string {
        return match ($triggerkey) {
            AutomationTriggerKeys::TRIAL_EXPIRED =>
                get_string('crm_automation_trial_expired', 'local_subscriptions'),

            AutomationTriggerKeys::PAYMENT_FAILED =>
                get_string('crm_automation_payment_failed', 'local_subscriptions'),

            AutomationTriggerKeys::DIGITAL_PURCHASE_PAID =>
                get_string('crm_automation_digital_purchase_paid', 'local_subscriptions'),

            AutomationTriggerKeys::SUBSCRIPTION_EXPIRED =>
                get_string('crm_automation_subscription_expired', 'local_subscriptions'),

            AutomationTriggerKeys::NOTE_ADDED =>
                get_string('crm_automation_note_added', 'local_subscriptions'),

            AutomationTriggerKeys::TAG_ADDED =>
                get_string('crm_automation_tag_added', 'local_subscriptions'),

            AutomationTriggerKeys::TAG_REMOVED =>
                get_string('crm_automation_tag_removed', 'local_subscriptions'),

            default =>
                get_string('crm_timeline_automation_executed', 'local_subscriptions'),
        };
    }

}