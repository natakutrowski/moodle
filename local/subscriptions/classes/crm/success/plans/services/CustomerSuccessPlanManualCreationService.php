<?php

declare(strict_types=1);

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanSource;
use local_subscriptions\crm\success\plans\logging\CustomerSuccessPlanAdminEventLogger;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanRepository;

/**
 * Creates a manually curated Customer Success plan from User360.
 */
final class CustomerSuccessPlanManualCreationService {

    public function __construct(
        private readonly CustomerSuccessPlanRepository $plans =
            new CustomerSuccessPlanRepository(),

        private readonly CustomerSuccessPlanAdminEventLogger $events =
            new CustomerSuccessPlanAdminEventLogger()
    ) {
    }

    public function create(
        int $userid,
        string $title,
        ?string $description,
        string $priority,
        int $actorid,
        ?int $targetdate = null,
        ?string $firststeptitle = null,
        ?string $firststepdescription = null,
        ?int $firststepdueat = null
    ): int {
        global $DB;

        $title = trim($title);

        if ($userid <= 0 || $title === '') {
            throw new \InvalidArgumentException(
                'Invalid Customer Success plan creation request.'
            );
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $planid = $this->plans->create_plan(
                userid: $userid,
                objectivekey: 'manual_follow_up',
                title: $title,
                description: $description,
                source: CustomerSuccessPlanSource::USER_360,
                priority: $priority,
                actorid: $actorid,
                targetdate: $targetdate
            );

            $firststeptitle = trim((string)$firststeptitle);

            if ($firststeptitle !== '') {
                $this->plans->create_step(
                    planid: $planid,
                    position: 1,
                    stepkey: 'manual_step_1',
                    title: $firststeptitle,
                    description: $firststepdescription,
                    priority: $priority,
                    actorid: $actorid,
                    dueat: $firststepdueat
                );
            }

            $transaction->allow_commit();

            $this->events->plan_created(
                $planid,
                $actorid
            );

            return $planid;
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);

            throw $exception;
        }
    }
}
