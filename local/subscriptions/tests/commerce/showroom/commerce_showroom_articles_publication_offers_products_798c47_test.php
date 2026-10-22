<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationIntegrityValidator;

final class commerce_showroom_articles_publication_offers_products_798c47_test extends \advanced_testcase {
    public function test_legacy_skus_field_is_not_required_anymore(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('offers', ['course', 'bundle', 'donation']);
        $skus = null;
        foreach ($schema['fields'] as $field) {
            if (($field['name'] ?? '') === 'skus') {
                $skus = $field;
                break;
            }
        }

        self::assertIsArray($skus);
        self::assertEmpty($skus['required']);
    }

    public function test_offers_publication_accepts_real_product_mapping(): void {
        $reflection = new \ReflectionClass(CommerceShowroomPublicationIntegrityValidator::class);
        $validator = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('validate_offer_products');
        $method->setAccessible(true);

        $method->invoke($validator, 'offers', [
            'course' => 'COURSE.ARTICLES',
            'bundle' => 'BUNDLE.ARTICLES.ALF',
            'donation' => 'DONATION.ALF',
        ]);

        self::assertTrue(true);
    }

    public function test_offers_publication_still_fails_without_any_product_mapping(): void {
        $reflection = new \ReflectionClass(CommerceShowroomPublicationIntegrityValidator::class);
        $validator = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('validate_offer_products');
        $method->setAccessible(true);

        try {
            $method->invoke($validator, 'offers', []);
            self::fail('Expected publication integrity failure.');
        } catch (\moodle_exception $exception) {
            self::assertSame('commerce_showroom_publish_integrity_failed', $exception->errorcode);
            self::assertStringContainsString('no product is configured for offers block', $exception->a);
        }
    }
}
