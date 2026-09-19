<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\participant;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;

/**
 * Explicit admin operations on an existing pedagogical participant.
 *
 * This service does not create Commerce rights or Moodle enrolments.
 */
final class CommercePedagogicalParticipantService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceStudentCourseAccessRepository $access,
        private readonly CommercePedagogicalGroupRepository $groups,
        private readonly CommercePedagogicalParticipationRepository $participations
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self(
            $db,
            CommerceStudentCourseAccessRepository::create($db),
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalParticipationRepository::create($db)
        );
    }

    public function set_profile(
        int $promotionid,
        int $userid,
        string $profile,
        ?int $actoruserid,
        int $now
    ): CommerceStudentCourseAccess {
        $profile = CommerceStudentAccessProfile::normalise($profile);
        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($promotionid);

        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion.');
        }

        $current = $this->access->find($promotion->get_course_id(), $userid);
        if ($current === null || $current->get_promotion_id() !== $promotionid) {
            throw new \coding_exception('User is not a participant of this pedagogical promotion.');
        }

        return $this->access->save(new CommerceStudentCourseAccess(
            $current->get_id(),
            $current->get_course_id(),
            $current->get_user_id(),
            $promotionid,
            $profile,
            $current->get_created_by(),
            $actoruserid,
            $current->get_time_created(),
            $now
        ));
    }

    public function move_group(
        int $promotionid,
        int $userid,
        int $targetgroupid,
        ?int $actoruserid,
        int $now
    ): void {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $target = $this->groups->get_group($targetgroupid);
        if (
            $target === null
            || $target->get_promotion_id() !== $promotionid
            || !$target->is_active()
        ) {
            throw new \coding_exception('Target group must be active and belong to the same promotion.');
        }

        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($promotionid);
        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion.');
        }

        $access = $this->access->find($promotion->get_course_id(), $userid);
        if ($access === null || $access->get_promotion_id() !== $promotionid) {
            throw new \coding_exception('User is not a participant of this pedagogical promotion.');
        }

        $participantproductid =
            $this->participations->active_product_id_for_user(
                $promotionid,
                $userid
            );

        if (
            $participantproductid !== null
            && $target->get_product_id() !== $participantproductid
        ) {
            throw new \coding_exception(
                'Target group belongs to another pedagogical offer.'
            );
        }

        $configuration = $this->groups->get_configuration($promotionid);
        $current = $this->groups->group_for_user($promotionid, $userid);

        if ($current !== null && $current->get_id() === $targetgroupid) {
            return;
        }

        if ($this->groups->member_count($targetgroupid) >= $configuration->get_group_size()) {
            throw new CommercePedagogicalParticipantMoveException(
                CommercePedagogicalParticipantMoveException::GROUP_FULL,
                'Target pedagogical group is full.'
            );
        }

        $transaction = $this->db->start_delegated_transaction();

        if ($current !== null) {
            $this->groups->deactivate_membership((int)$current->get_id(), $userid, $now);
            if (groups_is_member($current->get_moodle_group_id(), $userid)) {
                groups_remove_member($current->get_moodle_group_id(), $userid);
            }
        }

        $this->groups->save_membership($targetgroupid, $userid, $actoruserid, $now);
        if (!groups_is_member($target->get_moodle_group_id(), $userid)) {
            groups_add_member($target->get_moodle_group_id(), $userid);
        }

        $transaction->allow_commit();
    }
}
