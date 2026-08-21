<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n129a_assistant_workspace_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_assistant_index_is_paginated(): void {
        $index = $this->file(
            'admin/assistant/index.php'
        );

        self::assertStringContainsString(
            "optional_param(\n    'page'",
            $index
        );
        self::assertStringContainsString(
            "optional_param(\n    'perpage'",
            $index
        );
        self::assertStringContainsString(
            '$OUTPUT->paging_bar(',
            $index
        );
        self::assertStringContainsString(
            'offset: $page * $perpage',
            $index
        );
    }

    public function test_assistant_index_has_breadcrumb_and_dashboard_structure(): void {
        $index = $this->file(
            'admin/assistant/index.php'
        );

        self::assertStringContainsString(
            'CrmBreadcrumbRenderer::render(',
            $index
        );
        self::assertStringContainsString(
            'crm-assistant-main-dashboard',
            $index
        );

        $renderer = $this->file(
            'classes/crm/assistant/rendering/CrmAssistantRenderer.php'
        );

        self::assertStringContainsString(
            'crm-assistant-recommendation-list',
            $renderer
        );
        self::assertStringContainsString(
            'crm-assistant-results-toolbar',
            $renderer
        );
    }

    public function test_work_item_duplicate_query_uses_real_link_schema(): void {
        $repository = $this->file(
            'classes/crm/work/intelligence/repositories/WorkItemDuplicateRepository.php'
        );

        self::assertStringContainsString(
            'ON link.itemid = item.id',
            $repository
        );
        self::assertStringNotContainsString(
            'link.workitemid',
            $repository
        );
    }
}
