<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6A1 restriction semantics and resolver instrumentation. */
final class commerce_restriction_j66a1_test extends \advanced_testcase {
    public function test_generic_trial_restriction_is_not_relabelled_as_full(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/course/format/classes/output/local/content/section/'
            . 'availability.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$restrictiontype = 'subscriber';",
            $source
        );
        $this->assertStringContainsString(
            "\$titlekey = 'unlock_course_title';",
            $source
        );
        $this->assertStringNotContainsString(
            "\$presentationtype = 'full';",
            $source
        );
    }

    public function test_native_entitlement_resolution_precedes_legacy_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/course/storefront/'
            . 'CommerceCourseStorefrontTargetResolver.php'
        );

        $this->assertIsString($source);
        $native = strpos($source, '$this->resolve_native');
        $legacy = strpos($source, '$this->resolve_legacy');

        $this->assertIsInt($native);
        $this->assertIsInt($legacy);
        $this->assertLessThan($legacy, $native);
        $this->assertStringContainsString(
            'native_entitlement_definition',
            $source
        );
    }

    public function test_temporary_debug_is_admin_only(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/course/format/classes/output/local/content/section/'
            . 'availability.php'
        );

        $this->assertStringContainsString(
            "optional_param('campusrestrictionsdebug'",
            $source
        );
        $this->assertStringContainsString(
            "'moodle/site:config'",
            $source
        );
    }
}
