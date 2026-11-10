<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings as Settings;

/** Validate trusted external contact links without touching Commerce. */
final class commerce_798x422_lovable_contacts_test extends advanced_testcase {
    public function test_contact_urls_are_restricted_to_https_official_domains(): void {
        self::assertSame('https://t.me/CampusFR', Settings::validate_contact_url('telegram', 'https://t.me/CampusFR'));
        self::assertSame('https://t.me/+abcdefghij', Settings::validate_contact_url('telegram', 'https://t.me/+abcdefghij'));
        self::assertSame('https://wa.me/33612345678', Settings::validate_contact_url('whatsapp', 'https://wa.me/33612345678'));
        self::assertSame('https://t.me/CampusFR?start=abc_123',
            Settings::validate_contact_url('telegram', 'https://telegram.me/CampusFR?start=abc_123'));
        self::assertSame('https://wa.me/33612345678?text=Bonjour%20CampusFR',
            Settings::validate_contact_url('whatsapp', 'https://wa.me/33612345678?text=Bonjour%20CampusFR'));
        self::assertSame('https://wa.me/33612345678?text=Bonjour%20CampusFR',
            Settings::validate_contact_url('whatsapp',
                'https://api.whatsapp.com/send?phone=33612345678&text=Bonjour+CampusFR'));
        self::assertSame('https://wa.me/message/ABCdefGH12',
            Settings::validate_contact_url('whatsapp', 'https://wa.me/message/ABCdefGH12'));
        foreach (['http://t.me/CampusFR', 'https://t.me.evil.test/CampusFR',
            'javascript:alert(1)', 'https://t.me/CampusFR?q=1', 'https://evil.test/path',
            'https://wa.me/+33612345678', 'https://wa.me/123', 'https://wa.me@evil.test/33612345678',
            'https://wa.me/33612345678?redirect=https://evil.test',
            'https://wa.me/33612345678?text=hello&next=https://evil.test',
            'https://t.me/CampusFR?next=https://evil.test',
            'https://t.me/CampusFR?start=abc&start=def',
            'https://api.whatsapp.com/send?phone=33612345678&phone=1234567',
            'https://api.whatsapp.com/send?phone=33612345678&text=hi%XX',
            'https://wa.me/33612345678#fragment'] as $bad) {
            foreach (['telegram', 'whatsapp'] as $kind) {
                try {
                    Settings::validate_contact_url($kind, $bad);
                    self::fail('Unsafe contact URL accepted: ' . $bad);
                } catch (\invalid_parameter_exception $expected) {
                    self::assertNotSame('', $expected->getMessage());
                }
            }
        }
    }

    public function test_only_contact_keys_change_and_legacy_editor_preserves_them(): void {
        $base = json_encode([
            'seo' => ['fr' => ['title' => 'Titre original']],
            'designbridge' => [
                'id' => 'articles_alf_reimport',
                'content' => ['fr' => ['hero' => ['title1' => 'Ne pas modifier']]],
                'videoUrl' => 'https://example.org/video.mp4',
                'posterUrl' => 'https://example.org/image.jpg',
                'unknownKey' => 'conserver',
            ],
        ], JSON_THROW_ON_ERROR);
        $updated = Settings::merge_contact_links($base, 'https://t.me/CampusFR', 'https://wa.me/33612345678');
        $withmessage = Settings::merge_contact_links($base, 'https://t.me/CampusFR?start=welcome',
            'https://api.whatsapp.com/send?phone=33612345678&text=Bonjour');
        self::assertSame('https://t.me/CampusFR?start=welcome', Settings::from_json($withmessage)['telegramUrl']);
        self::assertSame('https://wa.me/33612345678?text=Bonjour', Settings::from_json($withmessage)['whatsappUrl']);
        $data = json_decode($updated, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Titre original', $data['seo']['fr']['title']);
        self::assertSame('conserver', $data['designbridge']['unknownKey']);
        self::assertSame('Ne pas modifier', $data['designbridge']['content']['fr']['hero']['title1']);
        $parsed = Settings::from_json($updated);
        self::assertSame('https://t.me/CampusFR', $parsed['telegramUrl']);
        self::assertSame('https://wa.me/33612345678', $parsed['whatsappUrl']);
        $legacy = Settings::merge_into_settings_json($updated, 'articles_alf_reimport', []);
        self::assertSame('https://wa.me/33612345678', Settings::from_json($legacy)['whatsappUrl']);
        self::assertSame('https://t.me/CampusFR', Settings::from_json($legacy)['telegramUrl']);
    }

    public function test_compiled_design_uses_payload_contact_links_and_keeps_checkout_gate(): void {
        $root = dirname(__DIR__, 3);
        $js = (string)file_get_contents($root . '/designs/articles_alf_reimport/dist/main.js');
        self::assertStringContainsString('"Telegram",links.telegram||""', $js);
        self::assertStringContainsString('"WhatsApp",links.whatsapp||""', $js);
        self::assertStringContainsString('"telegram","whatsapp"', $js);
        self::assertStringContainsString('"privacy","terms","offer"', $js);
        self::assertStringContainsString('campusfrNativeCheckoutV46', $js);
        self::assertStringNotContainsString('"Telegram","#"', $js);
        self::assertStringNotContainsString('"WhatsApp","#"', $js);
        $presenter = (string)file_get_contents($root . '/classes/commerce/showroom/bridge/CommerceShowroomBridgePresenter.php');
        self::assertStringContainsString("'telegram' => \$settings['telegramUrl']", $presenter);
        self::assertStringContainsString("'whatsapp' => \$settings['whatsappUrl']", $presenter);
        self::assertStringContainsString("hash_file('sha256'", $presenter);
    }
}
