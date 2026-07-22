<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

final class HelpContextResolver {

    private const PAGE_CONTEXTS = [
        'local-subscriptions-admin-dashboard' =>
            HelpContext::DASHBOARD,

        'local-subscriptions-admin-users-index' =>
            HelpContext::USER_EXPLORER,

        'local-subscriptions-admin-users-view' =>
            HelpContext::USER_PROFILE,

        'local-subscriptions-admin-users-email' =>
            HelpContext::EMAIL,

        'local-subscriptions-admin-users-email-preview' =>
            HelpContext::EMAIL,

        'local-subscriptions-admin-users-reset-password' =>
            HelpContext::USER_PROFILE,

        'local-subscriptions-admin-digital-purchases-index' =>
            HelpContext::DIGITAL_PURCHASES,

        'local-subscriptions-admin-automations-index' =>
            HelpContext::AUTOMATIONS,

        'local-subscriptions-admin-inbox-index' =>
            HelpContext::INBOX,

        'local-subscriptions-admin-inbox-thread' =>
            HelpContext::INBOX,

        'local-subscriptions-admin-inbox-reply' =>
            HelpContext::INBOX,

        'local-subscriptions-admin-inbox-diagnostics' =>
            HelpContext::INBOX_DIAGNOSTICS,

        'local-subscriptions-admin-inbox-ai-diagnostics' =>
            HelpContext::INBOX_AI,

        'local-subscriptions-admin-work-index' =>
            HelpContext::WORK_ITEMS,

        'local-subscriptions-admin-work-view' =>
            HelpContext::WORK_ITEMS,

        'local-subscriptions-admin-work-create' =>
            HelpContext::WORK_ITEMS,

        'local-subscriptions-admin-work-teams' =>
            HelpContext::WORK_ITEMS,

        'local-subscriptions-admin-assistant-index' =>
            HelpContext::ASSISTANT,

        'local-subscriptions-admin-assistant-plan' =>
            HelpContext::ASSISTANT,

        'local-subscriptions-admin-assistant-work-item' =>
            HelpContext::ASSISTANT,

        'local-subscriptions-admin-help-index' =>
            HelpContext::HELP_CENTER,

        'local-subscriptions-admin-help-article' =>
            HelpContext::HELP_CENTER,

        'local-subscriptions-admin-help-guide' =>
            HelpContext::HELP_CENTER,

        'local-subscriptions-admin-help-diagnostics' =>
            HelpContext::HELP_CENTER,

        'local-subscriptions-admin-tools-index' =>
            HelpContext::ADMIN_TOOLS,

        'local-subscriptions-admin-tools-history' =>
            HelpContext::ADMIN_TOOLS,

        'local-subscriptions-admin-tools-action' =>
            HelpContext::ADMIN_TOOLS,
    ];

    public static function from_page_id(
        string $pageid
    ): string {
        return self::PAGE_CONTEXTS[$pageid]
            ?? HelpContext::GENERAL;
    }

    public static function current(): string {
        global $PAGE;

        return self::from_page_id(
            (string)$PAGE->pagetype
        );
    }
}