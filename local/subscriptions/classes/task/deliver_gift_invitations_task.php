<?php

declare(strict_types=1);

namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\gift\CommerceGiftDeliveryDispatchService;

/** Moodle cron: no-op unless both the DEV E2E gate and delivery switch are enabled. */
final class deliver_gift_invitations_task extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('cfr_gift_g4b2_task', 'local_subscriptions');
    }

    public function execute(): void {
        if (!CommerceGiftDeliveryDispatchService::enabled()) { return; }
        global $DB;
        try {
            $counts = (new CommerceGiftDeliveryDispatchService($DB))->process_due();
            mtrace('[campusfr][gift_delivery] processed=' . $counts['processed'] .
                ' sent=' . $counts['sent'] . ' failed=' . $counts['failed'] .
                ' review=' . $counts['review']);
        } catch (\Throwable $e) {
            // Do not leak recipient, note, gift token or SMTP diagnostics into shared logs.
            mtrace('[campusfr][gift_delivery] halted; class=' . get_class($e));
            throw new \RuntimeException('DEV gift dispatch halted for manual inspection.', 0, $e);
        }
    }
}
