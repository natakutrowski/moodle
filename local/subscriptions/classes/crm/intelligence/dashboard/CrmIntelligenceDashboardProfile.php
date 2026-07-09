<?php

namespace local_subscriptions\crm\intelligence\dashboard;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\UserIntelligence;

final class CrmIntelligenceDashboardProfile {

    public function __construct(
        public readonly \stdClass $user,
        public readonly UserIntelligence $intelligence
    ) {
    }
}