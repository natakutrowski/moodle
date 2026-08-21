<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n117a_identity_summary_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_identity_evidence_is_grouped_instead_of_flattened(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        self::assertStringContainsString(
            'self::evidence_summary($evidence)',
            $renderer
        );
        self::assertStringContainsString(
            'self::evidence_details($evidence)',
            $renderer
        );
        self::assertStringContainsString(
            'self::group_evidence($evidence)',
            $renderer
        );

        self::assertStringNotContainsString(
            'implode(\' · \', array_unique($sources))',
            $renderer
        );
    }

    public function test_identity_summary_groups_business_sources(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        foreach ([
            'crm_user360_n117a_commerce_orders',
            'crm_user360_n117a_legacy_purchases',
            'crm_user360_n117a_personal_offers',
            'crm_user360_n117a_merge_sources',
        ] as $key) {
            self::assertStringContainsString(
                $key,
                $renderer
            );
        }
    }

    public function test_individual_source_ids_are_collapsed_by_default(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        self::assertStringContainsString(
            "'details'",
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-identity-source-details-summary',
            $renderer
        );
        self::assertStringContainsString(
            'crm_user360_n117a_identity_source_details',
            $renderer
        );
    }

    public function test_n117a_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            foreach ([
                'crm_user360_n117a_commerce_orders',
                'crm_user360_n117a_legacy_purchases',
                'crm_user360_n117a_personal_offers',
                'crm_user360_n117a_identity_source_details',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $strings
                );
            }
        }
    }
}
