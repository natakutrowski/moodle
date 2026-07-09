<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\dashboard\CrmIntelligenceDashboardRepository;
use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;
use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;

final class CrmAlertBuilder {

    public function __construct(
        private readonly CrmIntelligenceDashboardRepository $repository = new CrmIntelligenceDashboardRepository(),
        private readonly UserIntelligenceBuilder $intelligenceBuilder = new UserIntelligenceBuilder(),
        private readonly CrmAlertEngine $alertEngine = new CrmAlertEngine()
    ) {
    }

    public function build(int $limit = CrmIntelligenceLimits::DASHBOARD_ALERT_USERS): array {
        $alerts = [];

        foreach ($this->repository->get_candidate_users($limit) as $user) {
            $intelligence = $this->intelligenceBuilder->build_for_user($user, false);

            foreach ($this->alertEngine->detect($user, $intelligence) as $alert) {
                $alerts[] = $alert;
            }
        }

        usort($alerts, static fn($a, $b): int => $b->priority <=> $a->priority);

        return array_slice($alerts, 0, CrmIntelligenceLimits::DASHBOARD_ALERTS);
    }
}