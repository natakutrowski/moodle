<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_offer_order_j15a3_test extends \advanced_testcase {
    public function test_resolver_declares_pdf_bundle_course_merchandising_order(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "\$roles = ['pdf', 'bundle', 'course'];",
            $source
        );
    }
}
