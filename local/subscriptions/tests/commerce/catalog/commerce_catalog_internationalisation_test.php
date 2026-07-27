<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

final class commerce_catalog_internationalisation_test extends advanced_testcase {
    public function test_product_type_labels_do_not_expose_machine_codes(): void {
        $this->resetAfterTest();
        $this->assertNotSame('course_access', CommerceProductPresentation::type_label('course_access'));
        $this->assertNotSame('digital_download', CommerceProductPresentation::type_label('digital_download'));
    }

    public function test_step_labels_do_not_contain_embedded_numbers(): void {
        $this->resetAfterTest();
        foreach (['information', 'components', 'pricing', 'preview'] as $step) {
            $label = get_string('commerce_product_step_' . $step, 'local_subscriptions');
            $this->assertDoesNotMatchRegularExpression('/^\s*\d+[.)]/', $label);
        }
    }
}
