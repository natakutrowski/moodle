<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroup;

final class commerce_797j8_group_admin_polish_test extends advanced_testcase {
    public function test_group_admin_has_no_manual_order_or_levelup_xp_input(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/groups.php'
        );

        self::assertStringNotContainsString(
            "'name' => 'position'",
            $source
        );
        self::assertStringNotContainsString(
            "optional_param('levelupxp'",
            $source
        );
        self::assertStringNotContainsString(
            "'levelupxp', 'number'",
            $source
        );
        self::assertStringContainsString(
            'groups_for_promotion($promotionid)',
            $source
        );
        self::assertStringContainsString(
            'get_position()',
            $source
        );
    }

    public function test_group_admin_uses_free_text_tutor_and_controlled_languages(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/admin/commerce/education/groups.php'
        );

        self::assertStringContainsString(
            "'name' => 'tutorname'",
            $source
        );
        self::assertStringNotContainsString(
            "'name' => 'tutorid'",
            $source
        );
        self::assertStringContainsString(
            "['fr', 'ru', 'en']",
            $source
        );
        self::assertStringContainsString(
            "'type' => 'url'",
            $source
        );
        self::assertStringContainsString(
            "PARAM_URL",
            $source
        );
    }

    public function test_group_domain_can_hold_external_tutor_name(): void {
        $group = new CommercePedagogicalGroup(
            1,
            2,
            3,
            'Les Cigales',
            0,
            null,
            'fr',
            'https://t.me/cigales',
            null,
            true,
            null,
            null,
            100,
            100,
            'Nata'
        );

        self::assertSame(
            'Nata',
            $group->get_tutor_name()
        );
        self::assertNull($group->get_tutor_id());
    }

    public function test_schema_contains_external_tutor_name_field(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertStringContainsString(
            'NAME="tutorname" TYPE="char" LENGTH="255"',
            $install
        );
        self::assertStringContainsString(
            "'tutorname'",
            $upgrade
        );
        self::assertStringContainsString(
            '2026091007',
            $upgrade
        );
    }
}
