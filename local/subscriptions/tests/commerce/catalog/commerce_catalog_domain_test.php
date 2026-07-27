<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/**
 * Tests for Phase 7.94B1 Native Commerce catalogue domain objects.
 *
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProduct
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProductComponent
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProductPrice
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProductStatus
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProductTranslation
 * @covers \local_subscriptions\commerce\catalog\domain\CommerceProductType
 */
final class commerce_catalog_domain_test extends advanced_testcase {

    public function test_product_normalises_identity_and_checks_availability(): void {
        $product = new CommerceProduct(
            ' a1.full ',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1 Full',
            'Accès complet',
            ['family' => 'subscription'],
            12,
            100,
            200
        );

        $this->assertSame('A1.FULL', $product->get_sku());
        $this->assertSame('course_access', $product->get_type());
        $this->assertTrue($product->is_active());
        $this->assertFalse($product->is_available_at(99));
        $this->assertTrue($product->is_available_at(100));
        $this->assertTrue($product->is_available_at(200));
        $this->assertFalse($product->is_available_at(201));
        $this->assertSame('subscription', $product->get_metadata_value('family'));
    }

    public function test_archived_product_is_never_available(): void {
        $product = new CommerceProduct(
            'PDF.MOBILE',
            CommerceProductType::DIGITAL_DOWNLOAD,
            CommerceProductStatus::ARCHIVED,
            'PDF Mobile'
        );

        $this->assertTrue($product->is_archived());
        $this->assertFalse($product->is_available_at(time()));
    }

    public function test_custom_extensible_product_type_is_supported(): void {
        $product = new CommerceProduct(
            'WORKSHOP.ORAL',
            'live_workshop',
            CommerceProductStatus::DRAFT,
            'Atelier oral'
        );

        $this->assertSame('live_workshop', $product->get_type());
        $this->assertFalse(CommerceProductType::is_known($product->get_type()));
    }

    public function test_invalid_product_availability_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommerceProduct(
            'A1.FULL',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'A1 Full',
            '',
            [],
            null,
            200,
            100
        );
    }

    public function test_price_uses_existing_integer_money_value(): void {
        $price = new CommerceProductPrice(
            'a1.full',
            CommerceMoney::from_minor(25000, 'eur'),
            true,
            'STRIPE',
            'price_a1_full'
        );

        $this->assertSame('A1.FULL', $price->get_product_sku());
        $this->assertSame(25000, $price->get_amount_minor());
        $this->assertSame('EUR', $price->get_currency());
        $this->assertSame('stripe', $price->get_provider());
        $this->assertSame('price_a1_full', $price->get_provider_price_id());
    }

    public function test_provider_price_id_requires_provider(): void {
        $this->expectException(\coding_exception::class);

        new CommerceProductPrice(
            'A1.FULL',
            CommerceMoney::from_minor(25000, 'EUR'),
            true,
            null,
            'price_a1_full'
        );
    }

    public function test_translation_normalises_moodle_language_code(): void {
        $translation = new CommerceProductTranslation(
            'a1.full',
            'FR',
            'Français A1 complet',
            'Cours complet',
            'Description longue'
        );

        $this->assertSame('A1.FULL', $translation->get_product_sku());
        $this->assertSame('fr', $translation->get_language());
        $this->assertSame('Français A1 complet', $translation->get_name());
    }

    public function test_bundle_component_cannot_reference_itself(): void {
        $this->expectException(\coding_exception::class);

        new CommerceProductComponent('PACK.ESSENTIEL', 'pack.essentiel');
    }

    public function test_bundle_component_preserves_quantity_and_order(): void {
        $component = new CommerceProductComponent(
            'PACK.ESSENTIEL',
            'PDF.MOBILE',
            2,
            10,
            ['optional' => false]
        );

        $this->assertSame('PACK.ESSENTIEL', $component->get_parent_product_sku());
        $this->assertSame('PDF.MOBILE', $component->get_child_product_sku());
        $this->assertSame(2, $component->get_quantity());
        $this->assertSame(10, $component->get_sort_order());
    }

    public function test_entitlement_definition_distinguishes_lifetime_and_duration(): void {
        $lifetime = new CommerceProductEntitlementDefinition(
            'A1.FULL',
            'course_access',
            'course:17'
        );

        $temporary = new CommerceProductEntitlementDefinition(
            'WORKSHOP.ORAL',
            'session_access',
            'session:2026-09',
            86400
        );

        $this->assertTrue($lifetime->is_lifetime());
        $this->assertFalse($temporary->is_lifetime());
        $this->assertSame(86400, $temporary->get_duration_seconds());
    }

    public function test_non_positive_entitlement_duration_is_rejected(): void {
        $this->expectException(\coding_exception::class);

        new CommerceProductEntitlementDefinition(
            'A1.FULL',
            'course_access',
            'course:17',
            0
        );
    }
}
