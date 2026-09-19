<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\group;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;

final class CommercePedagogicalGroupOfferService {
    public function __construct(
        private readonly CommercePedagogicalGroupRepository $groups,
        private readonly CommercePedagogicalPromotionOfferRepository $offers,
        private readonly CommercePedagogicalParticipationRepository $participations
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalPromotionOfferRepository::create($db),
            CommercePedagogicalParticipationRepository::create($db)
        );
    }

    public function bind(
        int $promotionid,
        int $groupid,
        int $productid,
        ?int $actoruserid,
        int $now
    ): CommercePedagogicalGroup {
        $group = $this->groups->get_group($groupid);
        if (
            $group === null
            || $group->get_promotion_id() !== $promotionid
        ) {
            throw new \coding_exception(
                'Pedagogical group does not belong to this promotion.'
            );
        }

        $linked = false;
        foreach ($this->offers->links_for_promotion($promotionid) as $offer) {
            if ((int)$offer->productid === $productid) {
                $linked = true;
                break;
            }
        }
        if (!$linked) {
            throw new \coding_exception(
                'Pedagogical group can only be bound to an offer linked to the promotion.'
            );
        }

        foreach ($this->groups->member_user_ids($groupid) as $userid) {
            $participantproductid =
                $this->participations->active_product_id_for_user(
                    $promotionid,
                    $userid
                );

            if (
                $participantproductid !== null
                && $participantproductid !== $productid
            ) {
                throw new \coding_exception(
                    'This group contains a participant from another pedagogical offer. Move the participant first.'
                );
            }
        }

        return $this->groups->save_group(
            new CommercePedagogicalGroup(
                $group->get_id(),
                $group->get_promotion_id(),
                $group->get_moodle_group_id(),
                $group->get_display_name(),
                $group->get_position(),
                $group->get_tutor_id(),
                $group->get_support_language(),
                $group->get_telegram_reference(),
                $group->get_levelup_xp(),
                $group->is_active(),
                $group->get_created_by(),
                $actoruserid,
                $group->get_time_created(),
                $now,
                $group->get_tutor_name(),
                $productid
            )
        );
    }
}
