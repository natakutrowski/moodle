<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgePackageRegistry;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings;
use local_subscriptions\commerce\showroom\CommerceShowroomPresentationProfileRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomDefaultPresentationProfile;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRenderTemplateRegistry;

/** X0: the new render path must be explicit and backwards-compatible. */
final class commerce_798x0_design_bridge_test extends \advanced_testcase {
    public function test_legacy_templates_are_untouched_and_bridge_is_opt_in(): void {
        self::assertSame(
            'local_subscriptions/showroom/default',
            CommerceShowroomRenderTemplateRegistry::normalise('local_subscriptions/showroom/default')
        );
        self::assertSame(
            'local_subscriptions/showroom/third_group_verbs',
            CommerceShowroomRenderTemplateRegistry::normalise('local_subscriptions/showroom/third_group_verbs')
        );
        self::assertSame(
            CommerceShowroomBridgePackageRegistry::TEMPLATE,
            CommerceShowroomRenderTemplateRegistry::normalise(CommerceShowroomBridgePackageRegistry::TEMPLATE)
        );
    }

    public function test_bridge_gets_a_safe_existing_presentation_profile(): void {
        self::assertInstanceOf(
            CommerceShowroomDefaultPresentationProfile::class,
            CommerceShowroomPresentationProfileRegistry::for_template(
                CommerceShowroomBridgePackageRegistry::TEMPLATE
            )
        );
    }

    public function test_bridge_merges_into_settings_without_destroying_other_cms_options(): void {
        $saved = CommerceShowroomBridgeSettings::merge_into_settings_json(
            '{"seo":{"fr":{"title":"Titre"}},"offerconfig":{"course":{"detailsenabled":true}}}',
            'articles_alf',
            ['ru' => '{"hero":{"title1":"Тест <script>"}}', 'fr' => '{"hero":{"title1":"Bonjour"}}']
        );
        $settings = CommerceShowroomBridgeSettings::from_json($saved);
        self::assertSame('articles_alf', $settings['id']);
        self::assertSame('Тест <script>', $settings['content']['ru']['hero']['title1']);
        self::assertSame('Bonjour', $settings['content']['fr']['hero']['title1']);
        $decoded = json_decode($saved, true, 16, JSON_THROW_ON_ERROR);
        self::assertSame('Titre', $decoded['seo']['fr']['title']);
        self::assertTrue($decoded['offerconfig']['course']['detailsenabled']);
    }

    public function test_bridge_registry_rejects_unknown_source_code_packages(): void {
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgePackageRegistry::require_id('../untrusted');
    }

    public function test_bridge_fails_closed_if_compiled_assets_not_present(): void {
        $manifest = CommerceShowroomBridgePackageRegistry::require_manifest('articles_alf', false);
        self::assertSame('articles_alf', $manifest['id']);
        self::assertSame('dist/main.js', $manifest['entry']);
        self::assertSame('dist/style.css', $manifest['css']);
        self::assertSame('content/ru.json', $manifest['content']);
    }

    public function test_contract_uses_original_lovable_hero_not_a_php_recreation(): void {
        global $CFG;
        $template = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/bridge.mustache'
        );
        self::assertStringContainsString('type="module"', $template);
        self::assertStringContainsString('data-campusfr-bridge', $template);
        self::assertStringContainsString('type="application/json"', $template);
        self::assertStringNotContainsString('<iframe', $template);
    }
}
