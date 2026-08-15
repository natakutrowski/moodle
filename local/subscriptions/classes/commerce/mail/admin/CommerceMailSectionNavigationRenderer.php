<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\admin;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/** Secondary navigation inside the Commerce e-mail workspace. */
final class CommerceMailSectionNavigationRenderer {
    public const JOURNAL = 'journal';
    public const TEMPLATES = 'templates';
    public const CAMPAIGNS = 'campaigns';
    public const CONFIGURATION = 'configuration';

    public static function render(string $active): string {
        $items = [
            self::JOURNAL => [
                get_string('commerce_mail_nav_journal', 'local_subscriptions'),
                new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'),
                'fa-list-alt',
            ],
            self::TEMPLATES => [
                get_string('commerce_mail_nav_templates', 'local_subscriptions'),
                new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php'),
                'fa-file-text-o',
            ],
            self::CAMPAIGNS => [
                get_string('commerce_mail_nav_campaigns', 'local_subscriptions'),
                new moodle_url('/local/subscriptions/admin/commerce/mail/campaigns/index.php'),
                'fa-bullhorn',
            ],
            self::CONFIGURATION => [
                get_string('commerce_mail_nav_configuration', 'local_subscriptions'),
                new moodle_url('/local/subscriptions/admin/commerce/mail/configuration.php'),
                'fa-cog',
            ],
        ];

        $links = [];
        foreach ($items as $key => [$label, $url, $icon]) {
            $links[] = html_writer::link(
                $url,
                html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true'])
                    . html_writer::span($label),
                [
                    'class' => 'commerce-mail-section-nav__link' . ($key === $active ? ' is-active' : ''),
                    'aria-current' => $key === $active ? 'page' : null,
                ]
            );
        }

        return html_writer::tag(
            'nav',
            html_writer::div(implode('', $links), 'commerce-mail-section-nav__list'),
            [
                'class' => 'commerce-mail-section-nav',
                'aria-label' => get_string('commerce_mail_nav_label', 'local_subscriptions'),
            ]
        );
    }

    private function __construct() {}
}
