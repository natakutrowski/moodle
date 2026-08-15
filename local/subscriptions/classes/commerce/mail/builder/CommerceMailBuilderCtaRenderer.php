<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Email-safe CTA renderer shared by Mail Studio consumers.
 */
final class CommerceMailBuilderCtaRenderer {
    public function render_tags(string $html, string $url): string {
        if ($url === '') {
            return preg_replace(
                '/\{\{cta(?:\|[^}]*)?\}\}.*?\{\{\/cta\}\}/is',
                '',
                $html
            ) ?? $html;
        }

        return preg_replace_callback(
            '/\{\{cta(?:\|([a-z_]+))?\}\}(.*?)\{\{\/cta\}\}/is',
            function(array $match) use ($url): string {
                $variant = strtolower(trim((string)($match[1] ?? CommerceMailBuilder::CTA_GOLD)));
                if (!in_array($variant, CommerceMailBuilder::cta_variants(), true)) {
                    $variant = CommerceMailBuilder::CTA_GOLD;
                }
                $label = trim(html_entity_decode(
                    strip_tags((string)$match[2]),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ));
                return $label === '' ? '' : $this->button($url, $label, $variant);
            },
            $html
        ) ?? $html;
    }

    public function button(string $url, string $label, string $variant): string {
        $styles = [
            CommerceMailBuilder::CTA_GOLD => ['#dfbd59', '#fff7df', '#5f4514'],
            CommerceMailBuilder::CTA_CAMPUS_PINK => ['#f72585', '#f72585', '#ffffff'],
            CommerceMailBuilder::CTA_LEGACY_BLUE => ['#1281ad', '#1281ad', '#ffffff'],
        ];
        if (!isset($styles[$variant])) {
            $variant = CommerceMailBuilder::CTA_GOLD;
        }
        [$border, $background, $color] = $styles[$variant];
        $stars = $variant === CommerceMailBuilder::CTA_GOLD;

        return '<table role="presentation" cellspacing="0" cellpadding="0" border="0" '
            . 'align="center" style="margin:18px auto 20px;">'
            . '<tr><td align="center" style="padding:0;background:transparent;border:0;">'
            . '<a class="campusfr-campaign-cta campusfr-campaign-cta-' . $variant . '" href="'
            . s($url) . '" target="_blank" rel="noopener noreferrer" '
            . 'style="display:inline-block;padding:10px 22px;color:' . $color . ';'
            . 'text-decoration:none;font-family:Nunito,Segoe UI,Arial,Helvetica,sans-serif;'
            . 'font-size:14px;font-weight:800;line-height:18px;border:1px solid ' . $border . ';'
            . 'border-radius:11px;background:' . $background . ';white-space:nowrap;">'
            . ($stars ? '✦&nbsp; ' : '') . s($label) . ($stars ? ' &nbsp;✦' : '')
            . '</a></td></tr></table>';
    }

    public function hover_css(): string {
        return 'a.campusfr-campaign-cta.campusfr-campaign-cta-gold:hover{'
            . 'background:#f1dda0!important;background-color:#f1dda0!important;'
            . 'border-color:#cfa638!important;color:#5f4514!important;'
            . 'box-shadow:0 3px 8px rgba(95,69,20,.14)!important;}'
            . 'a.campusfr-campaign-cta-campus_pink:hover{background:#d91c73!important;'
            . 'border-color:#d91c73!important;color:#ffffff!important;}'
            . 'a.campusfr-campaign-cta-legacy_blue:hover{background:#0d7097!important;'
            . 'border-color:#0d7097!important;color:#ffffff!important;}'
            . 'a.campusfr-campaign-cta-secondary:hover{background:#f72585!important;'
            . 'border-color:#f72585!important;color:#ffffff!important;}';
    }
}
