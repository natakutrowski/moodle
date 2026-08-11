<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\CommerceShowroomSeoService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSeoConfig;

final class commerce_showroom_multilingual_seo_j16i3_test extends \advanced_testcase {
    public function test_seo_settings_merge_preserves_other_global_settings(): void {
        $json = CommerceShowroomSeoConfig::merge_into_settings_json(
            '{"currency":"EUR","feature":true}',
            [
                'fr' => [
                    'title' => 'Titre FR',
                    'description' => 'Description FR',
                    'socialtitle' => 'Social FR',
                    'socialdescription' => 'Description sociale FR',
                    'keywords' => 'verbes, français',
                ],
            ]
        );

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('EUR', $decoded['currency']);
        $this->assertTrue($decoded['feature']);
        $this->assertSame('Titre FR', $decoded['seo']['fr']['title']);
        $this->assertSame('', $decoded['seo']['en']['title']);
    }

    public function test_definition_exposes_language_specific_seo(): void {
        $definition = new CommerceShowroomDefinition(
            'demo',
            ['fr' => 'fr-demo', 'en' => 'en-demo', 'ru' => 'ru-demo'],
            'local_subscriptions/showroom/third_group_verbs',
            [],
            'legacy_title',
            'legacy_description',
            [
                'fr' => ['title' => 'Titre FR'],
                'en' => ['title' => 'Title EN'],
            ]
        );

        $this->assertSame('Titre FR', $definition->get_seo('fr')['title']);
        $this->assertSame('Title EN', $definition->get_seo('en')['title']);
    }

    public function test_seo_service_uses_social_overrides_and_keywords_in_head(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/showroom/CommerceShowroomSeoService.php'
        );

        $this->assertStringContainsString("\$seo['socialtitle']", $source);
        $this->assertStringContainsString("\$seo['socialdescription']", $source);
        $this->assertStringContainsString('<meta name="keywords"', $source);
        $this->assertStringContainsString("'name' => \$title", $source);
        $this->assertStringContainsString("'description' => \$description", $source);
    }

    public function test_builder_exposes_three_language_seo_cards(): void {
        global $CFG;

        $editor = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/showrooms/edit.php'
        );

        $this->assertStringContainsString("'fr' => ['label' => 'Français'", $editor);
        $this->assertStringContainsString("'en' => ['label' => 'English'", $editor);
        $this->assertStringContainsString("'ru' => ['label' => 'Русский'", $editor);
        $this->assertStringContainsString("'seotitle_' . \$language", $editor);
        $this->assertStringContainsString("'seodescription_' . \$language", $editor);
        $this->assertStringContainsString("'seosocialtitle_' . \$language", $editor);
    }
}
