<?php
// Regression: standalone Lovable iframe must initialise $PAGE context before Commerce price resolving.
declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_gustave_lovable_frame_context_test extends \advanced_testcase {
    public function test_frame_initialises_context_after_auth_and_before_commerce_resolver(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertIsString($source);
        $login = strpos($source, 'require_login();');
        $context = strpos($source, '$PAGE->set_context($context);');
        $access = strpos($source, 'require_capability(\'local/subscriptions:manage_showrooms\', $context);');
        $resolve = strpos($source, 'Link::read_only_review($DB, $id, $currency);');
        self::assertNotFalse($login);
        self::assertNotFalse($context);
        self::assertNotFalse($access);
        self::assertNotFalse($resolve);
        self::assertLessThan($context, $login);
        self::assertLessThan($access, $context);
        self::assertLessThan($resolve, $access);
    }

    public function test_frame_keeps_isolated_preview_and_no_checkout(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/lovable_cms_frame.php');
        self::assertIsString($source);
        self::assertStringContainsString('Draft::require_draft($DB, $id)', $source);
        self::assertStringContainsString('sandbox allow-scripts', $source);
        self::assertStringContainsString("connect-src 'none'", $source);
        self::assertStringContainsString("form-action 'none'", $source);
        self::assertStringNotContainsString('allow-same-origin', $source);
        self::assertStringNotContainsString('cart_action.php', $source);
    }
}
