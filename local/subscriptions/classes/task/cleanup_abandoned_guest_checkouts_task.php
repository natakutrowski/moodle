<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutAbandonedCleanupService;

final class cleanup_abandoned_guest_checkouts_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_cleanup_abandoned_guest_checkouts', 'local_subscriptions');
    }

    public function execute(): void {
        if (!(bool)get_config('local_subscriptions', 'guest_checkout_cleanup_enabled')) {
            mtrace('[Guest checkout cleanup] disabled');
            return;
        }

        $days = max(
            1,
            (int)(get_config('local_subscriptions', 'guest_checkout_cleanup_age_days') ?: 30)
        );
        $limit = max(
            1,
            (int)(get_config('local_subscriptions', 'guest_checkout_cleanup_batch_size') ?: 20)
        );

        $result = CommerceGuestCheckoutAbandonedCleanupService::create()->run(
            $days * DAYSECS,
            $limit
        );

        mtrace('[Guest checkout cleanup] ' . json_encode($result, JSON_UNESCAPED_SLASHES));
    }
}
