<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalCrmRanking;

/** CRM participant UI: real avatars, promotion ranking and history link. */
final class commerce_798_crm_participant_card_ui_test extends advanced_testcase {
    public function test_rank_numbers_for_non_podium_users_and_ties(): void {
        $participants = [];
        foreach (range(1, 6) as $id) {
            $participants[] = (object)['userid' => $id, 'timecreated' => $id];
        }
        $ranks = CommercePedagogicalCrmRanking::user_ranks($participants, [
            1 => 40, 2 => 30, 3 => 20, 4 => 10, 5 => 10, 6 => 0,
        ]);
        self::assertSame([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 4, 6 => 0], $ranks);
    }

    public function test_avatar_history_and_ranking_are_present_without_affecting_xp(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/admin/commerce/education/participants.php');
        $repository = file_get_contents($root
            . '/classes/commerce/education/participant/CommercePedagogicalParticipantRepository.php');
        $css = file_get_contents($root . '/styles.css');
        self::assertStringContainsString('u.picture', $repository);
        self::assertStringContainsString('new \user_picture($pictureuser)', $page);
        self::assertStringContainsString('get_url($PAGE)->out(false)', $page);
        self::assertStringContainsString('fa fa-user-circle', $page);
        self::assertStringContainsString('commerce-ped-m74-points-history-link', $page);
        self::assertStringContainsString("'commerce_education_m74_group_rank'", $page);
        self::assertStringContainsString("$" . "userplace > 0 ? '#' . $" . "userplace : '—'", $page);
        self::assertSame(1, substr_count($page, 'commerce-ped-m74-participant-meta-value commerce-ped-m74-rank-value'));
        self::assertStringContainsString('CAMPUSFR_CRM_PARTICIPANT_CARD_UX_V1', $css);
        self::assertStringContainsString('points_by_user($promotionid)', $page);
    }
}
