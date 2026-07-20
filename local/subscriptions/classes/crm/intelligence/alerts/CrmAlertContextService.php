<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;

/**
 * Loads operational context for Dashboard CRM alerts.
 *
 * The service performs a maximum of two grouped queries:
 * - one for active Work Items;
 * - one for open Customer Success plans.
 */
final class CrmAlertContextService {

    public function __construct(
        private readonly WorkItemReadRepository
            $workitems =
                new WorkItemReadRepository(),
        private readonly CustomerSuccessPlanReadRepository
            $plans =
                new CustomerSuccessPlanReadRepository()
    ) {
    }

    /**
     * @param CrmAlert[] $alerts
     * @return array<int,CrmAlertContext>
     */
    public function load(array $alerts): array {
        $userids = [];

        foreach ($alerts as $alert) {
            if (
                !$alert instanceof CrmAlert ||
                $alert->userid === null ||
                $alert->userid <= 0
            ) {
                continue;
            }

            $userids[] = $alert->userid;
        }

        $userids = array_values(
            array_unique($userids)
        );

        if ($userids === []) {
            return [];
        }

        $workitems =
            $this->workitems
                ->get_primary_active_for_users(
                    $userids
                );

        $plans =
            $this->plans
                ->get_primary_open_for_users(
                    $userids
                );

        $contexts = [];

        foreach ($userids as $userid) {
            $contexts[$userid] =
                new CrmAlertContext(
                    userid: $userid,
                    workitem:
                        $workitems[$userid] ?? null,
                    customersuccessplan:
                        $plans[$userid] ?? null
                );
        }

        return $contexts;
    }
}