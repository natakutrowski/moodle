<?php

namespace local_subscriptions\crm\intelligence\dashboard;

defined('MOODLE_INTERNAL') || die();

final class CrmIntelligenceDashboardOverview {

    public function __construct(
        public readonly int $analysedUsers,
        public readonly int $hotLeads,
        public readonly int $atRisk,
        public readonly int $vip,
        public readonly int $trialOpportunities,
        public readonly int $upgradeOpportunities,
        public readonly array $priorityProfiles = []
    ) {
    }
}