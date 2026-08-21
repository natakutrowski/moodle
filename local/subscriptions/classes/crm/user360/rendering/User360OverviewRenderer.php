<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;

/**
 * N11.3A presentation shell for the User360 overview.
 *
 * Keeps business data in the existing UserProfileViewModel and only
 * reorganises presentation. The lower detailed Workspace remains available
 * during the N11.3B/C/D migration.
 */
final class User360OverviewRenderer {

    public static function display_name(\stdClass $profile): string {
        $user = $profile->user ?? (object)[];

        if (empty($profile->iscommerceguest)) {
            // fullname() requires the complete Moodle name-field contract.
            foreach ([
                'firstnamephonetic',
                'lastnamephonetic',
                'middlename',
                'alternatename',
            ] as $field) {
                if (!property_exists($user, $field)) {
                    $user->{$field} = '';
                }
            }

            $name = trim(fullname($user));
            if ($name !== '') {
                return $name;
            }
        }

        $name = trim(
            trim((string)($user->firstname ?? ''))
            . ' '
            . trim((string)($user->lastname ?? ''))
        );

        if ($name !== '') {
            return $name;
        }

        $email = trim((string)($user->email ?? ''));
        return $email !== '' ? $email : get_string('unknownuser');
    }

    public static function render_hero(\stdClass $profile): string {
        $user = $profile->user;
        $stats = $profile->stats;
        $name = self::display_name($profile);
        $email = trim((string)($user->email ?? ''));

        $initials = self::initials($name, $email);
        $avatar = html_writer::div(
            html_writer::span(s($initials), 'crm-user360-n113-avatar-initials')
            . html_writer::span('', 'crm-user360-n113-avatar-status'),
            'crm-user360-n113-avatar'
        );

        $identity = html_writer::tag(
            'h2',
            s($name),
            ['class' => 'crm-user360-n113-name']
        );

        if ($email !== '') {
            $identity .= html_writer::div(
                s($email),
                'crm-user360-n113-email'
            );
        }

        $badges = [];
        $badges[] = self::status_badge((string)($stats->crmstatus ?? ''));

        if (!empty($profile->iscommerceguest)) {
            $badges[] = html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-user-times',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string('crm_no_moodle_account', 'local_subscriptions')
                ),
                'crm-user360-n113-badge is-commerce-only'
            );
        } else {
            $badges[] = html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-graduation-cap',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string('crm_user360_n113_moodle_account', 'local_subscriptions')
                ),
                'crm-user360-n113-badge is-moodle'
            );

            if (!empty($user->suspended)) {
                $badges[] = html_writer::span(
                    get_string('crm_user_account_suspended', 'local_subscriptions'),
                    'crm-user360-n113-badge is-suspended'
                );
            }
        }

        $meta = [];

        if (!empty($profile->iscommerceguest)) {
            $meta[] = get_string(
                'crm_commerce_identity_legacy_guest',
                'local_subscriptions'
            );
        } else if (!empty($user->country)) {
            $meta[] = '🌍 ' . s((string)$user->country);
        }

        if (empty($profile->iscommerceguest) && !empty($user->id)) {
            $meta[] = '#'.(int)$user->id;
        }

        if (!empty($user->timecreated)) {
            $meta[] = get_string('crm_user360_n113_created', 'local_subscriptions')
                . ' ' . AdminFormatter::date((int)$user->timecreated);
        }

        if (!empty($stats->lastactivity)) {
            $meta[] = get_string('crm_last_activity', 'local_subscriptions')
                . ' ' . AdminFormatter::datetime((int)$stats->lastactivity);
        }

        $actions = self::hero_actions($profile);

        return html_writer::tag(
            'section',
            html_writer::div(
                $avatar
                . html_writer::div(
                    $identity
                    . html_writer::div(
                        implode('', $badges),
                        'crm-user360-n113-badges'
                    )
                    . html_writer::div(
                        implode(
                            html_writer::span('·', 'crm-user360-n113-meta-separator'),
                            array_map(
                                static fn(string $item): string =>
                                    html_writer::span($item, 'crm-user360-n113-meta-item'),
                                $meta
                            )
                        ),
                        'crm-user360-n113-meta'
                    ),
                    'crm-user360-n113-identity'
                ),
                'crm-user360-n113-hero-main'
            )
            . html_writer::div(
                $actions,
                'crm-user360-n113-hero-actions'
            ),
            [
                'class' => 'crm-user360-n113-hero',
                'aria-label' => get_string(
                    'user360_workspace_hero',
                    'local_subscriptions'
                ),
            ]
        );
    }

    public static function render_kpis(\stdClass $profile): string {
        $stats = $profile->stats;
        $score = $profile->intelligence->leadscore ?? null;

        $cards = [
            [
                'icon' => 'fa fa-shopping-cart',
                'label' => get_string('crm_commerce_orders', 'local_subscriptions'),
                'value' => (string)($stats->purchasecount ?? 0),
                'tone' => 'orders',
            ],
            [
                'icon' => 'fa fa-credit-card',
                'label' => get_string('crm_total_spent', 'local_subscriptions'),
                'value' => self::spent($stats),
                'tone' => 'spent',
            ],
            [
                'icon' => 'fa fa-calendar-check-o',
                'label' => get_string('crm_user360_n113_active_subscriptions', 'local_subscriptions'),
                'value' => (string)($stats->subscriptions ?? 0),
                'tone' => 'subscriptions',
            ],
            [
                'icon' => 'fa fa-key',
                'label' => get_string('crm_commerce_active_grants', 'local_subscriptions'),
                'value' => (string)($stats->activegrantcount ?? 0),
                'tone' => 'access',
            ],
            [
                'icon' => 'fa fa-line-chart',
                'label' => get_string('crm_user360_n113_score', 'local_subscriptions'),
                'value' => $score !== null
                    ? ((int)($score->commercial ?? 0)) . '/100'
                    : '—',
                'tone' => 'score',
            ],
            [
                'icon' => 'fa fa-exclamation-triangle',
                'label' => get_string('crm_user360_n113_risk', 'local_subscriptions'),
                'value' => $score !== null
                    ? ((int)($score->risk ?? 0)) . '/100'
                    : '—',
                'tone' => 'risk',
            ],
        ];

        $out = html_writer::start_div('crm-user360-n113-kpis');

        foreach ($cards as $card) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => $card['icon'],
                        'aria-hidden' => 'true',
                    ]),
                    'crm-user360-n113-kpi-icon'
                )
                . html_writer::div(
                    html_writer::span(
                        s($card['label']),
                        'crm-user360-n113-kpi-label'
                    )
                    . html_writer::tag(
                        'strong',
                        s($card['value']),
                        ['class' => 'crm-user360-n113-kpi-value']
                    ),
                    'crm-user360-n113-kpi-copy'
                ),
                'crm-user360-n113-kpi is-' . $card['tone']
            );
        }

        $out .= html_writer::end_div();
        return $out;
    }

    public static function render_navigation(\stdClass $profile): string {
        $items = [
            ['user360-overview', 'fa fa-th-large', 'crm_user360_n113_tab_overview'],
            ['user360-commerce', 'fa fa-shopping-cart', 'crm_user360_n113_tab_commerce'],
        ];

        if (empty($profile->iscommerceguest)) {
            $items[] = ['user360-relation', 'fa fa-users', 'crm_user360_n113_tab_relation'];
        }

        $items[] = ['user360-identities', 'fa fa-id-card-o', 'crm_user360_n113_tab_identities'];
        $items[] = ['crm-user-timeline', 'fa fa-clock-o', 'crm_user360_n113_tab_timeline'];

        $out = html_writer::start_tag(
            'nav',
            [
                'class' => 'crm-user360-n113-nav',
                'aria-label' => get_string(
                    'crm_user360_n113_nav_label',
                    'local_subscriptions'
                ),
            ]
        );

        foreach ($items as $index => [$target, $icon, $labelkey]) {
            $out .= html_writer::link(
                '#' . $target,
                html_writer::tag('i', '', [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string($labelkey, 'local_subscriptions')
                ),
                [
                    'class' => 'crm-user360-n113-nav-link'
                        . ($index === 0 ? ' is-active' : ''),
                ]
            );
        }

        $out .= html_writer::end_tag('nav');
        return $out;
    }

    public static function render_overview(\stdClass $profile): string {
        // N11.3G: use the desktop width deliberately.
        // Left column = current state and recent activity.
        // Right column = decision aids (priority + Intelligence).
        $main = self::current_situation($profile)
            . self::recent_activity($profile);

        $sidebar = self::priority_actions($profile);

        if (empty($profile->iscommerceguest)) {
            $sidebar .= self::intelligence_summary($profile);
        } else {
            $sidebar .= self::commerce_identity_summary($profile);
        }

        return html_writer::tag(
            'section',
            html_writer::div(
                html_writer::tag(
                    'main',
                    $main,
                    ['class' => 'crm-user360-n113-overview-main']
                )
                . html_writer::tag(
                    'aside',
                    $sidebar,
                    ['class' => 'crm-user360-n113-overview-sidebar']
                ),
                'crm-user360-n113-overview-grid crm-user360-n113g-overview-grid'
            ),
            [
                'id' => 'user360-overview',
                'class' => 'crm-user360-n113-overview',
            ]
        );
    }

    private static function current_situation(\stdClass $profile): string {
        $stats = $profile->stats;
        $rows = [];

        if (!empty($profile->iscommerceguest)) {
            $rows[] = self::situation_row(
                'fa fa-id-card-o',
                get_string('crm_user360_n113_identity', 'local_subscriptions'),
                get_string('crm_no_moodle_account', 'local_subscriptions'),
                get_string('crm_commerce_identity_legacy_guest', 'local_subscriptions')
            );
        } else {
            $rows[] = self::situation_row(
                'fa fa-graduation-cap',
                get_string('crm_accessible_courses', 'local_subscriptions'),
                (string)($stats->accessiblecourses ?? 0),
                get_string('crm_stats_courses_hint', 'local_subscriptions')
            );
        }

        $rows[] = self::situation_row(
            'fa fa-shopping-bag',
            get_string('crm_commerce_orders', 'local_subscriptions'),
            (string)($stats->purchasecount ?? 0),
            get_string('crm_commerce_orders_hint', 'local_subscriptions')
        );

        $rows[] = self::situation_row(
            'fa fa-clock-o',
            get_string('crm_last_activity', 'local_subscriptions'),
            !empty($stats->lastactivity)
                ? AdminFormatter::datetime((int)$stats->lastactivity)
                : '—',
            get_string('crm_stats_activity_hint', 'local_subscriptions')
        );

        return self::card(
            get_string('crm_user360_n113_current_situation', 'local_subscriptions'),
            implode('', $rows),
            'crm-user360-n113-current'
        );
    }

    private static function intelligence_summary(\stdClass $profile): string {
        if (empty($profile->intelligence) || empty($profile->intelligence->leadscore)) {
            return self::card(
                get_string('crm_section_intelligence', 'local_subscriptions'),
                html_writer::div(
                    get_string('crm_user360_n113_no_intelligence', 'local_subscriptions'),
                    'text-muted'
                ),
                'crm-user360-n113-intelligence'
            );
        }

        $score = $profile->intelligence->leadscore;
        $metrics = [
            [get_string('crm_intelligence_commercial_score', 'local_subscriptions'), (int)($score->commercial ?? 0)],
            [get_string('crm_intelligence_engagement_score', 'local_subscriptions'), (int)($score->engagement ?? 0)],
            [get_string('crm_intelligence_risk_score', 'local_subscriptions'), (int)($score->risk ?? 0)],
            [get_string('crm_intelligence_global_score', 'local_subscriptions'), (int)($score->global ?? 0)],
        ];

        $content = html_writer::start_div('crm-user360-n113-score-grid');
        foreach ($metrics as [$label, $value]) {
            $content .= html_writer::div(
                html_writer::span(s($label), 'crm-user360-n113-score-label')
                . html_writer::tag(
                    'strong',
                    $value . '/100',
                    ['class' => 'crm-user360-n113-score-value']
                ),
                'crm-user360-n113-score'
            );
        }
        $content .= html_writer::end_div();

        $content .= self::compact_badges(
            get_string('crm_intelligence_segments', 'local_subscriptions'),
            $profile->intelligence->segments ?? []
        );
        $content .= self::compact_badges(
            get_string('crm_intelligence_opportunities', 'local_subscriptions'),
            $profile->intelligence->opportunities ?? []
        );

        return self::card(
            get_string('crm_section_intelligence', 'local_subscriptions'),
            $content,
            'crm-user360-n113-intelligence'
        );
    }

    private static function commerce_identity_summary(\stdClass $profile): string {
        return self::card(
            get_string('crm_user360_n113_identity', 'local_subscriptions'),
            html_writer::div(
                html_writer::span('🛍️', 'crm-user360-n113-identity-icon')
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string('crm_commerce_identity_legacy_guest', 'local_subscriptions')
                    )
                    . html_writer::div(
                        get_string(
                            'crm_user360_n113_guest_identity_help',
                            'local_subscriptions'
                        ),
                        'text-muted small'
                    ),
                    'crm-user360-n113-identity-copy'
                ),
                'crm-user360-n113-identity-callout'
            ),
            'crm-user360-n113-guest-identity'
        );
    }

    private static function recent_activity(\stdClass $profile): string {
        $events = array_slice($profile->timeline ?? [], 0, 5);
        $content = '';

        if ($events === []) {
            $content = html_writer::div(
                get_string('crm_timeline_empty', 'local_subscriptions'),
                'text-muted'
            );
        } else {
            foreach ($events as $event) {
                $content .= html_writer::div(
                    html_writer::span(
                        '•',
                        'crm-user360-n113-activity-dot'
                    )
                    . html_writer::span(
                        !empty($event->timecreated)
                            ? AdminFormatter::datetime((int)$event->timecreated)
                            : '—',
                        'crm-user360-n113-activity-date'
                    )
                    . html_writer::tag(
                        'strong',
                        s((string)($event->title ?? '—')),
                        ['class' => 'crm-user360-n113-activity-title']
                    ),
                    'crm-user360-n113-activity-row'
                );
            }
        }

        $content .= html_writer::link(
            '#crm-user-timeline',
            get_string('crm_timeline_view_full', 'local_subscriptions') . ' →',
            ['class' => 'crm-user360-n113-card-link']
        );

        return self::card(
            get_string('crm_user360_n113_recent_activity', 'local_subscriptions'),
            $content,
            'crm-user360-n113-activity'
        );
    }

    private static function priority_actions(\stdClass $profile): string {
        $items = [];

        foreach (array_slice($profile->intelligence->recommendations ?? [], 0, 3) as $recommendation) {
            $key = trim((string)($recommendation->key ?? ''));
            $label = trim((string)($recommendation->label ?? ''));

            if ($key !== '') {
                $stringkey = 'crm_intelligence_recommendation_'
                    . clean_param($key, PARAM_ALPHANUMEXT);

                if (get_string_manager()->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )) {
                    $label = get_string(
                        $stringkey,
                        'local_subscriptions'
                    );
                }
            }

            if ($label === '') {
                $label = $key;
            }

            if ($label === '') {
                continue;
            }
            $items[] = html_writer::div(
                html_writer::span(
                    get_string('crm_user360_n113_priority_normal', 'local_subscriptions'),
                    'crm-user360-n113-priority-badge'
                )
                . html_writer::span(
                    s($label),
                    'crm-user360-n113-priority-label'
                ),
                'crm-user360-n113-priority-row'
            );
        }

        if ($items === []) {
            $items[] = html_writer::div(
                get_string('crm_user360_n113_no_priority_action', 'local_subscriptions'),
                'text-muted small'
            );
        }

        return self::card(
            get_string('crm_user360_n113_priority_actions', 'local_subscriptions'),
            implode('', $items),
            'crm-user360-n113-priorities'
        );
    }

    private static function inbox_summary(\stdClass $profile): string {
        if (
            !Capabilities::can_view_inbox()
            || empty($profile->inbox)
            || empty($profile->inbox->available)
        ) {
            return '';
        }

        $inbox = $profile->inbox;
        $metrics = [
            ['fa fa-comments-o', (int)($inbox->conversationcount ?? 0), get_string('crm_user_inbox_conversations', 'local_subscriptions')],
            ['fa fa-folder-open-o', (int)($inbox->openconversationcount ?? 0), get_string('crm_user_inbox_open_conversations', 'local_subscriptions')],
            ['fa fa-envelope-o', (int)($inbox->unreadcount ?? 0), get_string('crm_user_inbox_unread', 'local_subscriptions')],
        ];

        $content = html_writer::start_div('crm-user360-n113-mini-metrics');
        foreach ($metrics as [$icon, $value, $label]) {
            $content .= html_writer::div(
                html_writer::tag('i', '', ['class' => $icon, 'aria-hidden' => 'true'])
                . html_writer::tag('strong', (string)$value)
                . html_writer::span(s($label)),
                'crm-user360-n113-mini-metric'
            );
        }
        $content .= html_writer::end_div();

        $content .= html_writer::link(
            new \moodle_url(
                subscription_config::admin_inbox_page(),
                ['q' => (string)$profile->user->email]
            ),
            get_string('crm_user_inbox_open_all', 'local_subscriptions') . ' →',
            ['class' => 'crm-user360-n113-card-link']
        );

        return self::card(
            get_string('crm_user_inbox_section', 'local_subscriptions'),
            $content,
            'crm-user360-n113-inbox'
        );
    }

    private static function work_items_summary(\stdClass $profile): string {
        if (!empty($profile->iscommerceguest)) {
            return '';
        }

        return self::card(
            get_string('crm_work_user_section', 'local_subscriptions'),
            html_writer::div(
                get_string('crm_user360_n113_work_items_hint', 'local_subscriptions'),
                'text-muted small'
            ),
            'crm-user360-n113-work-items'
        );
    }

    private static function hero_actions(\stdClass $profile): string {
        $user = $profile->user;
        $email = trim((string)($user->email ?? ''));
        $items = [];

        if (
            empty($profile->iscommerceguest)
            && !empty($user->id)
            && Capabilities::can_manage_users()
        ) {
            $items[] = html_writer::link(
                new \moodle_url(
                    subscription_config::admin_user_email_page(),
                    ['id' => (int)$user->id]
                ),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-envelope-o',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string('crm_user360_n113_send_email', 'local_subscriptions')
                ),
                ['class' => 'btn btn-primary crm-user360-n113-action-primary']
            );
        }

        if ($email !== '') {
            $items[] = html_writer::link(
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/personal-offers/create.php',
                    ['prefillemail' => $email]
                ),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-tag',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string('crm_user360_n113_create_offer', 'local_subscriptions')
                ),
                ['class' => 'btn btn-outline-secondary']
            );
        }

        if (empty($profile->iscommerceguest) && !empty($user->id)) {
            $items[] = html_writer::link(
                new \moodle_url('/user/profile.php', ['id' => (int)$user->id]),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-user',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string('view_moodle_profile', 'local_subscriptions')
                ),
                ['class' => 'btn btn-outline-secondary']
            );
        }

        return implode('', $items);
    }

    private static function situation_row(
        string $icon,
        string $label,
        string $value,
        string $hint
    ): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]),
                'crm-user360-n113-situation-icon'
            )
            . html_writer::div(
                html_writer::span(s($label), 'crm-user360-n113-situation-label')
                . html_writer::tag(
                    'strong',
                    s($value),
                    ['class' => 'crm-user360-n113-situation-value']
                )
                . html_writer::span(s($hint), 'crm-user360-n113-situation-hint'),
                'crm-user360-n113-situation-copy'
            ),
            'crm-user360-n113-situation-row'
        );
    }

    private static function card(
        string $title,
        string $content,
        string $classes = ''
    ): string {
        return html_writer::tag(
            'section',
            html_writer::tag(
                'h3',
                s($title),
                ['class' => 'crm-user360-n113-card-title']
            )
            . html_writer::div(
                $content,
                'crm-user360-n113-card-body'
            ),
            ['class' => trim('crm-user360-n113-card ' . $classes)]
        );
    }

    private static function compact_badges(string $title, array $items): string {
        if ($items === []) {
            return '';
        }

        $badges = [];
        foreach ($items as $item) {
            $label = trim((string)($item->label ?? $item->key ?? ''));
            if ($label === '') {
                continue;
            }
            $badges[] = html_writer::span(
                s($label),
                'crm-user360-n113-compact-badge'
            );
        }

        if ($badges === []) {
            return '';
        }

        return html_writer::div(
            html_writer::span(
                s($title),
                'crm-user360-n113-badge-group-label'
            )
            . html_writer::div(
                implode('', $badges),
                'crm-user360-n113-badge-group-items'
            ),
            'crm-user360-n113-badge-group'
        );
    }

    private static function status_badge(string $status): string {
        $map = [
            'active_customer' => ['crm_status_active_customer', 'is-active'],
            'trial' => ['crm_status_trial', 'is-trial'],
            'former_customer' => ['crm_status_former_customer', 'is-former'],
            'suspended' => ['crm_status_suspended', 'is-suspended'],
            'lead' => ['crm_status_lead', 'is-lead'],
        ];

        [$key, $class] = $map[$status]
            ?? ['crm_status_unknown', 'is-neutral'];

        return html_writer::span(
            get_string($key, 'local_subscriptions'),
            'crm-user360-n113-badge ' . $class
        );
    }

    private static function spent(\stdClass $stats): string {
        $parts = [];

        if (!empty($stats->spent_eur)) {
            $parts[] = AdminFormatter::price((float)$stats->spent_eur, 'EUR');
        }
        if (!empty($stats->spent_rub)) {
            $parts[] = AdminFormatter::price((float)$stats->spent_rub, 'RUB');
        }

        return $parts ? implode(' · ', $parts) : '—';
    }

    private static function initials(string $name, string $email): string {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts && count($parts) >= 2) {
            return \core_text::strtoupper(
                \core_text::substr($parts[0], 0, 1)
                . \core_text::substr($parts[count($parts) - 1], 0, 1)
            );
        }
        if ($parts && count($parts) === 1) {
            return \core_text::strtoupper(
                \core_text::substr($parts[0], 0, 2)
            );
        }
        return $email !== ''
            ? \core_text::strtoupper(\core_text::substr($email, 0, 2))
            : '??';
    }
}
