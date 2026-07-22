<?php

namespace local_subscriptions\dashboard\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\help\onboarding\HelpOnboardingRenderer;
use local_subscriptions\crm\help\onboarding\HelpOnboardingService;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;
use local_subscriptions\dashboard\personalization\DashboardCardRegistry;
use local_subscriptions\dashboard\services\DashboardPeriod;

/**
 * Builds the Dashboard implementation of the CRM Workspace.
 */
final class DashboardWorkspaceFactory {

    public const WORKSPACE_KEY = 'dashboard';

    public const PREFERENCE_KEY =
        'local_subscriptions_dashboard_layout';

    public const ZONE_ONBOARDING = 'onboarding';
    public const ZONE_HERO = 'hero';
    public const ZONE_MAIN = 'main';
    public const ZONE_SIDE = 'side';

    public const ITEM_ONBOARDING = 'crm_onboarding';

    /**
     * Creates the Dashboard Workspace for one user.
     */
    public static function create(
        int $userid,
        string $period,
        string $returnurl
    ): WorkspaceDefinition {
        $period = DashboardPeriod::normalize($period);

        $onboardingstate =
            (new HelpOnboardingService())
                ->get_state($userid);

        $workspace = new WorkspaceDefinition(
            self::WORKSPACE_KEY,
            self::PREFERENCE_KEY,
            [
                self::ZONE_ONBOARDING,
                self::ZONE_HERO,
                self::ZONE_MAIN,
                self::ZONE_SIDE,
            ]
        );

        $workspace->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_ONBOARDING,
                label: get_string(
                    'crm_onboarding_title',
                    'local_subscriptions'
                ),
                description: get_string(
                    'crm_onboarding_description',
                    'local_subscriptions'
                ),
                icon: '🚀',
                zone: self::ZONE_ONBOARDING,
                span: 3,
                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,
                hideable: true,
                movable: false,
                defaultvisible:
                    !$onboardingstate->finished,
                renderer: static function () use (
                    $onboardingstate,
                    $returnurl
                ): string {
                    return HelpOnboardingRenderer::render(
                        $onboardingstate,
                        $returnurl,
                        true
                    );
                }
            )
        );

        foreach (
            DashboardCardRegistry::definitions()
            as $key => $definition
        ) {
            $workspace->register(
                self::create_card_item(
                    $key,
                    $definition,
                    $period
                )
            );
        }

        return $workspace;
    }

    /**
     * Creates one Workspace item from a Dashboard registry definition.
     */
    private static function create_card_item(
        string $key,
        array $definition,
        string $period
    ): WorkspaceItemDefinition {
        return new WorkspaceItemDefinition(
            key: $key,
            label: $definition['label'],
            description:
                $definition['description'],
            icon: $definition['icon'],
            zone: $definition['zone'],
            span: (int)$definition['span'],
            type: $definition['type'],
            hideable:
                (bool)$definition['hideable'],
            movable:
                (bool)$definition['movable'],
            defaultvisible:
                (bool)$definition['defaultvisible'],
            renderer: static function () use (
                $key,
                $period
            ): string {
                return DashboardCardRegistry::render(
                    $key,
                    $period
                );
            },
            actions: $definition['actions']
        );
    }

    /**
     * Creates the definition required by an AJAX save/reset.
     *
     * The current onboarding state is loaded so that reset restores
     * the correct default visibility.
     */
    public static function create_for_preferences(
        int $userid
    ): WorkspaceDefinition {
        return self::create(
            $userid,
            DashboardPeriod::TODAY,
            ''
        );
    }
}