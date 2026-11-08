<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings;

/** 7.98X.1.2: video stays CMS-controlled and does not affect legacy renderers. */
final class commerce_798x12_bridge_video_test extends \advanced_testcase {
    public function test_video_url_is_persisted_with_existing_showroom_settings(): void {
        $original = '{"seo":{"fr":{"title":"Titre"}},"designbridge":{"videoUrl":"https://example.org/old.mp4"}}';
        $saved = CommerceShowroomBridgeSettings::merge_into_settings_json(
            $original, 'articles_alf', ['ru' => '{}', 'fr' => '{}', 'en' => '{}'],
            'https://media.example.org/presentation.mp4?token=abc'
        );
        $decoded = json_decode($saved, true, 32, JSON_THROW_ON_ERROR);
        self::assertSame('Titre', $decoded['seo']['fr']['title']);
        self::assertSame('https://media.example.org/presentation.mp4?token=abc',
            CommerceShowroomBridgeSettings::from_json($saved)['videoUrl']);
        self::assertSame('articles_alf', $decoded['designbridge']['id']);
    }

    public function test_video_url_is_preserved_for_old_callers_and_can_be_cleared(): void {
        $original = '{"designbridge":{"videoUrl":"https://example.org/presentation.webm"}}';
        $preserved = CommerceShowroomBridgeSettings::merge_into_settings_json($original, 'articles_alf', []);
        self::assertSame('https://example.org/presentation.webm',
            CommerceShowroomBridgeSettings::from_json($preserved)['videoUrl']);
        $cleared = CommerceShowroomBridgeSettings::merge_into_settings_json($preserved, 'articles_alf', [], '');
        self::assertSame('', CommerceShowroomBridgeSettings::from_json($cleared)['videoUrl']);
    }

    public function test_unsafe_video_urls_are_rejected(): void {
        foreach (['http://example.org/video.mp4', 'javascript:alert(1)',
            'https://user:password@example.org/video.mp4', 'https://example.org/video.mp4' . "\n"] as $bad) {
            try {
                CommerceShowroomBridgeSettings::validate_video_url($bad);
                self::fail('Accepted an unsafe video URL: ' . $bad);
            } catch (\invalid_parameter_exception $e) {
                self::assertNotEmpty($e->getMessage());
            }
        }
        self::assertSame('https://www.youtube.com/watch?v=abcdefghijk',
            CommerceShowroomBridgeSettings::validate_video_url('https://www.youtube.com/watch?v=abcdefghijk'));
    }
}
