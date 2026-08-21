<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n124e_inbox_ai_runtime_provider_wiring_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');

        self::assertFileExists($path);

        $content = file_get_contents($path);

        self::assertIsString($content);

        return $content;
    }

    public function test_runtime_factory_uses_production_ai_service_factory(): void {
        $runtime = $this->file(
            'classes/crm/inbox/ai/services/InboxAiRuntimeFactory.php'
        );

        self::assertStringContainsString(
            'InboxAiServiceFactory::orchestrator()',
            $runtime
        );

        self::assertStringNotContainsString(
            '\\core\\di::get(',
            $runtime
        );
    }

    public function test_service_factory_registers_openai_before_fallback(): void {
        $factory = $this->file(
            'classes/crm/inbox/ai/services/InboxAiServiceFactory.php'
        );

        $openai = strpos(
            $factory,
            '$openai,'
        );

        $fallback = strpos(
            $factory,
            'new FallbackInboxAiProvider()'
        );

        self::assertNotFalse($openai);
        self::assertNotFalse($fallback);
        self::assertLessThan(
            $fallback,
            $openai
        );
    }

    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
