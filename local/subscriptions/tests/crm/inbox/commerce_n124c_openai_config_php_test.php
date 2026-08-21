<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiApiKeyProvider;
use local_subscriptions\crm\inbox\ai\providers\openai\OpenAiInboxConfiguration;

final class commerce_n124c_openai_config_php_test extends \advanced_testcase {

    protected function tearDown(): void {
        global $CFG;

        unset(
            $CFG->local_subscriptions_openai_api_key,
            $CFG->local_subscriptions_openai_model,
            $CFG->local_subscriptions_openai_enabled
        );

        parent::tearDown();
    }

    public function test_config_php_key_enables_provider_when_no_db_toggle_exists(): void {
        global $CFG;

        $this->resetAfterTest();

        unset_config(
            'inbox_ai_openai_enabled',
            'local_subscriptions'
        );
        unset_config(
            'inbox_ai_openai_model',
            'local_subscriptions'
        );

        $CFG->local_subscriptions_openai_api_key =
            'test-key';

        $configuration =
            new OpenAiInboxConfiguration(
                new OpenAiApiKeyProvider()
            );

        self::assertTrue(
            $configuration->enabled()
        );
        self::assertSame(
            'gpt-5.6-luna',
            $configuration->model()
        );
        self::assertTrue(
            $configuration->available()
        );
    }

    public function test_explicit_config_php_toggle_can_disable_provider(): void {
        global $CFG;

        $this->resetAfterTest();

        unset_config(
            'inbox_ai_openai_enabled',
            'local_subscriptions'
        );

        $CFG->local_subscriptions_openai_api_key =
            'test-key';
        $CFG->local_subscriptions_openai_enabled =
            false;

        $configuration =
            new OpenAiInboxConfiguration(
                new OpenAiApiKeyProvider()
            );

        self::assertFalse(
            $configuration->enabled()
        );
        self::assertFalse(
            $configuration->available()
        );
    }

    public function test_config_php_model_overrides_moodle_setting(): void {
        global $CFG;

        $this->resetAfterTest();

        set_config(
            'inbox_ai_openai_model',
            'db-model',
            'local_subscriptions'
        );

        $CFG->local_subscriptions_openai_api_key =
            'test-key';
        $CFG->local_subscriptions_openai_model =
            'gpt-5.6-terra';

        $configuration =
            new OpenAiInboxConfiguration(
                new OpenAiApiKeyProvider()
            );

        self::assertSame(
            'gpt-5.6-terra',
            $configuration->model()
        );
    }
}
