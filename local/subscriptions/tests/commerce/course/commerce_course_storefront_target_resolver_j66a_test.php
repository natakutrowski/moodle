<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.6A structural checks for restriction offer resolution. */
final class commerce_course_storefront_target_resolver_j66a_test
        extends \advanced_testcase {

    public function test_resolver_deduplicates_native_products_by_sku(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/course/storefront/'
            . 'CommerceCourseStorefrontTargetResolver.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$products[$sku] = $product;',
            $source
        );
        $this->assertStringContainsString(
            'return array_values($products);',
            $source
        );
        $this->assertStringContainsString(
            'count_offers',
            $source
        );
    }

    public function test_availability_detects_specific_levels_before_generic_member_case(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/course/format/classes/output/local/content/section/availability.php');
        $grammar = strpos($source, "in_array('grammarstudent', \$allowed, true)");
        $full = strpos($source, "in_array('student', \$allowed, true)");
        $subscriber = strpos($source, "in_array('trialstudent', \$forbidden, true)");
        $this->assertIsInt($grammar);
        $this->assertIsInt($full);
        $this->assertIsInt($subscriber);
        $this->assertLessThan($full, $grammar);
        $this->assertLessThan($subscriber, $full);
        $this->assertStringContainsString("\$restrictiontype = 'grammar';", $source);
        $this->assertStringContainsString("\$restrictiontype = 'full';", $source);
        $this->assertStringContainsString("\$restrictiontype = 'subscriber';", $source);
    }
}
