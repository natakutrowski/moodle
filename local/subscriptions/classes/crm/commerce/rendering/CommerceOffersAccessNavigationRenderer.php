<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/** N7.1 local navigation for the unified Offers & access workspace. */
final class CommerceOffersAccessNavigationRenderer {
    public const OVERVIEW = 'overview';
    public const OFFERS = 'offers';
    public const GRANTS = 'grants';
    public const CAMPAIGNS = 'campaigns';

    public static function render(string $active): string {
        $items = [
            self::OVERVIEW => ['commerce_offers_access_tab_overview', '/local/subscriptions/admin/commerce/offers-access/index.php', 'fa-home'],
            self::OFFERS => ['commerce_offers_access_tab_offers', '/local/subscriptions/admin/commerce/personal-offers/index.php', 'fa-tag'],
            self::GRANTS => ['commerce_offers_access_tab_grants', '/local/subscriptions/admin/commerce/grants/index.php', 'fa-key'],
            self::CAMPAIGNS => ['commerce_offers_access_tab_campaigns', '/local/subscriptions/admin/commerce/offers-access/campaigns.php', 'fa-bullseye'],
        ];
        $links = [];
        foreach ($items as $key => [$label, $url, $icon]) {
            $semanticclass = match ($key) {
                self::OFFERS => ' is-offer',
                self::GRANTS => ' is-grant',
                default => '',
            };
            $content = html_writer::tag('i', '', [
                'class' => 'fa ' . $icon . ' crm-offers-access-tab-icon',
                'aria-hidden' => 'true',
            ]) . html_writer::span(get_string($label, 'local_subscriptions'));
            $links[] = html_writer::link(new moodle_url($url), $content, [
                'class' => 'crm-offers-access-tab'
                    . $semanticclass
                    . ($key === self::OVERVIEW ? ' is-overview' : '')
                    . ($key === $active ? ' is-active' : ''),
                'aria-current' => $key === $active ? 'page' : null,
            ]);
        }
        return html_writer::tag('nav', implode('', $links), [
            'class' => 'crm-offers-access-tabs',
            'aria-label' => get_string('commerce_offers_access_title', 'local_subscriptions'),
        ]);
    }
}
