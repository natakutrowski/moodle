<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

final class commerce_configuration_n1037_test extends advanced_testcase {
    public function test_legal_documents_are_split_by_region(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertStringContainsString('commerce_configuration_group_legal_ru_by', $source);
        $this->assertStringContainsString('commerce_configuration_group_legal_row', $source);
        $this->assertStringContainsString("'commerce_configuration_group_legal_ru_by' => '🇷🇺 🇧🇾'", $source);
        $this->assertStringContainsString("'commerce_configuration_group_legal_row' => '🌍'", $source);
        $this->assertStringNotContainsString('commerce_configuration_group_legal_links', $source);
    }

    public function test_storefront_only_keeps_active_global_and_legacy_fallback_settings(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $this->assertStringContainsString("\$field('storefront_ai_translation_enabled'", $source);
        $this->assertStringContainsString("\$field('featured_planid'", $source);
        $this->assertStringContainsString('commerce_configuration_group_storefront_legacy', $source);
        $this->assertStringNotContainsString("\$field('brand_logo_url', 'brand_logo_url_label'", $source);
    }

    public function test_brand_logo_is_presented_with_mail_identity(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/configuration/section.php');
        $mailidentity = strpos($source, 'commerce_configuration_group_mail_identity');
        $mailthrottle = strpos($source, 'commerce_configuration_group_mail_global_throttle');
        $slice = substr($source, $mailidentity, $mailthrottle - $mailidentity);
        $this->assertStringContainsString("\$field('brand_logo_url'", $slice);
    }

    public function test_storefront_audit_matches_runtime_consumers(): void {
        $root = dirname(__DIR__, 3);
        $translation = file_get_contents($root . '/classes/commerce/storefront/translation/CommerceStorefrontAiTranslationService.php');
        $legacyrenderer = file_get_contents($root . '/classes/output/renderer.php');
        $mailer = file_get_contents($root . '/classes/mail/MailRenderer.php');
        $this->assertStringContainsString("get_config('local_subscriptions', 'storefront_ai_translation_enabled')", $translation);
        $this->assertStringContainsString("get_config('local_subscriptions', 'featured_planid')", $legacyrenderer);
        $this->assertStringContainsString("get_config('local_subscriptions', 'brand_logo_url')", $mailer);
    }

    public function test_n1037_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');
        $this->assertStringContainsString('$plugin->version = 2026081602;', $version);
    }
}
