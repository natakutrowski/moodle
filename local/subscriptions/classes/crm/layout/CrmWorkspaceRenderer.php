<?php

namespace local_subscriptions\crm\layout;

defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;
use local_subscriptions\crm\navigation\CrmNavigationRenderer;

/**
 * Shared shell for CRM application pages.
 */
final class CrmWorkspaceRenderer {

    /**
     * Opens the CRM application shell and its main content area.
     *
     * The caller must later call self::end().
     */
    public static function start(
        string $activekey,
        ?context $context = null
    ): string {
        $out = html_writer::start_div(
            'local-subscriptions-crm-workspace-shell ' .
            'crm-app-shell'
        );

        $out .= (new CrmNavigationRenderer())
            ->render(
                $activekey,
                $context
            );

        $out .= html_writer::start_div(
            'crm-app-main'
        );

        return $out;
    }

    /**
     * Closes the main content area and the CRM application shell.
     */
    public static function end(): string {
        return
            html_writer::end_div() .
            html_writer::end_div();
    }
}