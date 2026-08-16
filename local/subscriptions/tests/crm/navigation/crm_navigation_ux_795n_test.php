<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Structural certification for the 7.95N CRM primary navigation. */
final class crm_navigation_ux_795n_test extends \advanced_testcase {
    public function test_registry_prioritises_users_and_commerce_and_embeds_showrooms(): void {
        global $CFG;
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );

        self::assertNotFalse($source);
        self::assertStringContainsString("key: CrmNavigationKeys::USERS", $source);
        self::assertStringContainsString("position: 10", $source);
        self::assertStringContainsString("key: CrmNavigationKeys::COMMERCE", $source);
        self::assertStringContainsString("position: 20", $source);
        self::assertStringContainsString("/admin/commerce/showrooms/index.php", $source);
        self::assertStringNotContainsString("key: CrmNavigationKeys::SHOWROOMS,", $source);
        self::assertStringContainsString("position: 999", $source);
    }

    public function test_active_items_remain_clickable_and_context_menus_are_rendered(): void {
        global $CFG;
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRenderer.php'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        self::assertNotFalse($renderer);
        self::assertNotFalse($css);
        self::assertStringContainsString('CrmNavigationKeys::SHOWROOMS', $renderer);
        self::assertStringContainsString('crm-app-navigation-submenu', $renderer);
        self::assertStringContainsString('crm-app-navigation-menu-toggle', $renderer);
        self::assertStringContainsString(
            '.crm-app-navigation-link[aria-current="page"] {' . PHP_EOL . '    pointer-events: auto;',
            $css
        );
    }

    public function test_navigation_uses_explicit_font_awesome_icons_and_short_labels(): void {
        global $CFG;
        $registry = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );
        $fr = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/lang/fr/local_subscriptions.php'
        );

        self::assertNotFalse($registry);
        self::assertNotFalse($fr);
        self::assertStringContainsString("icon: 'fa-users'", $registry);
        self::assertStringContainsString("icon: 'fa-shopping-cart'", $registry);
        self::assertStringContainsString("\$string['crm_nav_dashboard'] = 'CRM Dashboard';", $fr);
        self::assertStringContainsString("\$string['crm_nav_users'] = 'Utilisateurs';", $fr);
        self::assertStringContainsString("\$string['crm_nav_help'] = 'Aide';", $fr);
    }

    public function test_n1b_dashboard_is_first_and_renderer_marks_it_as_home(): void {
        global $CFG;
        $registry = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRenderer.php'
        );

        self::assertNotFalse($registry);
        self::assertNotFalse($renderer);
        $dashboard = strpos($registry, 'key: CrmNavigationKeys::DASHBOARD');
        $users = strpos($registry, 'key: CrmNavigationKeys::USERS');
        self::assertIsInt($dashboard);
        self::assertIsInt($users);
        self::assertLessThan($users, $dashboard);
        self::assertStringContainsString('position: 5', $registry);
        self::assertStringContainsString("CrmNavigationKeys::DASHBOARD ? ' is-dashboard'", $renderer);
    }

    public function test_n1b_desktop_dropdowns_are_not_clipped_and_split_button_hovers_together(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        self::assertNotFalse($css);
        self::assertStringContainsString('.crm-app-navigation-panel,', $css);
        self::assertStringContainsString('overflow: visible;', $css);
        self::assertStringContainsString('.crm-app-navigation-item.is-dashboard::after', $css);
        self::assertStringNotContainsString(
            '.crm-app-navigation-item.has-submenu:hover .crm-app-navigation-link',
            $css
        );
    }

    public function test_n1c_context_menus_are_click_only_and_close_outside(): void {
        global $CFG;
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRenderer.php'
        );
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/crm_shell.js'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        self::assertNotFalse($renderer);
        self::assertNotFalse($source);
        self::assertNotFalse($css);
        self::assertStringContainsString("'data-crm-navigation-menu-toggle' => '1'", $renderer);
        self::assertStringContainsString("'aria-expanded' => 'false'", $renderer);
        self::assertStringContainsString("navigationMenuToggle: '[data-crm-navigation-menu-toggle]'", $source);
        self::assertStringContainsString('closeNavigationMenus(shell);', $source);
        self::assertStringContainsString("item.classList.toggle(\n                    'is-menu-open'", $source);
        self::assertStringContainsString(
            '.crm-app-navigation-item.has-submenu.is-menu-open > .crm-app-navigation-submenu',
            $css
        );
        self::assertStringNotContainsString(
            '.crm-app-navigation-item.has-submenu:hover > .crm-app-navigation-submenu',
            $css
        );
    }

    public function test_n1c_submenus_have_font_awesome_icons_and_dashboard_is_quiet_when_inactive(): void {
        global $CFG;
        $registry = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );
        $renderer = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRenderer.php'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        self::assertNotFalse($registry);
        self::assertNotFalse($renderer);
        self::assertNotFalse($css);
        self::assertStringContainsString("'fa-credit-card'", $registry);
        self::assertStringContainsString("'fa-picture-o'", $registry);
        self::assertStringContainsString("'fa-stethoscope'", $registry);
        self::assertStringContainsString('crm-app-navigation-submenu-icon', $renderer);
        self::assertStringContainsString(
            '.crm-app-navigation-item.is-dashboard .crm-app-navigation-link.is-active',
            $css
        );
        self::assertStringContainsString('background: transparent;', $css);
        self::assertStringContainsString('height: 1.25rem;', $css);
    }


    public function test_submenus_are_click_driven_not_hover_driven(): void {
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $renderer = file_get_contents(
            __DIR__ . '/../../../classes/crm/navigation/CrmNavigationRenderer.php'
        );
        $this->assertIsString($styles);
        $this->assertIsString($renderer);
        $this->assertStringNotContainsString(
            '.crm-app-navigation-item.has-submenu:hover .crm-app-navigation-link',
            $styles
        );
        $this->assertStringContainsString('data-crm-navigation-menu-toggle', $renderer);
    }


    public function test_n27_commerce_submenu_order_and_no_checkout(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/crm/navigation/CrmNavigationRegistry.php'
        );
        $styles = file_get_contents(__DIR__ . '/../../../styles.css');
        $this->assertIsString($source);
        $this->assertIsString($styles);

        $overview = strpos($source, "crm_commerce_nav_overview");
        $sales = strpos($source, "crm_commerce_nav_purchases", $overview);
        $products = strpos($source, "crm_commerce_nav_products", $sales);
        $showrooms = strpos($source, "crm_nav_showrooms", $products);
        $offers = strpos($source, "crm_commerce_nav_offers_access", $showrooms);
        $mail = strpos($source, "crm_commerce_nav_mail", $offers);
        $statistics = strpos($source, "crm_commerce_nav_statistics", $mail);
        $configuration = strpos($source, "crm_commerce_nav_configuration", $statistics);

        foreach ([$overview, $sales, $products, $showrooms, $offers, $mail, $statistics, $configuration] as $position) {
            $this->assertNotFalse($position);
        }
        $this->assertLessThan($sales, $overview);
        $this->assertLessThan($products, $sales);
        $this->assertLessThan($showrooms, $products);
        $this->assertLessThan($offers, $showrooms);
        $this->assertLessThan($mail, $offers);
        $this->assertLessThan($statistics, $mail);
        $this->assertLessThan($configuration, $statistics);

        $commerceblock = substr($source, $overview, $configuration - $overview);
        $this->assertStringNotContainsString('crm_commerce_nav_unfinished_checkouts', $commerceblock);
        $this->assertStringContainsString('color: #312e81 !important', $styles);
    }

}
