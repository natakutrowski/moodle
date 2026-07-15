<?php

namespace local_subscriptions\crm\inbox\rendering;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxPriority;
use local_subscriptions\crm\inbox\domain\InboxThreadStatus;

final class InboxValuePresentation {

    public static function status_label(
        string $status
    ): string {
        $status = trim($status);

        $stringkey = match ($status) {
            InboxThreadStatus::OPEN =>
                'crm_inbox_status_open',

            InboxThreadStatus::PENDING =>
                'crm_inbox_status_pending',

            InboxThreadStatus::RESOLVED =>
                'crm_inbox_status_resolved',

            InboxThreadStatus::CLOSED =>
                'crm_inbox_status_closed',

            InboxThreadStatus::SPAM =>
                'crm_inbox_status_spam',

            default =>
                null,
        };

        if ($stringkey === null) {
            return get_string(
                'crm_inbox_status_unknown',
                'local_subscriptions'
            );
        }

        return get_string(
            $stringkey,
            'local_subscriptions'
        );
    }

    public static function priority_label(
        string $priority
    ): string {
        $priority = trim($priority);

        $stringkey = match ($priority) {
            InboxPriority::LOW =>
                'crm_inbox_priority_low',

            InboxPriority::NORMAL =>
                'crm_inbox_priority_normal',

            InboxPriority::HIGH =>
                'crm_inbox_priority_high',

            InboxPriority::URGENT =>
                'crm_inbox_priority_urgent',

            default =>
                null,
        };

        if ($stringkey === null) {
            return get_string(
                'crm_inbox_priority_unknown',
                'local_subscriptions'
            );
        }

        return get_string(
            $stringkey,
            'local_subscriptions'
        );
    }
}