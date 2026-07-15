<?php

namespace local_subscriptions\crm\intelligence\dashboard;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;
use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;
use local_subscriptions\crm\intelligence\inbox\CrmIntelligenceInboxRepository;

final class CrmIntelligenceDashboardBuilder {

    public function __construct(
        private readonly CrmIntelligenceDashboardRepository $repository = new CrmIntelligenceDashboardRepository(),
        private readonly UserIntelligenceBuilder $userIntelligenceBuilder = new UserIntelligenceBuilder(),
        private readonly CrmIntelligenceInboxRepository $inboxRepository = new CrmIntelligenceInboxRepository()
    ) {
    }

    public function build(
        int $limit =
            CrmIntelligenceLimits::DASHBOARD_USERS,
        bool $includeinbox = false
    ): CrmIntelligenceDashboardOverview {
        $users = $this->repository->get_candidate_users($limit);

        $hotleads = 0;
        $atrisk = 0;
        $vip = 0;
        $trialopportunities = 0;
        $upgradeopportunities = 0;
        $priorityprofiles = [];

        foreach ($users as $user) {
            $intelligence = $this->userIntelligenceBuilder->build_for_user($user, false);

            foreach ($intelligence->segments as $segment) {
                if ($segment->key === 'hot_lead') {
                    $hotleads++;
                }

                if ($segment->key === 'at_risk') {
                    $atrisk++;
                }

                if ($segment->key === 'vip') {
                    $vip++;
                }
            }

            foreach ($intelligence->opportunities as $opportunity) {
                if ($opportunity->key === 'trial_to_purchase') {
                    $trialopportunities++;
                }

                if ($opportunity->key === 'upgrade_subscription') {
                    $upgradeopportunities++;
                }
            }

            if (
                $intelligence->leadScore->global() >= 40 ||
                !empty($intelligence->recommendations) ||
                !empty($intelligence->opportunities)
            ) {
                $priorityprofiles[] = new CrmIntelligenceDashboardProfile($user, $intelligence);
            }
        }

        usort($priorityprofiles, static function($a, $b): int {
            return $b->intelligence->leadScore->global() <=> $a->intelligence->leadScore->global();
        });

        $priorityprofiles = array_slice(
            $priorityprofiles,
            0,
            CrmIntelligenceLimits::DASHBOARD_PROFILES
        );

        if (
            $includeinbox &&
            $priorityprofiles
        ) {
            $inboxbyuser =
                $this->inboxRepository
                    ->get_by_userids(
                        array_map(
                            static fn(
                                CrmIntelligenceDashboardProfile
                                    $profile
                            ): int =>
                                (int)$profile->user->id,
                            $priorityprofiles
                        )
                    );

            $priorityprofiles =
                array_map(
                    static function(
                        CrmIntelligenceDashboardProfile
                            $profile
                    ) use ($inboxbyuser):
                        CrmIntelligenceDashboardProfile {
                        $userid =
                            (int)$profile->user->id;

                        return new
                            CrmIntelligenceDashboardProfile(
                                $profile->user,
                                $profile->intelligence,
                                $inboxbyuser[
                                    $userid
                                ] ?? null
                            );
                    },
                    $priorityprofiles
                );
        }
        
        return new CrmIntelligenceDashboardOverview(
            count($users),
            $hotleads,
            $atrisk,
            $vip,
            $trialopportunities,
            $upgradeopportunities,
            $priorityprofiles
        );
    }
}