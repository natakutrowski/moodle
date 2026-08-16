<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_offers_access_n722_test extends advanced_testcase {
    public function test_email_only_offer_collects_missing_identity_before_identification(): void {
        $root = dirname(__DIR__, 3);
        $checkout = file_get_contents($root . '/commerce_checkout.php');
        $template = file_get_contents($root . '/templates/checkout/page.mustache');
        self::assertStringContainsString('$personalofferhascompleteidentity', $checkout);
        self::assertStringContainsString('personalofferneedsfirstname', $template);
        self::assertStringContainsString('personalofferneedslastname', $template);
    }

    public function test_mail_studio_exposes_safe_greeting_and_username_tokens(): void {
        $root = dirname(__DIR__, 3);
        $resolver = file_get_contents($root . '/classes/commerce/mail/template/studio/CommerceMailTokenResolver.php');
        $builder = file_get_contents($root . '/classes/commerce/mail/builder/CommerceMailBuilder.php');
        self::assertStringContainsString("'greeting' => " . '$greeting', $resolver);
        self::assertStringContainsString("'username' => " . '$username', $resolver);
        self::assertStringContainsString("'greeting'", $builder);
        self::assertStringContainsString("'username'", $builder);
    }

    public function test_new_manual_grant_account_gets_one_time_password_setup_in_access_mail(): void {
        $root = dirname(__DIR__, 3);
        $add = file_get_contents($root . '/admin/subscriptions/add.php');
        $context = file_get_contents($root . '/classes/commerce/mail/context/CommerceGrantAccessMailContextFactory.php');
        $template = file_get_contents($root . '/templates/commerce/mail/grant_access.mustache');
        self::assertStringContainsString("'crm_manual_grant'", $add);
        self::assertStringContainsString("'activation_pending'", $add);
        self::assertStringContainsString("'activationurl'", $context);
        self::assertStringContainsString('{{#hasaccountactivation}}', $template);
    }

    public function test_internal_navigation_has_icons_and_overview_separator(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents($root . '/classes/crm/commerce/rendering/CommerceOffersAccessNavigationRenderer.php');
        $styles = file_get_contents($root . '/styles.css');
        self::assertStringContainsString("'fa-home'", $renderer);
        self::assertStringContainsString("'fa-tag'", $renderer);
        self::assertStringContainsString("'fa-key'", $renderer);
        self::assertStringContainsString("'fa-bullseye'", $renderer);
        self::assertStringContainsString('crm-offers-access-tab.is-overview::before', $styles);
    }

    public function test_n722_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        self::assertStringContainsString('$plugin->version = 2026081601;', file_get_contents($root . '/version.php'));
    }
}