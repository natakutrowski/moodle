<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\admin;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/** Renders safe desktop, mobile, text and source previews for transactional e-mails. */
final class CommerceMailPreviewRenderer {

    public const DESKTOP = 'desktop';
    public const MOBILE = 'mobile';
    public const TEXT = 'text';
    public const SOURCE = 'source';

    public const FONT_BRAND = 'brand';
    public const FONT_FALLBACK = 'fallback';

    /** @return string[] */
    public static function views(): array {
        return [self::DESKTOP, self::MOBILE, self::TEXT, self::SOURCE];
    }

    public static function normalise_view(string $view): string {
        return in_array($view, self::views(), true) ? $view : self::DESKTOP;
    }

    public static function normalise_font(string $font): string {
        return in_array($font, [self::FONT_BRAND, self::FONT_FALLBACK], true)
            ? $font
            : self::FONT_BRAND;
    }

    public static function render_font_navigation(moodle_url $baseurl, string $activefont): string {
        $activefont = self::normalise_font($activefont);
        $items = [];
        foreach ([
            self::FONT_BRAND => get_string('commerce_mail_preview_font_brand', 'local_subscriptions'),
            self::FONT_FALLBACK => get_string('commerce_mail_preview_font_fallback', 'local_subscriptions'),
        ] as $font => $label) {
            $url = new moodle_url($baseurl);
            $url->param('font', $font);
            $items[] = html_writer::link($url, s($label), [
                'class' => 'commerce-mail-preview-font__link' . ($font === $activefont ? ' is-active' : ''),
                'aria-current' => $font === $activefont ? 'true' : null,
            ]);
        }

        return html_writer::div(
            html_writer::span(
                s(get_string('commerce_mail_preview_font_label', 'local_subscriptions')),
                'commerce-mail-preview-font__label'
            ) . implode('', $items),
            'commerce-mail-preview-font',
            ['aria-label' => get_string('commerce_mail_preview_font_label', 'local_subscriptions')]
        );
    }

    public static function render_navigation(moodle_url $baseurl, string $activeview): string {
        $activeview = self::normalise_view($activeview);
        $labels = [
            self::DESKTOP => get_string('commerce_mail_preview_desktop', 'local_subscriptions'),
            self::MOBILE => get_string('commerce_mail_preview_mobile', 'local_subscriptions'),
            self::TEXT => get_string('commerce_mail_preview_text', 'local_subscriptions'),
            self::SOURCE => get_string('commerce_mail_preview_source', 'local_subscriptions'),
        ];
        $icons = [
            self::DESKTOP => 'fa-solid fa-desktop',
            self::MOBILE => 'fa-solid fa-mobile-screen-button',
            self::TEXT => 'fa-solid fa-align-left',
            self::SOURCE => 'fa-solid fa-code',
        ];

        $items = [];
        foreach (self::views() as $view) {
            $url = new moodle_url($baseurl);
            $url->param('view', $view);
            $content = html_writer::tag('i', '', [
                'class' => $icons[$view],
                'aria-hidden' => 'true',
            ]) . html_writer::span(s($labels[$view]));
            $items[] = html_writer::link($url, $content, [
                'class' => 'commerce-mail-preview-nav__link' . ($view === $activeview ? ' is-active' : ''),
                'aria-current' => $view === $activeview ? 'page' : null,
            ]);
        }

        return html_writer::div(implode('', $items), 'commerce-mail-preview-nav', [
            'aria-label' => get_string('commerce_mail_preview_modes', 'local_subscriptions'),
        ]);
    }

    public static function render(
        string $html,
        string $text,
        string $view,
        string $font = self::FONT_BRAND
    ): string {
        $view = self::normalise_view($view);
        $font = self::normalise_font($font);
        return match ($view) {
            self::MOBILE => self::render_iframe($html, true, $font),
            self::TEXT => html_writer::tag('pre', s($text), [
                'class' => 'commerce-mail-preview-text',
            ]),
            self::SOURCE => html_writer::tag('pre', s($html), [
                'class' => 'commerce-mail-preview-source',
            ]),
            default => self::render_iframe($html, false, $font),
        };
    }

    private static function render_iframe(string $html, bool $mobile, string $font): string {
        $html = self::inject_preview_font($html, $font);
        if ($mobile) {
            $html = self::inject_mobile_preview_css($html);
        }

        $title = $mobile
            ? get_string('commerce_mail_preview_mobile_title', 'local_subscriptions')
            : get_string('commerce_mail_preview_desktop_title', 'local_subscriptions');
        $frame = html_writer::tag('iframe', '', [
            'class' => 'commerce-mail-preview-frame' . ($mobile ? ' is-mobile' : ''),
            'title' => $title,
            'srcdoc' => $html,
            'sandbox' => 'allow-popups allow-popups-to-escape-sandbox',
            'loading' => 'lazy',
        ]);

        return html_writer::div(
            html_writer::div($frame, 'commerce-mail-preview-device' . ($mobile ? ' is-mobile' : ' is-desktop')),
            'commerce-mail-preview-stage'
        );
    }

    private static function inject_mobile_preview_css(string $html): string {
        if (stripos($html, '</head>') === false) {
            return $html;
        }

        $css = '<style>'
            . 'html,body{margin:0!important;padding:0!important;width:100%!important;max-width:100%!important;overflow-x:hidden!important;}'
            . 'body>table,table.ls-shell,.ls-shell{width:100%!important;max-width:100%!important;min-width:0!important;}'
            . '.ls-body{padding:18px 14px 6px!important;}'
            . '.ls-footer{padding-left:14px!important;padding-right:14px!important;}'
            . 'img{max-width:100%!important;}'
            . 'table{max-width:100%!important;}'
            . 'td{max-width:100%!important;}'
            . '</style>';

        return preg_replace('/<\\/head>/i', $css . '</head>', $html, 1) ?? $html;
    }

    private static function inject_preview_font(string $html, string $font): string {
        if (stripos($html, '</head>') === false) {
            return $html;
        }

        if ($font === self::FONT_FALLBACK) {
            $injection = '<style>'
                . 'html,body,table,td,div,p,a,h1,h2,h3,h4,h5,h6,span,strong,small{'
                . 'font-family:Arial,Helvetica,sans-serif!important;}'
                . '</style>';
        } else {
            // Preview-only webfont loading. This is deliberately not injected into
            // the real e-mail payload because e-mail clients support webfonts unevenly.
            $injection = '<link rel="preconnect" href="https://fonts.googleapis.com">'
                . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
                . '<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">'
                . '<style>'
                . 'html,body,table,td,div,p,a,h1,h2,h3,h4,h5,h6,span,strong,small{'
                . 'font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif!important;}'
                . '</style>';
        }

        return preg_replace('/<\\/head>/i', $injection . '</head>', $html, 1) ?? $html;
    }
}
