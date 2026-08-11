<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceLanguagePresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\presentation\CommercePresentationContext;

final class commerce_products_polish_test extends advanced_testcase {
    public function test_product_type_select_labels_are_human_readable(): void {
        $this->resetAfterTest();

        $this->assertSame(
            get_string('commerce_vocabulary_product_type_crm_course_access', 'local_subscriptions'),
            CommerceProductPresentation::type_label('course_access')
        );
        $this->assertSame(
            get_string('commerce_vocabulary_product_type_crm_digital_download', 'local_subscriptions'),
            CommerceProductPresentation::type_label('digital_download')
        );
    }

    public function test_entitlement_html_hides_technical_reference_outside_diagnostics(): void {
        $this->resetAfterTest();

        $crmhtml = CommerceProductPresentation::entitlement_html(
            'course',
            'course:13:full'
        );

        $this->assertStringContainsString('crm-commerce-entitlement-label', $crmhtml);
        $this->assertStringNotContainsString('crm-commerce-technical-reference', $crmhtml);
        $this->assertStringNotContainsString('(course:13:full)', $crmhtml);

        $diagnostichtml = CommerceProductPresentation::entitlement_html(
            'course',
            'course:13:full',
            null,
            CommercePresentationContext::DIAGNOSTIC
        );

        $this->assertStringContainsString('crm-commerce-entitlement-label', $diagnostichtml);
        $this->assertStringContainsString('crm-commerce-technical-reference', $diagnostichtml);
        $this->assertStringContainsString('(course:13:full)', $diagnostichtml);
    }

    public function test_known_resource_keys_remain_human_readable_when_stored_type_is_generic(): void {
        $this->resetAfterTest();

        $coursehtml = CommerceProductPresentation::entitlement_html(
            'other',
            'course:13:full',
            null,
            CommercePresentationContext::DIAGNOSTIC
        );
        $this->assertStringContainsString('Course #13', $coursehtml);
        $this->assertStringNotContainsString('Autre droit : course:13:full', $coursehtml);
        $this->assertStringContainsString('(course:13:full)', $coursehtml);

        $digitalhtml = CommerceProductPresentation::entitlement_html(
            'other',
            'digital-product:2',
            null,
            CommercePresentationContext::DIAGNOSTIC
        );
        $this->assertStringContainsString('Digital product #2', $digitalhtml);
        $this->assertStringNotContainsString('Autre droit : digital-product:2', $digitalhtml);
        $this->assertStringContainsString('(digital-product:2)', $digitalhtml);
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
