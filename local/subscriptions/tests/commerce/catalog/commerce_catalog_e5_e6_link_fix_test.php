<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogIdentity;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogProductSummary;
use local_subscriptions\commerce\catalog\status\CommerceCatalogStatusSnapshot;

final class commerce_catalog_e5_e6_link_fix_test extends advanced_testcase {
    public function test_identity_round_trips_for_every_supported_origin(): void {
        foreach (['native', 'legacy_plan', 'legacy_digital'] as $origin) {
            $identity = new CommerceCatalogIdentity($origin, 17);
            $parsed = CommerceCatalogIdentity::from_string($identity->to_string());

            $this->assertNotNull($parsed);
            $this->assertSame($origin, $parsed->get_origin());
            $this->assertSame(17, $parsed->get_id());
        }
    }

    public function test_id_only_legacy_url_falls_back_to_native(): void {
        $identity = CommerceCatalogIdentity::from_request('', '', 15);

        $this->assertNotNull($identity);
        $this->assertSame('native', $identity->get_origin());
        $this->assertSame(15, $identity->get_id());
    }

    public function test_view_url_uses_stable_catalogue_key(): void {
        $product = new CommerceCatalogProductSummary(
            7,
            'COURSE.A1',
            'Cours A1',
            '',
            'course_access',
            'legacy_plan',
            new CommerceCatalogStatusSnapshot('published', 'visible', 'on_sale', 'valid')
        );

        $url = CommerceCatalogLinkGenerator::view_url($product);

        $this->assertStringContainsString('catalogkey=legacy_plan%3A7', $url->out(false));
    }

    public function test_catalogue_pages_use_centralised_link_generator(): void {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root . '/admin/commerce/products/index.php');
        $view = file_get_contents($root . '/admin/commerce/products/view.php');

        $this->assertStringContainsString('CommerceCatalogLinkGenerator::view_url', $index);
        $this->assertStringNotContainsString("compact('q'", $index);
        $this->assertStringContainsString('CommerceCatalogIdentity::from_request', $view);
    }
}
