<?php
// CR.7 — menu consistency across CRM, Commerce and Work.
namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;
use local_subscriptions\crm\commerce\rendering\CommerceWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmNavigationRegistry;

final class crm_rights_cr7_navigation_consistency_test extends \advanced_testcase {
    /** Assign only the capabilities relevant to this scenario, at system level. */
    private function sign_in_with_capabilities(array $capabilities): void {
        $this->resetAfterTest(true);
        $user = $this->getDataGenerator()->create_user();
        $roleid = create_role('CR7 nav test', 'cr7nav' . $user->id, '', '');
        set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
        $system = context_system::instance();
        foreach ($capabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $system->id, true);
        }
        role_assign($roleid, $user->id, $system->id);
        $this->setUser($user);
    }

    private static function crm_item(array $items, string $key): ?object {
        foreach ($items as $item) {
            if ($item->key === $key) {
                return $item;
            }
        }
        return null;
    }

    public function test_content_can_reach_showrooms_in_both_menus_without_payments_access(): void {
        $this->sign_in_with_capabilities([
            Capabilities::VIEW_DASHBOARD,
            Capabilities::MANAGE_SHOWROOMS,
        ]);
        $system = context_system::instance();
        $commerce = self::crm_item((new CrmNavigationRegistry())->visible_items($system), CrmNavigationKeys::COMMERCE);
        $this->assertNotNull($commerce);
        $this->assertStringContainsString('/commerce/showrooms/index.php', $commerce->url->out(false));
        $this->assertFalse(has_capability(Capabilities::VIEW_PAYMENTS, $system));

        $secondary = (new CommerceSectionNavigationRegistry())->visible_items($system);
        $keys = array_map(static fn($item): string => $item->key, $secondary);
        $this->assertContains(CommerceSectionNavigationRegistry::SHOWROOMS, $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::OVERVIEW, $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::PRODUCTS, $keys);
    }

    public function test_support_does_not_see_financial_commerce_navigation(): void {
        $this->sign_in_with_capabilities([
            Capabilities::VIEW_DASHBOARD,
            Capabilities::VIEW_USERS,
            Capabilities::VIEW_INBOX,
            Capabilities::MANAGE_INBOX,
            Capabilities::VIEW_WORK_ITEMS,
            Capabilities::MANAGE_WORK_ITEMS,
        ]);
        $this->assertNull(self::crm_item((new CrmNavigationRegistry())->visible_items(), CrmNavigationKeys::COMMERCE));
        $this->assertSame([], (new CommerceSectionNavigationRegistry())->visible_items());
        $guard = file_get_contents(__DIR__ . '/../../admin/commerce/index.php');
        $this->assertIsString($guard);
        $this->assertStringContainsString('Capabilities::VIEW_PAYMENTS', $guard);
    }

    public function test_technical_user_can_reach_work_teams_and_commerce_configuration(): void {
        $this->sign_in_with_capabilities([
            Capabilities::VIEW_DASHBOARD,
            Capabilities::MANAGE_CONFIGURATION,
            Capabilities::MANAGE_WORK_CONFIGURATION,
            Capabilities::MANAGE_CRM_ADMIN_TOOLS,
        ]);
        $items = (new CrmNavigationRegistry())->visible_items();
        $work = self::crm_item($items, CrmNavigationKeys::WORK);
        $this->assertNotNull($work);
        $this->assertStringContainsString('/work/teams.php', $work->url->out(false));
        $commerce = self::crm_item($items, CrmNavigationKeys::COMMERCE);
        $this->assertNotNull($commerce);
        $this->assertStringContainsString('/commerce/products/index.php', $commerce->url->out(false));
        $this->assertFalse(has_capability(Capabilities::VIEW_WORK_ITEMS, context_system::instance()));
        $this->assertFalse(has_capability(Capabilities::VIEW_PAYMENTS, context_system::instance()));
    }

    public function test_finance_sees_commerce_overview_but_not_showrooms(): void {
        $this->sign_in_with_capabilities([
            Capabilities::VIEW_DASHBOARD,
            Capabilities::VIEW_PAYMENTS,
            Capabilities::VIEW_STATISTICS,
        ]);
        $commerce = self::crm_item((new CrmNavigationRegistry())->visible_items(), CrmNavigationKeys::COMMERCE);
        $this->assertNotNull($commerce);
        $this->assertStringContainsString('/commerce/index.php', $commerce->url->out(false));
        $secondary = (new CommerceSectionNavigationRegistry())->visible_items();
        $keys = array_map(static fn($item): string => $item->key, $secondary);
        $this->assertContains(CommerceSectionNavigationRegistry::OVERVIEW, $keys);
        $this->assertNotContains(CommerceSectionNavigationRegistry::SHOWROOMS, $keys);
    }

    public function test_commerce_primary_and_secondary_agree_on_showroom_capability(): void {
        // PHPUnit must restore the global $USER after impersonating an admin.
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $commerce = self::crm_item((new CrmNavigationRegistry())->visible_items(), CrmNavigationKeys::COMMERCE);
        $this->assertNotNull($commerce);
        $showroomchildren = array_filter($commerce->children,
            static fn(array $child): bool => str_contains($child['url']->out(false), '/commerce/showrooms/index.php'));
        $this->assertCount(1, $showroomchildren);
        $child = reset($showroomchildren);
        $this->assertSame(Capabilities::MANAGE_SHOWROOMS, $child['capability']);
        $secondary = (new CommerceSectionNavigationRegistry())->all_items();
        $matches = array_filter($secondary,
            static fn($item): bool => $item->key === CommerceSectionNavigationRegistry::SHOWROOMS);
        $this->assertCount(1, $matches);
        $this->assertSame(Capabilities::MANAGE_SHOWROOMS, reset($matches)->capability);
    }

    public function test_commerce_entry_cards_require_the_target_pages_capabilities(): void {
        $method = new \ReflectionMethod(CommerceWorkspaceRenderer::class, 'definitions');
        $cards = $method->invoke(null);
        $expected = [
            '/admin/subscriptions/index.php' => Capabilities::VIEW_PAYMENTS,
            '/admin/digital/purchases/index.php' => Capabilities::VIEW_PAYMENTS,
            '/admin/digital/stats.php' => Capabilities::VIEW_DIGITAL,
        ];
        foreach ($expected as $path => $capability) {
            $matches = array_filter($cards, static fn(array $card): bool =>
                str_contains($card['url']->out(false), $path));
            $this->assertCount(1, $matches, $path);
            $this->assertSame($capability, reset($matches)['capability'], $path);
        }
    }

    public function test_inbox_and_help_submenus_hide_inaccessible_links(): void {
        $inbox = file_get_contents(__DIR__ . '/../../classes/crm/inbox/rendering/InboxSectionNavigationRenderer.php');
        $help = file_get_contents(__DIR__ . '/../../classes/crm/help/HelpInternalNavigationRenderer.php');
        $mainnav = file_get_contents(__DIR__ . '/../../classes/crm/navigation/CrmNavigationRenderer.php');
        $this->assertIsString($inbox);
        $this->assertIsString($help);
        $this->assertIsString($mainnav);
        $this->assertStringContainsString("'visible' => AdminSecurity::can(Capabilities::VIEW_INBOX)", $inbox);
        $this->assertStringContainsString("$" . "item['key'] === 'diagnostics'", $help);
        $this->assertStringContainsString('Capabilities::MANAGE_CONFIGURATION', $help);
        $this->assertStringContainsString('has_capability(Capabilities::VIEW_INBOX, $context)', $mainnav);
    }

    public function test_work_navigation_does_not_advertise_inaccessible_items(): void {
        $renderer = file_get_contents(__DIR__ . '/../../classes/crm/work/rendering/WorkSectionNavigationRenderer.php');
        $this->assertIsString($renderer);
        $this->assertStringContainsString("'visible' => AdminSecurity::can(Capabilities::VIEW_WORK_ITEMS)", $renderer);
        $this->assertStringContainsString('Capabilities::MANAGE_WORK_CONFIGURATION', $renderer);
    }
}
