<?php

namespace local_subscriptions\crm\layout;

defined('MOODLE_INTERNAL') || die();

use context;
use local_subscriptions\subscription_config;
use moodle_page;
use moodle_url;

/**
 * Centralises the common Moodle page configuration used by CRM pages.
 */
final class CrmPageConfigurator {

    /**
     * Moodle layout used by autonomous CRM pages.
     */
    private const PAGE_LAYOUT = 'embedded';

    /**
     * Shared body class applied to every CRM application page.
     */
    private const WORKSPACE_BODY_CLASS =
        'local-subscriptions-crm-workspace';

    /**
     * Body class marking a page as using the autonomous CRM shell.
     */
    private const AUTONOMOUS_BODY_CLASS =
        'local-subscriptions-crm-autonomous';

    /**
     * Configures a CRM application page.
     *
     * This method must be called before $OUTPUT->header().
     *
     * Existing callers may still pass a single body class.
     *
     * @param moodle_page $page Moodle page instance.
     * @param context $context Page context.
     * @param moodle_url $url Current page URL.
     * @param string $title Page title and heading.
     * @param string|string[] $bodyclasses Page-specific body classes.
     */
    public static function configure(
        moodle_page $page,
        context $context,
        moodle_url $url,
        string $title,
        string|array $bodyclasses
    ): void {
        $bodyclasses =
            self::normalise_body_classes(
                $bodyclasses
            );

        $page->set_context($context);
        $page->set_url($url);
        $page->set_pagelayout(
            self::PAGE_LAYOUT
        );
        $page->set_title($title);
        $page->set_heading($title);

        $page->add_body_class(
            self::WORKSPACE_BODY_CLASS
        );

        $page->add_body_class(
            self::AUTONOMOUS_BODY_CLASS
        );

        foreach ($bodyclasses as $bodyclass) {
            $page->add_body_class(
                $bodyclass
            );
        }

        $page->requires->css(
            new moodle_url(
                subscription_config::
                    plugin_stylesheet_page()
            )
        );

        $faviconurl =
            subscription_config::
                crm_favicon_url();

        $page->requires->js_call_amd(
            'local_subscriptions/crm_shell',
            'init',
            [
                $faviconurl !== null
                    ? $faviconurl->out(false)
                    : '',
            ]
        );

        $page->requires->js_call_amd(
            'local_subscriptions/command_center',
            'init'
        );
    }

    /**
     * Normalises and validates page-specific body classes.
     *
     * @param string|string[] $bodyclasses
     * @return string[]
     */
    private static function normalise_body_classes(
        string|array $bodyclasses
    ): array {
        if (is_string($bodyclasses)) {
            $bodyclasses = [
                $bodyclasses,
            ];
        }

        $normalised = [];

        foreach ($bodyclasses as $bodyclass) {
            if (!is_string($bodyclass)) {
                throw new \coding_exception(
                    'CRM page body classes must be strings.'
                );
            }

            $bodyclass = trim($bodyclass);

            self::validate_body_class(
                $bodyclass
            );

            $normalised[$bodyclass] =
                $bodyclass;
        }

        if ($normalised === []) {
            throw new \coding_exception(
                'At least one CRM page body class is required.'
            );
        }

        return array_values(
            $normalised
        );
    }

    /**
     * Prevents invalid or composite body classes from being passed.
     *
     * @param string $bodyclass
     */
    private static function validate_body_class(
        string $bodyclass
    ): void {
        if (
            $bodyclass === '' ||
            !preg_match(
                '/^[a-z][a-z0-9_-]*$/',
                $bodyclass
            )
        ) {
            throw new \coding_exception(
                'Invalid CRM page body class: ' .
                $bodyclass
            );
        }
    }
}