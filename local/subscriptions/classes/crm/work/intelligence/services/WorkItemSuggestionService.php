<?php

namespace local_subscriptions\crm\work\intelligence\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\dto\AssistantRecommendation;
use local_subscriptions\crm\assistant\repositories\AssistantRecommendationRepository;
use local_subscriptions\crm\work\intelligence\dto\WorkItemSuggestion;

/**
 * Builds human-reviewable Work Item suggestions.
 */
final class WorkItemSuggestionService {

    public function __construct(
        private readonly AssistantRecommendationRepository $recommendations =
            new AssistantRecommendationRepository(),
        private readonly WorkItemSuggestionPolicy $policy =
            new WorkItemSuggestionPolicy(),
        private readonly WorkTeamSuggestionService $teams =
            new WorkTeamSuggestionService(),
        private readonly WorkItemDuplicateDetector $duplicates =
            new WorkItemDuplicateDetector()
    ) {
    }

    public function build(
        int $recommendationid
    ): WorkItemSuggestion {
        $recommendation =
            $this->recommendations->get(
                $recommendationid
            );

        return $this->build_from_recommendation(
            $recommendation
        );
    }

    public function build_from_recommendation(
        AssistantRecommendation $recommendation
    ): WorkItemSuggestion {
        $now = time();

        $type =
            $this->policy->type(
                $recommendation
            );

        $priority =
            $this->policy->priority(
                $recommendation
            );

        $title =
            $this->title($recommendation);

        $description =
            $this->description(
                $recommendation
            );

        $teams =
            $this->teams->suggest(
                $type,
                $recommendation->key,
                $recommendation->sources
            );

        $duplicates =
            $this->duplicates->detect(
                recommendationid:
                    $recommendation->id,
                targetuserid:
                    $recommendation->targetid,
                type: $type,
                title: $title,
                description: $description
            );

        $suggestedteam =
            $teams[0] ?? null;

        $probableduplicate = false;

        foreach ($duplicates as $duplicate) {
            if (
                $duplicate
                    ->is_probable_duplicate()
            ) {
                $probableduplicate = true;
                break;
            }
        }

        $confidence =
            $this->policy->confidence(
                $recommendation,
                $suggestedteam !== null,
                $probableduplicate
            );

        $reasons = [
            'generated_from_recommendation',
            'priority_derived_from_recommendation',
            'type_derived_from_scenario',
        ];

        if ($suggestedteam !== null) {
            $reasons[] =
                'team_suggested_from_domain_and_workload';
        }

        if ($duplicates !== []) {
            $reasons[] =
                'duplicate_candidates_detected';
        }

        return new WorkItemSuggestion(
            recommendationid:
                $recommendation->id,
            title: $title,
            description: $description,
            type: $type,
            priority: $priority,
            targetuserid:
                $recommendation->targetid,
            suggestedteamid:
                $suggestedteam?->teamid,
            dueat:
                $this->policy->dueat(
                    $recommendation,
                    $now
                ),
            confidencescore:
                $confidence,
            reasons: $reasons,
            teams: $teams,
            duplicates: $duplicates
        );
    }

    private function title(
        AssistantRecommendation $recommendation
    ): string {
        $stringkey =
            'crm_work_suggestion_title_' .
            clean_param(
                $recommendation->key,
                PARAM_ALPHANUMEXT
            );

        if (
            get_string_manager()
                ->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions',
                $recommendation->targetname
            );
        }

        $recommendationkey =
            'crm_assistant_recommendation_' .
            clean_param(
                $recommendation->key,
                PARAM_ALPHANUMEXT
            );

        if (
            get_string_manager()
                ->string_exists(
                    $recommendationkey,
                    'local_subscriptions'
                )
        ) {
            return get_string(
                $recommendationkey,
                'local_subscriptions'
            );
        }

        return $this->recommendation_label(
            $recommendation->key
        );
    }

    private function description(
        AssistantRecommendation $recommendation
    ): string {
        $lines = [];

        $lines[] = get_string(
            'crm_work_suggestion_description_intro',
            'local_subscriptions'
        );

        $lines[] = '';

        $lines[] = get_string(
            'crm_work_suggestion_source_recommendation',
            'local_subscriptions',
            $this->recommendation_label(
                $recommendation->key
            )
        );

        $lines[] = get_string(
            'crm_work_suggestion_priority_score',
            'local_subscriptions',
            $recommendation->priority
        );

        if ($recommendation->evidence !== []) {
            $lines[] = '';
            $lines[] = get_string(
                'crm_work_suggestion_evidence_heading',
                'local_subscriptions'
            );

            foreach (
                array_slice(
                    $recommendation->evidence,
                    0,
                    8
                )
                as $evidence
            ) {
                $key =
                    (string)($evidence['key'] ?? '');

                $value =
                    $evidence['value'] ?? null;

                $label =
                    $this->evidence_label(
                        $key
                    );

                $valuetext =
                    $this->evidence_value(
                        $key,
                        $value
                    );

                $line =
                    '- ' .
                    $label;

                if ($valuetext !== '') {
                    $line .=
                        ' — ' .
                        $valuetext;
                }

                $lines[] = $line;
            }
        }

        return implode("\n", $lines);
    }

    private function recommendation_label(
        string $key
    ): string {
        $normalizedkey =
            $this->normalize_presentation_key(
                $key
            );

        $candidatekeys = [
            'crm_assistant_recommendation_' .
                $normalizedkey,

            'crm_intelligence_recommendation_' .
                $normalizedkey,
        ];

        foreach ($candidatekeys as $stringkey) {
            if (
                get_string_manager()->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )
            ) {
                return get_string(
                    $stringkey,
                    'local_subscriptions'
                );
            }
        }

        return $this->fallback_label(
            $key
        );
    }

    private function evidence_label(
        string $key
    ): string {
        $normalizedkey =
            $this->normalize_presentation_key(
                $key
            );

        $stringkey =
            'crm_assistant_evidence_' .
            $normalizedkey;

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return $this->fallback_label(
            $key
        );
    }

    private function evidence_value(
        string $key,
        mixed $value
    ): string {
        if (
            $value === null ||
            is_bool($value) ||
            !is_scalar($value)
        ) {
            return '';
        }

        $normalizedkey =
            $this->normalize_presentation_key(
                $key
            );

        $stringkey =
            'crm_assistant_evidence_value_' .
            $normalizedkey;

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions',
                $value
            );
        }

        return (string)$value;
    }

    private function normalize_presentation_key(
        string $key
    ): string {
        return clean_param(
            str_replace(
                [
                    '.',
                    '-',
                ],
                '_',
                trim($key)
            ),
            PARAM_ALPHANUMEXT
        );
    }

    private function fallback_label(
        string $key
    ): string {
        $segments = preg_split(
            '/[._-]+/',
            trim($key)
        );

        if (
            !is_array($segments) ||
            $segments === []
        ) {
            return $key;
        }

        $label = end($segments);

        if (
            !is_string($label) ||
            $label === ''
        ) {
            return $key;
        }

        return ucfirst(
            str_replace(
                '_',
                ' ',
                $label
            )
        );
    }

}