<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelation;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanRelationRepository;

/**
 * Links Customer Success plan steps with Work Items.
 *
 * Work Item creation remains handled by the Work Management services.
 */
final class CustomerSuccessPlanWorkItemService {

    public function __construct(
        private readonly CustomerSuccessPlanReadRepository $plans =
            new CustomerSuccessPlanReadRepository(),

        private readonly CustomerSuccessPlanRelationRepository $relations =
            new CustomerSuccessPlanRelationRepository()
    ) {
    }

    public function link_existing_work_item(
        int $stepid,
        int $workitemid,
        int $actorid
    ): int {
        if ($workitemid <= 0) {
            throw new \InvalidArgumentException(
                'Work Item ID must be greater than zero.'
            );
        }

        $step = $this->plans->get_step($stepid);

        return $this->relations->add(
            planid: $step->planid,
            stepid: $step->id,
            objecttype:
                CustomerSuccessPlanRelationType::WORK_ITEM,
            objectid: $workitemid,
            relation:
                CustomerSuccessPlanRelation::EXECUTED_BY,
            actorid: $actorid
        );
    }

    public function unlink_work_item(
        int $stepid,
        int $workitemid
    ): void {
        $this->relations->remove(
            stepid: $stepid,
            objecttype:
                CustomerSuccessPlanRelationType::WORK_ITEM,
            objectid: $workitemid,
            relation:
                CustomerSuccessPlanRelation::EXECUTED_BY
        );
    }
}