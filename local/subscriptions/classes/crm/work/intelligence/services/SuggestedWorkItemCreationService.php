<?php

namespace local_subscriptions\crm\work\intelligence\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\services\RecommendationLifecycleService;
use local_subscriptions\crm\work\domain\WorkItemRelation;
use local_subscriptions\crm\work\domain\WorkItemSource;
use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\intelligence\dto\CreateSuggestedWorkItemRequest;
use local_subscriptions\crm\work\services\WorkItemService;
use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;

/**
 * Creates a Work Item only after explicit administrator confirmation.
 */
final class SuggestedWorkItemCreationService {

    public function __construct(
        private readonly WorkItemSuggestionService $suggestions =
            new WorkItemSuggestionService(),
        private readonly WorkItemService $workitems =
            new WorkItemService(),
        private readonly RecommendationLifecycleService $lifecycle =
            new RecommendationLifecycleService()
    ) {
    }

    public function create(
        CreateSuggestedWorkItemRequest $request
    ): \stdClass {
        $suggestion =
            $this->suggestions->build(
                $request->recommendationid
            );

        if (
            $suggestion->has_probable_duplicate() &&
            !$request->allowduplicate
        ) {
            $duplicate =
                $suggestion->strongest_duplicate();

            throw new \DomainException(
                'A probable duplicate Work Item already exists: ' .
                ($duplicate?->reference ?? '')
            );
        }

        $item =
            $this->workitems->create(
                new CreateWorkItemRequest(
                    title:
                        trim($request->title),
                    description:
                        trim($request->description),
                    type: $request->type,
                    priority:
                        $request->priority,
                    source:
                        WorkItemSource::ASSISTANT,
                    createdby:
                        $request->createdby,
                    targetuserid:
                        $suggestion->targetuserid,
                    assigneduserid:
                        $request->assigneduserid,
                    assignedteamid:
                        $request->assignedteamid,
                    parentid: null,
                    dueat: $request->dueat
                )
            );

        $this->workitems->link(
            itemid: (int)$item->id,
            objecttype: 'recommendation',
            objectid:
                $request->recommendationid,
            relation:
                WorkItemRelation::CREATED_FROM,
            actorid:
                $request->createdby
        );

        if ($suggestion->targetuserid !== null) {
            $this->workitems->link(
                itemid: (int)$item->id,
                objecttype: 'user',
                objectid:
                    $suggestion->targetuserid,
                relation:
                    WorkItemRelation::RELATED,
                actorid:
                    $request->createdby
            );
        }

        /*
         * The administrator has accepted the recommendation by creating work.
         * It remains accepted until the operational work is completed.
         */
        try {
            $this->lifecycle->accept(
                $request->recommendationid,
                $request->createdby
            );
        } catch (\DomainException $exception) {
            /*
             * Recommendation may already be accepted.
             * Work Item creation remains valid.
             */
            debugging(
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }

        AdminLog::log(
            AdminEvents::
                WORK_ITEM_CREATED_FROM_RECOMMENDATION,
            $suggestion->targetuserid,
            'work_item',
            (int)$item->id,
            [
                'reference' =>
                    (string)$item->reference,
                'recommendationid' =>
                    $request->recommendationid,
                'type' => $request->type,
                'priority' =>
                    $request->priority,
                'assignedteamid' =>
                    $request->assignedteamid,
                'duplicateoverride' =>
                    $request->allowduplicate,
            ]
        );

        if (
            $request->allowduplicate &&
            $suggestion->has_probable_duplicate()
        ) {
            AdminLog::log(
                AdminEvents::
                    WORK_ITEM_DUPLICATE_OVERRIDE,
                $suggestion->targetuserid,
                'work_item',
                (int)$item->id,
                [
                    'recommendationid' =>
                        $request->recommendationid,
                    'duplicateworkitemid' =>
                        $suggestion
                            ->strongest_duplicate()
                            ?->workitemid,
                ]
            );
        }

        return $item;
    }
}