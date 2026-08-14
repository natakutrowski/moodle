<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;

final class commerce_showroom_pricing_validity_m14_2_test extends advanced_testcase {
    public function test_product_promotion_accepts_paris_date_and_time(): void {
        $editor = new CommerceStorefrontPageEditor();
        $metadata = $editor->merge_submission([], [
            'promotion_eur_compare' => '55.00',
            'promotion_eur_start' => '2026-08-14T12:00',
            'promotion_eur_end' => '2026-08-16T23:59',
        ], 'fr');

        $promotion = $metadata['storefront']['merchandising']['promotions']['EUR'];
        self::assertSame(
            (new \DateTimeImmutable('2026-08-14T12:00', new \DateTimeZone('Europe/Paris')))->getTimestamp(),
            $promotion['start']
        );
        self::assertSame(
            (new \DateTimeImmutable('2026-08-16T23:59', new \DateTimeZone('Europe/Paris')))->getTimestamp(),
            $promotion['end']
        );
    }

    public function test_legacy_date_only_values_keep_end_of_day_semantics(): void {
        $editor = new CommerceStorefrontPageEditor();
        $metadata = $editor->merge_submission([], [
            'promotion_rub_compare' => '5490.00',
            'promotion_rub_start' => '2026-08-14',
            'promotion_rub_end' => '2026-08-16',
        ], 'ru');

        $promotion = $metadata['storefront']['merchandising']['promotions']['RUB'];
        self::assertSame(
            (new \DateTimeImmutable('2026-08-14T00:00:00', new \DateTimeZone('Europe/Paris')))->getTimestamp(),
            $promotion['start']
        );
        self::assertSame(
            (new \DateTimeImmutable('2026-08-16T23:59:59', new \DateTimeZone('Europe/Paris')))->getTimestamp(),
            $promotion['end']
        );
    }

    public function test_showroom_uses_best_bundle_discount_and_renders_validity(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache'
        );
        $producteditor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront.php'
        );

        self::assertIsString($resolver);
        self::assertIsString($template);
        self::assertIsString($producteditor);
        self::assertStringContainsString('$bestcompare = max($candidates);', $resolver);
        self::assertStringContainsString('$configuredcompare', $resolver);
        self::assertStringContainsString('$combinedamount', $resolver);
        self::assertStringContainsString("'haspromotionend'", $resolver);
        self::assertStringContainsString('commerce-showroom-offer__validity', $template);
        self::assertStringContainsString("'type' => 'datetime-local'", $producteditor);
    }
}
