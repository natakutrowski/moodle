<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

final class commerce_bundle_phase_certification_test extends advanced_testcase {
    public function test_product_types_have_human_readable_labels(): void {
        $this->resetAfterTest();

        self::assertSame(
            get_string('commerce_vocabulary_product_type_crm_bundle', 'local_subscriptions'),
            CommerceProductPresentation::type_label('bundle')
        );
        self::assertSame(
            get_string('commerce_vocabulary_product_type_crm_course_access', 'local_subscriptions'),
            CommerceProductPresentation::type_label('course_access')
        );
    }

    public function test_technical_entitlement_is_presented_for_humans(): void {
        $this->resetAfterTest();

        $label = CommerceProductPresentation::entitlement_label('course', 'course:13:full');

        self::assertStringContainsString('13', $label);
        self::assertStringNotContainsString('course:13:full', $label);
    }
}
