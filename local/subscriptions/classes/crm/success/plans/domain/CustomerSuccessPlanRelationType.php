<?php

namespace local_subscriptions\crm\success\plans\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * CRM objects that can be attached to a Customer Success plan step.
 */
final class CustomerSuccessPlanRelationType {

    public const RECOMMENDATION =
        'recommendation';

    public const WORK_ITEM =
        'work_item';

    public const INBOX_THREAD =
        'inbox_thread';

    public const PAYMENT =
        'payment';

    public const SUBSCRIPTION =
        'subscription';

    public const COURSE =
        'course';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::RECOMMENDATION,
            self::WORK_ITEM,
            self::INBOX_THREAD,
            self::PAYMENT,
            self::SUBSCRIPTION,
            self::COURSE,
        ];
    }

    public static function is_valid(
        string $type
    ): bool {
        return in_array(
            $type,
            self::all(),
            true
        );
    }
}