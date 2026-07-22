<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;

/**
 * Provides access to the CRM Inbox Workspace preferences.
 */
final class InboxWorkspaceService {

    private readonly WorkspaceDefinition $definition;

    private readonly WorkspacePreferenceService $preferences;

    public function __construct(
        ?WorkspaceDefinition $definition = null
    ) {
        $this->definition =
            $definition
            ?? InboxWorkspaceFactory::
                create_for_preferences();

        $this->preferences =
            new WorkspacePreferenceService(
                $this->definition
            );
    }

    /**
     * Returns the current Workspace definition.
     */
    public function definition(): WorkspaceDefinition {
        return $this->definition;
    }

    /**
     * Loads one user's normalized Inbox layout.
     */
    public function load(
        ?int $userid = null
    ): WorkspaceLayout {
        return $this->preferences->load(
            $userid
        );
    }

    /**
     * Returns the default Inbox layout.
     */
    public function defaults(): WorkspaceLayout {
        return $this->preferences->defaults();
    }

    /**
     * Resets one user's Inbox layout.
     */
    public function reset(
        ?int $userid = null
    ): WorkspaceLayout {
        return $this->preferences->reset(
            $userid
        );
    }
}