<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1210d_help_pages_ux_harmonisation_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_help_pages_share_internal_navigation(): void {
        foreach ([
            'admin/help/index.php',
            'admin/help/guide.php',
            'admin/help/article.php',
            'admin/help/diagnostics.php',
        ] as $relative) {
            self::assertStringContainsString(
                'HelpInternalNavigationRenderer::render(',
                $this->file($relative)
            );
        }
    }

    public function test_help_index_has_breadcrumb_and_documentation_heading(): void {
        self::assertStringContainsString(
            'CrmBreadcrumbRenderer::render(',
            $this->file(
                'admin/help/index.php'
            )
        );

        $renderer = $this->file(
            'classes/crm/help/HelpRenderer.php'
        );

        self::assertStringContainsString(
            'crm_help_documentation_title_n1210d',
            $renderer
        );
        self::assertStringContainsString(
            'crm_help_documentation_description_n1210d',
            $renderer
        );
    }

    public function test_guide_page_uses_actual_guide_title(): void {
        $guide = $this->file(
            'admin/help/guide.php'
        );

        self::assertStringContainsString(
            '$pagetitle = $state->guide->title;',
            $guide
        );
        self::assertStringNotContainsString(
            'CrmBackLinkRenderer::render(',
            $guide
        );
    }

    public function test_article_breadcrumb_includes_category(): void {
        $article = $this->file(
            'admin/help/article.php'
        );

        self::assertStringContainsString(
            '$breadcrumbs[]',
            $article
        );
        self::assertStringNotContainsString(
            'CrmBackLinkRenderer::render(',
            $article
        );
    }
}
