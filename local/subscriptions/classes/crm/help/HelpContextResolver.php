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

        'local-subscriptions-admin-digital-purchases-index' =>
            HelpContext::DIGITAL_PURCHASES,

        'local-subscriptions-admin-automations-index' =>
            HelpContext::AUTOMATIONS,

        'local-subscriptions-admin-help-index' =>
            HelpContext::GENERAL,

        'local-subscriptions-admin-help-article' =>
            HelpContext::GENERAL,
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