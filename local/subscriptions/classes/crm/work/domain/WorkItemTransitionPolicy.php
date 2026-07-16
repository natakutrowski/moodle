<?php

namespace local_subscriptions\crm\work\domain;

defined('MOODLE_INTERNAL') || die();

final class WorkItemTransitionPolicy {

    public static function can_transition(
        string $from,
        string $to
    ): bool {
        if (
            !WorkItemStatus::is_valid($from) ||
            !WorkItemStatus::is_valid($to)
        ) {
            return false;
        }

        if ($from === $to) {
            return true;
        }

        $allowed = [
            WorkItemStatus::OPEN => [
                WorkItemStatus::IN_PROGRESS,
                WorkItemStatus::BLOCKED,
                WorkItemStatus::WAITING,
                WorkItemStatus::RESOLVED,
                WorkItemStatus::CANCELLED,
            ],

            WorkItemStatus::IN_PROGRESS => [
                WorkItemStatus::OPEN,
                WorkItemStatus::BLOCKED,
                WorkItemStatus::WAITING,
                WorkItemStatus::RESOLVED,
                WorkItemStatus::CANCELLED,
            ],

            WorkItemStatus::BLOCKED => [
                WorkItemStatus::OPEN,
                WorkItemStatus::IN_PROGRESS,
                WorkItemStatus::WAITING,
                WorkItemStatus::CANCELLED,
            ],

            WorkItemStatus::WAITING => [
                WorkItemStatus::OPEN,
                WorkItemStatus::IN_PROGRESS,
                WorkItemStatus::BLOCKED,
                WorkItemStatus::RESOLVED,
                WorkItemStatus::CANCELLED,
            ],

            WorkItemStatus::RESOLVED => [
                WorkItemStatus::OPEN,
                WorkItemStatus::CLOSED,
            ],

            WorkItemStatus::CLOSED => [
                WorkItemStatus::OPEN,
            ],

            WorkItemStatus::CANCELLED => [
                WorkItemStatus::OPEN,
            ],
        ];

        return in_array(
            $to,
            $allowed[$from] ?? [],
            true
        );
    }
}