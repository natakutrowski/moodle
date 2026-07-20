<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEventPresentation;
use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\crm\automation\AutomationTriggerKeys;
use local_subscriptions\subscription_config;

final class UserProfileTimelineBuilder {

    public function build_for_user(
        \stdClass $user,
        int $limit = 40,
        bool $includeinbox = false
    ): array {
        $repository = new UserProfileRepository();

        $events = [];
        $seenlogids = [];

        $this->add_admin_logs(
            $events,
            $seenlogids,
            $repository->get_admin_logs(
                (int)$user->id,
                $limit
            ),
            $includeinbox
        );
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

        if ($includeinbox) {
            $this->add_inbox_messages(
                $events,
                $repository
                    ->get_inbox_messages_for_timeline(
                        (int)$user->id,
                        $limit
                    )
            );
        }

        usort(
            $events,
            static function(
                UserProfileTimelineEvent $a,
                UserProfileTimelineEvent $b
            ): int {
                return
                    $b->timecreated <=>
                    $a->timecreated;
            }
        );

        $events = array_slice(
            $events,
            0,
            $limit
        );

        return $this->enrich_actor_labels(
            $events,
            $repository
        );
    }

    public function to_legacy_objects(array $events): array {
        return array_map(static function(UserProfileTimelineEvent $event): \stdClass {
            return $event->to_object();
        }, $events);
    }

    private function add_admin_logs(
        array &$events,
        array &$seenlogids,
        array $logs,
        bool $includeinbox
    ): void {
        foreach ($logs as $log) {
            $action =
                (string)$log->action;

            if (
                $action ===
                AdminEvents::USER_NOTE_ADDED
            ) {
                continue;
            }

            /*
             * La véritable réponse sortante est affichée depuis
             * la table des messages Inbox afin d’éviter un doublon.
             */
            if (
                $action ===
                AdminEvents::INBOX_REPLY_SENT
            ) {
                continue;
            }

            /*
             * Sans VIEW_INBOX, aucun événement Inbox ne doit être
             * chargé dans la timeline User 360°.
             */
            if (
                !$includeinbox &&
                str_starts_with(
                    $action,
                    'inbox.'
                )
            ) {
                continue;
            }

            $seenlogids[(int)$log->id] = true;

            $details = $this->details_to_array(
                $log->details ?? ''
            );

            $events[] = new UserProfileTimelineEvent(
                AdminEventPresentation::type(
                    $action
                ),
                (int)$log->timecreated,
                AdminEventPresentation::label(
                    $action
                ),
                AdminEventPresentation::description(
                    $action,
                    $details
                ),
                AdminEventPresentation::icon(
                    $action
                ),
                AdminEventPresentation::importance(
                    $action
                ),
                AdminEventPresentation::action_url(
                    $log
                ),
                [
                    'objecttype' =>
                        $log->objecttype ?? null,

                    'objectid' =>
                        !empty($log->objectid)
                            ? (int)$log->objectid
                            : null,

                    'threadid' =>
                        (
                            ($log->objecttype ?? '') ===
                            'inbox_thread'
                        )
                            ? (int)($log->objectid ?? 0)
                            : null,

                    'detailsraw' => $details,
                    'action' => $action,
                    'category' =>
                        AdminEventPresentation::
                            category($action),
                    'logid' => (int)$log->id,
                    'actorid' => (int)$log->actorid,
                    'rawtype' => 'admin_log',
                ]
            );
        }
    }

    private function add_digital_logs(
        array &$events,
        array &$seenlogids,
        array $logs
    ): void {
        foreach ($logs as $log) {
            if (
                !empty(
                    $seenlogids[(int)$log->id]
                )
            ) {
                continue;
            }

            $seenlogids[(int)$log->id] = true;

            $action = (string)$log->action;

            $details = $this->details_to_array(
                $log->details ?? ''
            );

            $events[] = new UserProfileTimelineEvent(
                AdminEventPresentation::type(
                    $action
                ),
                (int)$log->timecreated,
                AdminEventPresentation::label(
                    $action
                ),
                AdminEventPresentation::description(
                    $action,
                    $details
                ),
                AdminEventPresentation::icon(
                    $action
                ),
                AdminEventPresentation::importance(
                    $action
                ),
                AdminEventPresentation::action_url(
                    $log
                ),
                [
                    'objecttype' =>
                        $log->objecttype ?? null,

                    'objectid' =>
                        !empty($log->objectid)
                            ? (int)$log->objectid
                            : null,

                    'detailsraw' => $details,
                    'action' => $action,
                    'category' =>
                        AdminEventPresentation::
                            category($action),
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

    private function add_inbox_messages(
        array &$events,
        array $messages
    ): void {
        foreach ($messages as $message) {
            $direction = strtolower(
                trim(
                    (string)$message->direction
                )
            );

            $isoutbound =
                $direction === 'outbound';

            $action = $isoutbound
                ? AdminEvents::INBOX_REPLY_SENT
                : AdminEvents::INBOX_MESSAGE_RECEIVED;

            $timecreated = (int)(
                $message->receivedat
                ?? $message->sentat
                ?? $message->timecreated
            );

            $subject = trim(
                (string)(
                    $message->subject
                    ?: $message->threadsubject
                    ?: ''
                )
            );

            if ($subject === '') {
                $subject = get_string(
                    'crm_inbox_no_subject',
                    'local_subscriptions'
                );
            }

            $body = trim(
                strip_tags(
                    (string)(
                        $message->bodytext
                        ?: $message->bodyhtml
                        ?: ''
                    )
                )
            );

            $body = shorten_text(
                $body,
                300
            );

            $events[] =
                new UserProfileTimelineEvent(
                    $isoutbound
                        ? 'inbox_message_sent'
                        : 'inbox_message_received',

                    $timecreated,

                    AdminEventPresentation::label(
                        $action
                    ),

                    $body,

                    AdminEventPresentation::icon(
                        $action
                    ),

                    'normal',

                    (
                        new \moodle_url(
                            subscription_config::
                                admin_inbox_thread_page(),
                            [
                                'id' =>
                                    (int)$message->threadid,
                            ]
                        )
                    )->out(false),

                    [
                        'objecttype' =>
                            'inbox_message',

                        'threadid' =>
                            (int)$message->threadid,

                        'messageid' =>
                            (int)$message->id,

                        'direction' =>
                            $direction,

                        'subject' =>
                            $subject,

                        'email' =>
                            (string)(
                                $message->contactemail
                                ?? ''
                            ),

                        'contactname' =>
                            (string)(
                                $message->contactname
                                ?? ''
                            ),

                        'actorid' =>
                            (int)(
                                $message->createdby
                                ?? 0
                            ),

                        'action' =>
                            $action,

                        'category' =>
                            AdminEventPresentation::
                                category($action),

                        'rawtype' =>
                            'inbox_message',
                    ]
                );
        }
    }

    /**
     * Adds resolved actor labels to timeline event metadata.
     *
     * @param UserProfileTimelineEvent[] $events
     * @return UserProfileTimelineEvent[]
     */
    private function enrich_actor_labels(
        array $events,
        UserProfileRepository $repository
    ): array {
        $actorids = [];

        foreach ($events as $event) {
            $actorid = (int)(
                $event->metadata['actorid']
                ?? 0
            );

            if ($actorid > 0) {
                $actorids[] = $actorid;
            }
        }

        if ($actorids === []) {
            return $events;
        }

        $actors =
            $repository->get_timeline_actors(
                $actorids
            );

        return array_map(
            function(
                UserProfileTimelineEvent $event
            ) use ($actors): UserProfileTimelineEvent {
                $metadata = $event->metadata;

                $actorid = (int)(
                    $metadata['actorid']
                    ?? 0
                );

                if ($actorid <= 0) {
                    return $event;
                }

                $metadata['actorname'] =
                    $this->resolve_actor_name(
                        $actorid,
                        $actors
                    );

                return new UserProfileTimelineEvent(
                    type:
                        $event->type,

                    timecreated:
                        $event->timecreated,

                    title:
                        $event->title,

                    description:
                        $event->description,

                    icon:
                        $event->icon,

                    importance:
                        $event->importance,

                    actionurl:
                        $event->actionurl,

                    metadata:
                        $metadata
                );
            },
            $events
        );
    }

    /**
     * Resolves the display name of a timeline actor.
     *
     * @param array<int, \stdClass> $actors
     */
    private function resolve_actor_name(
        int $actorid,
        array $actors
    ): string {
        if (!isset($actors[$actorid])) {
            return '';
        }

        $actor = $actors[$actorid];

        $name = trim(
            (string)($actor->firstname ?? '') .
            ' ' .
            (string)($actor->lastname ?? '')
        );

        if ($name !== '') {
            return $name;
        }

        $email = trim(
            (string)($actor->email ?? '')
        );

        if ($email !== '') {
            return $email;
        }

        return '#' . $actorid;
    }

}