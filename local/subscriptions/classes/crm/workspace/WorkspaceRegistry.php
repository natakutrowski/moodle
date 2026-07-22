<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\workspace\InboxThreadWorkspaceFactory;
use local_subscriptions\crm\inbox\workspace\InboxWorkspaceFactory;
use local_subscriptions\dashboard\workspace\DashboardWorkspaceFactory;
use local_subscriptions\crm\user360\workspace\User360WorkspaceFactory;

/**
 * Registry of CRM Workspaces allowed by generic infrastructure.
 *
 * This registry is intentionally explicit. A Workspace key supplied by
 * the browser can only resolve to one of the definitions listed here.
 */
final class WorkspaceRegistry {

    public const DASHBOARD =
        DashboardWorkspaceFactory::WORKSPACE_KEY;

    public const INBOX =
        InboxWorkspaceFactory::WORKSPACE_KEY;

    public const INBOX_THREAD =
        InboxThreadWorkspaceFactory::WORKSPACE_KEY;

    public const USER360 =
        User360WorkspaceFactory::WORKSPACE_KEY;

    /**
     * Returns all registered Workspace keys.
     *
     * @return string[]
     */
    public static function keys(): array {
        return [
            self::DASHBOARD,
            self::INBOX,
            self::INBOX_THREAD,
            self::USER360,
        ];
    }

    /**
     * Normalizes and validates one Workspace key.
     */
    public static function normalize_key(
        string $workspace
    ): string {
        $workspace = trim(
            \core_text::strtolower($workspace)
        );

        if (!self::has($workspace)) {
            throw new \invalid_parameter_exception(
                'Unknown CRM Workspace.'
            );
        }

        return $workspace;
    }

    /**
     * Reports whether a Workspace is registered.
     */
    public static function has(
        string $workspace
    ): bool {
        return in_array(
            $workspace,
            self::keys(),
            true
        );
    }

    /**
     * Returns the capability required to access one Workspace.
     */
    public static function capability(
        string $workspace
    ): string {
        $workspace = self::normalize_key(
            $workspace
        );

        return match ($workspace) {
            self::DASHBOARD =>
                Capabilities::VIEW_DASHBOARD,

            self::INBOX,
            self::INBOX_THREAD =>
                Capabilities::VIEW_INBOX,

            self::USER360 =>
                Capabilities::VIEW_USERS,

            default =>
                throw new \coding_exception(
                    'Registered Workspace has no capability.'
                ),
        };
    }

    /**
     * Creates a definition suitable for preference operations.
     *
     * Preference factories must not render business data. Their role is
     * only to expose zones, registered items and default visibility.
     */
    public static function definition_for_preferences(
        string $workspace,
        int $userid
    ): WorkspaceDefinition {
        $workspace = self::normalize_key(
            $workspace
        );

        return match ($workspace) {
            self::DASHBOARD =>
                DashboardWorkspaceFactory::
                    create_for_preferences(
                        $userid
                    ),

            self::INBOX =>
                InboxWorkspaceFactory::
                    create_for_preferences(),

            self::INBOX_THREAD =>
                InboxThreadWorkspaceFactory::
                    create_for_preferences(
                        $userid
                    ),

            self::USER360 =>
                User360WorkspaceFactory::
                    create_for_preferences(
                        $userid
                    ),

            default =>
                throw new \coding_exception(
                    'Registered Workspace has no preference factory.'
                ),
        };
    }

    /**
     * Returns the user preference key of one Workspace.
     */
    public static function preference_key(
        string $workspace
    ): string {
        $workspace = self::normalize_key(
            $workspace
        );

        return match ($workspace) {
            self::DASHBOARD =>
                DashboardWorkspaceFactory::PREFERENCE_KEY,

            self::INBOX =>
                InboxWorkspaceFactory::PREFERENCE_KEY,

            self::INBOX_THREAD =>
                InboxThreadWorkspaceFactory::PREFERENCE_KEY,

            self::USER360 =>
                User360WorkspaceFactory::PREFERENCE_KEY,

            default =>
                throw new \coding_exception(
                    'Registered Workspace has no preference key.'
                ),
        };
    }
}