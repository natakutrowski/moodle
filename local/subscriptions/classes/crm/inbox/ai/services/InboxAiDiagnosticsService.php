<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\fallback\FallbackInboxAiProvider;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;

final class InboxAiDiagnosticsService {

    public function __construct(
        private readonly InboxAiUsageRepository $usage,
        private readonly InboxAiQuotaService $quota
    ) {
    }

    public function diagnose(
        ?int $actorid = null
    ): array {
        global $DB;

        $checks = [];

        $tableexists =
            $DB->get_manager()->table_exists(
                new \xmldb_table(
                    'local_subscriptions_inbox_ai_result'
                )
            );

        $checks[] = [
            'key' => 'result_table',
            'success' => $tableexists,
            'message' => $tableexists
                ? get_string(
                    'crm_inbox_ai_diagnostic_table_ok',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_inbox_ai_diagnostic_table_missing',
                    'local_subscriptions'
                ),
        ];

        $fallback =
            new FallbackInboxAiProvider();

        $checks[] = [
            'key' => 'fallback',
            'success' =>
                $fallback->is_available(),
            'message' =>
                get_string(
                    'crm_inbox_ai_diagnostic_fallback',
                    'local_subscriptions'
                ),
        ];

        $keys = new OpenAiApiKeyProvider();

        $openai = new OpenAiInboxConfiguration(
            $keys
        );

        $checks[] = [
            'key' => 'openai_enabled',
            'success' => $openai->enabled(),
            'message' => $openai->enabled()
                ? get_string(
                    'crm_inbox_ai_openai_enabled',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_inbox_ai_openai_disabled',
                    'local_subscriptions'
                ),
        ];

        $checks[] = [
            'key' => 'openai_key',
            'success' => $keys->has_key(),
            'message' => $keys->has_key()
                ? get_string(
                    'crm_inbox_ai_openai_key_available',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_inbox_ai_openai_key_missing',
                    'local_subscriptions'
                ),
        ];

        $checks[] = [
            'key' => 'openai_model',
            'success' => $openai->model() !== '',
            'message' => $openai->model() !== ''
                ? get_string(
                    'crm_inbox_ai_openai_model_configured',
                    'local_subscriptions',
                    $openai->model()
                )
                : get_string(
                    'crm_inbox_ai_openai_model_missing',
                    'local_subscriptions'
                ),
        ];

        try {
            $orchestrator = InboxAiServiceFactory::orchestrator();

            $checks[] = [
                'key' => 'orchestrator',
                'success' => true,
                'message' =>
                    get_string(
                        'crm_inbox_ai_diagnostic_orchestrator_ok',
                        'local_subscriptions'
                    ),
            ];
        } catch (\Throwable $exception) {
            $checks[] = [
                'key' => 'orchestrator',
                'success' => false,
                'message' =>
                    $exception->getMessage(),
            ];
        }

        $since = usergetmidnight(time());

        return [
            'success' => !array_filter(
                $checks,
                static fn(array $check): bool =>
                    !$check['success']
            ),
            'checks' => $checks,
            'usage' =>
                $this->quota->usage($actorid),
            'failures' =>
                $this->usage
                    ->count_failures_since(
                        $since
                    ),
            'latest' =>
                $this->usage->latest(),
            'automatic' =>
                (bool)get_config(
                    'local_subscriptions',
                    'inbox_ai_automatic_analysis'
                ),
        ];
    }
}