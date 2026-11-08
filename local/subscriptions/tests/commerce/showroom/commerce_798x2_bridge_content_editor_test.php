<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomBridgeContentEditor.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeContentEditor;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings;

/** X2: package-driven RU/FR/EN CMS editor without changing React or Commerce. */
final class commerce_798x2_bridge_content_editor_test extends \advanced_testcase {
    /** @return array<string,string> */
    private function input_for(string $language, array $overrides = []): array {
        $model = CommerceShowroomBridgeContentEditor::model('articles_alf', $overrides);
        $input = [];
        foreach ($model['fields'] as $id => $field) {
            $input[$id] = $field['value'][$language];
        }
        return $input;
    }

    public function test_design_catalogues_drive_all_three_languages_and_hero_fact_fields(): void {
        $model = CommerceShowroomBridgeContentEditor::model('articles_alf');
        self::assertCount(196, $model['fields']);
        self::assertArrayHasKey('hero__title1', $model['fields']);
        self::assertArrayHasKey('hero__facts__0', $model['fields']);
        self::assertArrayHasKey('hero__facts__1', $model['fields']);
        self::assertArrayHasKey('hero__facts__2', $model['fields']);
        self::assertArrayHasKey('page__offers_01', $model['fields']);
        self::assertArrayHasKey('page__faq_01', $model['fields']);
        self::assertSame('Разберись в логике', $model['fields']['hero__title1']['reference']);
        self::assertNotSame('', $model['fields']['hero__title1']['default']['fr']);
        self::assertNotSame('', $model['fields']['hero__title1']['default']['en']);
        self::assertSame('Тест', CommerceShowroomBridgeContentEditor::model('articles_alf',
            ['ru' => ['hero' => ['title1' => 'Тест']]])['fields']['hero__title1']['value']['ru']);
    }

    public function test_only_changed_fr_fields_are_saved_ru_en_and_legacy_options_are_preserved(): void {
        $existing = [
            'ru' => ['page' => ['hero_04' => 'Русский текст']],
            'fr' => ['page' => ['future_extension' => 'Garder']],
            'en' => ['hero' => ['title1' => 'English correction']],
        ];
        $input = $this->input_for('fr', $existing);
        $input['page__faq_01'] = 'Une question modifiée ?';
        $output = CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'fr', $input, $existing);
        self::assertSame($existing['ru'], $output['ru']);
        self::assertSame($existing['en'], $output['en']);
        self::assertSame('Garder', $output['fr']['page']['future_extension']);
        self::assertSame('Une question modifiée ?', $output['fr']['page']['faq_01']);
        self::assertArrayNotHasKey('hero', $output['fr']);
        $jsons = [];
        foreach (CommerceShowroomBridgeSettings::LANGUAGES as $language) {
            $jsons[$language] = json_encode($output[$language], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        $settingsjson = CommerceShowroomBridgeSettings::merge_into_settings_json(
            '{"seo":{"fr":{"title":"SEO intact"}},"offerconfig":{"course":{"donationmode":"optional"}},'
                . '"designbridge":{"id":"articles_alf","videoUrl":"https://example.org/presentation.mp4"}}',
            'articles_alf', $jsons
        );
        $saved = json_decode($settingsjson, true, 32, JSON_THROW_ON_ERROR);
        self::assertSame('SEO intact', $saved['seo']['fr']['title']);
        self::assertSame('optional', $saved['offerconfig']['course']['donationmode']);
        self::assertSame('https://example.org/presentation.mp4', $saved['designbridge']['videoUrl']);
        self::assertSame('Une question modifiée ?', $saved['designbridge']['content']['fr']['page']['faq_01']);
    }

    public function test_reverting_to_catalogue_default_removes_override(): void {
        $existing = ['fr' => ['hero' => ['title1' => 'Ancien titre']], 'ru' => [], 'en' => []];
        $input = $this->input_for('fr', $existing);
        $base = CommerceShowroomBridgeContentEditor::model('articles_alf');
        $input['hero__title1'] = $base['fields']['hero__title1']['default']['fr'];
        $output = CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'fr', $input, $existing);
        self::assertSame([], $output['fr']);
    }

    public function test_three_fact_list_is_saved_atomically_and_other_language_untouched(): void {
        $input = $this->input_for('en');
        $input['hero__facts__1'] = 'Five connected ideas';
        $output = CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'en', $input, []);
        self::assertCount(3, $output['en']['hero']['facts']);
        self::assertSame('Five connected ideas', $output['en']['hero']['facts'][1]);
        self::assertSame([], $output['ru']);
        self::assertSame([], $output['fr']);
        // A complete array is required by the current BridgeSettings validation.
        $jsons = [];
        foreach ($output as $lang => $content) {
            $jsons[$lang] = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
        $settings = CommerceShowroomBridgeSettings::merge_into_settings_json('{}', 'articles_alf', $jsons);
        self::assertSame('Five connected ideas',
            CommerceShowroomBridgeSettings::from_json($settings)['content']['en']['hero']['facts'][1]);
    }

    public function test_partial_submission_is_rejected_without_erasing_existing_text(): void {
        $input = $this->input_for('fr');
        unset($input['page__faq_01']);
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'fr', $input,
            ['ru' => ['hero' => ['title1' => 'Original still here']]]);
    }

    public function test_unknown_field_is_rejected(): void {
        $input = $this->input_for('fr');
        $input['page__payment_account'] = 'malicious field';
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'fr', $input, []);
    }

    public function test_unknown_language_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'de', [], []);
    }

    public function test_oversized_value_is_rejected(): void {
        $input = $this->input_for('fr');
        $input['hero__title1'] = str_repeat('x', 5001);
        $this->expectException(\invalid_parameter_exception::class);
        CommerceShowroomBridgeContentEditor::merge_language('articles_alf', 'fr', $input, []);
    }
}
