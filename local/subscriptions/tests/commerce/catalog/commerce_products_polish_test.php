<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceLanguagePresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

final class commerce_products_polish_test extends advanced_testcase {
    public function test_product_type_select_labels_are_human_readable(): void {
        $this->resetAfterTest();

        $this->assertSame(
            get_string('commerce_product_type_course_access', 'local_subscriptions'),
            CommerceProductPresentation::type_label('course_access')
        );
        $this->assertSame(
            get_string('commerce_product_type_digital_download', 'local_subscriptions'),
            CommerceProductPresentation::type_label('digital_download')
        );
    }

    public function test_entitlement_html_keeps_small_technical_reference(): void {
        $this->resetAfterTest();

        $html = CommerceProductPresentation::entitlement_html(
            'course',
            'course:13:full'
        );

        $this->assertStringContainsString('crm-commerce-entitlement-label', $html);
        $this->assertStringContainsString('crm-commerce-technical-reference', $html);
        $this->assertStringContainsString('(course:13:full)', $html);
    }

    public function test_language_flags_use_uk_for_english_and_fallback_for_unknown_codes(): void {
        $this->resetAfterTest();

        $this->assertSame('🇬🇧', CommerceLanguagePresentation::flag('en'));
        $this->assertSame('🇮🇹', CommerceLanguagePresentation::flag('it'));
        $this->assertSame('🌐', CommerceLanguagePresentation::flag('xx'));
    }

    public function test_language_variants_use_their_base_language_flag(): void {
        $this->resetAfterTest();

        $this->assertSame('🇬🇧', CommerceLanguagePresentation::flag('en_us'));
        $this->assertSame('🇫🇷', CommerceLanguagePresentation::flag('fr_ca'));
    }
}
