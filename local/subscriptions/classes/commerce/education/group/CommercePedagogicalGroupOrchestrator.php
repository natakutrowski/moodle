<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;

/**
 * Creates promotion-specific Moodle groups and auto-fills the first available group.
 */
final class CommercePedagogicalGroupOrchestrator {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalGroupRepository $repository,
        private readonly CommercePedagogicalPromotionOfferRepository $offers
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self(
            $db,
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalPromotionOfferRepository::create($db)
        );
    }

    public function create_group(
        int $promotionid,
        string $displayname,
        int $position,
        ?int $tutorid,
        ?string $supportlang,
        ?string $telegramref,
        ?int $levelupxp,
        ?int $actoruserid,
        int $now,
        ?string $tutorname = null,
        ?int $productid = null
    ): CommercePedagogicalGroup {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($promotionid);
        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion.');
        }

        if ($productid !== null) {
            $linked = false;
            foreach ($this->offers->links_for_promotion($promotionid) as $offer) {
                if ((int)$offer->productid === $productid) {
                    $linked = true;
                    break;
                }
            }
            if (!$linked) {
                throw new \coding_exception(
                    'Pedagogical group product must be linked to the promotion.'
                );
            }
        }

        $displayname = trim($displayname);
        if ($displayname === '') {
            throw new \coding_exception('Pedagogical group visible name is required.');
        }

        // Technical Moodle name is promotion-specific even when the visible
        // CampusFR name is deliberately reused in another promotion.
        $technicalname = $displayname . ' · ' . $promotion->get_promotion_key();
        $moodlegroup = (object)[
            'courseid' => $promotion->get_course_id(),
            'name' => $technicalname,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $moodlegroupid = groups_create_group($moodlegroup);

        return $this->repository->save_group(
            new CommercePedagogicalGroup(
                null,
                $promotionid,
                (int)$moodlegroupid,
                $displayname,
                $position,
                $tutorid,
                $supportlang !== null && trim($supportlang) !== '' ? trim($supportlang) : null,
                $telegramref !== null && trim($telegramref) !== '' ? trim($telegramref) : null,
                $levelupxp,
                true,
                $actoruserid,
                $actoruserid,
                $now,
                $now,
                $tutorname !== null && trim($tutorname) !== ''
                    ? trim($tutorname)
                    : null,
                $productid
            )
        );
    }

    /**
     * Update the human-facing group metadata and keep the backing Moodle group
     * technical name aligned with the CampusFR visible name.
     */
    public function update_group(
        int $promotionid,
        int $groupid,
        string $displayname,
        ?string $tutorname,
        ?string $supportlang,
        ?string $telegramref,
        ?int $actoruserid,
        int $now
    ): CommercePedagogicalGroup {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $promotion = CommercePedagogicalPromotionRepository::create($this->db)
            ->get_by_id($promotionid);
        if ($promotion === null) {
            throw new \coding_exception('Unknown pedagogical promotion.');
        }

        $group = $this->repository->get_group($groupid);
        if ($group === null || $group->get_promotion_id() !== $promotionid) {
            throw new \coding_exception(
                'Pedagogical group does not belong to this promotion.'
            );
        }

        $displayname = trim($displayname);
        if ($displayname === '') {
            throw new \coding_exception('Pedagogical group visible name is required.');
        }

        $updated = $this->repository->save_group(
            new CommercePedagogicalGroup(
                $group->get_id(),
                $group->get_promotion_id(),
                $group->get_moodle_group_id(),
                $displayname,
                $group->get_position(),
                $group->get_tutor_id(),
                $supportlang !== null && trim($supportlang) !== ''
                    ? trim($supportlang)
                    : null,
                $telegramref !== null && trim($telegramref) !== ''
                    ? trim($telegramref)
                    : null,
                $group->get_levelup_xp(),
                $group->is_active(),
                $group->get_created_by(),
                $actoruserid,
                $group->get_time_created(),
                $now,
                $tutorname !== null && trim($tutorname) !== ''
                    ? trim($tutorname)
                    : null,
                $group->get_product_id()
            )
        );

        $moodlegroup = groups_get_group($group->get_moodle_group_id());
        if (!$moodlegroup) {
            throw new \coding_exception('Backing Moodle group could not be loaded.');
        }
        $moodlegroup->name = $displayname . ' · ' . $promotion->get_promotion_key();
        groups_update_group($moodlegroup);

        return $updated;
    }

    public function assign_first_available_for_product(
        int $promotionid,
        int $productid,
        int $userid,
        ?int $actoruserid,
        int $now
    ): ?CommercePedagogicalGroup {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $configuration = $this->repository->get_configuration($promotionid);
        if (!$configuration->is_enabled()) {
            return null;
        }

        $existing = $this->repository->group_for_user(
            $promotionid,
            $userid
        );
        if ($existing !== null) {
            if ($existing->get_product_id() !== $productid) {
                throw new \coding_exception(
                    'Existing pedagogical group does not match purchased offer.'
                );
            }
            return $existing;
        }

        foreach (
            $this->repository->groups_for_product(
                $promotionid,
                $productid,
                true
            ) as $group
        ) {
            if (
                $this->repository->member_count(
                    (int)$group->get_id()
                ) >= $configuration->get_group_size()
            ) {
                continue;
            }

            $transaction = $this->db->start_delegated_transaction();
            $this->repository->save_membership(
                (int)$group->get_id(),
                $userid,
                $actoruserid,
                $now
            );
            if (
                !groups_is_member(
                    $group->get_moodle_group_id(),
                    $userid
                )
            ) {
                groups_add_member(
                    $group->get_moodle_group_id(),
                    $userid
                );
            }
            $transaction->allow_commit();

            return $group;
        }

        return null;
    }

    public function assign_first_available(
        int $promotionid,
        int $userid,
        ?int $actoruserid,
        int $now
    ): ?CommercePedagogicalGroup {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $configuration = $this->repository->get_configuration($promotionid);
        if (!$configuration->is_enabled()) {
            return null;
        }

        $existing = $this->repository->group_for_user($promotionid, $userid);
        if ($existing !== null) {
            return $existing;
        }

        foreach ($this->repository->groups_for_promotion($promotionid, true) as $group) {
            if ($this->repository->member_count((int)$group->get_id()) >= $configuration->get_group_size()) {
                continue;
            }

            $transaction = $this->db->start_delegated_transaction();
            $this->repository->save_membership((int)$group->get_id(), $userid, $actoruserid, $now);
            if (!groups_is_member($group->get_moodle_group_id(), $userid)) {
                groups_add_member($group->get_moodle_group_id(), $userid);
            }
            $transaction->allow_commit();
            return $group;
        }

        return null;
    }
}
