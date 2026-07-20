<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Central presentation registry for unified CRM administrative events.
 *
 * This class is presentation-only:
 * - no database query;
 * - no repository access;
 * - no service execution;
 * - no business recalculation.
 */
final class AdminEventPresentation {

    public const IMPORTANCE_NORMAL = 'normal';
    public const IMPORTANCE_MEDIUM = 'medium';
    public const IMPORTANCE_HIGH = 'high';
    public const IMPORTANCE_CRITICAL = 'critical';

    /**
     * Return the translated human-readable event label.
     */
    public static function label(
        string $event
    ): string {
        $stringkey =
            self::string_key($event);

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return self::fallback_label($event);
    }

    /**
     * Return the decorative event icon.
     */
    public static function icon(
        string $event
    ): string {
        return match ($event) {
            // Emails.
            AdminEvents::EMAIL_CUSTOM_SENT,
            AdminEvents::EMAIL_PASSWORD_RESET_NOTICE_SENT,
            AdminEvents::EMAIL_WELCOME_SENT,
            AdminEvents::EMAIL_RECEIPT_SENT,
            AdminEvents::EMAIL_SUBSCRIPTION_ACCESS_SENT =>
                '✉️',

            // Users.
            AdminEvents::USER_PASSWORD_UPDATED =>
                '🔑',

            AdminEvents::USER_NOTE_ADDED =>
                '📝',

            AdminEvents::USER_SUSPENDED =>
                '⏸️',

            AdminEvents::USER_REACTIVATED =>
                '▶️',

            // Subscriptions.
            AdminEvents::SUBSCRIPTION_CREATED,
            AdminEvents::SUBSCRIPTION_CREATED_MANUAL,
            AdminEvents::SUBSCRIPTION_CREATED_AUTO =>
                '📚',

            AdminEvents::SUBSCRIPTION_UPDATED,
            AdminEvents::SUBSCRIPTION_STATUS_UPDATED,
            AdminEvents::SUBSCRIPTION_DATES_UPDATED =>
                '✏️',

            AdminEvents::SUBSCRIPTION_EXTENDED =>
                '🗓️',

            AdminEvents::SUBSCRIPTION_DELETED =>
                '🗑️',

            // Digital.
            AdminEvents::DIGITAL_PURCHASE_CREATED =>
                '📦',

            AdminEvents::DIGITAL_PURCHASE_PAID =>
                '💳',

            AdminEvents::DIGITAL_PURCHASE_FAILED =>
                '⚠️',

            AdminEvents::DIGITAL_PURCHASE_CANCELLED =>
                '🚫',

            AdminEvents::DIGITAL_LINK_RESENT =>
                '✉️',

            AdminEvents::DIGITAL_TOKEN_REGENERATED =>
                '🔗',

            AdminEvents::DIGITAL_TOKEN_EXTENDED =>
                '🕒',

            AdminEvents::DIGITAL_PROVIDER_CHECKED =>
                '🔎',

            // Payments.
            AdminEvents::PAYMENT_REQUEST_CREATED =>
                '🧾',

            AdminEvents::PAYMENT_REQUEST_PAID =>
                '💳',

            AdminEvents::PAYMENT_REQUEST_FAILED =>
                '⚠️',

            AdminEvents::PAYMENT_REQUEST_CANCELLED =>
                '🚫',

            // Trials.
            AdminEvents::TRIAL_STARTED =>
                '🎁',

            AdminEvents::TRIAL_EXPIRED =>
                '⌛',

            // Inbox.
            AdminEvents::INBOX_MESSAGE_RECEIVED =>
                '📥',

            AdminEvents::INBOX_REPLY_SENT =>
                '📤',

            AdminEvents::INBOX_THREAD_ASSIGNED =>
                '👤',

            AdminEvents::INBOX_THREAD_UNASSIGNED =>
                '👥',

            AdminEvents::INBOX_THREAD_STATUS_CHANGED =>
                '🔄',

            AdminEvents::INBOX_THREAD_PRIORITY_CHANGED =>
                '🚩',

            AdminEvents::INBOX_AI_ANALYSIS_EXECUTED =>
                '✨',

            AdminEvents::INBOX_AI_REPLY_SUGGESTED =>
                '💡',

            // Work Items.
            AdminEvents::WORK_ITEM_CREATED =>
                '✅',

            AdminEvents::WORK_ITEM_STATUS_CHANGED =>
                '🔄',

            AdminEvents::WORK_ITEM_PRIORITY_CHANGED =>
                '🚩',

            AdminEvents::WORK_ITEM_ASSIGNED =>
                '👤',

            AdminEvents::WORK_ITEM_COMMENT_ADDED =>
                '💬',

            AdminEvents::WORK_ITEM_LINKED =>
                '🔗',

            AdminEvents::WORK_ITEM_SUGGESTION_OPENED =>
                '💡',

            AdminEvents::WORK_ITEM_CREATED_FROM_RECOMMENDATION =>
                '✨',

            AdminEvents::WORK_ITEM_DUPLICATE_OVERRIDE =>
                '⚠️',

            // Recommendations.
            AdminEvents::RECOMMENDATION_CREATED =>
                '💡',

            AdminEvents::RECOMMENDATION_REFRESHED =>
                '🔄',

            AdminEvents::RECOMMENDATION_ACCEPTED =>
                '👍',

            AdminEvents::RECOMMENDATION_DISMISSED =>
                '🙅',

            AdminEvents::RECOMMENDATION_COMPLETED =>
                '✅',

            AdminEvents::RECOMMENDATION_EXPIRED =>
                '⌛',

            AdminEvents::RECOMMENDATION_RUN_COMPLETED =>
                '⚙️',

            AdminEvents::RECOMMENDATION_RUN_PARTIAL =>
                '⚠️',

            AdminEvents::RECOMMENDATION_RUN_FAILED =>
                '❌',

            AdminEvents::RECOMMENDATION_RUN_SKIPPED =>
                '⏭️',

            // Customer Success.
            AdminEvents::CUSTOMER_SUCCESS_PLAN_CREATED =>
                '🧭',

            AdminEvents::CUSTOMER_SUCCESS_PLAN_ACTIVATED =>
                '▶️',

            AdminEvents::CUSTOMER_SUCCESS_PLAN_PAUSED =>
                '⏸️',

            AdminEvents::CUSTOMER_SUCCESS_PLAN_CANCELLED =>
                '🚫',

            AdminEvents::CUSTOMER_SUCCESS_PLAN_COMPLETED =>
                '✅',

            AdminEvents::CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED =>
                '🎯',

            AdminEvents::CUSTOMER_SUCCESS_STEP_STARTED =>
                '🚀',

            AdminEvents::CUSTOMER_SUCCESS_STEP_COMPLETED =>
                '✔️',

            AdminEvents::CUSTOMER_SUCCESS_STEP_SKIPPED =>
                '⏭️',

            AdminEvents::CUSTOMER_SUCCESS_STEP_BLOCKED =>
                '⛔',

            AdminEvents::CUSTOMER_SUCCESS_STEP_UNBLOCKED =>
                '🔓',

            default =>
                '⚙️',
        };
    }

    /**
     * Return the broad visual category.
     */
    public static function category(
        string $event
    ): string {
        if (
            str_starts_with(
                $event,
                'customer_success.'
            )
        ) {
            return 'customer-success';
        }

        if (
            str_starts_with(
                $event,
                'recommendation.'
            )
        ) {
            return 'recommendation';
        }

        if (
            str_starts_with(
                $event,
                'work_item.'
            )
        ) {
            return 'work';
        }

        if (
            str_starts_with(
                $event,
                'inbox.'
            )
        ) {
            return 'inbox';
        }

        if (
            str_starts_with(
                $event,
                'digital.'
            )
        ) {
            return 'digital';
        }

        if (
            str_starts_with(
                $event,
                'payment_request.'
            )
        ) {
            return 'payment';
        }

        if (
            str_starts_with(
                $event,
                'trial.'
            )
        ) {
            return 'trial';
        }

        if (
            str_starts_with(
                $event,
                'email.'
            )
        ) {
            return 'email';
        }

        if (
            str_starts_with(
                $event,
                'user.'
            )
        ) {
            return 'user';
        }

        if (
            str_starts_with(
                $event,
                'subscription.'
            )
        ) {
            return 'subscription';
        }

        if (
            str_starts_with(
                $event,
                'automation.'
            )
        ) {
            return 'automation';
        }

        return 'system';
    }

    /**
     * Return a stable Timeline event type.
     */
    public static function type(
        string $event
    ): string {
        $type = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            strtolower(trim($event))
        );

        $type = trim(
            (string)$type,
            '_'
        );

        return $type !== ''
            ? $type
            : 'admin_log';
    }

    /**
     * Return the business importance level.
     */
    public static function importance(
        string $event
    ): string {
        return match ($event) {
            AdminEvents::RECOMMENDATION_RUN_FAILED,
            AdminEvents::CUSTOMER_SUCCESS_STEP_BLOCKED =>
                self::IMPORTANCE_CRITICAL,

            AdminEvents::USER_SUSPENDED,
            AdminEvents::SUBSCRIPTION_DELETED,
            AdminEvents::DIGITAL_PURCHASE_FAILED,
            AdminEvents::DIGITAL_PURCHASE_CANCELLED,
            AdminEvents::PAYMENT_REQUEST_FAILED,
            AdminEvents::PAYMENT_REQUEST_CANCELLED,
            AdminEvents::TRIAL_EXPIRED,
            AdminEvents::INBOX_THREAD_PRIORITY_CHANGED,
            AdminEvents::WORK_ITEM_DUPLICATE_OVERRIDE,
            AdminEvents::RECOMMENDATION_RUN_PARTIAL =>
                self::IMPORTANCE_HIGH,

            AdminEvents::SUBSCRIPTION_CREATED,
            AdminEvents::SUBSCRIPTION_CREATED_MANUAL,
            AdminEvents::SUBSCRIPTION_CREATED_AUTO,
            AdminEvents::SUBSCRIPTION_EXTENDED,
            AdminEvents::DIGITAL_PURCHASE_PAID,
            AdminEvents::PAYMENT_REQUEST_PAID,
            AdminEvents::TRIAL_STARTED,
            AdminEvents::INBOX_THREAD_ASSIGNED,
            AdminEvents::INBOX_AI_REPLY_SUGGESTED,
            AdminEvents::WORK_ITEM_CREATED,
            AdminEvents::WORK_ITEM_STATUS_CHANGED,
            AdminEvents::WORK_ITEM_PRIORITY_CHANGED,
            AdminEvents::WORK_ITEM_ASSIGNED,
            AdminEvents::WORK_ITEM_CREATED_FROM_RECOMMENDATION,
            AdminEvents::RECOMMENDATION_ACCEPTED,
            AdminEvents::RECOMMENDATION_COMPLETED,
            AdminEvents::CUSTOMER_SUCCESS_PLAN_CREATED,
            AdminEvents::CUSTOMER_SUCCESS_PLAN_ACTIVATED,
            AdminEvents::CUSTOMER_SUCCESS_PLAN_COMPLETED,
            AdminEvents::CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED,
            AdminEvents::CUSTOMER_SUCCESS_STEP_STARTED,
            AdminEvents::CUSTOMER_SUCCESS_STEP_COMPLETED =>
                self::IMPORTANCE_MEDIUM,

            default =>
                self::IMPORTANCE_NORMAL,
        };
    }

    /**
     * Build a concise business description from event details.
     *
     * @param array<string,mixed> $details
     */
    public static function description(
        string $event,
        array $details
    ): string {
        $subject = self::detail_text(
            $details,
            'subject'
        );

        if (
            str_starts_with(
                $event,
                'inbox.'
            ) &&
            $subject !== ''
        ) {
            return $subject;
        }

        if (
            $event ===
                AdminEvents::EMAIL_CUSTOM_SENT &&
            $subject !== ''
        ) {
            return $subject;
        }

        $reference = self::detail_text(
            $details,
            'reference'
        );

        if (
            str_starts_with(
                $event,
                'work_item.'
            ) &&
            $reference !== ''
        ) {
            return get_string(
                'admin_event_description_reference',
                'local_subscriptions',
                $reference
            );
        }

        $planreference = self::detail_text(
            $details,
            'planreference'
        );

        $plantitle = self::detail_text(
            $details,
            'plantitle'
        );

        $steptitle = self::detail_text(
            $details,
            'steptitle'
        );

        if (
            str_starts_with(
                $event,
                'customer_success.step.'
            ) &&
            $steptitle !== ''
        ) {
            return get_string(
                'admin_event_description_cs_step',
                'local_subscriptions',
                (object)[
                    'plan' =>
                        $planreference,
                    'step' =>
                        $steptitle,
                ]
            );
        }

        if (
            str_starts_with(
                $event,
                'customer_success.plan.'
            ) &&
            (
                $planreference !== '' ||
                $plantitle !== ''
            )
        ) {
            return get_string(
                'admin_event_description_cs_plan',
                'local_subscriptions',
                (object)[
                    'reference' =>
                        $planreference,
                    'title' =>
                        $plantitle,
                ]
            );
        }

        $recommendationkey =
            self::detail_text(
                $details,
                'recommendationkey'
            );

        if (
            str_starts_with(
                $event,
                'recommendation.'
            ) &&
            $recommendationkey !== ''
        ) {
            return get_string(
                'admin_event_description_recommendation',
                'local_subscriptions',
                self::humanize_value(
                    $recommendationkey
                )
            );
        }

        $oldstatus = self::detail_text(
            $details,
            'oldstatus'
        );

        $newstatus = self::detail_text(
            $details,
            'newstatus'
        );

        if (
            $oldstatus !== '' &&
            $newstatus !== ''
        ) {
            return get_string(
                'admin_event_description_transition',
                'local_subscriptions',
                (object)[
                    'from' =>
                        self::humanize_value(
                            $oldstatus
                        ),
                    'to' =>
                        self::humanize_value(
                            $newstatus
                        ),
                ]
            );
        }

        $oldpriority = self::detail_text(
            $details,
            'oldpriority'
        );

        $newpriority = self::detail_text(
            $details,
            'newpriority'
        );

        if (
            $oldpriority !== '' &&
            $newpriority !== ''
        ) {
            return get_string(
                'admin_event_description_transition',
                'local_subscriptions',
                (object)[
                    'from' =>
                        self::humanize_value(
                            $oldpriority
                        ),
                    'to' =>
                        self::humanize_value(
                            $newpriority
                        ),
                ]
            );
        }

        $status = self::detail_text(
            $details,
            'status'
        );

        if ($status !== '') {
            return get_string(
                'admin_event_description_status',
                'local_subscriptions',
                self::humanize_value($status)
            );
        }

        $priority = self::detail_text(
            $details,
            'priority'
        );

        if ($priority !== '') {
            return get_string(
                'admin_event_description_priority',
                'local_subscriptions',
                self::humanize_value($priority)
            );
        }

        $plan = self::detail_text(
            $details,
            'plan'
        );

        if ($plan !== '') {
            return get_string(
                'admin_event_description_plan',
                'local_subscriptions',
                $plan
            );
        }

        $contactemail = self::detail_text(
            $details,
            'contactemail'
        );

        if ($contactemail !== '') {
            return get_string(
                'admin_event_description_contact',
                'local_subscriptions',
                $contactemail
            );
        }

        return '';
    }

    /**
     * Decode the JSON details stored by AdminLog.
     *
     * @return array<string,mixed>
     */
    public static function details(
        \stdClass $log
    ): array {
        $raw = $log->details ?? null;

        if (is_array($raw)) {
            return $raw;
        }

        if (
            !is_string($raw) ||
            trim($raw) === ''
        ) {
            return [];
        }

        $decoded = json_decode(
            $raw,
            true
        );

        return is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * Resolve the main contextual URL for one event.
     */
    public static function url(
        \stdClass $log
    ): ?moodle_url {
        $objecttype = trim(
            (string)($log->objecttype ?? '')
        );

        $objectid = (int)(
            $log->objectid ?? 0
        );

        $targetuserid = (int)(
            $log->targetuserid ?? 0
        );

        $details = self::details($log);

        if (
            $objecttype === 'inbox_thread' &&
            $objectid > 0
        ) {
            return new moodle_url(
                subscription_config::
                    admin_inbox_thread_page(),
                [
                    'id' => $objectid,
                ]
            );
        }

        if (
            $objecttype === 'work_item' &&
            $objectid > 0
        ) {
            return new moodle_url(
                subscription_config::
                    admin_work_item_view_page(),
                [
                    'id' => $objectid,
                ]
            );
        }

        if (
            $objecttype ===
                'customer_success_plan' &&
            $objectid > 0
        ) {
            return new moodle_url(
                subscription_config::
                    admin_customer_success_plan_page(),
                [
                    'id' => $objectid,
                ]
            );
        }

        if (
            $objecttype ===
                'customer_success_step'
        ) {
            $planid = (int)(
                $details['planid'] ?? 0
            );

            if ($planid > 0) {
                return new moodle_url(
                    subscription_config::
                        admin_customer_success_plan_page(),
                    [
                        'id' => $planid,
                    ]
                );
            }
        }

        if (
            $objecttype ===
                'digital_purchase' &&
            $objectid > 0
        ) {
            return new moodle_url(
                subscription_config::
                    digital_purchase_view_admin_page(),
                [
                    'id' => $objectid,
                ]
            );
        }

        if (
            $objecttype === 'subscription' &&
            $objectid > 0
        ) {
            return new moodle_url(
                subscription_config::
                    user_subscription_view_page(),
                [
                    'id' => $objectid,
                ]
            );
        }

        if (
            $objecttype ===
                'recommendation'
        ) {
            return new moodle_url(
                subscription_config::
                    admin_crm_assistant_page()
            );
        }

        if ($targetuserid > 0) {
            return new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                [
                    'id' => $targetuserid,
                ]
            );
        }

        return null;
    }

    /**
     * Resolve the contextual URL as a non-escaped string.
     */
    public static function action_url(
        \stdClass $log
    ): ?string {
        $url = self::url($log);

        return $url !== null
            ? $url->out(false)
            : null;
    }

    /**
     * Whether the event is part of the CRM Inbox family.
     */
    public static function is_inbox_event(
        string $event
    ): bool {
        return str_starts_with(
            $event,
            'inbox.'
        );
    }

    /**
     * Generate the expected Moodle language key.
     */
    public static function string_key(
        string $event
    ): string {
        $suffix = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            strtolower(trim($event))
        );

        $suffix = trim(
            (string)$suffix,
            '_'
        );

        return 'admin_event_' . $suffix;
    }

    /**
     * Return a safe text detail.
     *
     * @param array<string,mixed> $details
     */
    private static function detail_text(
        array $details,
        string $key
    ): string {
        if (
            !array_key_exists(
                $key,
                $details
            ) ||
            !is_scalar($details[$key])
        ) {
            return '';
        }

        return trim(
            (string)$details[$key]
        );
    }

    private static function humanize_value(
        string $value
    ): string {
        $value = str_replace(
            [
                '_',
                '-',
                '.',
            ],
            ' ',
            trim($value)
        );

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        return ucfirst(
            trim((string)$value)
        );
    }

    private static function fallback_label(
        string $event
    ): string {
        $label = self::humanize_value(
            $event
        );

        if ($label === '') {
            return get_string(
                'admin_event_unknown',
                'local_subscriptions'
            );
        }

        return $label;
    }

    private function __construct() {
    }
}