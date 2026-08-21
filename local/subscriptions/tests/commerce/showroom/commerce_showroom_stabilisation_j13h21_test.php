<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stabilisation_j13h21_test extends \advanced_testcase {
    public function test_admin_statuses_are_localised_and_get_does_not_require_sesskey(): void {
        global $CFG;

        $index = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/index.php'
        );
        $edit = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/edit.php'
        );
        $status = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomStatus.php'
        );

        self::assertIsString($index);
        self::assertIsString($edit);
        self::assertIsString($status);
        self::assertStringContainsString(
            'CommerceShowroomStatus::label',
            $index
        );
        self::assertStringNotContainsString(
            'CommerceShowroomStatus::options()',
            $edit
        );
        self::assertStringContainsString(
            "\$_SERVER['REQUEST_METHOD'] === 'POST'",
            $edit
        );
        self::assertStringContainsString(
            'require_sesskey();',
            $edit
        );
        self::assertStringContainsString(
            'CommerceShowroomStatus::label',
            $edit
        );
        self::assertStringNotContainsString(
            'if ($id > 0 && confirm_sesskey())',
            $edit
        );
    }

    public function test_live_currency_endpoint_and_offer_anchor_are_hardened(): void {
        global $CFG;

        $endpoint = file_get_contents($CFG->dirroot . '/local/subscriptions/ajax/showroom_prices.php');
        $javascript = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertIsString($endpoint);
        self::assertIsString($javascript);
        self::assertIsString($template);
        self::assertStringContainsString("Cache-Control: no-store", $endpoint);
        self::assertStringContainsString("\$PAGE->set_context(context_system::instance());", $endpoint);
        self::assertStringContainsString("cache: 'no-store'", $javascript);
        self::assertStringContainsString("rect.bottom - window.innerHeight + bottomPadding", $javascript);
        self::assertStringContainsString("window.location.hash === '#showroom-offers'", $javascript);
        self::assertStringContainsString('commerce-showroom-offers__header', $template);
        self::assertStringContainsString('data-error-message="{{currencyerrormessage}}"', $template);
    }
}
