<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeOfferContract;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeTextCatalog;

/** X1: preserve server authority and the canonical Russian Lovable copy. */
final class commerce_798x1_bridge_commerce_translation_test extends \advanced_testcase {
    public function test_public_contract_exposes_companion_checkout_fields_but_no_internal_metadata(): void {
        $course = [
            'role' => 'course', 'sku' => 'ARTICLES', 'canbuy' => true,
            'priceid' => 123, 'amountminor' => 2500, 'currency' => 'EUR',
            'donationcompanionsku' => 'ALF', 'donationcompanionpriceid' => 456,
            'donationcompanionrole' => 'alf', 'donationcompanionamountminor' => 100,
            'hasdonationcompanion' => true, 'donationcompanionoptional' => true,
            'sesskey' => 'test-csrf', 'cartaction' => '/cart_action.php',
            'secretinternalfield' => 'NEVER LEAK',
        ];
        $donation = [
            'role' => 'alf', 'sku' => 'ALF', 'isdonation' => true,
            'priceid' => 456, 'amountminor' => 100, 'currency' => 'EUR',
            'donationpresets' => [['units' => 5], ['units' => 10]],
            'donationdefaultunits' => 10, 'donationmaxunits' => 100,
        ];
        $offers = CommerceShowroomBridgeOfferContract::expose([$course, $donation]);
        self::assertCount(2, $offers);
        self::assertSame('ALF', $offers[0]['donationcompanionsku']);
        self::assertSame(456, $offers[0]['donationcompanionpriceid']);
        self::assertSame(2500, $offers[0]['amountminor']);
        self::assertArrayNotHasKey('secretinternalfield', $offers[0]);
        self::assertSame(10, $offers[1]['donationdefaultunits']);
        self::assertSame([['units' => 5], ['units' => 10]], $offers[1]['donationpresets']);
    }

    public function test_ru_source_fr_en_translations_and_cms_override_priority(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/designs/articles_alf';
        $ru = CommerceShowroomBridgeTextCatalog::load($root, 'content/ru.json', 'ru', []);
        self::assertSame('Разберись в логике', $ru['hero']['title1']);
        self::assertSame('Для какого уровня подходит курс?', $ru['page']['faq_01']);
        $fr = CommerceShowroomBridgeTextCatalog::load($root, 'content/ru.json', 'fr', []);
        self::assertSame('Comprends la logique', $fr['hero']['title1']);
        self::assertSame('Questions fréquentes', $fr['page']['faq_15']);
        $en = CommerceShowroomBridgeTextCatalog::load($root, 'content/ru.json', 'en', []);
        self::assertSame('Discover the logic', $en['hero']['title1']);
        self::assertSame('Frequently asked questions', $en['page']['faq_15']);
        $override = CommerceShowroomBridgeTextCatalog::load($root, 'content/ru.json', 'fr', [
            'hero' => ['title1' => 'Mon titre CMS'],
            'page' => ['faq_15' => 'Questions modifiées'],
        ]);
        self::assertSame('Mon titre CMS', $override['hero']['title1']);
        self::assertSame('Questions modifiées', $override['page']['faq_15']);
        self::assertSame('Ce cours est', $override['page']['forwhom_14']);
    }

    public function test_all_derived_language_catalogues_cover_same_original_text_ids(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/designs/articles_alf/content/';
        $ru = json_decode((string)file_get_contents($root . 'ru.json'), true, 32, JSON_THROW_ON_ERROR);
        foreach (['fr', 'en'] as $locale) {
            $translation = json_decode((string)file_get_contents($root . $locale . '.json'), true, 32, JSON_THROW_ON_ERROR);
            $expectedkeys = array_keys($ru['page']);
            $actualkeys = array_keys($translation['page']);
            sort($expectedkeys);
            sort($actualkeys);
            self::assertSame($expectedkeys, $actualkeys);
            self::assertCount(count($ru['page']), $translation['page']);
        }
    }
}
