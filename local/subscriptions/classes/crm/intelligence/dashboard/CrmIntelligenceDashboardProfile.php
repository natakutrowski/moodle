<?php

namespace local_subscriptions\crm\intelligence\dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Read model for one priority profile displayed on the CRM Dashboard.
 *
 * This object contains presentation data read from persisted snapshots.
 * It must not contain a runtime UserIntelligence instance.
 */
final class CrmIntelligenceDashboardProfile {

    public function __construct(
        public readonly \stdClass $user,
        public readonly int $globalScore,
        public readonly int $snapshotTime,
        public readonly ?\stdClass $inbox = null
    ) {
    }
}