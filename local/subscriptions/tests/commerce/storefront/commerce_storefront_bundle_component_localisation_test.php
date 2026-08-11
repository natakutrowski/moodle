<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontComponentLocaliser;

/**
 * @covers \local_subscriptions\commerce\storefront\localisation\CommerceStorefrontComponentLocaliser
 */
final class commerce_storefront_bundle_component_localisation_test extends advanced_testcase {
    public function test_component_uses_requested_translation_and_short_description(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $productid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'COURSE.TEST',
            'type' => 'course_access',
            'status' => 'draft',
            'name' => 'third-group-verbs-course',
            'description' => '',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        foreach ([
            ['fr', 'Entraîneur des verbes du 3e groupe', '180 verbes · 30 étapes'],
            ['en', 'Third-group verb trainer', '180 verbs · 30 stages'],
            ['ru', 'Тренажёр глаголов 3-й группы', '180 глаголов · 30 этапов'],
        ] as [$language, $name, $shortdescription]) {
            $DB->insert_record('local_subs_commerce_prod_tr', (object)[
                'productid' => $productid,
                'language' => $language,
                'name' => $name,
                'shortdescription' => $shortdescription,
                'description' => '',
                'metadatajson' => '{}',
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }

        $components = [[
            'id' => $productid,
            'sku' => 'COURSE.TEST',
            'name' => 'third-group-verbs-course',
            'type' => 'course_access',
            'quantity' => 1,
        ]];

        $localised = (new CommerceStorefrontComponentLocaliser($DB))->localise($components, 'ru');

        self::assertSame('Тренажёр глаголов 3-й группы', $localised[0]['name']);
        self::assertSame('180 глаголов · 30 этапов', $localised[0]['description']);
    }

    public function test_component_falls_back_to_french_then_native_name(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $translatedid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'PDF.TEST',
            'type' => 'digital_download',
            'status' => 'draft',
            'name' => 'native-pdf-name',
            'description' => '',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_prod_tr', (object)[
            'productid' => $translatedid,
            'language' => 'fr',
            'name' => 'Cartes des verbes du 3e groupe',
            'shortdescription' => '',
            'description' => '',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $nativeid = $DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'OTHER.TEST',
            'type' => 'digital_download',
            'status' => 'draft',
            'name' => 'Native fallback',
            'description' => '',
            'metadatajson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $components = [
            ['id' => $translatedid, 'sku' => 'PDF.TEST', 'name' => 'native-pdf-name', 'type' => 'digital_download', 'quantity' => 1],
            ['id' => $nativeid, 'sku' => 'OTHER.TEST', 'name' => 'Native fallback', 'type' => 'digital_download', 'quantity' => 1],
        ];

        $localised = (new CommerceStorefrontComponentLocaliser($DB))->localise($components, 'de');

        self::assertSame('Cartes des verbes du 3e groupe', $localised[0]['name']);
        self::assertSame('Native fallback', $localised[1]['name']);
    }
}
