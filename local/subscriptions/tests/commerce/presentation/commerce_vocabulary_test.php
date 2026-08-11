<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\presentation\CommercePresentationContext;
use local_subscriptions\commerce\presentation\CommercePresentationLabel;
use local_subscriptions\commerce\presentation\CommerceVocabulary;

final class commerce_vocabulary_test extends \advanced_testcase {
    public function test_context_normalisation_and_validation(): void {
        $this->assertSame(
            CommercePresentationContext::CLIENT,
            CommercePresentationContext::require_valid(' CLIENT ')
        );
        $this->assertSame(
            [
                CommercePresentationContext::CLIENT,
                CommercePresentationContext::CRM,
                CommercePresentationContext::DIAGNOSTIC,
            ],
            CommercePresentationContext::all()
        );
        $this->assertFalse(
            CommercePresentationContext::allows_technical_details(CommercePresentationContext::CLIENT)
        );
        $this->assertFalse(
            CommercePresentationContext::allows_technical_details(CommercePresentationContext::CRM)
        );
        $this->assertTrue(
            CommercePresentationContext::allows_technical_details(CommercePresentationContext::DIAGNOSTIC)
        );
    }

    public function test_invalid_context_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommercePresentationContext::require_valid('public');
    }

    public function test_presentation_label_never_exposes_reference_outside_diagnostic(): void {
        $label = new CommercePresentationLabel(
            'Achat payé',
            CommercePresentationLabel::INTENT_SUCCESS,
            'purchase_status:paid'
        );

        $this->assertNull($label->diagnostic_reference(CommercePresentationContext::CLIENT));
        $this->assertNull($label->diagnostic_reference(CommercePresentationContext::CRM));
        $this->assertSame(
            'purchase_status:paid',
            $label->diagnostic_reference(CommercePresentationContext::DIAGNOSTIC)
        );

        $this->assertSame(
            [
                'label' => 'Achat payé',
                'intent' => CommercePresentationLabel::INTENT_SUCCESS,
                'technicalreference' => null,
            ],
            $label->export(CommercePresentationContext::CLIENT)
        );
    }

    public function test_empty_label_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        new CommercePresentationLabel('   ');
    }

    public function test_invalid_intent_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        new CommercePresentationLabel('Libellé', 'primary');
    }

    public function test_product_type_uses_distinct_client_and_crm_labels(): void {
        $client = CommerceVocabulary::product_type('bundle', CommercePresentationContext::CLIENT);
        $crm = CommerceVocabulary::product_type('bundle', CommercePresentationContext::CRM);
        $diagnostic = CommerceVocabulary::product_type('bundle', CommercePresentationContext::DIAGNOSTIC);

        $this->assertSame(get_string('commerce_vocabulary_product_type_client_bundle', 'local_subscriptions'), $client->label());
        $this->assertSame(get_string('commerce_vocabulary_product_type_crm_bundle', 'local_subscriptions'), $crm->label());
        $this->assertSame($crm->label(), $diagnostic->label());
        $this->assertSame(CommercePresentationLabel::INTENT_SUCCESS, $client->intent());
        $this->assertNull($client->diagnostic_reference(CommercePresentationContext::CLIENT));
        $this->assertSame(
            'product_type:bundle',
            $diagnostic->diagnostic_reference(CommercePresentationContext::DIAGNOSTIC)
        );
    }

    public function test_values_are_normalised_before_lookup(): void {
        $expected = CommerceVocabulary::payment_status(
            'partially_refunded',
            CommercePresentationContext::CRM
        );

        foreach ([' PARTIALLY-REFUNDED ', 'partially refunded', 'partially_refunded'] as $value) {
            $actual = CommerceVocabulary::payment_status($value, CommercePresentationContext::CRM);
            $this->assertSame($expected->label(), $actual->label());
            $this->assertSame($expected->intent(), $actual->intent());
        }
    }

    public function test_unknown_values_use_human_fallback_without_raw_value(): void {
        $rawvalue = 'internal_state_42';
        $client = CommerceVocabulary::purchase_status($rawvalue, CommercePresentationContext::CLIENT);
        $crm = CommerceVocabulary::purchase_status($rawvalue, CommercePresentationContext::CRM);

        $this->assertSame(
            get_string('commerce_vocabulary_purchase_status_unknown', 'local_subscriptions'),
            $client->label()
        );
        $this->assertSame($client->label(), $crm->label());
        $this->assertStringNotContainsString($rawvalue, $client->label());
        $this->assertNull($client->diagnostic_reference(CommercePresentationContext::CLIENT));
        $this->assertSame(
            'purchase_status:internal_state_42',
            $crm->diagnostic_reference(CommercePresentationContext::DIAGNOSTIC)
        );
    }

    public function test_empty_value_uses_human_fallback_and_stable_diagnostic_reference(): void {
        $label = CommerceVocabulary::fulfillment_status('', CommercePresentationContext::DIAGNOSTIC);

        $this->assertSame(
            get_string('commerce_vocabulary_fulfillment_status_unknown', 'local_subscriptions'),
            $label->label()
        );
        $this->assertSame(
            'fulfillment_status:empty',
            $label->diagnostic_reference(CommercePresentationContext::DIAGNOSTIC)
        );
    }

    public function test_status_intents_are_stable(): void {
        $this->assertSame(
            CommercePresentationLabel::INTENT_SUCCESS,
            CommerceVocabulary::purchase_status('paid')->intent()
        );
        $this->assertSame(
            CommercePresentationLabel::INTENT_WARNING,
            CommerceVocabulary::payment_status('pending')->intent()
        );
        $this->assertSame(
            CommercePresentationLabel::INTENT_DANGER,
            CommerceVocabulary::fulfillment_status('failed')->intent()
        );
        $this->assertSame(
            CommercePresentationLabel::INTENT_MUTED,
            CommerceVocabulary::product_status('archived')->intent()
        );
    }

    public function test_all_vocabulary_families_return_non_empty_labels(): void {
        $labels = [
            CommerceVocabulary::product_type('course_access'),
            CommerceVocabulary::product_status('active'),
            CommerceVocabulary::purchase_status('fulfilled'),
            CommerceVocabulary::payment_status('paid'),
            CommerceVocabulary::fulfillment_status('completed'),
            CommerceVocabulary::access_type('course'),
        ];

        foreach ($labels as $label) {
            $this->assertNotSame('', trim($label->label()));
        }
    }

    public function test_declared_strings_exist_in_supported_languages_without_deprecated_get_string_usage(): void {
        $keys = [
            'commerce_vocabulary_product_type_client_bundle',
            'commerce_vocabulary_product_type_crm_bundle',
            'commerce_vocabulary_product_type_unknown',
            'commerce_vocabulary_purchase_status_client_paid',
            'commerce_vocabulary_purchase_status_crm_paid',
            'commerce_vocabulary_purchase_status_unknown',
            'commerce_vocabulary_payment_status_client_pending',
            'commerce_vocabulary_payment_status_crm_pending',
            'commerce_vocabulary_payment_status_unknown',
            'commerce_vocabulary_fulfillment_status_client_fulfilled',
            'commerce_vocabulary_fulfillment_status_crm_fulfilled',
            'commerce_vocabulary_fulfillment_status_unknown',
        ];
        $stringmanager = get_string_manager();

        foreach (['fr', 'en', 'ru'] as $language) {
            foreach ($keys as $key) {
                $this->assertTrue(
                    $stringmanager->string_exists($key, 'local_subscriptions'),
                    'Missing language key: ' . $key
                );

                $translation = $stringmanager->get_string(
                    $key,
                    'local_subscriptions',
                    null,
                    $language
                );

                $this->assertNotSame('', trim($translation));
                $this->assertNotSame('[[' . $key . ']]', $translation);
            }
        }
    }

}
