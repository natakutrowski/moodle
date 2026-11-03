<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalActivityXpDisplayService;

/** Read-only presentation: never infer promotion XP from Moodle completion alone. */
final class commerce_798_promotion_xp_activity_badges_test extends advanced_testcase {
    public function test_activity_states_are_derived_from_proof_not_from_course_completion(): void {
        self::assertSame('earned', CommercePedagogicalActivityXpDisplayService::classify(true, false));
        self::assertSame('earned', CommercePedagogicalActivityXpDisplayService::classify(true, true));
        self::assertSame('retry', CommercePedagogicalActivityXpDisplayService::classify(false, true));
        self::assertSame('potential', CommercePedagogicalActivityXpDisplayService::classify(false, false));
    }

    public function test_no_active_promotion_means_no_student_badges_and_no_new_xp_rows(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $before = $DB->count_records('local_subs_commerce_ped_xp');
        $badges = CommercePedagogicalActivityXpDisplayService::create($DB)
            ->for_user_course((int)$user->id, (int)$course->id);
        self::assertSame([], $badges);
        self::assertSame($before, $DB->count_records('local_subs_commerce_ped_xp'));
        self::assertSame([], CommercePedagogicalActivityXpDisplayService::create($DB)
            ->for_user_course(0, (int)$course->id));
        self::assertSame([], CommercePedagogicalActivityXpDisplayService::create($DB)
            ->for_user_course((int)$user->id, (int)SITEID));
    }
}
