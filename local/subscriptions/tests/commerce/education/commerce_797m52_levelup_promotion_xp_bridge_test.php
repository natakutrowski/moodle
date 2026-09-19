<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupXpBridgeService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;

/**
 * @covers \local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupXpBridgeService
 */
final class commerce_797m52_levelup_promotion_xp_bridge_test extends \advanced_testcase {
    public function test_course_context_gain_is_isolated_to_current_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);

        [$promotionid, $course, $user] = $this->create_current_participation('m52-course');

        $service = CommercePedagogicalLevelupXpBridgeService::create($DB);
        $repository = CommercePedagogicalXpRepository::create($DB);

        $first = $service->record_gain((int)$user->id, (int)$course->id, 25, time(), 'gain-a');
        $this->assertNotNull($first);
        $this->assertTrue($first->was_created());
        $this->assertSame(25, $repository->user_points($promotionid, (int)$user->id));

        $retry = $service->record_gain((int)$user->id, (int)$course->id, 25, time(), 'gain-a');
        $this->assertNotNull($retry);
        $this->assertFalse($retry->was_created());
        $this->assertSame(25, $repository->user_points($promotionid, (int)$user->id));

        $second = $service->record_gain((int)$user->id, (int)$course->id, 25, time(), 'gain-b');
        $this->assertNotNull($second);
        $this->assertTrue($second->was_created());
        $this->assertSame(50, $repository->user_points($promotionid, (int)$user->id));

        $DB->set_field('local_subs_commerce_ped_join', 'state', 'cancelled', [
            'promotionid' => $promotionid,
            'userid' => (int)$user->id,
        ]);
        $ignored = $service->record_gain((int)$user->id, (int)$course->id, 10, time(), 'gain-c');
        $this->assertNull($ignored);
        $this->assertSame(50, $repository->user_points($promotionid, (int)$user->id));
    }

    public function test_global_gain_is_projected_to_each_current_promotion_only(): void {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        [$promotiona, $coursea] = $this->create_current_participation_for_user($user, 'm52-global-a', 'started');
        [$promotionb, $courseb] = $this->create_current_participation_for_user($user, 'm52-global-b', 'scheduled');
        [$promotionarchived] = $this->create_current_participation_for_user($user, 'm52-global-old', 'archived');

        $service = CommercePedagogicalLevelupXpBridgeService::create($DB);
        $repository = CommercePedagogicalXpRepository::create($DB);

        $results = $service->record_global_gain((int)$user->id, 7, time(), 'global-gain-a');

        $this->assertArrayHasKey($promotiona, $results);
        $this->assertArrayHasKey($promotionb, $results);
        $this->assertArrayNotHasKey($promotionarchived, $results);
        $this->assertSame(7, $repository->user_points($promotiona, (int)$user->id));
        $this->assertSame(7, $repository->user_points($promotionb, (int)$user->id));
        $this->assertSame(0, $repository->user_points($promotionarchived, (int)$user->id));

        // Replaying one global gain is idempotent independently in each promotion.
        $retry = $service->record_global_gain((int)$user->id, 7, time(), 'global-gain-a');
        $this->assertFalse($retry[$promotiona]->was_created());
        $this->assertFalse($retry[$promotionb]->was_created());
        $this->assertSame(7, $repository->user_points($promotiona, (int)$user->id));
        $this->assertSame(7, $repository->user_points($promotionb, (int)$user->id));

        // Sanity: distinct course relations were really created.
        $this->assertNotSame((int)$coursea->id, (int)$courseb->id);
    }

    public function test_user_without_current_promotion_is_ignored_in_both_modes(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $service = CommercePedagogicalLevelupXpBridgeService::create($DB);
        $this->assertNull($service->record_gain((int)$user->id, (int)$course->id, 10, time(), 'gain-none'));
        $this->assertSame([], $service->record_global_gain((int)$user->id, 10, time(), 'global-none'));
    }

    /** @return array{0:int,1:\stdClass,2:\stdClass} */
    private function create_current_participation(string $prefix): array {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        [$promotionid] = $this->create_current_participation_for_user($user, $prefix, 'scheduled', $course);
        return [$promotionid, $course, $user];
    }

    /** @return array{0:int,1:\stdClass} */
    private function create_current_participation_for_user(
        \stdClass $user,
        string $prefix,
        string $status,
        ?\stdClass $course = null
    ): array {
        global $DB;
        $course ??= $this->getDataGenerator()->create_course();
        $now = time();

        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => $prefix . '-' . bin2hex(random_bytes(4)),
            'name' => 'M5.2 ' . $prefix,
            'courseid' => (int)$course->id,
            'status' => $status,
            'published' => 1,
            'salesopensat' => $now - 60,
            'salesclosesat' => $now + 3600,
            'startsat' => null,
            'endsat' => null,
            'capacitytotal' => 10,
            'createdby' => 2,
            'modifiedby' => 2,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => strtoupper($prefix) . '-' . bin2hex(random_bytes(5)),
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'M5.2 product',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid,
            'courseid' => (int)$course->id,
            'productid' => $productid,
            'userid' => (int)$user->id,
            'purchasereference' => $prefix . '-ref-' . bin2hex(random_bytes(4)),
            'state' => 'active',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'userid' => (int)$user->id,
            'courseid' => (int)$course->id,
            'promotionid' => $promotionid,
            'profile' => 'promotion_progressive',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return [$promotionid, $course];
    }
}
