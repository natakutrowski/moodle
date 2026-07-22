<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves the functional category of a User360 timeline event.
 */
final class UserProfileTimelineCategory {

    public const ALL = 'all';
    public const COMMERCIAL = 'commercial';
    public const LEARNING = 'learning';
    public const INBOX = 'inbox';
    public const NOTES = 'notes';
    public const WORK = 'work';
    public const CUSTOMER_SUCCESS = 'customer_success';
    public const AUTOMATION = 'automation';
    public const ADMINISTRATION = 'administration';

    /**
     * Returns all categories displayed in the filter toolbar.
     *
     * @return string[]
     */
    public static function values(): array {
        return [
            self::COMMERCIAL,
            self::LEARNING,
            self::INBOX,
            self::NOTES,
            self::WORK,
            self::CUSTOMER_SUCCESS,
            self::AUTOMATION,
            self::ADMINISTRATION,
        ];
    }

    /**
     * Resolves one event category.
     */
    public static function resolve(
        \stdClass $event
    ): string {
        $explicit = self::normalise(
            (string)($event->category ?? '')
        );

        if ($explicit !== null) {
            return $explicit;
        }

        $type = strtolower(
            trim(
                (string)($event->type ?? '')
            )
        );

        $rawtype = strtolower(
            trim(
                (string)($event->rawtype ?? '')
            )
        );

        $action = strtolower(
            trim(
                (string)($event->action ?? '')
            )
        );

        $objecttype = strtolower(
            trim(
                (string)($event->objecttype ?? '')
            )
        );

        if (
            str_starts_with($type, 'inbox_')
            || $rawtype === 'inbox_message'
            || str_starts_with($action, 'inbox.')
            || str_contains($objecttype, 'inbox')
        ) {
            return self::INBOX;
        }

        if (
            $rawtype === 'note'
            || $type === 'note_added'
            || str_contains($type, 'note')
            || str_contains($type, 'tag')
            || str_contains($action, 'note')
            || str_contains($action, 'tag')
        ) {
            return self::NOTES;
        }

        if (
            $rawtype === 'automation_history'
            || str_starts_with($type, 'automation_')
            || str_starts_with($action, 'automation.')
        ) {
            return self::AUTOMATION;
        }

        if (
            str_contains($type, 'work_item')
            || str_contains($objecttype, 'work_item')
            || str_starts_with($action, 'work.')
            || str_starts_with($action, 'work_item.')
        ) {
            return self::WORK;
        }

        if (
            str_contains($type, 'customer_success')
            || str_contains($type, 'success_plan')
            || str_contains($objecttype, 'customer_success')
            || str_contains($objecttype, 'success_plan')
            || str_starts_with($action, 'customer_success.')
            || str_starts_with($action, 'success_plan.')
        ) {
            return self::CUSTOMER_SUCCESS;
        }

        if (
            str_contains($type, 'course')
            || str_contains($type, 'progress')
            || str_contains($type, 'activity')
            || str_contains($type, 'lesson')
            || str_contains($type, 'levelup')
            || str_contains($type, 'xp')
            || str_contains($objecttype, 'course')
            || str_contains($objecttype, 'activity')
            || str_starts_with($action, 'course.')
            || str_starts_with($action, 'learning.')
        ) {
            return self::LEARNING;
        }

        if (
            str_contains($type, 'subscription')
            || str_contains($type, 'trial')
            || str_contains($type, 'purchase')
            || str_contains($type, 'payment')
            || str_contains($type, 'digital')
            || str_contains($objecttype, 'subscription')
            || str_contains($objecttype, 'purchase')
            || str_contains($objecttype, 'payment')
            || str_contains($action, 'subscription')
            || str_contains($action, 'trial')
            || str_contains($action, 'purchase')
            || str_contains($action, 'payment')
        ) {
            return self::COMMERCIAL;
        }

        return self::ADMINISTRATION;
    }

    /**
     * Returns the translated category label.
     */
    public static function label(
        string $category
    ): string {
        return get_string(
            'crm_timeline_category_' . $category,
            'local_subscriptions'
        );
    }

    /**
     * Returns the visual category icon.
     */
    public static function icon(
        string $category
    ): string {
        return match ($category) {
            self::COMMERCIAL => '💳',
            self::LEARNING => '🎓',
            self::INBOX => '✉️',
            self::NOTES => '📝',
            self::WORK => '✅',
            self::CUSTOMER_SUCCESS => '🤝',
            self::AUTOMATION => '⚙️',
            self::ADMINISTRATION => '🛠️',
            default => '🕒',
        };
    }

    /**
     * Normalises categories already supplied by event providers.
     */
    private static function normalise(
        string $category
    ): ?string {
        $category = strtolower(
            trim($category)
        );

        return match ($category) {
            'commercial',
            'subscriptions',
            'subscription',
            'purchases',
            'purchase',
            'payments',
            'payment' =>
                self::COMMERCIAL,

            'learning',
            'course',
            'courses',
            'education',
            'pedagogy',
            'pedagogical' =>
                self::LEARNING,

            'inbox',
            'email',
            'emails',
            'support' =>
                self::INBOX,

            'note',
            'notes',
            'tag',
            'tags' =>
                self::NOTES,

            'work',
            'work_item',
            'work_items' =>
                self::WORK,

            'customer_success',
            'success',
            'success_plan',
            'success_plans' =>
                self::CUSTOMER_SUCCESS,

            'automation',
            'automations' =>
                self::AUTOMATION,

            'administration',
            'admin',
            'other' =>
                self::ADMINISTRATION,

            default => null,
        };
    }
}