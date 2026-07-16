<?php

namespace local_subscriptions\crm\work\rendering;

defined('MOODLE_INTERNAL') || die();

final class WorkItemPresentation {

    public static function status_label(string $status): string {
        return get_string('crm_work_status_' . $status, 'local_subscriptions');
    }

    public static function priority_label(string $priority): string {
        return get_string('crm_work_priority_' . $priority, 'local_subscriptions');
    }

    public static function type_label(string $type): string {
        return get_string('crm_work_type_' . $type, 'local_subscriptions');
    }

    public static function status_class(string $status): string {
        return match ($status) {
            'resolved', 'closed' => 'bg-success',
            'blocked' => 'bg-danger',
            'waiting' => 'bg-warning text-dark',
            'cancelled' => 'bg-secondary',
            'in_progress' => 'bg-primary',
            default => 'bg-info text-dark',
        };
    }

    public static function priority_class(string $priority): string {
        return match ($priority) {
            'critical' => 'bg-dark',
            'urgent' => 'bg-danger',
            'high' => 'bg-warning text-dark',
            'low' => 'bg-light text-dark border',
            default => 'bg-secondary',
        };
    }
}