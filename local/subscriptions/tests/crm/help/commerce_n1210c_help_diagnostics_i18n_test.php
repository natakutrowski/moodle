<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1210c_help_diagnostics_i18n_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_help_validator_uses_language_strings_for_diagnostic_messages(): void {
        $validator = $this->file(
            'classes/crm/help/validation/HelpCenterValidator.php'
        );

        self::assertStringContainsString(
            'crm_help_diag_msg_language_key_extra_n1210c',
            $validator
        );
        self::assertStringContainsString(
            'crm_help_diag_msg_markdown_missing_n1210c',
            $validator
        );
        self::assertStringNotContainsString(
            'Language string exists in %s but not in en',
            $validator
        );
        self::assertStringNotContainsString(
            'Missing Markdown file for article',
            $validator
        );
    }

    public function test_configuration_labels_exist_in_en_and_ru(): void {
        foreach (['en', 'ru'] as $language) {
            $lang = $this->file(
                'lang/' . $language . '/local_subscriptions.php'
            );

            foreach ([
                'commerce_configuration_setting_alfa_env',
                'commerce_configuration_setting_commerce_runtime_mode',
                'commerce_configuration_setting_defaultemaillang',
                'commerce_configuration_setting_storefront_ai_translation_enabled',
                'commerce_configuration_setting_terms_url_ru',
            ] as $key) {
                self::assertStringContainsString(
                    "\$string['" . $key . "']",
                    $lang
                );
            }
        }
    }
}
