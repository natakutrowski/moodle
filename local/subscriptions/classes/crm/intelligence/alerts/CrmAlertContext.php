<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

/**
 * Related operational context for one CRM alert user.
 */
final class CrmAlertContext {

    public function __construct(
        public readonly int $userid,
        public readonly ?\stdClass $workitem = null,
        public readonly ?\stdClass $customersuccessplan = null
    ) {
    }

    public function has_work_item(): bool {
        return
            $this->workitem !== null &&
            !empty($this->workitem->id);
    }

    public function has_customer_success_plan(): bool {
        return
            $this->customersuccessplan !== null &&
            !empty(
                $this->customersuccessplan->id
            );
    }
}