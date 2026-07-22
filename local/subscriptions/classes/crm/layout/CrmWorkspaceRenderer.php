<?php

namespace local_subscriptions\crm\layout;

defined('MOODLE_INTERNAL') || die();

use context;
use html_writer;
use local_subscriptions\commandcenter\CommandCenterRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmNavigationRenderer;

/**
 * Shared shell for CRM application pages.
 */
final class CrmWorkspaceRenderer {

    /**
     * Stable shell identifier.
     */
    private const SHELL_ID =
        'local-subscriptions-crm-shell';

    /**
     * Stable main content identifier.
     */
    private const MAIN_ID =
        'local-subscriptions-crm-main';

    /**
     * Class used by the CRM skip link.
     */
    private const SKIP_LINK_CLASS =
        'crm-app-skip-link';

    /**
     * Opens the CRM application shell and its main content area.
     *
     * The caller must later call self::end().
     *
     * @param string $activekey Active CRM navigation key.
     * @param context|null $context Current page context.
     * @return string
     */
    public static function start(
        string $activekey,
        ?context $context = null
    ): string {
        if (
            !CrmNavigationKeys::is_valid(
                $activekey
            )
        ) {
            throw new \coding_exception(
                'Invalid CRM workspace active key: ' .
                $activekey
            );
        }

        $out = html_writer::start_tag(
            'div',
            [
                'id' =>
                    self::SHELL_ID,

                'class' =>
                    'local-subscriptions-crm-workspace-shell ' .
                    'crm-app-shell',

                'data-crm-shell' =>
                    '1',

                'data-crm-active-navigation' =>
                    $activekey,
            ]
        );

        $out .= html_writer::link(
            '#' . self::MAIN_ID,
            get_string(
                'crm_skip_to_content',
                'local_subscriptions'
            ),
            [
                'class' =>
                    self::SKIP_LINK_CLASS,
            ]
        );

        $out .= (
            new CrmTopBarRenderer()
        )->render($context);

        $out .= (
            new CrmNavigationRenderer()
        )->render(
            $activekey,
            $context
        );

        /*
        * A single global Command Center instance is hosted by the CRM shell.
        *
        * Its large historical trigger is omitted because the TopBar now provides
        * the visible entry point on every CRM page.
        */
        $out .= CommandCenterRenderer::render(
            false
        );

        $out .= html_writer::start_div(
            'crm-app-content',
            [
                'data-crm-content' =>
                    '1',
            ]
        );

        $out .= html_writer::start_tag(
            'main',
            [
                'id' =>
                    self::MAIN_ID,

                'class' =>
                    'crm-app-main',

                'tabindex' =>
                    '-1',
            ]
        );

        return $out;
    }

    /**
     * Closes the main content area and the CRM application shell.
     *
     * @return string
     */
    public static function end(): string {
        return
            html_writer::end_tag('main') .
            html_writer::end_div() .
            html_writer::end_tag('div');
    }
}