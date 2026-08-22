<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Central registry for the main CRM application navigation.
 *
 * This class is the single source of truth for:
 * - item identifiers;
 * - translated labels;
 * - icons;
 * - URLs;
 * - capabilities;
 * - display order.
 */
final class CrmNavigationRegistry {

    /**
     * Returns every item the current user is allowed to see.
     *
     * @param context|null $context
     * @return CrmNavigationItem[]
     */
    public function visible_items(
        ?context $context = null
    ): array {
        $context = $context
            ?? context_system::instance();

        $items = array_filter(
            $this->all_items(),
            static function (
                CrmNavigationItem $item
            ) use ($context): bool {
                return has_capability(
                    $item->capability,
                    $context
                );
            }
        );

        usort(
            $items,
            static function (
                CrmNavigationItem $left,
                CrmNavigationItem $right
            ): int {
                return $left->position
                    <=> $right->position;
            }
        );

        return array_values($items);
    }

    /**
     * Returns the complete registry before capability filtering.
     *
     * @return CrmNavigationItem[]
     */
    private function all_items(): array {
        $child = static function(string $label, string $path, string $capability, string $icon): array {
            return [
                'label' => $label,
                'url' => new moodle_url($path),
                'capability' => $capability,
                'icon' => $icon,
            ];
        };

        return [
            new CrmNavigationItem(
                key: CrmNavigationKeys::DASHBOARD,
                label: get_string('crm_nav_dashboard', 'local_subscriptions'),
                icon: 'fa-dashboard',
                url: new moodle_url(subscription_config::admin_dashboard_page()),
                capability: Capabilities::VIEW_DASHBOARD,
                position: 5
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::USERS,
                label: get_string('crm_nav_users', 'local_subscriptions'),
                icon: 'fa-users',
                url: new moodle_url(subscription_config::admin_users_page()),
                capability: Capabilities::VIEW_USERS,
                position: 10,
                children: [
                    $child(
                        get_string('crm_nav_users_overview', 'local_subscriptions'),
                        subscription_config::admin_users_page(),
                        Capabilities::VIEW_USERS,
                        'fa-user-circle'
                    ),
                    $child(
                        get_string('crm_commerce_nav_identities', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/customer-identities/index.php',
                        Capabilities::VIEW_PAYMENTS,
                        'fa-link'
                    ),
                ]
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::COMMERCE,
                label: get_string('crm_nav_commerce', 'local_subscriptions'),
                icon: 'fa-shopping-cart',
                url: new moodle_url(subscription_config::admin_commerce_page()),
                capability: Capabilities::VIEW_DASHBOARD,
                position: 20,
                children: [
                    $child(
                        get_string('crm_commerce_nav_overview', 'local_subscriptions'),
                        subscription_config::admin_commerce_page(),
                        Capabilities::VIEW_DASHBOARD,
                        'fa-dashboard'
                    ),
                    $child(
                        get_string('crm_commerce_nav_purchases', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/purchases/index.php',
                        Capabilities::VIEW_PAYMENTS,
                        'fa-credit-card'
                    ),
                    $child(
                        get_string('crm_commerce_nav_products', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/products/index.php',
                        Capabilities::MANAGE_CONFIGURATION,
                        'fa-cube'
                    ),
                    $child(
                        get_string('crm_nav_showrooms', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/showrooms/index.php',
                        'local/subscriptions:manage_showrooms',
                        'fa-picture-o'
                    ),
                    $child(
                        get_string('crm_commerce_nav_offers_access', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/offers-access/index.php',
                        Capabilities::VIEW_PAYMENTS,
                        'fa-gift'
                    ),
                    $child(
                        get_string('crm_commerce_nav_mail', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/mail/index.php',
                        Capabilities::VIEW_PAYMENTS,
                        'fa-envelope'
                    ),
                    $child(
                        get_string('crm_commerce_nav_statistics', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/statistics/index.php',
                        Capabilities::VIEW_STATISTICS,
                        'fa-bar-chart'
                    ),
                    $child(
                        get_string('crm_commerce_nav_configuration', 'local_subscriptions'),
                        '/local/subscriptions/admin/commerce/configuration/index.php',
                        Capabilities::MANAGE_CONFIGURATION,
                        'fa-cog'
                    ),
                ]
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::INBOX,
                label: get_string('crm_nav_inbox', 'local_subscriptions'),
                icon: 'fa-envelope',
                url: new moodle_url(subscription_config::admin_inbox_page()),
                capability: Capabilities::VIEW_INBOX,
                position: 30,
                children: [
                    $child(
                        get_string('crm_nav_inbox_overview', 'local_subscriptions'),
                        subscription_config::admin_inbox_page(),
                        Capabilities::VIEW_INBOX,
                        'fa-inbox'
                    ),
                    $child(
                        get_string('crm_inbox_o15_nav_compose', 'local_subscriptions'),
                        subscription_config::admin_inbox_compose_page(),
                        Capabilities::MANAGE_INBOX,
                        'fa-pencil'
                    ),
                    $child(
                        get_string('crm_inbox_o15_nav_drafts', 'local_subscriptions'),
                        subscription_config::admin_inbox_drafts_page(),
                        Capabilities::MANAGE_INBOX,
                        'fa-file-text-o'
                    ),
                    $child(
                        get_string('crm_inbox_o15_nav_templates', 'local_subscriptions'),
                        subscription_config::admin_inbox_templates_page(),
                        Capabilities::MANAGE_INBOX,
                        'fa-bolt'
                    ),
                    $child(
                        get_string('crm_nav_diagnostics', 'local_subscriptions'),
                        '/local/subscriptions/admin/inbox/diagnostics.php',
                        Capabilities::VIEW_INBOX,
                        'fa-stethoscope'
                    ),
                    $child(
                        get_string(
                            'crm_nav_inbox_ai_diagnostics_n1210a',
                            'local_subscriptions'
                        ),
                        subscription_config::admin_inbox_ai_diagnostics_page(),
                        Capabilities::MANAGE_CONFIGURATION,
                        'fa-magic'
                    ),
                ]
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::WORK,
                label: get_string('crm_nav_work', 'local_subscriptions'),
                icon: 'fa-tasks',
                url: new moodle_url(subscription_config::admin_work_items_page()),
                capability: Capabilities::VIEW_WORK_ITEMS,
                position: 40,
                children: [
                    $child(
                        get_string('crm_nav_work_items', 'local_subscriptions'),
                        subscription_config::admin_work_items_page(),
                        Capabilities::VIEW_WORK_ITEMS,
                        'fa-tasks'
                    ),
                    $child(
                        get_string('crm_nav_work_teams', 'local_subscriptions'),
                        subscription_config::admin_work_teams_page(),
                        Capabilities::VIEW_WORK_ITEMS,
                        'fa-users'
                    ),
                ]
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::ASSISTANT,
                label: get_string('crm_nav_assistant', 'local_subscriptions'),
                icon: 'fa-magic',
                url: new moodle_url(subscription_config::admin_crm_assistant_page()),
                capability: Capabilities::VIEW_USERS,
                position: 50
            ),


            new CrmNavigationItem(
                key: CrmNavigationKeys::TOOLS,
                label: get_string('crm_nav_tools', 'local_subscriptions'),
                icon: 'fa-wrench',
                url: new moodle_url(subscription_config::admin_crm_tools_page()),
                capability: Capabilities::MANAGE_CRM_ADMIN_TOOLS,
                position: 70,
                children: [
                    $child(
                        get_string('crm_nav_tools_overview', 'local_subscriptions'),
                        subscription_config::admin_crm_tools_page(),
                        Capabilities::MANAGE_CRM_ADMIN_TOOLS,
                        'fa-wrench'
                    ),
                    $child(
                        get_string('crm_nav_tools_history', 'local_subscriptions'),
                        subscription_config::admin_crm_tool_history_page(),
                        Capabilities::MANAGE_CRM_ADMIN_TOOLS,
                        'fa-history'
                    ),
                ]
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::HELP,
                label: get_string('crm_nav_help', 'local_subscriptions'),
                icon: 'fa-question-circle',
                url: new moodle_url(subscription_config::admin_help_page()),
                capability: Capabilities::VIEW_DASHBOARD,
                position: 999,
                children: [
                    $child(
                        get_string('crm_nav_help_overview', 'local_subscriptions'),
                        subscription_config::admin_help_page(),
                        Capabilities::VIEW_DASHBOARD,
                        'fa-question-circle'
                    ),
                    $child(
                        get_string('crm_nav_diagnostics', 'local_subscriptions'),
                        subscription_config::admin_help_diagnostics_page(),
                        Capabilities::VIEW_DASHBOARD,
                        'fa-stethoscope'
                    ),
                ]
            ),
        ];
    }
}