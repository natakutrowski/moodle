<?php

namespace local_subscriptions\crm\inbox\ai\providers\openai;

defined('MOODLE_INTERNAL') || die();

final class OpenAiApiKeyProvider {

    public function get(): string {
        global $CFG;

        $key = trim(
            (string)(
                $CFG->local_subscriptions_openai_api_key
                ?? getenv('OPENAI_API_KEY')
                ?: ''
            )
        );

        return $key;
    }

    public function has_key(): bool {
        return $this->get() !== '';
    }

    public function project_id(): string {
        global $CFG;

        return trim(
            (string)(
                $CFG->local_subscriptions_openai_project
                ?? getenv('OPENAI_PROJECT_ID')
                ?: ''
            )
        );
    }

    public function organization_id(): string {
        global $CFG;

        return trim(
            (string)(
                $CFG->local_subscriptions_openai_organization
                ?? getenv('OPENAI_ORGANIZATION_ID')
                ?: ''
            )
        );
    }
}