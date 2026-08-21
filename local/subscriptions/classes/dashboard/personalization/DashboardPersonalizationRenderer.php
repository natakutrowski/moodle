<?php

namespace local_subscriptions\dashboard\personalization;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;
use local_subscriptions\crm\workspace\WorkspacePersonalizationOptions;
use local_subscriptions\crm\workspace\rendering\WorkspacePersonalizationRenderer;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\workspace\DashboardWorkspaceFactory;

/**
 * Dashboard adapter for the generic Workspace personalization renderer.
 */
final class DashboardPersonalizationRenderer {

    /**
     * Renders the Dashboard personalization controller.
     */
    public static function render(
        WorkspaceLayout|array $layout
    ): string {
        global $USER, $PAGE;

        $definition =
            DashboardWorkspaceFactory::create(
                (int)$USER->id,
                DashboardPeriod::TODAY,
                $PAGE->url->out_as_local_url(false)
            );

        $options =
            new WorkspacePersonalizationOptions(
                panelid:
                    'crm-dashboard-personalization-panel',

                titleid:
                    'crm-dashboard-personalization-title',

                openlabel: get_string(
                    'dashboard_personalization_open',
                    'local_subscriptions'
                ),

                title: get_string(
                    'dashboard_personalization_title',
                    'local_subscriptions'
                ),

                description: get_string(
                    'dashboard_personalization_description',
                    'local_subscriptions'
                ),

                closelabel: get_string(
                    'dashboard_personalization_close',
                    'local_subscriptions'
                ),

                resetlabel: get_string(
                    'dashboard_personalization_reset',
                    'local_subscriptions'
                ),

                saveerror: get_string(
                    'dashboard_personalization_save_error',
                    'local_subscriptions'
                ),

                resetconfirm: get_string(
                    'dashboard_personalization_reset_confirm',
                    'local_subscriptions'
                ),

                savemethod:
                    'local_subscriptions_save_workspace_layout',

                zonelabels:
                    self::zone_labels(),

                itempresentation:
                    self::item_presentations(
                        $definition
                    ),

                orderhint: get_string(
                    'dashboard_personalization_order_hint',
                    'local_subscriptions'
                ),

                visibilitylabeltemplate:
                    get_string(
                        'dashboard_personalization_visibility',
                        'local_subscriptions',
                        '{$a}'
                    ),

                includefixeditems: true,

                rootclass:
                    'crm-dashboard-personalization'
            );

        return WorkspacePersonalizationRenderer::render(
            $definition,
            $layout,
            $options
        );
    }

    /**
     * Returns translated Dashboard zone labels.
     *
     * @return array<string, string>
     */
    private static function zone_labels(): array {
        return [
            DashboardWorkspaceFactory::ZONE_ONBOARDING =>
                get_string(
                    'dashboard_personalization_zone_onboarding',
                    'local_subscriptions'
                ),

            DashboardWorkspaceFactory::ZONE_HERO =>
                get_string(
                    'dashboard_personalization_zone_hero',
                    'local_subscriptions'
                ),

            DashboardWorkspaceFactory::ZONE_FOCUS =>
                get_string(
                    'dashboard_n1211_focus_title',
                    'local_subscriptions'
                ),

            DashboardWorkspaceFactory::ZONE_OPERATIONS =>
                get_string(
                    'dashboard_n1211_operations_title',
                    'local_subscriptions'
                ),

            DashboardWorkspaceFactory::ZONE_INSIGHTS =>
                get_string(
                    'dashboard_n1211_insights_title',
                    'local_subscriptions'
                ),

            DashboardWorkspaceFactory::ZONE_SECONDARY =>
                get_string(
                    'dashboard_n1211_secondary_title',
                    'local_subscriptions'
                ),
        ];
    }

    /**
     * Builds generic presentation metadata for Dashboard items.
     *
     * @return array<string, array{
     *     classes: string[],
     *     attributes: array<string, string>,
     *     badges: array<int, array{
     *         label: string,
     *         kind: string
     *     }>
     * }>
     */
    private static function item_presentations(
        WorkspaceDefinition $definition
    ): array {
        $presentations = [];

        foreach (
            $definition->items()
            as $key => $item
        ) {
            $metadata =
                self::metadata($item);

            $badges = [
                [
                    'label' =>
                        DashboardCardRegistry::
                            category_label(
                                $metadata['category']
                            ),
                    'kind' => 'category',
                ],

                [
                    'label' =>
                        self::span_label(
                            $item->normalized_span()
                        ),
                    'kind' => 'span',
                ],

                [
                    'label' =>
                        self::type_label(
                            $item->type
                        ),
                    'kind' => 'type',
                ],
            ];

            if ($metadata['periodaware']) {
                $badges[] = [
                    'label' => get_string(
                        'dashboard_personalization_period_aware',
                        'local_subscriptions'
                    ),
                    'kind' => 'period',
                ];
            }

            $presentations[$key] = [
                'classes' => [
                    'crm-dashboard-personalization-card-category-' .
                        $metadata['category'],
                ],

                'attributes' => [
                    'data-dashboard-category' =>
                        $metadata['category'],

                    'data-dashboard-span' =>
                        (string)$item->normalized_span(),

                    'data-dashboard-period-aware' =>
                        $metadata['periodaware']
                            ? '1'
                            : '0',
                ],

                'badges' => $badges,
            ];
        }

        return $presentations;
    }

    /**
     * Returns Dashboard-specific metadata for one item.
     *
     * @return array{
     *     category: string,
     *     periodaware: bool
     * }
     */
    private static function metadata(
        WorkspaceItemDefinition $item
    ): array {
        $registered =
            DashboardCardRegistry::get(
                $item->key
            );

        if ($registered !== null) {
            return [
                'category' =>
                    $registered['category'],

                'periodaware' =>
                    (bool)$registered['periodaware'],
            ];
        }

        return [
            'category' =>
                DashboardCardRegistry::CATEGORY_SYSTEM,

            'periodaware' => false,
        ];
    }

    /**
     * Returns the translated grid width label.
     */
    private static function span_label(
        int $span
    ): string {
        $stringkey = match ($span) {
            1 =>
                'dashboard_personalization_width_compact',

            2 =>
                'dashboard_personalization_width_medium',

            default =>
                'dashboard_personalization_width_full',
        };

        return get_string(
            $stringkey,
            'local_subscriptions'
        );
    }

    /**
     * Returns the translated Workspace item type.
     */
    private static function type_label(
        string $type
    ): string {
        $stringkey = match ($type) {
            WorkspaceItemDefinition::TYPE_WIDGET =>
                'dashboard_personalization_type_widget',

            WorkspaceItemDefinition::TYPE_SYSTEM =>
                'dashboard_personalization_type_system',

            default =>
                'dashboard_personalization_type_card',
        };

        return get_string(
            $stringkey,
            'local_subscriptions'
        );
    }
}