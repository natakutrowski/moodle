<?php

namespace local_subscriptions\crm\intelligence\dashboard;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;
use local_subscriptions\crm\intelligence\inbox\CrmIntelligenceInboxRepository;

/**
 * Builds the CRM Intelligence Dashboard from persisted snapshots.
 *
 * This builder belongs to the read side. It must never invoke:
 * - UserIntelligenceBuilder;
 * - CustomerSuccessRuntime;
 * - RecommendationEngine;
 * - CRM collectors.
 */
final class CrmIntelligenceDashboardBuilder {

    public function __construct(
        private readonly CrmIntelligenceDashboardRepository
            $repository =
                new CrmIntelligenceDashboardRepository(),
        private readonly CrmIntelligenceInboxRepository
            $inboxRepository =
                new CrmIntelligenceInboxRepository()
    ) {
    }

    /**
     * Builds the Dashboard Intelligence overview.
     *
     * @param int $limit Maximum number of latest user snapshots.
     * @param bool $includeinbox Include grouped Inbox summaries.
     * @return CrmIntelligenceDashboardOverview
     */
    public function build(
        int $limit =
            CrmIntelligenceLimits::DASHBOARD_USERS,
        bool $includeinbox = false
    ): CrmIntelligenceDashboardOverview {
        $snapshots =
            $this->repository
                ->get_latest_snapshots($limit);

        $hotleads = 0;
        $atrisk = 0;
        $vip = 0;
        $trialopportunities = 0;
        $upgradeopportunities = 0;
        $priorityprofiles = [];

        foreach ($snapshots as $snapshot) {
            $segments = self::decode_keys(
                $snapshot->segmentsjson ?? null
            );

            $opportunities = self::decode_keys(
                $snapshot->opportunitiesjson ?? null
            );

            $recommendations = self::decode_keys(
                $snapshot->recommendationsjson ?? null
            );

            if (in_array('hot_lead', $segments, true)) {
                $hotleads++;
            }

            if (in_array('at_risk', $segments, true)) {
                $atrisk++;
            }

            if (in_array('vip', $segments, true)) {
                $vip++;
            }

            if (
                in_array(
                    'trial_to_purchase',
                    $opportunities,
                    true
                )
            ) {
                $trialopportunities++;
            }

            if (
                in_array(
                    'upgrade_subscription',
                    $opportunities,
                    true
                )
            ) {
                $upgradeopportunities++;
            }

            $globalscore =
                (int)$snapshot->globalscore;

            if (
                $globalscore >= 40 ||
                $recommendations ||
                $opportunities
            ) {
                $priorityprofiles[] =
                    new CrmIntelligenceDashboardProfile(
                        user: self::user_from_snapshot(
                            $snapshot
                        ),
                        globalScore: $globalscore,
                        snapshotTime:
                            (int)$snapshot->snapshottime
                    );
            }
        }

        /*
         * The repository already orders snapshots by global score,
         * but the explicit sort protects the read model if the
         * repository ordering changes later.
         */
        usort(
            $priorityprofiles,
            static function(
                CrmIntelligenceDashboardProfile $left,
                CrmIntelligenceDashboardProfile $right
            ): int {
                if (
                    $left->globalScore ===
                    $right->globalScore
                ) {
                    return
                        $right->snapshotTime <=>
                        $left->snapshotTime;
                }

                return
                    $right->globalScore <=>
                    $left->globalScore;
            }
        );

        $priorityprofiles = array_slice(
            $priorityprofiles,
            0,
            CrmIntelligenceLimits::
                DASHBOARD_PROFILES
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
                    ) use (
                        $inboxbyuser
                    ): CrmIntelligenceDashboardProfile {
                        $userid =
                            (int)$profile->user->id;

                        return new
                            CrmIntelligenceDashboardProfile(
                                user: $profile->user,
                                globalScore:
                                    $profile->globalScore,
                                snapshotTime:
                                    $profile->snapshotTime,
                                inbox:
                                    $inboxbyuser[
                                        $userid
                                    ] ?? null
                            );
                    },
                    $priorityprofiles
                );
        }

        return new CrmIntelligenceDashboardOverview(
            analysedUsers: count($snapshots),
            hotLeads: $hotleads,
            atRisk: $atrisk,
            vip: $vip,
            trialOpportunities:
                $trialopportunities,
            upgradeOpportunities:
                $upgradeopportunities,
            priorityProfiles:
                $priorityprofiles
        );
    }

    /**
     * Safely decodes a JSON list of technical keys.
     *
     * Invalid legacy JSON is treated as an empty list. A malformed
     * snapshot must not break the whole Dashboard.
     *
     * @param mixed $json JSON source.
     * @return string[]
     */
    private static function decode_keys(
        mixed $json
    ): array {
        if (
            !is_string($json) ||
            trim($json) === ''
        ) {
            return [];
        }

        try {
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $keys = [];

        foreach ($decoded as $value) {
            if (
                is_string($value) &&
                $value !== ''
            ) {
                $keys[] = $value;
            }
        }

        return array_values(
            array_unique($keys)
        );
    }

    /**
     * Creates the user presentation record from a joined snapshot.
     *
     * @param \stdClass $snapshot Joined snapshot record.
     * @return \stdClass
     */
    private static function user_from_snapshot(
        \stdClass $snapshot
    ): \stdClass {
        return (object)[
            'id' => (int)$snapshot->userid,
            'firstname' =>
                (string)$snapshot->firstname,
            'lastname' =>
                (string)$snapshot->lastname,
            'firstnamephonetic' =>
                (string)(
                    $snapshot->firstnamephonetic
                    ?? ''
                ),
            'lastnamephonetic' =>
                (string)(
                    $snapshot->lastnamephonetic
                    ?? ''
                ),
            'middlename' =>
                (string)(
                    $snapshot->middlename
                    ?? ''
                ),
            'alternatename' =>
                (string)(
                    $snapshot->alternatename
                    ?? ''
                ),
            'email' =>
                (string)$snapshot->email,
        ];
    }
}