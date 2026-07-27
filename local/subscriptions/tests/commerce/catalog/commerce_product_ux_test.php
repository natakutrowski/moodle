<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

final class commerce_product_ux_test extends advanced_testcase {
    public function test_step_labels_are_presentation_neutral(): void {
        $this->resetAfterTest();
        foreach (['information', 'components', 'pricing', 'preview'] as $step) {
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*\d+[.)]/',
                get_string('commerce_product_step_' . $step, 'local_subscriptions')
            );
        }
    }

    public function test_machine_product_types_have_human_labels(): void {
        $this->resetAfterTest();
        $this->assertNotSame('course_access', CommerceProductPresentation::type_label('course_access'));
        $this->assertNotSame('digital_download', CommerceProductPresentation::type_label('digital_download'));
    }
}
