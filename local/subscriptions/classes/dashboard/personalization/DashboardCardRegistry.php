<?php

namespace local_subscriptions\dashboard\personalization;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanDashboardCard;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemActionDefinition;
use local_subscriptions\dashboard\cards\ActivityCard;
use local_subscriptions\dashboard\cards\AlertsCard;
use local_subscriptions\dashboard\cards\CrmAssistantCard;
use local_subscriptions\dashboard\cards\CrmDailyPrioritiesCard;
use local_subscriptions\dashboard\cards\CrmFunnelCard;
use local_subscriptions\dashboard\cards\CrmIntelligenceAlertsCard;
use local_subscriptions\dashboard\cards\CrmIntelligenceCard;
use local_subscriptions\dashboard\cards\CrmTrendsCard;
use local_subscriptions\dashboard\cards\InboxOverviewCard;
use local_subscriptions\dashboard\cards\NavigationCard;
use local_subscriptions\dashboard\cards\StatsCard;
use local_subscriptions\dashboard\cards\TeamCard;
use local_subscriptions\dashboard\cards\WorkItemOverviewCard;
use local_subscriptions\dashboard\DashboardSection;
use local_subscriptions\dashboard\runtime\DashboardCardProfiler;
use local_subscriptions\dashboard\services\DashboardPeriod;

/**
 * Central registry of the Cards available on the CRM Dashboard.
 *
 * The registry is the single source of truth for:
 * - stable Card identifiers;
 * - presentation labels and descriptions;
 * - categories;
 * - item types;
 * - default zones and ordering;
 * - grid column spans;
 * - personalization permissions;
 * - default visibility;
 * - period awareness;
 * - contextual Workspace actions;
 * - lazy rendering.
 */
final class DashboardCardRegistry {

    public const ZONE_HERO = 'hero';
    public const ZONE_UTILITY = 'utility';
    public const ZONE_FOCUS = 'focus';
    public const ZONE_OPERATIONS = 'operations';
    public const ZONE_OPERATIONS_SIDE = 'operations_side';
    public const ZONE_INSIGHTS = 'insights';
    public const ZONE_SECONDARY = 'secondary';

    public const CATEGORY_OVERVIEW = 'overview';
    public const CATEGORY_INTELLIGENCE = 'intelligence';
    public const CATEGORY_OPERATIONS = 'operations';
    public const CATEGORY_CUSTOMER_SUCCESS = 'customer_success';
    public const CATEGORY_NAVIGATION_ACTIVITY =
        'navigation_activity';
    public const CATEGORY_TEAM = 'team';
    public const CATEGORY_SYSTEM = 'system';

    /**
     * Returns all registered Dashboard Cards.
     *
     * @return array<string, array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     icon: string,
     *     category: string,
     *     zone: string,
     *     span: int,
     *     type: string,
     *     hideable: bool,
     *     movable: bool,
     *     defaultvisible: bool,
     *     periodaware: bool,
     *     actions: array
     * }>
     */
    public static function definitions(): array {
        return [
            'stats' => self::definition(
                key: 'stats',
                labelkey:
                    'dashboard_personalization_card_stats',
                descriptionkey:
                    'dashboard_personalization_card_stats_description',
                icon: '📊',
                category: self::CATEGORY_OVERVIEW,
                zone: self::ZONE_HERO,
                span: 3,
                periodaware: true,
                actions: self::detail_actions()
            ),

            'intelligence' => self::definition(
                key: 'intelligence',
                labelkey:
                    'dashboard_personalization_card_intelligence',
                descriptionkey:
                    'dashboard_personalization_card_intelligence_description',
                icon: '🧠',
                category: self::CATEGORY_INTELLIGENCE,
                zone: self::ZONE_INSIGHTS,
                span: 1,
                actions: self::detail_actions()
            ),

            'assistant' => self::definition(
                key: 'assistant',
                labelkey:
                    'dashboard_personalization_card_assistant',
                descriptionkey:
                    'dashboard_personalization_card_assistant_description',
                icon: '🧭',
                category: self::CATEGORY_INTELLIGENCE,
                zone: self::ZONE_FOCUS,
                span: 1,
                actions: self::detail_actions()
            ),

            'inbox' => self::definition(
                key: 'inbox',
                labelkey:
                    'dashboard_personalization_card_inbox',
                descriptionkey:
                    'dashboard_personalization_card_inbox_description',
                icon: '✉️',
                category: self::CATEGORY_OPERATIONS,
                zone: self::ZONE_OPERATIONS,
                span: 2,
                actions: self::detail_actions()
            ),

            'work' => self::definition(
                key: 'work',
                labelkey:
                    'dashboard_personalization_card_work',
                descriptionkey:
                    'dashboard_personalization_card_work_description',
                icon: '✅',
                category: self::CATEGORY_OPERATIONS,
                zone: self::ZONE_OPERATIONS_SIDE,
                span: 1,
                actions: self::detail_actions()
            ),

            'customer_success' => self::definition(
                key: 'customer_success',
                labelkey:
                    'dashboard_personalization_card_customer_success',
                descriptionkey:
                    'dashboard_personalization_card_customer_success_description',
                icon: '🎯',
                category:
                    self::CATEGORY_CUSTOMER_SUCCESS,
                zone: self::ZONE_OPERATIONS_SIDE,
                span: 1
            ),

            'issues' => self::definition(
                key: 'issues',
                labelkey:
                    'dashboard_personalization_card_issues',
                descriptionkey:
                    'dashboard_personalization_card_issues_description',
                icon: '⚠️',
                category: self::CATEGORY_OPERATIONS,
                zone: self::ZONE_FOCUS,
                span: 2
            ),

            'priorities' => self::definition(
                key: 'priorities',
                labelkey:
                    'dashboard_personalization_card_priorities',
                descriptionkey:
                    'dashboard_personalization_card_priorities_description',
                icon: '⭐',
                category: self::CATEGORY_INTELLIGENCE,
                zone: self::ZONE_FOCUS,
                span: 1,
                actions: self::detail_actions()
            ),

            'funnel' => self::definition(
                key: 'funnel',
                labelkey:
                    'dashboard_personalization_card_funnel',
                descriptionkey:
                    'dashboard_personalization_card_funnel_description',
                icon: '📈',
                category: self::CATEGORY_INTELLIGENCE,
                zone: self::ZONE_INSIGHTS,
                span: 1,
                periodaware: true,
                actions: self::detail_actions()
            ),

            'trends' => self::definition(
                key: 'trends',
                labelkey:
                    'dashboard_personalization_card_trends',
                descriptionkey:
                    'dashboard_personalization_card_trends_description',
                icon: '📉',
                category: self::CATEGORY_INTELLIGENCE,
                zone: self::ZONE_INSIGHTS,
                span: 1,
                periodaware: true,
                actions: self::detail_actions()
            ),

            'intelligence_alerts' => self::definition(
                key: 'intelligence_alerts',
                labelkey:
                    'dashboard_personalization_card_intelligence_alerts',
                descriptionkey:
                    'dashboard_personalization_card_intelligence_alerts_description',
                icon: '🚨',
                category: self::CATEGORY_INTELLIGENCE,
                zone: self::ZONE_INSIGHTS,
                span: 1,
                actions: self::detail_actions()
            ),

            'navigation' => self::definition(
                key: 'navigation',
                labelkey:
                    'dashboard_personalization_card_navigation',
                descriptionkey:
                    'dashboard_personalization_card_navigation_description',
                icon: '🧰',
                category:
                    self::CATEGORY_NAVIGATION_ACTIVITY,
                zone: self::ZONE_SECONDARY,
                span: 1,
                defaultvisible: false
            ),

            'activity' => self::definition(
                key: 'activity',
                labelkey:
                    'dashboard_personalization_card_activity',
                descriptionkey:
                    'dashboard_personalization_card_activity_description',
                icon: '🕒',
                category:
                    self::CATEGORY_NAVIGATION_ACTIVITY,
                zone: self::ZONE_SECONDARY,
                span: 2
            ),

            'team' => self::definition(
                key: 'team',
                labelkey:
                    'dashboard_personalization_card_team',
                descriptionkey:
                    'dashboard_personalization_card_team_description',
                icon: '👥',
                category: self::CATEGORY_TEAM,
                zone: self::ZONE_UTILITY,
                span: 1,
                defaultvisible: true,
                actions: self::detail_actions()
            ),
        ];
    }

    /**
     * Returns the stable keys for all registered Cards.
     *
     * @return string[]
     */
    public static function keys(): array {
        return array_keys(self::definitions());
    }

    /**
     * Returns the Cards registered in one layout zone.
     *
     * @return array<string, array>
     */
    public static function for_zone(string $zone): array {
        return array_filter(
            self::definitions(),
            static fn(array $definition): bool =>
                $definition['zone'] === $zone
        );
    }

    /**
     * Returns the Cards registered in one category.
     *
     * @return array<string, array>
     */
    public static function for_category(
        string $category
    ): array {
        return array_filter(
            self::definitions(),
            static fn(array $definition): bool =>
                $definition['category'] === $category
        );
    }

    /**
     * Returns the default ordering for every layout zone.
     *
     * @return array<string, string[]>
     */
    public static function default_order(): array {
        $order = [
            self::ZONE_HERO => [],
            self::ZONE_UTILITY => [],
            self::ZONE_FOCUS => [],
            self::ZONE_OPERATIONS => [],
            self::ZONE_OPERATIONS_SIDE => [],
            self::ZONE_INSIGHTS => [],
            self::ZONE_SECONDARY => [],
        ];

        foreach (
            self::definitions()
            as $key => $definition
        ) {
            $order[$definition['zone']][] = $key;
        }

        return $order;
    }

    /**
     * Returns whether a Card key exists.
     */
    public static function exists(string $key): bool {
        return isset(self::definitions()[$key]);
    }

    /**
     * Returns one Card definition.
     */
    public static function get(string $key): ?array {
        return self::definitions()[$key] ?? null;
    }

    /**
     * Returns a translated category label.
     */
    public static function category_label(
        string $category
    ): string {
        $stringkey = match ($category) {
            self::CATEGORY_OVERVIEW =>
                'dashboard_category_overview',

            self::CATEGORY_INTELLIGENCE =>
                'dashboard_category_intelligence',

            self::CATEGORY_OPERATIONS =>
                'dashboard_category_operations',

            self::CATEGORY_CUSTOMER_SUCCESS =>
                'dashboard_category_customer_success',

            self::CATEGORY_NAVIGATION_ACTIVITY =>
                'dashboard_category_navigation_activity',

            self::CATEGORY_TEAM =>
                'dashboard_category_team',

            self::CATEGORY_SYSTEM =>
                'dashboard_category_system',

            default =>
                'dashboard_category_other',
        };

        return get_string(
            $stringkey,
            'local_subscriptions'
        );
    }

    /**
     * Lazily renders one registered Card.
     *
     * This method must only be called after visibility has been resolved.
     */
    public static function render(
        string $key,
        string $period = DashboardPeriod::TODAY
    ): string {
        $period = DashboardPeriod::normalize($period);

        return DashboardCardProfiler::render(
            $key,
            static function () use (
                $key,
                $period
            ): string {
                return match ($key) {
                    'stats' =>
                        StatsCard::render($period),

                    'intelligence' =>
                        CrmIntelligenceCard::render(),

                    'assistant' =>
                        CrmAssistantCard::render(),

                    'inbox' =>
                        InboxOverviewCard::render(),

                    'work' =>
                        WorkItemOverviewCard::render(),

                    'customer_success' =>
                        (new CustomerSuccessPlanDashboardCard())
                            ->render(),

                    'issues' =>
                        AlertsCard::render(),

                    'priorities' =>
                        CrmDailyPrioritiesCard::render(),

                    'funnel' =>
                        CrmFunnelCard::render($period),

                    'trends' =>
                        CrmTrendsCard::render($period),

                    'intelligence_alerts' =>
                        CrmIntelligenceAlertsCard::render(),

                    'navigation' =>
                        DashboardSection::render(
                            [NavigationCard::class]
                        ),

                    'activity' =>
                        DashboardSection::render(
                            [ActivityCard::class],
                            'd-block'
                        ),

                    'team' =>
                        DashboardSection::render(
                            [TeamCard::class],
                            'd-block'
                        ),

                    default => '',
                };
            }
        );
    }

    /**
     * Returns the contextual action opening a detailed CRM view.
     *
     * @return WorkspaceItemActionDefinition[]
     */
    private static function detail_actions(): array {
        return [
            new WorkspaceItemActionDefinition(
                key: 'open_details',
                label: get_string(
                    'dashboard_workspace_action_open_details',
                    'local_subscriptions'
                ),
                icon: '↗'
            ),
        ];
    }

    /**
     * Creates a normalized Card definition.
     */
    private static function definition(
        string $key,
        string $labelkey,
        string $descriptionkey,
        string $icon,
        string $category,
        string $zone,
        int $span,
        string $type =
            WorkspaceItemDefinition::TYPE_CARD,
        bool $hideable = true,
        bool $movable = true,
        bool $defaultvisible = true,
        bool $periodaware = false,
        array $actions = []
    ): array {
        return [
            'key' => $key,
            'label' => get_string(
                $labelkey,
                'local_subscriptions'
            ),
            'description' => get_string(
                $descriptionkey,
                'local_subscriptions'
            ),
            'icon' => $icon,
            'category' => $category,
            'zone' => $zone,
            'span' => max(1, min(3, $span)),
            'type' => $type,
            'hideable' => $hideable,
            'movable' => $movable,
            'defaultvisible' => $defaultvisible,
            'periodaware' => $periodaware,
            'actions' => $actions,
        ];
    }
}