<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefaultPresentationProfile;
use local_subscriptions\commerce\showroom\CommerceShowroomPresentationProfileRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomThirdGroupVerbsPresentationProfile;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRenderTemplateRegistry;

final class commerce_showroom_generic_render_engine_798a4_test extends \advanced_testcase {
    public function test_generic_render_template_is_registered(): void {
        $definition = CommerceShowroomRenderTemplateRegistry::require_definition(
            'local_subscriptions/showroom/default'
        );

        $this->assertSame('Showroom — Générique', $definition['label']);
        $this->assertInstanceOf(
            CommerceShowroomDefaultPresentationProfile::class,
            CommerceShowroomPresentationProfileRegistry::for_template(
                'local_subscriptions/showroom/default'
            )
        );
    }

    public function test_legacy_verbs_template_keeps_its_specific_profile(): void {
        $this->assertInstanceOf(
            CommerceShowroomThirdGroupVerbsPresentationProfile::class,
            CommerceShowroomPresentationProfileRegistry::for_template(
                'local_subscriptions/showroom/third_group_verbs'
            )
        );
    }

    public function test_verbs_template_is_only_a_generic_engine_wrapper(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        $this->assertNotFalse($template);
        $this->assertStringContainsString(
            '{{> local_subscriptions/showroom/default }}',
            (string)$template
        );
        $this->assertStringNotContainsString('12 / 30', (string)$template);
        $this->assertStringNotContainsString('fa-mountain-sun', (string)$template);
    }

    public function test_generic_template_contains_no_verbs_specific_device_constants(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache'
        );
        $this->assertNotFalse($template);
        $this->assertStringContainsString('{{herodevicestage}}', (string)$template);
        $this->assertStringContainsString('{{heromobilevalue}}', (string)$template);
        $this->assertStringContainsString('{{expeditionicon}}', (string)$template);
        $this->assertStringNotContainsString('>12 / 30<', (string)$template);
        $this->assertStringNotContainsString('<strong>30</strong>', (string)$template);
    }

    public function test_generic_profile_is_neutral_while_verbs_profile_enables_expedition_theme(): void {
        $generic = (new CommerceShowroomDefaultPresentationProfile())->fallback_data([]);
        $verbs = (new CommerceShowroomThirdGroupVerbsPresentationProfile())->fallback_data([]);

        $this->assertFalse($generic['showexpeditiontheme']);
        $this->assertSame('fa-solid fa-compass', $generic['expeditionicon']);
        $this->assertTrue($verbs['showexpeditiontheme']);
        $this->assertSame('fa-solid fa-mountain-sun', $verbs['expeditionicon']);
        $this->assertSame('12 / 30', $verbs['herodevicestage']);
        $this->assertSame('30', $verbs['heromobilevalue']);
    }

    public function test_generic_template_renders_comparison_cells_without_legacy_role_columns(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache'
        );
        $this->assertNotFalse($template);
        $this->assertStringContainsString('{{#cells}}', (string)$template);
        $this->assertStringContainsString('data-comparison-role="{{role}}"', (string)$template);
        $this->assertStringNotContainsString('data-comparison-role="pdf"', (string)$template);
        $this->assertStringNotContainsString('data-comparison-role="bundle"', (string)$template);
        $this->assertStringNotContainsString('data-comparison-role="course"', (string)$template);
        $this->assertStringContainsString('--showroom-comparison-columns: {{comparisoncolumncount}}', (string)$template);
    }

    public function test_generic_styles_do_not_force_legacy_product_role_order(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $this->assertNotFalse($css);
        $this->assertStringNotContainsString(".commerce-showroom-offer--pdf {\n    order:", (string)$css);
        $this->assertStringNotContainsString(".commerce-showroom-offer--bundle {\n    order:", (string)$css);
        $this->assertStringNotContainsString(".commerce-showroom-offer--course {\n    order:", (string)$css);
        $this->assertStringContainsString('repeat(var(--showroom-comparison-columns, 3)', (string)$css);
    }

    public function test_comparison_presenter_materialises_generic_cells(): void {
        $reflection = new \ReflectionClass(
            \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter::class
        );
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_comparison');
        $method->setAccessible(true);

        $data = [
            'comparisonoffers' => [
                ['role' => 'course', 'featured' => false],
                ['role' => 'donation', 'featured' => true],
            ],
            'comparisonrows' => [[
                'label' => 'Accès',
                'course' => [
                    'included' => true,
                    'notincluded' => false,
                    'hastext' => false,
                    'text' => '',
                ],
                'donation' => [
                    'included' => false,
                    'notincluded' => true,
                    'hastext' => false,
                    'text' => '',
                ],
            ]],
        ];

        $result = $method->invoke($presenter, $data, []);

        $this->assertSame(2, $result['comparisoncolumncount']);
        $this->assertCount(2, $result['comparisonrows'][0]['cells']);
        $this->assertSame('course', $result['comparisonrows'][0]['cells'][0]['role']);
        $this->assertTrue($result['comparisonrows'][0]['cells'][0]['value']['included']);
        $this->assertSame('donation', $result['comparisonrows'][0]['cells'][1]['role']);
        $this->assertTrue($result['comparisonrows'][0]['cells'][1]['featured']);
        $this->assertTrue($result['comparisonrows'][0]['cells'][1]['value']['notincluded']);
    }

    public function test_public_runtime_no_longer_selects_featured_offer_by_bundle_role(): void {
        global $CFG;

        $route = file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');
        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );
        $this->assertNotFalse($route);
        $this->assertNotFalse($javascript);
        $this->assertStringNotContainsString("offer['role'] ?? '') !== 'bundle'", (string)$route);
        $this->assertStringNotContainsString('payload.offers.bundle', (string)$javascript);
        $this->assertStringContainsString('.commerce-showroom-offer.is-featured', (string)$javascript);
    }
}
