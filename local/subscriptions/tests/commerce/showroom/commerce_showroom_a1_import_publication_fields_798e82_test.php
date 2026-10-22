<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationIntegrityValidator;

/**
 * A portable import may keep editorial values only in translations.fr/en/ru.
 * Publication must accept those values without allowing genuinely empty blocks.
 */
final class commerce_showroom_a1_import_publication_fields_798e82_test extends \advanced_testcase {
    /** @param array<string,mixed> $config */
    private function validate(string $type, array $config): void {
        $reflection = new \ReflectionClass(CommerceShowroomPublicationIntegrityValidator::class);
        $validator = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('validate_required_fields');
        $method->invoke($validator, $type, $type, $config);
    }

    /** @param array<string,mixed> $config */
    private function assert_invalid(string $type, array $config, string $detail): void {
        try {
            $this->validate($type, $config);
            self::fail('Expected a publication integrity failure.');
        } catch (\moodle_exception $exception) {
            self::assertSame('commerce_showroom_publish_integrity_failed', $exception->errorcode);
            self::assertStringContainsString($detail, (string)$exception->a);
        }
    }

    public function test_stats_accepts_items_in_imported_translations_without_root_copy(): void {
        $this->validate('stats', [
            'layout' => 'bar',
            'translations' => [
                'fr' => ['items' => "30 étapes|2 nouvelles leçons par semaine|fa-solid fa-location-dot"],
                'en' => ['items' => "30 stops|2 lessons per week|fa-solid fa-location-dot"],
                'ru' => ['items' => "30 остановок|2 урока в неделю|fa-solid fa-location-dot"],
            ],
        ]);
        self::assertTrue(true);
    }

    public function test_stats_still_fails_when_every_translation_is_empty(): void {
        $this->assert_invalid('stats', [
            'translations' => ['fr' => ['items' => '  '], 'ru' => ['items' => '']],
        ], 'required field "items" is empty');
    }

    public function test_translated_title_and_items_work_for_other_imported_blocks(): void {
        $this->validate('audience_profiles', [
            'translations' => ['fr' => [
                'title' => 'À qui s’adresse ce cours ?',
                'items' => 'Débutants|Bienvenue|fa-solid fa-user',
            ]],
        ]);
        self::assertTrue(true);
    }

    public function test_interactive_a1_map_requires_real_geodata_but_not_legacy_items(): void {
        $this->validate('journey_map', [
            'presentation' => 'interactive_a1',
            'items' => '',
            'captions' => '01|Jour 1 — Nice',
            'geopoints' => '01|Nice|43.70094|7.26839|CampusFR_A1_01.jpg',
        ]);
        self::assertTrue(true);
    }

    public function test_interactive_a1_map_rejects_missing_geopoints(): void {
        $this->assert_invalid('journey_map', [
            'presentation' => 'interactive_a1',
            'captions' => '01|Jour 1 — Nice',
            'geopoints' => '',
        ], 'required field "geopoints" is empty');
    }

    public function test_a1_travel_faq_uses_structured_translated_entries_instead_of_legacy_items(): void {
        $entries = json_encode([['question' => 'Pour qui ?', 'answer' => 'Pour les débutants.']], JSON_THROW_ON_ERROR);
        $this->validate('faq', [
            'presentation' => 'a1_travel',
            'translations' => ['fr' => ['entriesjson' => $entries]],
        ]);
        self::assertTrue(true);
    }

    public function test_a1_travel_faq_rejects_empty_or_invalid_structured_entries(): void {
        $this->assert_invalid('faq', [
            'presentation' => 'a1_travel',
            'translations' => ['fr' => ['entriesjson' => '[{"question":"","answer":""}]']],
        ], 'no valid entriesjson entry');
    }

    public function test_standard_faq_still_requires_original_items(): void {
        $this->assert_invalid('faq', [
            'presentation' => 'standard',
            'items' => '',
            'translations' => ['fr' => ['entriesjson' => '[{"question":"Q","answer":"A"}]']],
        ], 'required field "items" is empty');
    }
}
