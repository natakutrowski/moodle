<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_customer_xp_j10e6_test extends \advanced_testcase {
    public function test_levelup_repository_supports_sitewide_world_and_deduplicates_it(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/crm/success/repositories/'
                . 'LevelUpXpRepository.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('[SITEID]', $source);
        $this->assertStringContainsString(
            '$candidatecourseids',
            $source
        );
        $this->assertStringContainsString(
            '$processedworlds',
            $source
        );
        $this->assertStringContainsString(
            "method_exists(\$world, 'get_courseid')",
            $source
        );
    }

    public function test_hub_reads_level_number_from_current_levelup_api(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/hub/'
                . 'CommerceCustomerHubService.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "method_exists(\$level, 'get_level')",
            $source
        );
        $this->assertStringContainsString(
            '$level->get_badge_url()',
            $source
        );
    }

    public function test_course_fallback_uses_graduation_cap(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/templates/customer/hub.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            'fa-graduation-cap',
            $template
        );
        $this->assertStringNotContainsString(
            '{{initial}}</span>{{/hasimage}}',
            $template
        );
    }
}
