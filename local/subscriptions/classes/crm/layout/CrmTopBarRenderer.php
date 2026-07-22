<?php

namespace local_subscriptions\crm\layout;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use html_writer;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders the shared CRM top bar.
 */
final class CrmTopBarRenderer {

    /**
     * Renders the CRM top bar.
     *
     * @param context|null $context Current page context.
     * @return string
     */
    public function render(
        ?context $context = null
    ): string {
        global $OUTPUT, $USER;

        $context ??= context_system::instance();

        $out = html_writer::start_tag(
            'header',
            [
                'class' => 'crm-app-topbar',
            ]
        );

        $out .= html_writer::start_div(
            'crm-app-topbar-brand'
        );

        $brandcontent = html_writer::empty_tag(
            'img',
            [
                'src' => subscription_config::
                    crm_brand_logo_url()
                        ->out(false),
                'alt' => '',
                'class' => 'crm-app-topbar-brand-logo',
                'aria-hidden' => 'true',
                'loading' => 'eager',
                'decoding' => 'async',
            ]
        );

        $brandcontent .= html_writer::span(
            'CampusFR',
            'crm-app-topbar-brand-fallback'
        );

        $brandcontent .= html_writer::span(
            get_string(
                'crm_topbar_brand_suffix',
                'local_subscriptions'
            ),
            'crm-app-topbar-brand-secondary'
        );

        $out .= html_writer::link(
            new moodle_url(
                subscription_config::admin_dashboard_page()
            ),
            $brandcontent,
            [
                'class' => 'crm-app-topbar-brand-link',
                'aria-label' => get_string(
                    'crm_topbar_dashboard_link',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'crm-app-topbar-actions'
        );

        $out .= $this->render_navigation_toggle();

        $out .= $this->render_command_center_toggle();

        if (
            has_capability(
                'moodle/site:config',
                $context
            )
        ) {
            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::moodle_admin_page()
                ),
                html_writer::span(
                    '⚙',
                    'crm-app-topbar-action-icon',
                    [
                        'aria-hidden' => 'true',
                    ]
                ) .
                html_writer::span(
                    get_string(
                        'crm_topbar_moodle_admin',
                        'local_subscriptions'
                    ),
                    'crm-app-topbar-action-label'
                ),
                [
                    'class' =>
                        'crm-app-topbar-action ' .
                        'crm-app-topbar-admin-link',
                ]
            );
        }

        $out .= $this->render_language_menu();

        if (
            isloggedin() &&
            !isguestuser()
        ) {
            $out .= $this->render_user_menu();
        }

        $out .= html_writer::end_div();

        $out .= html_writer::end_tag('header');

        return $out;
    }

    /**
     * Renders the topbar Command Center shortcut.
     *
     * The CRM shell JavaScript forwards the click to the actual Command Center
     * trigger rendered elsewhere in the page.
     *
     * @return string
     */
    private function render_command_center_toggle(): string {
        $content = html_writer::span(
            '⌕',
            'crm-app-topbar-action-icon',
            [
                'aria-hidden' =>
                    'true',
            ]
        );

        $content .= html_writer::span(
            get_string(
                'crm_command_center_short_label',
                'local_subscriptions'
            ),
            'crm-app-topbar-action-label'
        );

        $content .= html_writer::span(
            '⌘ K',
            'crm-app-command-shortcut',
            [
                'aria-hidden' =>
                    'true',
            ]
        );

        return html_writer::tag(
            'button',
            $content,
            [
                'type' =>
                    'button',

                'class' =>
                    'crm-app-topbar-action ' .
                    'crm-app-command-toggle',

                'data-crm-command-open' =>
                    '1',

                'aria-label' =>
                    get_string(
                        'command_center_open',
                        'local_subscriptions'
                    ),
            ]
        );
    }

    /**
     * Renders the mobile CRM navigation toggle.
     *
     * @return string
     */
    private function render_navigation_toggle(): string {
        $content = html_writer::span(
            '☰',
            'crm-app-topbar-action-icon',
            [
                'aria-hidden' =>
                    'true',
            ]
        );

        $content .= html_writer::span(
            get_string(
                'crm_navigation_toggle',
                'local_subscriptions'
            ),
            'crm-app-topbar-action-label'
        );

        return html_writer::tag(
            'button',
            $content,
            [
                'type' =>
                    'button',

                'class' =>
                    'crm-app-topbar-action ' .
                    'crm-app-navigation-toggle',

                'data-crm-navigation-toggle' =>
                    '1',

                'aria-controls' =>
                    'crm-app-navigation-panel',

                'aria-expanded' =>
                    'false',

                'aria-label' =>
                    get_string(
                        'crm_navigation_open',
                        'local_subscriptions'
                    ),

                'data-open-label' =>
                    get_string(
                        'crm_navigation_open',
                        'local_subscriptions'
                    ),

                'data-close-label' =>
                    get_string(
                        'crm_navigation_close',
                        'local_subscriptions'
                    ),
            ]
        );
    }

    /**
     * Renders the autonomous CRM user menu.
     *
     * The embedded Edly layout only returns a flat list of user links from
     * $OUTPUT->user_menu(). We therefore render an explicit and accessible
     * CRM menu instead of depending on theme-specific markup.
     *
     * @return string
     */
    private function render_user_menu(): string {
        global $OUTPUT, $PAGE, $USER;

        $fullname = fullname($USER);

        $picture = $OUTPUT->user_picture(
            $USER,
            [
                'size' => 35,
                'link' => false,
                'alttext' => false,
                'class' => 'crm-app-topbar-user-picture',
            ]
        );

        $summarycontent =
            html_writer::span(
                $picture,
                'crm-app-topbar-user-avatar'
            ) .
            html_writer::span(
                s($fullname),
                'crm-app-topbar-user-name'
            ) .
            html_writer::span(
                '▾',
                'crm-app-topbar-user-chevron',
                [
                    'aria-hidden' => 'true',
                ]
            );

        $out = html_writer::start_tag(
            'details',
            [
                'class' => 'crm-app-topbar-user',
            ]
        );

        $out .= html_writer::tag(
            'summary',
            $summarycontent,
            [
                'class' => 'crm-app-topbar-user-toggle',
                'aria-label' => get_string(
                    'crm_topbar_user_menu',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::start_div(
            'crm-app-topbar-user-dropdown'
        );

        $out .= $this->render_user_menu_header(
            $picture,
            $fullname
        );

        $out .= html_writer::start_tag(
            'nav',
            [
                'class' => 'crm-app-topbar-user-links',
                'aria-label' => get_string(
                    'crm_topbar_user_navigation',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= $this->render_user_menu_link(
            new moodle_url(
                subscription_config::
                    campus_my_courses_page()
            ),
            '📚',
            get_string(
                'crm_topbar_my_courses',
                'local_subscriptions'
            )
        );

/*         $out .= $this->render_user_menu_link(
            new moodle_url(
                subscription_config::
                    moodle_grade_overview_page()
            ),
            '🎓',
            get_string(
                'crm_topbar_grades',
                'local_subscriptions'
            )
        );
 */

        $out .= $this->render_user_menu_link(
            new moodle_url(
                subscription_config::
                    moodle_calendar_page(),
                [
                    'view' => 'month',
                ]
            ),
            '📅',
            get_string(
                'crm_topbar_calendar',
                'local_subscriptions'
            )
        );

        $out .= $this->render_user_menu_link(
            new moodle_url(
                subscription_config::
                    moodle_user_preferences_page()
            ),
            '⚙',
            get_string(
                'crm_topbar_preferences',
                'local_subscriptions'
            )
        );

        if (
            has_capability(
                'moodle/role:switchroles',
                $PAGE->context
            )
        ) {
            $returnurl = $PAGE->url
                ->out_as_local_url(false);

            $out .= $this->render_user_menu_link(
                new moodle_url(
                    subscription_config::
                        moodle_switch_role_page(),
                    [
                        'id' => SITEID,
                        'switchrole' => -1,
                        'returnurl' => $returnurl,
                    ]
                ),
                '👤',
                get_string(
                    'crm_topbar_switch_role',
                    'local_subscriptions'
                )
            );
        }

        $out .= html_writer::end_tag('nav');

        $out .= html_writer::div(
            '',
            'crm-app-topbar-user-divider',
            [
                'aria-hidden' => 'true',
            ]
        );

        $out .= $this->render_user_menu_link(
            new moodle_url(
                subscription_config::
                    moodle_logout_page(),
                [
                    'sesskey' => sesskey(),
                ]
            ),
            '⏻',
            get_string(
                'crm_topbar_logout',
                'local_subscriptions'
            ),
            true
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('details');

        return $out;
    }

    /**
     * Renders the user identity block displayed at the top of the menu.
     *
     * @param string $picture Rendered user picture.
     * @param string $fullname User full name.
     * @return string
     */
    private function render_user_menu_header(
        string $picture,
        string $fullname
    ): string {
        $profileurl = new moodle_url(
            subscription_config::
                moodle_user_profile_page()
        );

        $content = html_writer::span(
            $picture,
            'crm-app-topbar-menu-avatar'
        );

        $content .= html_writer::start_div(
            'crm-app-topbar-menu-identity'
        );

        $content .= html_writer::div(
            s($fullname),
            'crm-app-topbar-menu-fullname'
        );

        $content .= html_writer::link(
            $profileurl,
            get_string(
                'crm_topbar_view_profile',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'crm-app-topbar-menu-profile-link',
            ]
        );

        $content .= html_writer::end_div();

        return html_writer::div(
            $content,
            'crm-app-topbar-user-header'
        );
    }

    /**
     * Renders one CRM user menu link.
     *
     * @param moodle_url $url Link URL.
     * @param string $icon Visual icon.
     * @param string $label Link label.
     * @param bool $danger Whether this is a destructive/session-ending action.
     * @return string
     */
    private function render_user_menu_link(
        moodle_url $url,
        string $icon,
        string $label,
        bool $danger = false
    ): string {
        $classes = 'crm-app-topbar-user-link';

        if ($danger) {
            $classes .=
                ' crm-app-topbar-user-link-danger';
        }

        $content = html_writer::span(
            $icon,
            'crm-app-topbar-user-link-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $content .= html_writer::span(
            s($label),
            'crm-app-topbar-user-link-label'
        );

        return html_writer::link(
            $url,
            $content,
            [
                'class' => $classes,
            ]
        );
    }

    /**
     * Renders the autonomous CRM language selector.
     *
     * The standard theme language menu displays technical language codes.
     * The CRM selector presents a compact flag and human-readable names.
     *
     * @return string
     */
    private function render_language_menu(): string {
        global $PAGE;

        $languages = get_string_manager()
            ->get_list_of_translations();

        if (count($languages) <= 1) {
            return '';
        }

        $currentlanguage = current_language();

        $togglecontent = html_writer::span(
            $this->get_language_flag($currentlanguage),
            'crm-app-topbar-language-flag',
            [
                'aria-hidden' => 'true',
            ]
        );

        $togglecontent .= html_writer::span(
            get_string(
                'crm_topbar_language',
                'local_subscriptions'
            ),
            'crm-app-topbar-language-label'
        );

        $togglecontent .= html_writer::span(
            '▾',
            'crm-app-topbar-language-chevron',
            [
                'aria-hidden' => 'true',
            ]
        );

        $out = html_writer::start_tag(
            'details',
            [
                'class' => 'crm-app-topbar-language',
            ]
        );

        $out .= html_writer::tag(
            'summary',
            $togglecontent,
            [
                'class' =>
                    'crm-app-topbar-language-toggle',
                'aria-label' => get_string(
                    'crm_topbar_language_menu',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::start_tag(
            'nav',
            [
                'class' =>
                    'crm-app-topbar-language-dropdown',
                'aria-label' => get_string(
                    'crm_topbar_language_navigation',
                    'local_subscriptions'
                ),
            ]
        );

        foreach ($languages as $languagecode => $languagename) {
            $languageurl = new moodle_url(
                $PAGE->url,
                [
                    'lang' => $languagecode,
                ]
            );

            $classes =
                'crm-app-topbar-language-option';

            if ($languagecode === $currentlanguage) {
                $classes .=
                    ' crm-app-topbar-language-option-active';
            }

            $content = html_writer::span(
                $this->get_language_flag(
                    $languagecode
                ),
                'crm-app-topbar-language-option-flag',
                [
                    'aria-hidden' => 'true',
                ]
            );

            $content .= html_writer::span(
                $this->get_language_display_name(
                    $languagecode,
                    $languagename
                ),
                'crm-app-topbar-language-option-name'
            );

            if ($languagecode === $currentlanguage) {
                $content .= html_writer::span(
                    '✓',
                    'crm-app-topbar-language-option-check',
                    [
                        'aria-hidden' => 'true',
                    ]
                );
            }

            $out .= html_writer::link(
                $languageurl,
                $content,
                [
                    'class' => $classes,
                    'lang' => $languagecode,
                    'hreflang' => $languagecode,
                    'aria-current' =>
                        $languagecode === $currentlanguage
                            ? 'true'
                            : null,
                ]
            );
        }

        $out .= html_writer::end_tag('nav');
        $out .= html_writer::end_tag('details');

        return $out;
    }

    /**
     * Returns a visual flag for a supported language.
     *
     * Only installed Moodle languages are displayed. This method merely
     * provides a visual representation for the languages that may be enabled.
     *
     * @param string $languagecode Moodle language code.
     * @return string
     */
    private function get_language_flag(
        string $languagecode
    ): string {
        $normalizedcode = strtolower(
            str_replace('-', '_', $languagecode)
        );

        $basecode = explode(
            '_',
            $normalizedcode
        )[0];

        return match ($normalizedcode) {
            'en_us' => '🇺🇸',
            'en_gb' => '🇬🇧',
            'pt_br' => '🇧🇷',
            'pt_pt' => '🇵🇹',
            'zh_cn' => '🇨🇳',
            'zh_tw' => '🇹🇼',

            default => match ($basecode) {
                'fr' => '🇫🇷',
                'en' => '🇬🇧',
                'ru' => '🇷🇺',
                'es' => '🇪🇸',
                'de' => '🇩🇪',
                'it' => '🇮🇹',
                'pt' => '🇵🇹',
                'zh' => '🇨🇳',
                'ja' => '🇯🇵',
                'ko' => '🇰🇷',
                'ar' => '🇸🇦',
                'tr' => '🇹🇷',
                'pl' => '🇵🇱',
                'uk' => '🇺🇦',
                'nl' => '🇳🇱',
                default => '🌐',
            },
        };
    }

    /**
     * Returns a clean language name without Moodle's technical code suffix.
     *
     * Known languages use stable native labels. Unknown future languages fall
     * back to Moodle's translated language name with any technical suffix
     * removed.
     *
     * @param string $languagecode Moodle language code.
     * @param string $fallbackname Name returned by Moodle.
     * @return string
     */
    private function get_language_display_name(
        string $languagecode,
        string $fallbackname
    ): string {
        $normalizedcode = strtolower(
            str_replace('-', '_', $languagecode)
        );

        $basecode = explode(
            '_',
            $normalizedcode
        )[0];

        $displayname = match ($normalizedcode) {
            'en_us' => 'English (US)',
            'en_gb' => 'English (UK)',
            'pt_br' => 'Português do Brasil',
            'pt_pt' => 'Português',
            'zh_cn' => '简体中文',
            'zh_tw' => '繁體中文',

            default => match ($basecode) {
                'fr' => 'Français',
                'en' => 'English',
                'ru' => 'Русский',
                'es' => 'Español',
                'de' => 'Deutsch',
                'it' => 'Italiano',
                'pt' => 'Português',
                'zh' => '中文',
                'ja' => '日本語',
                'ko' => '한국어',
                'ar' => 'العربية',
                'tr' => 'Türkçe',
                'pl' => 'Polski',
                'uk' => 'Українська',
                'nl' => 'Nederlands',
                default => null,
            },
        };

        if ($displayname !== null) {
            return $displayname;
        }

        $cleanname = preg_replace(
            '/\s*[\x{200E}\x{200F}\x{202A}-\x{202E}]?\s*' .
            '\([a-zA-Z0-9_-]+\)\s*$/u',
            '',
            $fallbackname
        );

        return trim(
            $cleanname ?? $fallbackname
        );
    }

}