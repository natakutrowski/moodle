<?php

namespace local_subscriptions\crm\intelligence\priority;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\dashboard\CrmIntelligenceDashboardRepository;
use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;
use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;

final class DailyPriorityBuilder {

    public function __construct(
        private readonly CrmIntelligenceDashboardRepository $users = new CrmIntelligenceDashboardRepository(),
        private readonly UserIntelligenceBuilder $builder = new UserIntelligenceBuilder()
    ) {
    }

    public function build(int $limit = CrmIntelligenceLimits::DASHBOARD_PRIORITY_USERS): array {
        $priorities = [];

        foreach ($this->users->get_candidate_users($limit) as $user) {
            $intelligence = $this->builder->build_for_user($user, false);

            foreach ($intelligence->recommendations as $recommendation) {
                $priorities[] = new DailyPriority(
                    (int)$user->id,
                    $recommendation->key,
                    $recommendation->priority,
                    $recommendation->action?->action ?? 'open_user_profile'
                );
            }
        }

        usort($priorities, static fn($a, $b): int => $b->score <=> $a->score);

        return array_slice($priorities, 0, CrmIntelligenceLimits::DASHBOARD_PRIORITIES);
    }
}