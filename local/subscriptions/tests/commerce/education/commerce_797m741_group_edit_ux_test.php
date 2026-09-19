<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797m741_group_edit_ux_test extends advanced_testcase {
    public function test_group_admin_exposes_editable_metadata_and_telegram_in_card(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/education/groups.php');
        $styles = file_get_contents($root . '/styles.css');

        self::assertStringContainsString("'action', 'value' => 'edit'", $source);
        self::assertStringContainsString("'name' => 'displayname'", $source);
        self::assertStringContainsString("'name' => 'tutorname'", $source);
        self::assertStringContainsString("], 'supportlang', \$group->get_support_language() ?? '', false, [", $source);
        self::assertStringContainsString("'name' => 'telegramref'", $source);
        self::assertStringContainsString('commerce-ped-m74-telegram-url', $source);
        self::assertStringContainsString('commerce-ped-m741-group-edit', $source);

        self::assertStringContainsString('.commerce-ped-m73-panel-icon::before', $styles);
        self::assertStringContainsString('.commerce-ped-m74-panel-icon::before', $styles);
        self::assertStringContainsString('place-items: center', $styles);
        self::assertStringContainsString('.commerce-ped-m74-group-meta-item.is-wide', $styles);

        // M5 contract remains intact: XP is informational, never editable here.
        self::assertStringNotContainsString("'name' => 'levelupxp'", $source);
        self::assertStringNotContainsString("optional_param('levelupxp'", $source);
    }

    public function test_orchestrator_updates_group_metadata_and_backing_moodle_group_name(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/group/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $now = time();
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm741-edit',
                'M7.4.1 edit',
                (int)$course->id,
                CommercePedagogicalPromotionStatus::STARTED,
                true,
                null,
                null,
                $now,
                null,
                null,
                null,
                null,
                $now,
                $now
            )
        );

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $group = $orchestrator->create_group(
            (int)$promotion->get_id(),
            'Les Anciennes Cigales',
            0,
            null,
            'fr',
            'https://t.me/old-cicadas',
            null,
            null,
            $now,
            'Ancien tuteur'
        );

        $updated = $orchestrator->update_group(
            (int)$promotion->get_id(),
            (int)$group->get_id(),
            'Les Cigales',
            'Nicolas',
            'en',
            'https://t.me/cigales',
            null,
            $now + 5
        );

        self::assertSame('Les Cigales', $updated->get_display_name());
        self::assertSame('Nicolas', $updated->get_tutor_name());
        self::assertSame('en', $updated->get_support_language());
        self::assertSame('https://t.me/cigales', $updated->get_telegram_reference());
        self::assertSame($group->get_product_id(), $updated->get_product_id());
        self::assertSame($group->get_levelup_xp(), $updated->get_levelup_xp());

        $moodlegroup = groups_get_group($updated->get_moodle_group_id());
        self::assertNotFalse($moodlegroup);
        self::assertSame('Les Cigales · m741-edit', $moodlegroup->name);
    }
}
