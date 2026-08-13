<?php

namespace local_subscriptions\crm\user360\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;
use local_subscriptions\output\UserProfileRenderer;
use local_subscriptions\crm\user360\merge\User360MergeHistoryRenderer;
use local_subscriptions\crm\user360\guest\User360GuestCheckoutRecoveryRenderer;

/**
 * Builds the CRM User360 Workspace.
 */
final class User360WorkspaceFactory {

    public const WORKSPACE_KEY =
        'user360';

    public const PREFERENCE_KEY =
        'local_subscriptions_user360_workspace_layout';

    public const ZONE_HERO =
        'hero';

    public const ZONE_SUMMARY =
        'summary';

    public const ZONE_MAIN =
        'main';

    public const ZONE_SIDEBAR =
        'sidebar';

    public const ZONE_TIMELINE =
        'timeline';

    public const ITEM_HERO =
        'user360_hero';

    public const ITEM_STATS =
        'user360_stats';

    public const ITEM_QUICK_ACTIONS =
        'user360_quick_actions';

    public const ITEM_INTELLIGENCE =
        'user360_intelligence';

    public const ITEM_CUSTOMER_SUCCESS =
        'user360_customer_success';

    public const ITEM_COMMERCIAL =
        'user360_commercial';

    public const ITEM_MERGE_HISTORY =
        'user360_merge_history';

    public const ITEM_GUEST_CHECKOUT_RECOVERY =
        'user360_guest_checkout_recovery';

    public const ITEM_COURSES =
        'user360_courses';

    public const ITEM_ASSISTANT =
        'user360_assistant';

    public const ITEM_INBOX =
        'user360_inbox';

    public const ITEM_NOTES =
        'user360_notes';

    public const ITEM_WORK_ITEMS =
        'user360_work_items';

    public const ITEM_TIMELINE =
        'user360_timeline';

    public const ITEM_TIMELINE_SUMMARY =
        'user360_timeline_summary';

    /**
     * Creates the User360 Workspace definition.
     */
    public static function create(
        ?\stdClass $profile = null,
        ?bool $canviewinbox = null
    ): WorkspaceDefinition {
        $canviewinbox ??=
            Capabilities::can_view_inbox();

        $definition = new WorkspaceDefinition(
            self::WORKSPACE_KEY,
            self::PREFERENCE_KEY,
            [
                self::ZONE_HERO,
                self::ZONE_SUMMARY,
                self::ZONE_MAIN,
                self::ZONE_SIDEBAR,
                self::ZONE_TIMELINE,
            ]
        );

        self::register_hero(
            $definition,
            $profile
        );

        self::register_stats(
            $definition,
            $profile
        );

        self::register_timeline_summary(
            $definition,
            $profile
        );

        self::register_quick_actions(
            $definition,
            $profile
        );

        $iscommerceguest = !empty($profile?->iscommerceguest);

        if (!$iscommerceguest) {
            self::register_intelligence(
                $definition,
                $profile
            );

            self::register_customer_success(
                $definition,
                $profile
            );
        }

        self::register_commercial(
            $definition,
            $profile
        );

        self::register_guest_checkout_recovery(
            $definition,
            $profile
        );

        if (!$iscommerceguest) {
            self::register_merge_history(
                $definition,
                $profile
            );
        }

        if (!$iscommerceguest) {
            self::register_courses(
                $definition,
                $profile
            );

            self::register_notes(
                $definition,
                $profile
            );

            if ($canviewinbox) {
                self::register_inbox(
                    $definition,
                    $profile
                );
            }

            self::register_assistant(
                $definition,
                $profile
            );

            self::register_work_items(
                $definition,
                $profile
            );
        }

        self::register_timeline(
            $definition,
            $profile
        );

        return $definition;
    }

    /**
     * Creates the definition used by generic preference operations.
     *
     * The target user's Inbox capability is evaluated so inaccessible
     * items are never accepted by the generic preference endpoint.
     */
    public static function create_for_preferences(
        int $userid
    ): WorkspaceDefinition {
        $canviewinbox = has_capability(
            Capabilities::VIEW_INBOX,
            \context_system::instance(),
            $userid
        );

        return self::create(
            profile: null,
            canviewinbox: $canviewinbox
        );
    }

    /**
     * Registers the fixed identity Hero.
     */
    private static function register_hero(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_HERO,

                label: get_string(
                    'user360_workspace_hero',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_hero_description',
                    'local_subscriptions'
                ),

                icon: '👤',

                zone: self::ZONE_HERO,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_hero(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers the fixed User360 overview.
     */
    private static function register_stats(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_STATS,

                label: get_string(
                    'user360_workspace_stats',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_stats_description',
                    'local_subscriptions'
                ),

                icon: '📊',

                zone: self::ZONE_SUMMARY,

                span: 2,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_stats_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers the fixed quick actions panel.
     */
    private static function register_quick_actions(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_QUICK_ACTIONS,

                label: get_string(
                    'user360_workspace_quick_actions',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_quick_actions_description',
                    'local_subscriptions'
                ),

                icon: '⚡',

                zone: self::ZONE_SUMMARY,

                span: 1,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_quick_actions_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers CRM Intelligence.
     */
    private static function register_intelligence(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_INTELLIGENCE,

                label: get_string(
                    'user360_workspace_intelligence',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_intelligence_description',
                    'local_subscriptions'
                ),

                icon: '🧠',

                zone: self::ZONE_MAIN,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_intelligence_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers Customer Success plans.
     */
    private static function register_customer_success(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key:
                    self::ITEM_CUSTOMER_SUCCESS,

                label: get_string(
                    'user360_workspace_customer_success',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_customer_success_description',
                    'local_subscriptions'
                ),

                icon: '🎯',

                zone: self::ZONE_MAIN,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_customer_success_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers commercial activity.
     */
    private static function register_commercial(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_COMMERCIAL,

                label: get_string(
                    'user360_workspace_commercial',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_commercial_description',
                    'local_subscriptions'
                ),

                icon: '💳',

                zone: self::ZONE_MAIN,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_commercial_panel(
                            $profile
                        );
                }
            )
        );
    }

    private static function register_guest_checkout_recovery(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_GUEST_CHECKOUT_RECOVERY,
                label: get_string('user360_guest_checkout_recovery_title', 'local_subscriptions'),
                description: get_string('user360_guest_checkout_recovery_description', 'local_subscriptions'),
                icon: '↻',
                zone: self::ZONE_MAIN,
                span: 3,
                type: WorkspaceItemDefinition::TYPE_WIDGET,
                hideable: true,
                movable: true,
                defaultvisible: true,
                renderer: static function () use ($profile): string {
                    if ($profile === null || empty($profile->user->id)) {
                        return '';
                    }
                    $content = User360GuestCheckoutRecoveryRenderer::render((int)$profile->user->id);
                    if ($content === '') {
                        return '';
                    }
                    return UserProfileRenderer::section(
                        get_string('user360_guest_checkout_recovery_title', 'local_subscriptions'),
                        $content,
                        'crm-section-guest-checkout-recovery'
                    );
                }
            )
        );
    }

    /**
     * Registers auditable account merge history.
     */
    private static function register_merge_history(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_MERGE_HISTORY,
                label: get_string('user360_merge_history_title', 'local_subscriptions'),
                description: get_string('user360_merge_history_description', 'local_subscriptions'),
                icon: '🔗',
                zone: self::ZONE_MAIN,
                span: 3,
                type: WorkspaceItemDefinition::TYPE_WIDGET,
                hideable: true,
                movable: true,
                defaultvisible: true,
                renderer: static function () use ($profile): string {
                    if ($profile === null || empty($profile->user->id)) {
                        return '';
                    }
                    $content = User360MergeHistoryRenderer::render((int)$profile->user->id);
                    if ($content === '') {
                        return '';
                    }
                    return UserProfileRenderer::section(
                        get_string('user360_merge_history_title', 'local_subscriptions'),
                        $content,
                        'crm-section-merge-history'
                    );
                }
            )
        );
    }

    /**
     * Registers accessible courses.
     */
    private static function register_courses(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_COURSES,

                label: get_string(
                    'user360_workspace_courses',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_courses_description',
                    'local_subscriptions'
                ),

                icon: '🎓',

                zone: self::ZONE_MAIN,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_courses_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers the CRM Assistant.
     */
    private static function register_assistant(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_ASSISTANT,

                label: get_string(
                    'user360_workspace_assistant',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_assistant_description',
                    'local_subscriptions'
                ),

                icon: '✨',

                zone: self::ZONE_SIDEBAR,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_assistant_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers Notes.
     */
    private static function register_notes(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_NOTES,

                label: get_string(
                    'user360_workspace_notes',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_notes_description',
                    'local_subscriptions'
                ),

                icon: '📝',

                zone: self::ZONE_MAIN,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_notes_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers the Inbox summary.
     */
    private static function register_inbox(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_INBOX,

                label: get_string(
                    'user360_workspace_inbox',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_inbox_description',
                    'local_subscriptions'
                ),

                icon: '📥',

                zone: self::ZONE_SIDEBAR,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_inbox_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers Work Items.
     */
    private static function register_work_items(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_WORK_ITEMS,

                label: get_string(
                    'user360_workspace_work_items',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_work_items_description',
                    'local_subscriptions'
                ),

                icon: '✅',

                zone: self::ZONE_SIDEBAR,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_work_items_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers the fixed Timeline.
     */
    private static function register_timeline(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_TIMELINE,

                label: get_string(
                    'user360_workspace_timeline',
                    'local_subscriptions'
                ),

                description: get_string(
                    'user360_workspace_timeline_description',
                    'local_subscriptions'
                ),

                icon: '🕒',

                zone: self::ZONE_TIMELINE,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function () use (
                    $profile
                ): string {
                    if ($profile === null) {
                        return '';
                    }

                    return UserProfileRenderer::
                        render_timeline_panel(
                            $profile
                        );
                }
            )
        );
    }

    /**
     * Registers the compact Timeline summary.
     */
    private static function register_timeline_summary(
        WorkspaceDefinition $definition,
        ?\stdClass $profile
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key:
                    self::ITEM_TIMELINE_SUMMARY,

                label:
                    get_string(
                        'user360_workspace_timeline_summary',
                        'local_subscriptions'
                    ),

                description:
                    get_string(
                        'user360_workspace_timeline_summary_description',
                        'local_subscriptions'
                    ),

                icon:
                    '🕒',

                zone:
                    self::ZONE_SUMMARY,

                span:
                    1,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable:
                    false,

                movable:
                    false,

                defaultvisible:
                    true,

                renderer:
                    static function () use (
                        $profile
                    ): string {
                        if ($profile === null) {
                            return '';
                        }

                        return UserProfileRenderer::
                            render_timeline_summary_panel(
                                $profile
                            );
                    }
            )
        );
    }

}