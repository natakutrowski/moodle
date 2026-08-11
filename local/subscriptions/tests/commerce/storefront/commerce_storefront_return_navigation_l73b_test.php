<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\storefront\navigation\CommerceStorefrontReturnNavigationResolver;
use local_subscriptions\url\UrlFactory;

/**
 * @covers \local_subscriptions\commerce\storefront\navigation\CommerceStorefrontReturnNavigationResolver
 */
final class commerce_storefront_return_navigation_l73b_test extends advanced_testcase {
    private const SHOWROOM = 'third-group-verbs';

    public function test_shop_origin_returns_to_shop(): void {
        $result = (new CommerceStorefrontReturnNavigationResolver())->resolve(
            'shop',
            '',
            '',
            '',
            'EUR',
            'en'
        );

        self::assertTrue($result['show']);
        self::assertSame(
            UrlFactory::digital_catalog(['currency' => 'EUR'])->out(false),
            $result['url']
        );
        self::assertSame(
            [['name' => 'from', 'value' => 'shop']],
            $result['params']
        );
    }

    public function test_published_showroom_origin_returns_to_same_showroom(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => self::SHOWROOM,
            'status' => 'published',
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

        $result = (new CommerceStorefrontReturnNavigationResolver())->resolve(
            '',
            'showroom',
            self::SHOWROOM,
            'course',
            'RUB',
            'en'
        );

        self::assertTrue($result['show']);
        self::assertStringContainsString('/third-group-verbs', $result['url']);
        self::assertStringContainsString('currency=RUB', $result['url']);
        self::assertContains(
            ['name' => 'showroom', 'value' => self::SHOWROOM],
            $result['params']
        );
        self::assertContains(
            ['name' => 'showroomoffer', 'value' => 'course'],
            $result['params']
        );
    }

    public function test_unknown_showroom_origin_does_not_create_back_link(): void {
        $this->resetAfterTest(true);

        $result = (new CommerceStorefrontReturnNavigationResolver())->resolve(
            '',
            'showroom',
            'does-not-exist',
            'course',
            'EUR',
            'fr'
        );

        self::assertFalse($result['show']);
        self::assertSame([], $result['params']);
    }
}
