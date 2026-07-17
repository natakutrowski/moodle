<?php

namespace local_subscriptions\crm\assistant\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\assistant\ai\dto\CrmAssistantContext;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\crm\assistant\ai\repositories\CrmAssistantContextRepository;
use local_subscriptions\crm\assistant\dto\AssistantRecommendation;

/**
 * Builds the minimized structured context supplied to the AI provider.
 */
final class CrmAssistantContextBuilder {

    public function __construct(
        private readonly CrmAssistantContextRepository $repository =
            new CrmAssistantContextRepository()
    ) {
    }

    public function build(
        CrmAssistantQuestion $question
    ): CrmAssistantContext {
        return match ($question->scope) {
            CrmAssistantQuestion::SCOPE_USER =>
                $this->for_user(
                    (int)$question->userid
                ),

            CrmAssistantQuestion::SCOPE_RECOMMENDATION =>
                $this->for_recommendation(
                    (int)$question->recommendationid
                ),

            default =>
                $this->global(),
        };
    }

    private function global():
        CrmAssistantContext {
        $recommendations =
            $this->repository
                ->global_recommendations(50);

        $workitems =
            $this->repository
                ->active_work_items(null, 30);

        $counts =
            $this->repository
                ->recommendation_counts();

        return new CrmAssistantContext(
            scope:
                CrmAssistantQuestion::SCOPE_GLOBAL,
            summary: [
                'active_recommendations' =>
                    (int)($counts->activecount ?? 0),
                'critical_recommendations' =>
                    (int)($counts->criticalcount ?? 0),
                'urgent_recommendations' =>
                    (int)($counts->urgentcount ?? 0),
                'affected_users' =>
                    (int)($counts->usercount ?? 0),
            ],
            recommendations:
                $this->map_recommendations(
                    $recommendations
                ),
            workitems:
                $this->map_work_items(
                    $workitems
                ),
            allowedreferences:
                $this->references(
                    $recommendations,
                    $workitems
                )
        );
    }

    private function for_user(
        int $userid
    ): CrmAssistantContext {
        $user =
            $this->repository
                ->user_summary($userid);

        if ($user === null) {
            throw new \moodle_exception(
                'invaliduser'
            );
        }

        $recommendations =
            $this->repository
                ->user_recommendations(
                    $userid,
                    30
                );

        $workitems =
            $this->repository
                ->active_work_items(
                    $userid,
                    30
                );

        return new CrmAssistantContext(
            scope:
                CrmAssistantQuestion::SCOPE_USER,
            summary: [
                'recommendation_count' =>
                    count($recommendations),
                'active_work_item_count' =>
                    count($workitems),
            ],
            recommendations:
                $this->map_recommendations(
                    $recommendations
                ),
            workitems:
                $this->map_work_items(
                    $workitems
                ),
            user: [
                'id' => (int)$user->id,
                'name' =>
                    (string)$user->fullname,
                'language' =>
                    (string)$user->language,
                'lastaccess' =>
                    (int)$user->lastaccess,
                'suspended' =>
                    (bool)$user->suspended,
            ],
            allowedreferences:
                $this->references(
                    $recommendations,
                    $workitems,
                    $user
                )
        );
    }

    private function for_recommendation(
        int $recommendationid
    ): CrmAssistantContext {
        $recommendation =
            $this->repository
                ->recommendation(
                    $recommendationid
                );

        $user = null;
        $workitems = [];

        if ($recommendation->targetid !== null) {
            $user =
                $this->repository
                    ->user_summary(
                        $recommendation->targetid
                    );

            $workitems =
                $this->repository
                    ->active_work_items(
                        $recommendation->targetid,
                        20
                    );
        }

        return new CrmAssistantContext(
            scope:
                CrmAssistantQuestion::SCOPE_RECOMMENDATION,
            summary: [
                'recommendation_id' =>
                    $recommendation->id,
                'recommendation_key' =>
                    $recommendation->key,
            ],
            recommendations:
                $this->map_recommendations([
                    $recommendation,
                ]),
            workitems:
                $this->map_work_items(
                    $workitems
                ),
            user: $user !== null
                ? [
                    'id' => (int)$user->id,
                    'name' =>
                        (string)$user->fullname,
                    'language' =>
                        (string)$user->language,
                    'lastaccess' =>
                        (int)$user->lastaccess,
                    'suspended' =>
                        (bool)$user->suspended,
                ]
                : null,
            allowedreferences:
                $this->references(
                    [$recommendation],
                    $workitems,
                    $user
                )
        );
    }

    /**
     * @param AssistantRecommendation[] $recommendations
     */
    private function map_recommendations(
        array $recommendations
    ): array {
        return array_map(
            static fn(
                AssistantRecommendation $item
            ): array => [
                'id' => $item->id,
                'key' => $item->key,
                'type' => $item->type,
                'priority' => $item->priority,
                'prioritylevel' =>
                    $item->prioritylevel,
                'status' => $item->status,
                'targetuserid' =>
                    $item->is_user_target()
                        ? $item->targetid
                        : null,
                'targetname' =>
                    $item->targetname,
                'sources' =>
                    $item->sources,
                'evidence' =>
                    array_slice(
                        $item->evidence,
                        0,
                        8
                    ),
                'firstdetectedat' =>
                    $item->firstdetectedat,
                'lastdetectedat' =>
                    $item->lastdetectedat,
                'validuntil' =>
                    $item->validuntil,
            ],
            $recommendations
        );
    }

    private function map_work_items(
        array $workitems
    ): array {
        return array_map(
            static fn(\stdClass $item): array => [
                'id' => (int)$item->id,
                'reference' =>
                    (string)$item->reference,
                'title' =>
                    (string)$item->title,
                'type' =>
                    (string)$item->type,
                'priority' =>
                    (string)$item->priority,
                'status' =>
                    (string)$item->status,
                'targetuserid' =>
                    $item->targetuserid !== null
                        ? (int)$item->targetuserid
                        : null,
                'assignedteamid' =>
                    $item->assignedteamid !== null
                        ? (int)$item->assignedteamid
                        : null,
                'assigneduserid' =>
                    $item->assigneduserid !== null
                        ? (int)$item->assigneduserid
                        : null,
                'dueat' =>
                    $item->dueat !== null
                        ? (int)$item->dueat
                        : null,
            ],
            $workitems
        );
    }

    private function references(
        array $recommendations,
        array $workitems,
        ?\stdClass $user = null
    ): array {
        $references = [];

        if ($user !== null) {
            $references[] = [
                'type' => 'user',
                'id' => (int)$user->id,
            ];
        }

        foreach ($recommendations as $item) {
            $references[] = [
                'type' => 'recommendation',
                'id' => (int)$item->id,
            ];

            if ($item->targetid !== null) {
                $references[] = [
                    'type' => 'user',
                    'id' => (int)$item->targetid,
                ];
            }
        }

        foreach ($workitems as $item) {
            $references[] = [
                'type' => 'work_item',
                'id' => (int)$item->id,
            ];
        }

        $unique = [];

        foreach ($references as $reference) {
            $key =
                $reference['type'] .
                ':' .
                $reference['id'];

            $unique[$key] = $reference;
        }

        return array_values($unique);
    }
}