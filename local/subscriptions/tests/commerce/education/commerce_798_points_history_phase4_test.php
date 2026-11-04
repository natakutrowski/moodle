<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpHistoryService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;
use local_subscriptions\commerce\education\xp\CommercePersonalXpHistoryService;

/** Phase 4: learner privacy, multiple promotions, no mutations and personal XP log separation. */
final class commerce_798_points_history_phase4_test extends advanced_testcase {
    /** @return array{0:int,1:int} Course and purchasable product. */
    private function course_and_product(): array {
        global $DB;
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'HIST4-' . strtoupper(random_string(10)),
            'type' => 'course_access', 'status' => 'active',
            'name' => 'History test product', 'description' => null, 'metadatajson' => null,
            'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return [(int)$course->id, $productid];
    }

    private function promotion(int $courseid, int $productid, int $userid, string $state,
            string $name): int {
        global $DB;
        $now = time();
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'hist4-' . random_string(11), 'name' => $name,
            'courseid' => $courseid, 'status' => 'finished', 'published' => 1,
            'salesopensat' => null, 'salesclosesat' => null, 'startsat' => $now - 100,
            'endsat' => $now - 10, 'capacitytotal' => null,
            'createdby' => null, 'modifiedby' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid, 'courseid' => $courseid,
            'userid' => $userid, 'productid' => $productid,
            'purchasereference' => 'hist4-' . random_string(12), 'state' => $state,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return $promotionid;
    }

    public function test_promotion_tabs_include_active_and_earned_former_promotions_only(): void {
        global $DB;
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $stranger = $this->getDataGenerator()->create_user();
        [$courseid, $productid] = $this->course_and_product();
        $activeid = $this->promotion($courseid, $productid, (int)$user->id, 'active', 'Active cohort');
        $formerid = $this->promotion($courseid, $productid, (int)$user->id, 'active', 'Former cohort');
        $revokedemptyid = $this->promotion($courseid, $productid, (int)$user->id, 'revoked', 'Empty revoked');
        $otherid = $this->promotion($courseid, $productid, (int)$stranger->id, 'active', 'Foreign cohort');
        CommercePedagogicalXpService::create($DB)->record_once(
            $formerid, (int)$user->id, 'block_xp', 'points_increased', 'retained', 17, time(), time()
        );
        // Real life-cycle: earn points as an active member, then leave the promotion.
        $DB->set_field('local_subs_commerce_ped_join', 'state', 'revoked', [
            'promotionid' => $formerid, 'userid' => (int)$user->id,
        ]);
        $service = CommercePedagogicalXpHistoryService::create($DB);
        $tabs = $service->list_for_learner((int)$user->id);
        $ids = array_column($tabs, 'id');
        self::assertContains($activeid, $ids);
        self::assertContains($formerid, $ids);
        self::assertNotContains($revokedemptyid, $ids);
        self::assertNotContains($otherid, $ids);
        self::assertNull($service->get_for_user($formerid, (int)$user->id));
        $archive = $service->get_for_learner_archive($formerid, (int)$user->id);
        self::assertSame(17, $archive['totalpoints']);
        self::assertArrayNotHasKey('crmreason', $archive['rows'][0]);
        self::assertNull($service->get_for_learner_archive($otherid, (int)$user->id));
        self::assertNull($service->get_for_learner_archive($revokedemptyid, (int)$user->id));
        self::assertSame([], $service->list_for_learner((int)$stranger->id - 100000));
    }

    public function test_personal_history_is_scoped_and_does_not_award_points(): void {
        global $DB;
        $this->resetAfterTest(true);
        if (!$DB->get_manager()->table_exists(new \xmldb_table('block_xp_logs'))) {
            $this->markTestSkipped('Level Up XP logs not installed');
        }
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $now = time();
        foreach ([(int)$user->id, (int)$other->id] as $userid) {
            $DB->insert_record('block_xp_logs', (object)[
                'contextid' => (int)\context_system::instance()->id,
                'userid' => $userid, 'points' => $userid === (int)$user->id ? 11 : 99,
                'reason' => 'activity_viewed', 'subtype' => null,
                'envid' => (int)\context_system::instance()->id,
                'parentid' => null, 'objectid' => 48, 'ruleid' => 22,
                'reasontypehash' => substr(sha1('activity_viewed:'), 0, 9),
                'timerecorded' => $now, 'legacysource' => null, 'legacyid' => null,
            ]);
        }
        $before = $DB->count_records('block_xp_logs');
        $history = CommercePersonalXpHistoryService::create($DB)->get_for_user((int)$user->id, 999);
        self::assertSame(1, $history['rewardcount']);
        self::assertSame(0, $history['page']);
        self::assertSame(11, $history['rows'][0]['points']);
        self::assertFalse($history['rows'][0]['hasactivityurl']);
        self::assertArrayNotHasKey('crmreason', $history['rows'][0]);
        self::assertSame($before, $DB->count_records('block_xp_logs'));
    }

    public function test_controller_navigation_and_translations_are_wired(): void {
        $root = dirname(__DIR__, 3);
        $controller = file_get_contents($root . '/my_points.php');
        $legacy = file_get_contents($root . '/my_promotion_xp.php');
        $hubservice = file_get_contents($root . '/classes/commerce/customer/hub/CommerceCustomerHubService.php');
        $leaderboard = file_get_contents($root . '/classes/commerce/education/xp/CommercePedagogicalStudentLeaderboardService.php');
        $template = file_get_contents($root . '/templates/customer/points_history.mustache');
        $hub = file_get_contents($root . '/templates/customer/hub.mustache');
        self::assertStringContainsString("optional_param('promotionid', 0", $controller);
        self::assertStringContainsString('get_for_learner_archive(', $controller);
        self::assertStringContainsString('CommercePersonalXpHistoryService::create()', $controller);
        self::assertStringContainsString("/local/subscriptions/my_points.php", $controller);
        self::assertStringContainsString("/local/subscriptions/my_points.php", $legacy);
        self::assertStringContainsString("optional_param('promotionid', 0", $legacy);
        self::assertStringContainsString("optional_param('page', 0", $legacy);
        self::assertStringContainsString("/local/subscriptions/my_points.php", $hubservice);
        self::assertSame(2, substr_count($leaderboard, '/local/subscriptions/my_points.php'));
        self::assertStringNotContainsString('/local/subscriptions/my_promotion_xp.php', $controller);
        self::assertStringContainsString('{{#hastabs}}', $template);
        self::assertStringContainsString('aria-current="page"', $template);
        self::assertStringContainsString('mypointshistoryurl', $hub);
        foreach (['fr', 'en', 'ru'] as $lang) {
            $strings = file_get_contents($root . '/lang/' . $lang . '/local_subscriptions.php');
            self::assertStringContainsString("$" . "string['commerce_points_history_personal']", $strings);
            self::assertStringContainsString("$" . "string['commerce_points_history_link']", $strings);
        }
    }
}
