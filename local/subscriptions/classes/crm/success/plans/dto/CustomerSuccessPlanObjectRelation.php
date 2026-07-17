<?php

namespace local_subscriptions\crm\success\plans\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelation;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanRelationType;

/**
 * Relation between a plan step and another CRM object.
 */
final class CustomerSuccessPlanObjectRelation {

    public function __construct(
        public readonly int $id,
        public readonly int $planid,
        public readonly int $stepid,
        public readonly string $objecttype,
        public readonly int $objectid,
        public readonly string $relation,
        public readonly int $timecreated,
        public readonly int $createdby
    ) {
        if (
            $this->id <= 0 ||
            $this->planid <= 0 ||
            $this->stepid <= 0 ||
            $this->objectid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Customer Success relation IDs must be greater than zero.'
            );
        }

        if (
            !CustomerSuccessPlanRelationType::is_valid(
                $this->objecttype
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success relation object type.'
            );
        }

        if (
            !CustomerSuccessPlanRelation::is_valid(
                $this->relation
            )
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success semantic relation.'
            );
        }
    }
}