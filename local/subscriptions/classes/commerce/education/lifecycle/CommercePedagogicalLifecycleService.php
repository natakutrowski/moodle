<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\lifecycle;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;

final class CommercePedagogicalLifecycleService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalParticipationRepository $participations,
        private readonly CommercePedagogicalGroupRepository $groups
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self(
            $db,
            CommercePedagogicalParticipationRepository::create($db),
            CommercePedagogicalGroupRepository::create($db)
        );
    }

    /**
     * Ends pedagogical participation after a full refund/cancellation.
     *
     * The Commerce entitlement/enrolment layer remains authoritative for
     * Moodle enrolment. Here we stop progressive pedagogical participation
     * and remove the promotion-specific Moodle group membership only.
     */
    public function terminate_purchase(
        string $purchasereference,
        string $state,
        int $now
    ): void {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $records = $this->participations->change_state_for_purchase(
            $purchasereference,
            $state,
            $now
        );

        foreach ($records as $record) {
            $group = $this->groups->group_for_user(
                (int)$record->promotionid,
                (int)$record->userid
            );
            if ($group === null) {
                continue;
            }

            $this->groups->deactivate_membership(
                (int)$group->get_id(),
                (int)$record->userid,
                $now
            );
            if (groups_is_member($group->get_moodle_group_id(), (int)$record->userid)) {
                groups_remove_member($group->get_moodle_group_id(), (int)$record->userid);
            }
        }
    }
}
