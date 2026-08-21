<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n113e_user360_final_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_potential_identity_matches_are_collapsible(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        self::assertStringContainsString(
            "'details'",
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-identity-potential-summary',
            $renderer
        );
        self::assertStringContainsString(
            'count($graph[\'potential\'])',
            $renderer
        );
    }

    public function test_raw_numeric_identity_score_is_not_rendered(): void {
        $renderer = $this->file(
            'classes/crm/user360/identity/User360IdentityGraphRenderer.php'
        );

        self::assertStringNotContainsString(
            "' · score '",
            $renderer
        );
        self::assertStringContainsString(
            'confidence_label(',
            $renderer
        );
    }

    public function test_empty_identity_sidebar_does_not_consume_layout_column(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360IdentitiesRenderer.php'
        );
        $styles = $this->file('styles.css');

        self::assertStringContainsString(
            "if (\$sidebar === '')",
            $renderer
        );
        self::assertStringContainsString(
            "' is-single-column'",
            $renderer
        );
        self::assertStringContainsString(
            '.crm-user360-n113d-identities-grid.is-single-column',
            $styles
        );
        self::assertStringContainsString(
            'crm-user360-n113e-safety-notice',
            $renderer
        );
    }

    public function test_timeline_metrics_remain_compact_summary_metrics(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360TimelineRenderer.php'
        );

        self::assertStringContainsString(
            'crm-user360-n117c-timeline-metrics',
            $renderer
        );
        self::assertStringContainsString(
            'crm-user360-n117c-timeline-metric',
            $renderer
        );
        self::assertStringContainsString(
            'crm_user360_n113d_timeline_events',
            $renderer
        );
        self::assertStringContainsString(
            'crm_user360_n113d_timeline_important',
            $renderer
        );
    }


    public function test_n113e_strings_exist_in_all_languages(): void {
        foreach (['en', 'fr', 'ru'] as $lang) {
            $strings = $this->file(
                'lang/' . $lang . '/local_subscriptions.php'
            );

            foreach ([
                'crm_user360_n113e_match_high',
                'crm_user360_n113e_match_medium',
                'crm_user360_n113e_match_low',
            ] as $key) {
                self::assertStringContainsString(
                    '$string[\'' . $key . '\']',
                    $strings
                );
            }
        }
    }

    public function test_n113e_does_not_bump_plugin_version(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
