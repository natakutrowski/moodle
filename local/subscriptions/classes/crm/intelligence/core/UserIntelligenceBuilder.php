<?php

namespace local_subscriptions\crm\intelligence\core;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\explanation\ExplanationBuilder;
use local_subscriptions\crm\intelligence\scoring\LeadScoreEngine;
use local_subscriptions\crm\intelligence\segmentation\SegmentEngine;
use local_subscriptions\crm\intelligence\opportunities\OpportunityEngine;
use local_subscriptions\crm\intelligence\recommendations\RecommendationEngine;
use local_subscriptions\crm\intelligence\recommendations\services\RecommendationContextBuilder;
use local_subscriptions\crm\intelligence\trends\CrmScoreTrendService;

final class UserIntelligenceBuilder {

    public function __construct(
        private readonly CrmIntelligenceRepository $repository = new CrmIntelligenceRepository(),
        private readonly LeadScoreEngine $leadScoreEngine = new LeadScoreEngine(),
        private readonly SegmentEngine $segmentEngine = new SegmentEngine(),
        private readonly OpportunityEngine $opportunityEngine = new OpportunityEngine(),
        private readonly RecommendationEngine $recommendationEngine = new RecommendationEngine(),
        private readonly RecommendationContextBuilder $recommendationContextBuilder = new RecommendationContextBuilder(),
        private readonly CrmScoreTrendService $trendService = new CrmScoreTrendService(),
        private readonly ExplanationBuilder $explanationBuilder = new ExplanationBuilder()
    ) {
    }

    public function build_for_user(\stdClass $user, bool $withtrend = true): UserIntelligence {
        $snapshot = $this->repository->snapshot_for_user($user);
        $score = $this->leadScoreEngine->score($snapshot);
        $segments = $this->segmentEngine->detect($snapshot, $score);
        $opportunities = $this->opportunityEngine->detect($snapshot, $score);
        $recommendationcontext =
            $this->recommendationContextBuilder->build(
                userid: (int)$user->id,
                snapshot: $snapshot,
                leadscore: $score,
                opportunities: $opportunities
            );

        $recommendationresult =
            $this->recommendationEngine->generate(
                $recommendationcontext
            );

        $recommendations =
            $recommendationresult->recommendations;

        $trend = $withtrend
            ? $this->trendService->global_trend_for_user((int)$user->id)
            : null;

        $intelligence = new UserIntelligence(
            $snapshot,
            $score,
            $segments,
            $opportunities,
            $recommendations,
            $trend
        );

        return new UserIntelligence(
            $snapshot,
            $score,
            $segments,
            $opportunities,
            $recommendations,
            $trend,
            $this->explanationBuilder->build($intelligence)
        );
    }
}