<?php

namespace local_subscriptions\crm\work\intelligence\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\intelligence\dto\WorkTeamSuggestion;
use local_subscriptions\crm\work\intelligence\repositories\WorkTeamSuggestionRepository;

/**
 * Suggests an existing Work Team for a Work Item.
 */
final class WorkTeamSuggestionService {

    private const TYPE_KEYWORDS = [
        WorkItemType::SUPPORT => [
            'support',
            'assistance',
            'help',
            'inbox',
            'student',
            'étudiant',
            'ученик',
            'поддерж',
        ],
        WorkItemType::INCIDENT => [
            'support',
            'incident',
            'technical',
            'technique',
            'технич',
            'development',
            'développement',
        ],
        WorkItemType::BUG => [
            'development',
            'developer',
            'développement',
            'technique',
            'technical',
            'bug',
            'разработ',
        ],
        WorkItemType::FEATURE => [
            'development',
            'product',
            'produit',
            'développement',
            'разработ',
        ],
        WorkItemType::CONTENT => [
            'content',
            'contenu',
            'pedagogy',
            'pédagog',
            'course',
            'cours',
            'контент',
            'педагог',
        ],
        WorkItemType::MARKETING => [
            'marketing',
            'commercial',
            'sales',
            'vente',
            'маркет',
            'продаж',
        ],
        WorkItemType::FINANCE => [
            'finance',
            'payment',
            'paiement',
            'billing',
            'facturation',
            'оплат',
            'финанс',
        ],
        WorkItemType::ADMINISTRATION => [
            'administration',
            'administratif',
            'admin',
            'операц',
        ],
        WorkItemType::FOLLOW_UP => [
            'success',
            'student',
            'étudiant',
            'customer',
            'learner',
            'suivi',
            'accompagnement',
            'ученик',
        ],
        WorkItemType::TASK => [],
    ];

    public function __construct(
        private readonly WorkTeamSuggestionRepository $repository =
            new WorkTeamSuggestionRepository()
    ) {
    }

    /**
     * @return WorkTeamSuggestion[]
     */
    public function suggest(
        string $type,
        string $recommendationkey,
        array $sources,
        int $limit = 5
    ): array {
        $teams =
            $this->repository
                ->get_enabled_teams_with_workload();

        $suggestions = [];

        foreach ($teams as $team) {
            [$score, $reasons] =
                $this->score_team(
                    $team,
                    $type,
                    $recommendationkey,
                    $sources
                );

            if ($score <= 0) {
                continue;
            }

            $suggestions[] =
                new WorkTeamSuggestion(
                    teamid: (int)$team->id,
                    teamname:
                        (string)$team->name,
                    score: $score,
                    activeworkload:
                        (int)$team->activeworkload,
                    reasons: $reasons
                );
        }

        usort(
            $suggestions,
            static function (
                WorkTeamSuggestion $left,
                WorkTeamSuggestion $right
            ): int {
                $scorecomparison =
                    $right->score <=>
                    $left->score;

                if ($scorecomparison !== 0) {
                    return $scorecomparison;
                }

                $loadcomparison =
                    $left->activeworkload <=>
                    $right->activeworkload;

                if ($loadcomparison !== 0) {
                    return $loadcomparison;
                }

                return strcmp(
                    $left->teamname,
                    $right->teamname
                );
            }
        );

        return array_slice(
            $suggestions,
            0,
            max(1, $limit)
        );
    }

    /**
     * @return array{0:int,1:string[]}
     */
    private function score_team(
        \stdClass $team,
        string $type,
        string $recommendationkey,
        array $sources
    ): array {
        $haystack =
            \core_text::strtolower(
                trim(
                    (string)$team->name .
                    ' ' .
                    (string)($team->description ?? '')
                )
            );

        $score = 15;
        $reasons = [
            'enabled_team',
        ];

        $keywords =
            self::TYPE_KEYWORDS[$type] ?? [];

        $matches = 0;

        foreach ($keywords as $keyword) {
            if (
                \core_text::strpos(
                    $haystack,
                    \core_text::strtolower(
                        $keyword
                    )
                ) !== false
            ) {
                $matches++;
            }
        }

        if ($matches > 0) {
            $score += min(
                50,
                $matches * 15
            );

            $reasons[] =
                'team_domain_match';
        }

        $recommendationtext =
            \core_text::strtolower(
                $recommendationkey .
                ' ' .
                implode(' ', $sources)
            );

        foreach (
            preg_split(
                '/[_\s.-]+/',
                $recommendationtext
            ) ?: []
            as $token
        ) {
            if (
                \core_text::strlen($token) < 4
            ) {
                continue;
            }

            if (
                \core_text::strpos(
                    $haystack,
                    $token
                ) !== false
            ) {
                $score += 5;
                $reasons[] =
                    'recommendation_context_match';
                break;
            }
        }

        $membercount =
            (int)($team->membercount ?? 0);

        if ($membercount <= 0) {
            $score -= 25;
            $reasons[] = 'team_without_members';
        } else {
            $score += min(
                10,
                $membercount * 2
            );
        }

        $activeworkload =
            (int)($team->activeworkload ?? 0);

        if ($activeworkload === 0) {
            $score += 10;
            $reasons[] = 'available_capacity';
        } else if ($activeworkload <= 5) {
            $score += 5;
            $reasons[] = 'moderate_workload';
        } else if ($activeworkload >= 20) {
            $score -= 15;
            $reasons[] = 'high_workload';
        } else if ($activeworkload >= 10) {
            $score -= 8;
            $reasons[] = 'elevated_workload';
        }

        return [
            max(0, min(100, $score)),
            array_values(array_unique(
                $reasons
            )),
        ];
    }
}