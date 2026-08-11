<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomProductLinkService;

final class commerce_product_discovery_routing_l2_l3_test extends advanced_testcase {
    private const SHOWROOM = 'third-group-verbs';

    public function test_showroom_is_used_as_discovery_destination_when_published(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->create_showroom('published');

        $metadata = $this->metadata('showroom', true);
        $url = CommerceProductDiscoveryUrlResolver::resolve(
            'COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE',
            'course_access',
            $metadata,
            ['currency' => 'EUR'],
            null,
            'fr'
        );

        self::assertStringContainsString('/verbes-3e-groupe', $url->out(false));
        self::assertStringContainsString('currency=EUR', $url->out(false));
    }

    public function test_unpublished_showroom_falls_back_to_storefront(): void {
        $this->resetAfterTest(true);
        $this->create_showroom('draft');

        $metadata = $this->metadata('showroom', true);
        $url = CommerceProductDiscoveryUrlResolver::resolve(
            'COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE',
            'course_access',
            $metadata,
            [],
            null,
            'fr'
        );

        self::assertStringContainsString(
            '/local/subscriptions/storefront_product.php',
            $url->out(false)
        );
        self::assertStringContainsString(
            'sku=COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE',
            $url->out(false)
        );
    }

    public function test_same_showroom_context_never_loops_to_itself(): void {
        $this->resetAfterTest(true);
        $this->create_showroom('published');

        $url = CommerceProductDiscoveryUrlResolver::resolve(
            'COURSE_ACCESS.THIRD_GROUP_VERBS_COURSE',
            'course_access',
            $this->metadata('showroom', true),
            [],
            self::SHOWROOM,
            'fr'
        );

        self::assertStringContainsString(
            '/local/subscriptions/storefront_product.php',
            $url->out(false)
        );
        self::assertStringNotContainsString('/verbes-3e-groupe', $url->out(false));
    }

    public function test_storefront_full_presentation_cta_is_configurable(): void {
        $this->resetAfterTest(true);
        $this->create_showroom('published');

        $service = new CommerceShowroomProductLinkService();

        $enabled = $service->present($this->metadata('storefront', true), 'fr');
        self::assertTrue($enabled['hasshowroom']);
        self::assertStringContainsString('/verbes-3e-groupe', $enabled['showroomurl']);

        $disabled = $service->present($this->metadata('storefront', false), 'fr');
        self::assertFalse($disabled['hasshowroom']);
    }

    public function test_showroom_offer_details_explicitly_bypass_discovery_routing(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );

        self::assertStringContainsString(
            'CommerceStorefrontUrlResolver::direct_storefront(',
            $source
        );
    }

    public function test_product_editor_exposes_discovery_controls(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/storefront.php'
        );

        self::assertStringContainsString(
            'storefront_showroom_discoverymode',
            $source
        );
        self::assertStringContainsString(
            'storefront_showroom_showstorefrontcta',
            $source
        );
    }

    /** @return array<string,mixed> */
    private function metadata(string $mode, bool $showcta): array {
        return [
            'showroom' => [
                'key' => self::SHOWROOM,
                'discoverymode' => $mode,
                'showstorefrontcta' => $showcta,
            ],
        ];
    }

    private function create_showroom(string $status): void {
        global $DB;

        $now = time();
        $DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => self::SHOWROOM,
            'status' => $status,
            'name' => 'Third-group verbs',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'verbes-3e-groupe',
            'slugen' => 'third-group-verbs',
            'slugru' => 'glagoly-tretey-gruppy',
            'titlekey' => 'commerce_showroom_third_group_verbs_title',
            'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
            'productsjson' => '{}',
            'settingsjson' => '{}',
            'timecreated' => $now,
            'timemodified' => $now,
            'usermodified' => null,
        ]);
    }
}
