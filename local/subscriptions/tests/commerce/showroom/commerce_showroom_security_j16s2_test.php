<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_security_j16s2_test extends \advanced_testcase {
    public function test_public_router_converts_only_showroom_not_found_to_clean_404(): void {
        global $CFG;

        $router = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/public_router.php'
        );

        self::assertStringContainsString(
            "if (\$route === CommerceRouteRegistry::SHOWROOM)",
            $router
        );
        self::assertStringContainsString(
            "catch (\\moodle_exception \$exception)",
            $router
        );
        self::assertStringContainsString(
            "\$exception->errorcode !== 'commerce_showroom_not_found'",
            $router
        );
        self::assertStringContainsString(
            'throw $exception;',
            $router
        );
        self::assertStringContainsString(
            'http_response_code(404);',
            $router
        );
        self::assertStringContainsString(
            "\$PAGE->set_pagelayout('standard');",
            $router
        );
        self::assertStringNotContainsString(
            'print_exception',
            $router
        );
    }
}
