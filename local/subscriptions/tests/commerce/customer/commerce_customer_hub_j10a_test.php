<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_hub_j10a_test extends \advanced_testcase {
    public function test_hub_page_and_customer_service_are_exposed(): void {
        $root = dirname(__DIR__, 3);
        $page = file_get_contents($root . '/mon_campus.php');
        $service = file_get_contents(
            $root . '/classes/commerce/customer/hub/CommerceCustomerHubService.php'
        );
        $template = file_get_contents(
            $root . '/templates/customer/hub.mustache'
        );
        $urls = file_get_contents($root . '/classes/url/UrlFactory.php');

        $this->assertIsString($page);
        $this->assertIsString($service);
        $this->assertIsString($template);
        $this->assertIsString($urls);
        $this->assertStringContainsString('require_login()', $page);
        $this->assertStringContainsString('CommerceCustomerHubService::create()', $page);
        $this->assertStringContainsString('build($USER, $PAGE)', $page);
        $this->assertStringContainsString('new EnrolledCourseProvider()', $service);
        $this->assertStringContainsString('CommerceDigitalLibraryService::create()', $service);
        $this->assertStringContainsString('new LevelUpXpRepository()', $service);
        $this->assertStringContainsString('commerce-customer-hub__shortcuts', $template);
        $this->assertStringContainsString('commerce_customer_hub_shop', $template);
        $this->assertStringContainsString('get_url($page)', $service);
        $this->assertStringContainsString('public static function my_campus', $urls);
    }

    public function test_hub_course_cards_include_all_enrolled_courses(): void {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $courseids = [];

        for ($index = 1; $index <= 8; $index++) {
            $course = $generator->create_course([
                'fullname' => 'Hub course ' . $index,
                'shortname' => 'hub-course-' . $index,
            ]);
            $generator->enrol_user((int)$user->id, (int)$course->id);
            $courseids[] = (int)$course->id;
        }

        $service = \local_subscriptions\commerce\customer\hub\CommerceCustomerHubService::create();
        $method = new \ReflectionMethod($service, 'course_cards');
        $method->setAccessible(true);

        $cards = $method->invoke($service, (int)$user->id);

        $this->assertCount(8, $cards);
        $cardids = array_map(
            static fn(array $card): int => (int)$card['id'],
            $cards
        );
        sort($courseids);
        sort($cardids);
        $this->assertSame($courseids, $cardids);
    }

    public function test_hub_language_strings_exist_in_all_supported_languages(): void {
        $root = dirname(__DIR__, 3);
        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            $this->assertIsString($source);
            $this->assertStringContainsString(
                "commerce_customer_hub_title",
                $source
            );
            $this->assertStringContainsString(
                "commerce_customer_hub_xp_title",
                $source
            );
        }
    }
}
