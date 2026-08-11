<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockDefaultsCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_multilingual_cms_j15d_test extends \advanced_testcase {
    public function test_defaults_contain_three_languages(): void {
        $hero = CommerceShowroomBlockDefaultsCatalog::for_block(
            'third-group-verbs',
            'hero'
        );

        $this->assertArrayHasKey('translations', $hero);
        foreach (['fr', 'en', 'ru'] as $language) {
            $this->assertArrayHasKey($language, $hero['translations']);
            $this->assertNotEmpty($hero['translations'][$language]['title']);
        }
    }

    public function test_normalisation_preserves_translated_and_common_fields(): void {
        $config = CommerceShowroomBlockEditorRegistry::normalise('hero', [
            'translations' => [
                'fr' => ['title' => 'Titre FR'],
                'en' => ['title' => 'English title'],
                'ru' => ['title' => 'Русский заголовок'],
            ],
            'primarytarget' => '#showroom-offers',
            'showgustave' => true,
        ]);

        $this->assertSame('English title', $config['translations']['en']['title']);
        $this->assertSame('Titre FR', $config['title']);
        $this->assertSame('#showroom-offers', $config['primarytarget']);
        $this->assertTrue($config['showgustave']);
    }

    public function test_technical_fields_are_not_translatable(): void {
        $fields = [];
        foreach (
            CommerceShowroomBlockEditorRegistry::schema('offers')['fields']
            as $field
        ) {
            $fields[$field['name']] = $field;
        }

        $this->assertTrue($fields['title']['translatable']);
        $this->assertFalse($fields['skus']['translatable']);
        $this->assertFalse($fields['featuredsku']['translatable']);
    }
}
