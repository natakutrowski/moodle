<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

/**
 * M7.5B2 regression: merge UI must not call fullname() on partial projections.
 */
final class local_subscriptions_commerce_customer_merge_ui_fullname_m75b2_test extends advanced_testcase {
    public function test_merge_page_routes_user_names_through_safe_helper(): void {
        global $CFG;

        $path = $CFG->dirroot .
            '/local/subscriptions/admin/commerce/customer-identities/merge.php';
        $source = (string)file_get_contents($path);

        self::assertStringContainsString('$mergefullname = static function', $source);
        self::assertStringContainsString(
            '$DB->get_record(',
            $source
        );
        self::assertStringContainsString("'firstnamephonetic'", $source);
        self::assertStringContainsString("'lastnamephonetic'", $source);
        self::assertStringContainsString("'middlename'", $source);
        self::assertStringContainsString("'alternatename'", $source);

        self::assertStringContainsString('$mergefullname($selecteduser)', $source);
        self::assertStringContainsString('$mergefullname($candidate)', $source);
        self::assertStringContainsString('$mergefullname($profile->user)', $source);
        self::assertStringContainsString('$mergefullname($target->user)', $source);

        preg_match_all('/(?<![A-Za-z0-9_])fullname\s*\(/', $source, $matches);
        self::assertCount(1, $matches[0]);
        self::assertStringContainsString('return fullname($user);', $source);
    }
}
