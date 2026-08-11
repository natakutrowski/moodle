<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_security_j16s4_test extends \advanced_testcase {
    public function test_builder_preview_is_admin_only_and_not_public_route(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $edit = file_get_contents(
            $root . 'admin/commerce/showrooms/edit.php'
        );
        $preview = file_get_contents(
            $root . 'admin/commerce/showrooms/preview.php'
        );
        $page = file_get_contents($root . 'showroom.php');

        self::assertStringContainsString(
            '/admin/commerce/showrooms/preview.php',
            $edit
        );
        self::assertStringNotContainsString(
            "new moodle_url('/' . ltrim(\$slug, '/'))",
            $edit
        );

        self::assertStringContainsString('require_login();', $preview);
        self::assertStringContainsString(
            "require_capability('local/subscriptions:manage_showrooms'",
            $preview
        );
        self::assertStringContainsString(
            'CommerceShowroomPreviewDefinitionResolver',
            $preview
        );
        self::assertStringContainsString(
            'CommerceShowroomRuntimeBlockSet::load_preview',
            $preview
        );

        self::assertStringContainsString(
            '$isadminpreview = is_array($adminpreview)',
            $page
        );
        self::assertStringContainsString(
            'if (!$isadminpreview) {',
            $page
        );
        self::assertStringContainsString(
            'noindex,nofollow,noarchive',
            $page
        );
    }

    public function test_preview_currency_endpoint_is_capability_protected(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/preview_prices.php'
        );

        self::assertStringContainsString('require_login();', $source);
        self::assertStringContainsString(
            "require_capability('local/subscriptions:manage_showrooms'",
            $source
        );
        self::assertStringContainsString(
            'CommerceShowroomPreviewDefinitionResolver',
            $source
        );
        self::assertStringNotContainsString(
            'CommerceShowroomPublishedDefinitionResolver',
            $source
        );
        self::assertStringContainsString(
            "header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');",
            $source
        );
    }

    public function test_preview_resolver_has_no_publication_gate(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomPreviewDefinitionResolver.php'
        );

        self::assertStringContainsString(
            'CommerceShowroomCmsDefinitionFactory',
            $source
        );
        self::assertStringNotContainsString(
            'CommerceShowroomStatus::PUBLISHED',
            $source
        );
    }
}
