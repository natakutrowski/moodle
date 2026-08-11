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
        return [
            new CrmNavigationItem(
                key: CrmNavigationKeys::DASHBOARD,
                label: get_string(
                    'admin_dashboard',
                    'local_subscriptions'
                ),
                icon: '⌂',
                url: new moodle_url(
                    subscription_config::
                        admin_dashboard_page()
                ),
                capability:
                    Capabilities::VIEW_DASHBOARD,
                position: 10
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::USERS,
                label: get_string(
                    'crm_users',
                    'local_subscriptions'
                ),
                icon: '👥',
                url: new moodle_url(
                    subscription_config::
                        admin_users_page()
                ),
                capability:
                    Capabilities::VIEW_USERS,
                position: 20
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::INBOX,
                label: get_string(
                    'crm_inbox_title',
                    'local_subscriptions'
                ),
                icon: '✉',
                url: new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
                capability:
                    Capabilities::VIEW_INBOX,
                position: 30
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::WORK,
                label: get_string(
                    'crm_work_title',
                    'local_subscriptions'
                ),
                icon: '✓',
                url: new moodle_url(
                    subscription_config::
                        admin_work_items_page()
                ),
                capability:
                    Capabilities::VIEW_WORK_ITEMS,
                position: 40
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::ASSISTANT,
                label: get_string(
                    'crm_assistant_title',
                    'local_subscriptions'
                ),
                icon: '✦',
                url: new moodle_url(
                    subscription_config::
                        admin_crm_assistant_page()
                ),
                capability:
                    Capabilities::VIEW_USERS,
                position: 50
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::COMMERCE,
                label: get_string(
                    'crm_commerce_nav',
                    'local_subscriptions'
                ),
                icon: '◆',
                url: new moodle_url(
                    subscription_config::
                        admin_commerce_page()
                ),
                capability:
                    Capabilities::VIEW_DASHBOARD,
                position: 60
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::SHOWROOMS,
                label: get_string(
                    'commerce_showroom_cms_title',
                    'local_subscriptions'
                ),
                icon: '▣',
                url: new moodle_url(
                    '/local/subscriptions/admin/commerce/showrooms/index.php'
                ),
                capability: 'local/subscriptions:manage_showrooms',
                position: 65
            ),

            new CrmNavigationItem(
                key: CrmNavigationKeys::HELP,
                label: get_string(
                    'crm_help_title',
                    'local_subscriptions'
                ),
                icon: '?',
                url: new moodle_url(
                    subscription_config::
                        admin_help_page()
                ),
                capability:
                    Capabilities::VIEW_DASHBOARD,
                position: 70
            ),
            new CrmNavigationItem(
                key: CrmNavigationKeys::TOOLS,
                label: get_string(
                    'crm_admin_tools_nav',
                    'local_subscriptions'
                ),
                icon: '⚙',
                url: new moodle_url(
                    subscription_config::
                        admin_crm_tools_page()
                ),
                capability:
                    Capabilities::
                        MANAGE_CRM_ADMIN_TOOLS,
                position: 80
            ),

        ];
    }
}