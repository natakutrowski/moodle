<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;

/** Preconfigure any product while its target showroom is still a private draft. */
final class commerce_2p_prepublication_product_showroom_routing_test extends advanced_testcase {
    public function test_all_product_types_fallback_then_switch_only_when_published(): void {
        global $DB;
        $this->resetAfterTest(true);

        $key = 'two-p-discovery-test';
        $id = $this->create_showroom($key, 'draft');
        $types = [
            'course_access', 'digital_download', 'bundle', 'donation', 'subscription',
        ];
        $metadata = [
            'showroom' => [
                'key' => $key,
                'discoverymode' => 'showroom',
                'showstorefrontcta' => false,
            ],
        ];
        foreach ($types as $type) {
            $draft = CommerceProductDiscoveryUrlResolver::resolve(
                'COURSE_ACCESS.GENERIC_TEST', $type, $metadata, [], null, 'fr'
            )->out(false);
            self::assertStringContainsString('storefront_product.php', $draft);
        }

        // Publication is the only operation that activates the public link.
        $DB->set_field('local_subs_showroom', 'status', 'published', ['id' => $id]);
        foreach ($types as $type) {
            foreach (['fr' => 'test-2p-fr', 'en' => 'test-2p-en', 'ru' => 'test-2p-ru'] as $lang => $slug) {
                $published = CommerceProductDiscoveryUrlResolver::resolve(
                    'COURSE_ACCESS.GENERIC_TEST', $type, $metadata, ['currency' => 'EUR'], null, $lang
                )->out(false);
                self::assertStringContainsString('/' . $slug, $published);
                self::assertStringContainsString('currency=EUR', $published);
            }
        }

        // A link from inside that showroom must not loop back to itself.
        $same = CommerceProductDiscoveryUrlResolver::resolve(
            'COURSE_ACCESS.GENERIC_TEST', 'course_access', $metadata, [], $key, 'fr'
        )->out(false);
        self::assertStringContainsString('storefront_product.php', $same);

        $DB->set_field('local_subs_showroom', 'status', 'draft', ['id' => $id]);
        $unpublished = CommerceProductDiscoveryUrlResolver::resolve(
            'COURSE_ACCESS.GENERIC_TEST', 'course_access', $metadata, [], null, 'fr'
        )->out(false);
        self::assertStringContainsString('storefront_product.php', $unpublished);
    }

    public function test_both_crm_editors_offer_unpublished_cms_showrooms_and_no_legacy_registry(): void {
        global $CFG;
        foreach (['storefront_distribution.php', 'storefront_builder.php'] as $filename) {
            $file = $CFG->dirroot . '/local/subscriptions/admin/commerce/products/' . $filename;
            $source = (string)file_get_contents($file);
            self::assertStringContainsString('CommerceShowroomCmsRepository', $source);
            self::assertStringContainsString('CommerceShowroomStatus::DRAFT', $source);
            self::assertStringContainsString('CommerceShowroomStatus::PUBLISHED', $source);
            self::assertStringContainsString('CommerceShowroomPublishedDefinitionResolver', $source);
            self::assertStringContainsString('Choose an existing, non-archived CMS Showroom.', $source);
            self::assertStringNotContainsString('CommerceShowroomRegistry::definitions()', $source);
        }
    }

    private function create_showroom(string $key, string $status): int {
        global $DB;
        $now = time();
        $id = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => $key,
            'status' => $status,
            'name' => 'Generic product discovery test',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => 'test-2p-fr',
            'slugen' => 'test-2p-en',
            'slugru' => 'test-2p-ru',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        $DB->insert_record('local_subs_showroom_block', (object)[
            'showroomid' => $id,
            'blockkey' => 'hero',
            'blocktype' => 'hero',
            'sortorder' => 10,
            'enabled' => 1,
            'configjson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
        return $id;
    }
}
