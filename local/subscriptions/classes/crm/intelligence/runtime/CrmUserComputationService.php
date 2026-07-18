<?php

namespace local_subscriptions\crm\intelligence\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceRepository;
use local_subscriptions\crm\intelligence\core\UserIntelligence;
use local_subscriptions\crm\intelligence\explanation\ExplanationBuilder;
use local_subscriptions\crm\intelligence\opportunities\OpportunityEngine;
use local_subscriptions\crm\intelligence\recommendations\RecommendationEngine;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationContextBuilder;
use local_subscriptions\crm\intelligence\scoring\LeadScoreEngine;
use local_subscriptions\crm\intelligence\segmentation\SegmentEngine;
use local_subscriptions\crm\intelligence\trends\CrmScoreTrendService;

/**
 * Executes the complete CRM Intelligence computation for one user.
 *
 * This service is computation-only:
 * - it does not write score snapshots;
 * - it does not persist recommendations;
 * - it does not update cursors;
 * - it does not manage batch runs.
 */
final class CrmUserComputationService {

    public function __construct(
        private readonly CrmIntelligenceRepository
            $repository =
                new CrmIntelligenceRepository(),
        private readonly LeadScoreEngine
            $scoreengine =
                new LeadScoreEngine(),
        private readonly SegmentEngine
            $segmentengine =
                new SegmentEngine(),
        private readonly OpportunityEngine
            $opportunityengine =
                new OpportunityEngine(),
        private readonly RecommendationContextBuilder
            $recommendationcontexts =
                new RecommendationContextBuilder(),
        private readonly RecommendationEngine
            $recommendationengine =
                new RecommendationEngine(),
        private readonly CrmScoreTrendService
            $trendservice =
                new CrmScoreTrendService(),
        private readonly ExplanationBuilder
            $explanationbuilder =
                new ExplanationBuilder()
    ) {
    }

    /**
     * Computes complete CRM Intelligence for one Moodle user.
     *
     * @param \stdClass $user Moodle user record.
     * @param CrmComputationContext $context Shared run context.
     * @param bool $withtrend Whether score history should be read.
     * @return CrmUserComputationResult
     */
    public function compute(
        \stdClass $user,
        CrmComputationContext $context,
        bool $withtrend = false
    ): CrmUserComputationResult {
        if (empty($user->id)) {
            throw new \InvalidArgumentException(
                'CRM computation requires a valid Moodle user.'
            );
        }

        $started = microtime(true);
        $userid = (int)$user->id;

        $totalprofile =
            CrmComputationProfiler::start();

        $stageprofile =
            CrmComputationProfiler::start();

        $snapshot =
            $this->repository
                ->snapshot_for_user($user);

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'snapshot',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();

        $score =
            $this->scoreengine
                ->score($snapshot);

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'score',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();

        $segments =
            $this->segmentengine
                ->detect(
                    $snapshot,
                    $score
                );

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'segments',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();

        $opportunities =
            $this->opportunityengine
                ->detect(
                    $snapshot,
                    $score
                );

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'opportunities',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();

        $recommendationcontext =
            $this->recommendationcontexts
                ->build(
                    userid:
                        $userid,

                    snapshot:
                        $snapshot,

                    leadscore:
                        $score,

                    opportunities:
                        $opportunities,

                    generatedat:
                        $context->startedat,

                    useremail:
                        (string)($user->email ?? ''),

                    userlastaccess:
                        $snapshot->lastaccess
                );

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'recommendation_context',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();

        $recommendationresult =
            $this->recommendationengine
                ->generate(
                    $recommendationcontext
                );

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'recommendation_engine',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();

        $trend = $withtrend
            ? $this->trendservice
                ->global_trend_for_user($userid)
            : null;

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'trend',
            start: $stageprofile
        );

        $stageprofile =
            CrmComputationProfiler::start();        

        $baseintelligence =
            new UserIntelligence(
                snapshot: $snapshot,
                leadScore: $score,
                segments: $segments,
                opportunities: $opportunities,
                recommendations:
                    $recommendationresult
                        ->recommendations,
                trend: $trend
            );

        $intelligence =
            new UserIntelligence(
                snapshot: $snapshot,
                leadScore: $score,
                segments: $segments,
                opportunities: $opportunities,
                recommendations:
                    $recommendationresult
                        ->recommendations,
                trend: $trend,
                explanations:
                    $this->explanationbuilder
                        ->build(
                            $baseintelligence
                        )
            );

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'explanations',
            start: $stageprofile
        );

        CrmComputationProfiler::finish(
            runid: $context->runid,
            userid: $userid,
            stage: 'total',
            start: $totalprofile
        );

        return new CrmUserComputationResult(
            context: $context,
            userid: $userid,
            intelligence: $intelligence,
            recommendationresult:
                $recommendationresult,
            durationms:
                self::duration_ms($started)
        );
    }

    /**
     * Returns elapsed milliseconds.
     *
     * @param float $started Start microtime.
     * @return int
     */
    private static function duration_ms(
        float $started
    ): int {
        return max(
            0,
            (int)round(
                (microtime(true) - $started) *
                1000
            )
        );
    }
}