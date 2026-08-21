<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n119a_customer_identities_polish_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_identity_navigation_uses_shared_modern_navigation_contract(): void {
        $renderer = $this->file(
            'classes/commerce/customer/identity/CommerceCustomerIdentityNavigationRenderer.php'
        );

        self::assertStringContainsString(
            'crm-identity-operations-nav',
            $renderer
        );
        self::assertStringContainsString(
            'crm-identity-operations-nav-list',
            $renderer
        );

        foreach ([
            'fa fa-link',
            'fa fa-search',
            'fa fa-random',
            'fa fa-sitemap',
            'fa fa-user-plus',
            'fa fa-shield',
        ] as $icon) {
            self::assertStringContainsString(
                $icon,
                $renderer
            );
        }
    }

    public function test_identity_list_pages_expose_shared_table_class(): void {
        foreach ([
            'admin/commerce/customer-identities/index.php',
            'admin/commerce/customer-identities/similarities.php',
            'admin/commerce/customer-identities/legacy-quality.php',
            'admin/commerce/customer-identities/provisioning.php',
            'admin/commerce/customer-identities/bulk.php',
            'admin/commerce/customer-identities/legacy-link.php',
        ] as $file) {
            $page = $this->file($file);

            self::assertStringContainsString(
                'crm-identity-table',
                $page
            );
        }
    }

    public function test_identity_filter_forms_expose_shared_filter_class(): void {
        foreach ([
            'admin/commerce/customer-identities/index.php',
            'admin/commerce/customer-identities/legacy-quality.php',
        ] as $file) {
            $page = $this->file($file);

            self::assertStringContainsString(
                'crm-identity-filter-card',
                $page
            );
        }

        $relationships = $this->file(
            'admin/commerce/customer-identities/relationships.php'
        );
        self::assertStringContainsString(
            'crm-identity-relationship-inspector-form',
            $relationships
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
