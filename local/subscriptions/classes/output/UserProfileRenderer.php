<?php

namespace local_subscriptions\output;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\url\UrlFactory;

use html_writer;
use html_table;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\support\SubsPresenter;
use local_subscriptions\support\DigitalPresenter;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\rendering\InboxValuePresentation;
use local_subscriptions\crm\work\rendering\UserWorkItemSection;
use local_subscriptions\crm\assistant\rendering\UserAssistantSection;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanUserSection;
use local_subscriptions\crm\user\UserProfileTimelineCategory;

use local_subscriptions\crm\user360\rendering\User360OverviewRenderer;
final class UserProfileRenderer {

    /**
     * Renders the fixed User360 identity Hero.
     *
     * This public entry point allows the Hero to be registered as an
     * independent Workspace item without duplicating its business
     * presentation logic.
     */
    public static function render_hero(
        \stdClass $profile
    ): string {
        $user = $profile->user;
        $stats = $profile->stats;

        $displayname = User360OverviewRenderer::display_name($profile);

        $identity = html_writer::tag('h2', s($displayname), [
            'class' => 'crm-hero-title mb-1',
        ]);

        $identity .= html_writer::div(
            s($user->email),
            'crm-hero-email text-muted'
        );

        $badges = self::crm_status_badge(
            $stats
        );

        if (
            !empty($profile->inbox) &&
            !empty($profile->inbox->available)
        ) {
            $badges .= ' ' .
                self::inbox_hero_badge(
                    $profile->inbox
                );
        }

        $identity .= html_writer::div(
            $badges,
            'mt-3'
        );

        if (!empty($profile->tags)) {
            $identity .= html_writer::div(
                self::tags($profile->tags),
                'mt-2'
            );
        }        

        if (empty($profile->iscommerceguest) && has_capability(Capabilities::VIEW_USERS, \context_system::instance())) {
            $identity .= self::tag_controls($profile);
        }

        $meta = [];

        if (!empty($profile->iscommerceguest)) {
            $meta[] = self::hero_meta_item(
                get_string('crm_commerce_identity_type', 'local_subscriptions'),
                get_string('crm_commerce_identity_legacy_guest', 'local_subscriptions')
            );
        } else {
            $meta[] = self::hero_meta_item(
                get_string('country'),
                s($user->country ?: '-')
            );
        }

        $meta[] = self::hero_meta_item(
            !empty($profile->iscommerceguest)
                ? get_string('crm_first_purchase', 'local_subscriptions')
                : get_string('timecreated'),
            !empty($user->timecreated) ? AdminFormatter::date((int)$user->timecreated) : '-'
        );

        if (empty($profile->iscommerceguest)) {
            $meta[] = self::hero_meta_item(
                get_string('lastaccess'),
                !empty($user->lastaccess) ? AdminFormatter::datetime((int)$user->lastaccess) : '-'
            );
        }

        $meta[] = self::hero_meta_item(
            get_string('crm_last_activity', 'local_subscriptions'),
            !empty($stats->lastactivity) ? AdminFormatter::datetime((int)$stats->lastactivity) : '-'
        );

        $links = !empty($profile->iscommerceguest)
            ? html_writer::span(
                get_string('crm_no_moodle_account', 'local_subscriptions'),
                'badge bg-light text-dark border'
            )
            : html_writer::link(
                UrlFactory::my_profile(['id' => (int)$user->id]),
                get_string('view_moodle_profile', 'local_subscriptions'),
                ['class' => 'btn btn-outline-primary btn-sm']
            );

        return html_writer::div(
            html_writer::div($identity, 'crm-hero-main') .
            html_writer::div(implode('', $meta), 'crm-hero-meta') .
            html_writer::div($links, 'crm-hero-links mt-3'),
            'crm-hero card card-body mb-4'
        );
    }

    /**
     * Renders the User360 Intelligence panel.
     */
    public static function render_intelligence_panel(
        \stdClass $profile
    ): string {
        return self::intelligence(
            $profile
        );
    }

    /**
     * Renders the User360 Customer Success panel.
     */
    public static function render_customer_success_panel(
        \stdClass $profile
    ): string {
        $content =
            (new CustomerSuccessPlanUserSection())
                ->render(
                    (int)$profile->user->id,
                    Capabilities::can_manage_users()
                );

        if ($content === '') {
            return '';
        }

        return self::section(
            get_string(
                'csplanusersection',
                'local_subscriptions'
            ),
            $content,
            'crm-section-customer-success-plans'
        );
    }

    /**
     * Renders the User360 Inbox panel.
     */
    public static function render_inbox_panel(
        \stdClass $profile
    ): string {
        return self::inbox(
            $profile
        );
    }

    /**
     * Renders the User360 Work Items panel.
     */
    public static function render_work_items_panel(
        \stdClass $profile
    ): string {
        $content =
            UserWorkItemSection::render(
                (int)$profile->user->id
            );

        if ($content === '') {
            return '';
        }

        return self::section(
            get_string(
                'crm_work_user_section',
                'local_subscriptions'
            ),
            $content,
            'crm-section-work-items'
        );
    }

    /**
     * Renders the User360 Notes panel.
     */
    public static function render_notes_panel(
        \stdClass $profile
    ): string {
        return self::section(
            get_string(
                'crm_section_notes',
                'local_subscriptions'
            ),
            self::notes(
                $profile->notes ?? []
            ),
            'crm-section-notes'
        );
    }

    /**
     * Renders the fixed User360 Timeline panel.
     */
    public static function render_timeline_panel(
        \stdClass $profile
    ): string {
        return self::section(
            get_string(
                'crm_section_timeline',
                'local_subscriptions'
            ),
            self::timeline_content(
                $profile
            ),
            'crm-section-timeline'
        );
    }    

    /**
     * N11.3G raw Timeline content adapter.
     *
     * The User360 Timeline domain owns its visual shell, so callers can reuse
     * the complete Timeline engine without nesting the legacy section card.
     */
    public static function render_timeline_content(
        \stdClass $profile
    ): string {
        return self::timeline_content($profile);
    }


    /**
     * N11.3C relation adapter preserving current CRM actions and note composer.
     */
    public static function render_relation_actions_content(
        \stdClass $profile
    ): string {
        return self::quick_actions($profile);
    }

    /**
     * Renders the User360 quick actions panel.
     */
    public static function render_quick_actions_panel(
        \stdClass $profile
    ): string {
        return self::section(
            get_string(
                'crm_section_quick_actions',
                'local_subscriptions'
            ),
            self::quick_actions($profile),
            'crm-section-actions'
        );
    }

    /**
     * Renders the User360 overview statistics.
     */
    public static function render_stats_panel(
        \stdClass $profile
    ): string {
        return html_writer::div(
            self::stats($profile),
            'crm-section-overview-panel'
        );
    }

    /**
     * N11.5D raw CRM Assistant recommendations adapter.
     */
    public static function render_assistant_recommendations_content(
        \stdClass $profile,
        int $recommendationlimit = 10
    ): string {
        return UserAssistantSection::render_recommendations(
            (int)$profile->user->id,
            $recommendationlimit
        );
    }

    /**
     * N11.5D raw CRM Assistant question panel adapter.
     */
    public static function render_assistant_conversation_content(
        \stdClass $profile
    ): string {
        return UserAssistantSection::render_conversation(
            (int)$profile->user->id
        );
    }

    /**
     * Renders the User360 CRM Assistant panel.
     */
    public static function render_assistant_panel(
        \stdClass $profile,
        int $recommendationlimit = 10
    ): string {
        $content = UserAssistantSection::render(
            (int)$profile->user->id,
            $recommendationlimit
        );

        if ($content === '') {
            return '';
        }

        return self::section(
            get_string(
                'crm_assistant_user_section',
                'local_subscriptions'
            ),
            $content,
            'crm-section-assistant'
        );
    }

    /**
     * Renders the complete commercial activity panel.
     */
    public static function render_commercial_panel(
        \stdClass $profile
    ): string {
        if (!empty($profile->iscommerceguest)) {
            $content = '';

            if (!empty($profile->commercepurchases)) {
                $content .= self::commercial_subsection(
                    get_string('crm_commerce_native_history', 'local_subscriptions'),
                    self::commerce_purchases_content($profile->commercepurchases),
                    'crm-commercial-native'
                );
            }

            $content .= self::commercial_subsection(
                get_string('crm_section_digital_purchases', 'local_subscriptions'),
                self::digital_purchases_content($profile->digitalpayments ?? []),
                'crm-commercial-digital'
            );

            return self::section(
                get_string('user360_workspace_commercial', 'local_subscriptions'),
                $content,
                'crm-section-commercial crm-section-commerce-guest'
            );
        }

        if (!empty($profile->commercepurchases)) {
            return self::section(
                get_string(
                    'user360_workspace_commercial',
                    'local_subscriptions'
                ),
                self::commerce_purchases_content($profile->commercepurchases),
                'crm-section-commercial crm-section-commercial-native'
            );
        }

        $subscriptions = self::commercial_subsection(
            get_string(
                'crm_section_subscriptions',
                'local_subscriptions'
            ),
            self::subscriptions_content(
                $profile->subscriptions ?? []
            ),
            'crm-commercial-subscriptions'
        );

        $digital = self::commercial_subsection(
            get_string(
                'crm_section_digital_purchases',
                'local_subscriptions'
            ),
            self::digital_purchases_content(
                $profile->digitalpayments ?? []
            ),
            'crm-commercial-digital'
        );

        return self::section(
            get_string(
                'user360_workspace_commercial',
                'local_subscriptions'
            ),
            html_writer::div(
                $subscriptions . $digital,
                'crm-user360-commercial-grid'
            ),
            'crm-section-commercial'
        );
    }

    /**
     * Renders the User360 courses panel.
     */
    public static function render_courses_panel(
        \stdClass $profile
    ): string {
        return self::section(
            get_string(
                'crm_section_courses',
                'local_subscriptions'
            ),
            self::courses_content(
                $profile->courses ?? []
            ),
            'crm-section-courses'
        );
    }

    /**
     * Renders Timeline groups for progressive AJAX loading.
     *
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     html: string
     * }>
     */
    public static function render_timeline_ajax_groups(
        array $items
    ): array {
        $groups =
            self::group_timeline_items(
                $items
            );

        $result = [];

        foreach ($groups as $key => $group) {
            $html = '';

            foreach ($group['items'] as $item) {
                $html .= self::timeline_item(
                    $item
                );
            }

            $result[] = [
                'key' => (string)$key,
                'label' => (string)$group['label'],
                'html' => $html,
            ];
        }

        return $result;
    }

    /**
     * Renders one subsection of the commercial activity panel.
     */
    private static function commercial_subsection(
        string $title,
        string $content,
        string $class
    ): string {
        return html_writer::tag(
            'section',
            html_writer::tag(
                'h4',
                $title,
                [
                    'class' =>
                        'crm-commercial-subsection-title h6 mb-3',
                ]
            ) .
            html_writer::div(
                $content,
                'crm-commercial-subsection-content'
            ),
            [
                'class' =>
                    'crm-commercial-subsection ' . $class,
            ]
        );
    }

    private static function hero_meta_item(string $label, string $value): string {
        return html_writer::div(
            html_writer::div($label, 'crm-hero-meta-label') .
            html_writer::div($value, 'crm-hero-meta-value'),
            'crm-hero-meta-item'
        );
    }

    private static function tag_controls(\stdClass $profile): string {
        $active = [];

        foreach (($profile->tags ?? []) as $tag) {
            $active[] = (string)$tag->tag;
        }

        $out = html_writer::start_div('crm-tag-controls mt-3');

        foreach (\local_subscriptions\crm\user\UserProfileTag::allowed_tags() as $tag) {
            $enabled = in_array($tag, $active, true);

            $url = new moodle_url(
                subscription_config::admin_user_toggle_tag_page(),
                [
                    'id' => $profile->user->id,
                    'tag' => $tag,
                    'action' => $enabled ? 'remove' : 'add',
                    'sesskey' => sesskey(),
                ]
            );

            $label = get_string('crm_tag_' . $tag, 'local_subscriptions');

            $out .= html_writer::link(
                $url,
                ($enabled ? '✓ ' : '+ ') . $label,
                [
                    'class' => 'btn btn-sm ' . ($enabled ? 'btn-primary' : 'btn-outline-secondary') . ' mr-1 mb-1',
                ]
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    public static function section(string $title, string $content, string $class = ''): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag('h3', $title, ['class' => 'crm-section-title mb-0']),
                'crm-section-header'
            ) .
            html_writer::div($content, 'crm-section-body'),
            trim('crm-section card card-body mb-4 ' . $class)
        );
    }

    private static function tags(array $tags): string {
        $out = '';

        foreach ($tags as $tag) {
            $name = (string)($tag->tag ?? '');
            $label = (string)($tag->label ?? $name);

            $out .= html_writer::span(
                s($label),
                'badge crm-user-tag crm-user-tag-' . s($name)
            ) . ' ';
        }

        return html_writer::div($out, 'crm-user-tags');
    }

    private static function stats(\stdClass $profile): string {
        $stats = $profile->stats;

        $cards = [
            [
                'icon' => '🟢',
                'label' => get_string('crm_status', 'local_subscriptions'),
                'value' => self::crm_status_badge($stats),
                'muted' => get_string('crm_stats_status_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '🧾',
                'label' => get_string('crm_commerce_orders', 'local_subscriptions'),
                'value' => $stats->purchasecount ?? (($stats->subscriptions ?? 0) + ($stats->digitalpayments ?? 0)),
                'muted' => get_string('crm_commerce_orders_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '🔑',
                'label' => get_string('crm_commerce_active_grants', 'local_subscriptions'),
                'value' => $stats->activegrantcount ?? 0,
                'muted' => get_string('crm_commerce_active_grants_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '🎓',
                'label' => get_string('crm_accessible_courses', 'local_subscriptions'),
                'value' => $stats->accessiblecourses ?? 0,
                'muted' => get_string('crm_stats_courses_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '💳',
                'label' => get_string('crm_total_spent', 'local_subscriptions'),
                'value' => self::stats_total_spent($stats),
                'muted' => get_string('crm_stats_spent_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '🕒',
                'label' => get_string('crm_last_activity', 'local_subscriptions'),
                'value' => !empty($stats->lastactivity)
                    ? AdminFormatter::datetime((int)$stats->lastactivity)
                    : '-',
                'muted' => get_string('crm_stats_activity_hint', 'local_subscriptions'),
            ],
        ];

        $out = html_writer::tag('h3', get_string('crm_section_overview', 'local_subscriptions'), [
            'class' => 'crm-section-title mb-3',
        ]);

        $out .= html_writer::start_div('row mb-4 crm-stats-grid crm-section-overview');

        foreach ($cards as $card) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::div(
                        html_writer::span($card['icon'], 'crm-stat-icon') .
                        html_writer::tag('div', $card['value'], ['class' => 'crm-stat-number']) .
                        html_writer::tag('div', $card['label'], ['class' => 'crm-stat-label']) .
                        html_writer::tag('div', $card['muted'], ['class' => 'text-muted small mt-1']),
                        'card card-body crm-stat-card h-100'
                    )
                ),
                'col-md-4 col-lg mb-3'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function crm_status_badge(\stdClass $stats): string {
        $status = (string)($stats->crmstatus ?? 'unknown');

        $map = [
            'active_customer' => ['crm_status_active_customer', 'success'],
            'trial' => ['crm_status_trial', 'info'],
            'former_customer' => ['crm_status_former_customer', 'secondary'],
            'suspended' => ['crm_status_suspended', 'danger'],
            'lead' => ['crm_status_lead', 'warning'],
            'unknown' => ['crm_status_unknown', 'light text-dark border'],
        ];

        [$key, $class] = $map[$status] ?? $map['unknown'];

        return html_writer::span(
            get_string($key, 'local_subscriptions'),
            'badge bg-' . $class
        );
    }

    private static function inbox_hero_badge(
        \stdClass $inbox
    ): string {
        $count = (int)(
            $inbox->unreadcount
            ?? 0
        );

        if ($count > 0) {
            $label = get_string(
                'crm_user_inbox_badge_unread',
                'local_subscriptions',
                $count
            );

            $class = 'bg-danger';
        } else if (
            !empty(
                $inbox->conversationcount
            )
        ) {
            $label = get_string(
                'crm_user_inbox_badge',
                'local_subscriptions'
            );

            $class = 'bg-primary';
        } else {
            $label = get_string(
                'crm_user_inbox_badge_empty',
                'local_subscriptions'
            );

            $class =
                'bg-light text-dark border';
        }

        return html_writer::span(
            '📥 ' . $label,
            'badge ' . $class
        );
    }

    private static function stats_total_spent(\stdClass $stats): string {
        $parts = [];

        if (!empty($stats->spent_eur)) {
            $parts[] = AdminFormatter::price((float)$stats->spent_eur, 'EUR');
        }

        if (!empty($stats->spent_rub)) {
            $parts[] = AdminFormatter::price((float)$stats->spent_rub, 'RUB');
        }

        return $parts ? implode(' · ', $parts) : '-';
    }

    private static function inbox(
        \stdClass $profile
    ): string {
        if (
            !Capabilities::can_view_inbox() ||
            empty($profile->inbox) ||
            empty($profile->inbox->available)
        ) {
            return '';
        }

        $inbox = $profile->inbox;

        $content =
            self::inbox_summary_cards($inbox);

        $content .=
            self::inbox_last_message($inbox);

        $content .=
            self::inbox_recent_threads(
                $inbox->recentthreads ?? []
            );

        $actions = html_writer::link(
            new moodle_url(
                subscription_config::
                    admin_inbox_page(),
                [
                    'q' =>
                        (string)$profile->user->email,
                ]
            ),
            get_string(
                'crm_user_inbox_open_all',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-primary btn-sm',
            ]
        );

        $content .= html_writer::div(
            $actions,
            'crm-user-inbox-actions mt-3'
        );

        return self::section(
            get_string(
                'crm_user_inbox_section',
                'local_subscriptions'
            ),
            $content,
            'crm-section-inbox'
        );
    }

    private static function inbox_summary_cards(
        \stdClass $inbox
    ): string {
        $cards = [
            [
                'icon' => '💬',
                'label' => get_string(
                    'crm_user_inbox_conversations',
                    'local_subscriptions'
                ),
                'value' => (int)(
                    $inbox->conversationcount
                    ?? 0
                ),
            ],
            [
                'icon' => '📂',
                'label' => get_string(
                    'crm_user_inbox_open_conversations',
                    'local_subscriptions'
                ),
                'value' => (int)(
                    $inbox->openconversationcount
                    ?? 0
                ),
            ],
            [
                'icon' => '📥',
                'label' => get_string(
                    'crm_user_inbox_unread',
                    'local_subscriptions'
                ),
                'value' => (int)(
                    $inbox->unreadcount
                    ?? 0
                ),
            ],
            [
                'icon' => '💡',
                'label' => get_string(
                    'crm_user_inbox_ai_suggestions',
                    'local_subscriptions'
                ),
                'value' => (int)(
                    $inbox->aisuggestioncount
                    ?? 0
                ),
            ],
        ];

        $out = html_writer::start_tag(
            'div',
            [
                'class' =>
                    'row crm-user-inbox-stats',

                'role' => 'list',

                'aria-label' =>
                    get_string(
                        'crm_user_inbox_statistics_label',
                        'local_subscriptions'
                    ),
            ]
        );

        foreach ($cards as $card) {
            $out .= html_writer::tag(
                'div',
                html_writer::div(
                    html_writer::span(
                        $card['icon'],
                        'crm-user-inbox-stat-icon',
                        [
                            'aria-hidden' => 'true',
                        ]
                    ) .
                    html_writer::div(
                        (string)$card['value'],
                        'crm-user-inbox-stat-value'
                    ) .
                    html_writer::div(
                        $card['label'],
                        'crm-user-inbox-stat-label'
                    ),
                    'crm-user-inbox-stat card h-100'
                ),
                [
                    'class' =>
                        'col-6 col-lg-3 mb-3',

                    'role' => 'listitem',

                    'aria-label' =>
                        get_string(
                            'crm_user_inbox_stat_aria',
                            'local_subscriptions',
                            (object)[
                                'label' =>
                                    $card['label'],

                                'value' =>
                                    $card['value'],
                            ]
                        ),
                ]
            );
        }

        $out .= html_writer::end_tag(
            'div'
        );

        return $out;
    }

    private static function inbox_last_message(
        \stdClass $inbox
    ): string {
        if (
            empty($inbox->lastthreadid) ||
            empty($inbox->lastmessageat)
        ) {
            return html_writer::div(
                get_string(
                    'crm_user_inbox_no_conversations',
                    'local_subscriptions'
                ),
                'alert alert-light border mb-3'
            );
        }

        $direction =
            (string)(
                $inbox->lastdirection
                ?? ''
            );

        $directionlabel =
            $direction === 'outbound'
                ? get_string(
                    'crm_user_inbox_last_sent',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_user_inbox_last_received',
                    'local_subscriptions'
                );

        $subject = trim(
            (string)(
                $inbox->lastsubject
                ?? ''
            )
        );

        if ($subject === '') {
            $subject = get_string(
                'crm_inbox_no_subject',
                'local_subscriptions'
            );
        }

        $url = new moodle_url(
            subscription_config::
                admin_inbox_thread_page(),
            [
                'id' =>
                    (int)$inbox->lastthreadid,
            ]
        );

        $content =
            html_writer::div(
                html_writer::span(
                    $direction === 'outbound'
                        ? '📤'
                        : '📥',
                    'mr-2',
                    [
                        'aria-hidden' => 'true',
                    ]
                ) .
                html_writer::span(
                    $directionlabel,
                    'text-muted small'
                ),
                'mb-1'
            );

        $content .= html_writer::link(
            $url,
            format_string($subject),
            [
                'class' =>
                    'crm-user-inbox-last-subject',
            ]
        );

        $content .= html_writer::div(
            AdminFormatter::datetime(
                (int)$inbox->lastmessageat
            ),
            'text-muted small mt-1'
        );

        return html_writer::div(
            html_writer::tag(
                'h4',
                get_string(
                    'crm_user_inbox_last_email',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'h6 mb-3',
                ]
            ) .
            $content,
            'crm-user-inbox-last-message mb-4'
        );
    }

    private static function inbox_recent_threads(
        array $threads
    ): string {
        if (!$threads) {
            return '';
        }

        $out = html_writer::tag(
            'h4',
            get_string(
                'crm_user_inbox_recent_conversations',
                'local_subscriptions'
            ),
            [
                'class' => 'h6 mb-3',
            ]
        );

        $out .= html_writer::start_div(
            'crm-user-inbox-thread-list'
        );

        foreach ($threads as $thread) {
            $subject = trim(
                (string)(
                    $thread->subject
                    ?: $thread->lastmessagesubject
                    ?: ''
                )
            );

            if ($subject === '') {
                $subject = get_string(
                    'crm_inbox_no_subject',
                    'local_subscriptions'
                );
            }

            $url = new moodle_url(
                subscription_config::
                    admin_inbox_thread_page(),
                [
                    'id' => (int)$thread->id,
                ]
            );

            $badges =
                self::inbox_thread_status_badge(
                    (string)$thread->status
                );

            $badges .= ' ' .
                self::inbox_thread_priority_badge(
                    (string)$thread->priority
                );

            if (
                !empty($thread->unreadcount)
            ) {
                $badges .= ' ' .
                    html_writer::span(
                        get_string(
                            'crm_user_inbox_unread_badge',
                            'local_subscriptions',
                            (int)$thread->unreadcount
                        ),
                        'badge bg-danger'
                    );
            }

            $contact = trim(
                (string)(
                    $thread->contactname
                    ?: $thread->contactemail
                    ?: ''
                )
            );

            $meta = [];

            if ($contact !== '') {
                $meta[] = s($contact);
            }

            if (!empty($thread->lastmessageat)) {
                $meta[] =
                    AdminFormatter::datetime(
                        (int)$thread->lastmessageat
                    );
            }

            $out .= html_writer::div(
                html_writer::div(
                    html_writer::div(
                        html_writer::link(
                            $url,
                            format_string($subject),
                            [
                                'class' =>
                                    'crm-user-inbox-thread-title',
                            ]
                        ) .
                        html_writer::div(
                            $badges,
                            'crm-user-inbox-thread-badges mt-2'
                        ),
                        'crm-user-inbox-thread-main'
                    ) .
                    html_writer::div(
                        implode(' · ', $meta),
                        'crm-user-inbox-thread-meta text-muted small'
                    ),
                    'crm-user-inbox-thread-inner'
                ),
                'crm-user-inbox-thread'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function
        inbox_thread_status_badge(
            string $status
        ): string {
        $class = match ($status) {
            'open' =>
                'bg-primary',

            'pending' =>
                'bg-warning text-dark',

            'resolved' =>
                'bg-success',

            'closed' =>
                'bg-secondary',

            'spam' =>
                'bg-danger',

            default =>
                'bg-light text-dark border',
        };

        return html_writer::span(
            s(
                InboxValuePresentation::
                    status_label($status)
            ),
            'badge ' . $class
        );
    }

    private static function
        inbox_thread_priority_badge(
            string $priority
        ): string {
        $class = match ($priority) {
            'low' =>
                'bg-light text-dark border',

            'normal' =>
                'bg-secondary',

            'high' =>
                'bg-warning text-dark',

            'urgent' =>
                'bg-danger',

            default =>
                'bg-light text-dark border',
        };

        return html_writer::span(
            s(
                InboxValuePresentation::
                    priority_label(
                        $priority
                    )
            ),
            'badge ' . $class
        );
    }

    /** Renders unified Native Commerce purchases for User360. */
    private static function commerce_purchases_content(array $purchases): string {
        global $OUTPUT;

        if ($purchases === []) {
            return $OUTPUT->notification(
                get_string('crm_commerce_no_purchases', 'local_subscriptions'),
                'info'
            );
        }

        $table = new html_table();
        $table->attributes['class'] = 'generaltable crm-commerce-purchases-table';
        $table->head = [
            get_string('crm_commerce_reference', 'local_subscriptions'),
            get_string('crm_commerce_purchase_type', 'local_subscriptions'),
            get_string('crm_commerce_contents', 'local_subscriptions'),
            get_string('status', 'local_subscriptions'),
            get_string('crm_commerce_amount', 'local_subscriptions'),
            get_string('date'),
            get_string('actions', 'local_subscriptions'),
        ];

        foreach ($purchases as $purchase) {
            $type = (string)($purchase->type ?? 'unknown');
            $typekey = 'crm_commerce_type_' . $type;
            $typelabel = get_string_manager()->string_exists($typekey, 'local_subscriptions')
                ? get_string($typekey, 'local_subscriptions')
                : ucfirst($type);

            $status = (string)($purchase->status ?? '');
            $statuskey = 'crm_commerce_status_' . $status;
            $statuslabel = get_string_manager()->string_exists($statuskey, 'local_subscriptions')
                ? get_string($statuskey, 'local_subscriptions')
                : ucfirst($status);

            $badgeclass = !empty($purchase->successful)
                ? 'bg-success'
                : (!empty($purchase->failedpayment) ? 'bg-danger' : 'bg-secondary');

            $amount = AdminFormatter::price(
                (float)($purchase->total ?? 0),
                (string)($purchase->currency ?? '')
            );

            $table->data[] = [
                s((string)($purchase->publicreference ?? $purchase->reference ?? '-')),
                html_writer::span(s($typelabel), 'badge bg-light text-dark border'),
                s((string)($purchase->label ?? '-')),
                html_writer::span(s($statuslabel), 'badge ' . $badgeclass),
                $amount,
                AdminFormatter::datetime((int)($purchase->timecreated ?? 0)),
                html_writer::link(
                    (string)($purchase->orderurl ?? '#'),
                    get_string('crm_commerce_view_order', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-outline-primary']
                ),
            ];
        }

        return html_writer::table($table);
    }

    /**
     * Public N11.3 adapter for the Commerce & access workspace.
     */
    public static function render_commerce_orders_content(array $purchases): string {
        return self::commerce_purchases_content($purchases);
    }

    /**
     * Public N11.3 adapter for subscriptions.
     */
    public static function render_subscriptions_content(array $subscriptions): string {
        return self::subscriptions_content($subscriptions);
    }

    /**
     * Public N11.3 adapter for Legacy/digital purchases.
     */
    public static function render_digital_purchases_content(array $payments): string {
        return self::digital_purchases_content($payments);
    }

    /**
     * Public N11.3 adapter for effective Moodle course access.
     */
    public static function render_courses_content(array $courses): string {
        return self::courses_content($courses);
    }

    private static function subscriptions_content(array $subscriptions): string {
        global $OUTPUT;

        if (!$subscriptions) {
            return $OUTPUT->notification(get_string('crm_no_subscriptions', 'local_subscriptions'), 'info');
        }

        $table = new html_table();
        $table->head = [
            get_string('plan', 'local_subscriptions'),
            get_string('subscription_period', 'local_subscriptions'),
            get_string('price', 'local_subscriptions'),
            get_string('status', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions'),
        ];

        foreach ($subscriptions as $sub) {
            $start = !empty($sub->start_date) ? AdminFormatter::date((int)$sub->start_date) : '-';

            if (empty($sub->end_date) || (int)$sub->end_date > strtotime('2100-01-01')) {
                $period = $start . '<br><span class="badge bg-light text-dark border">♾️ ' .
                    get_string('unlimited', 'local_subscriptions') . '</span>';
            } else {
                $period = $start . '<br><span class="text-muted">→ ' . AdminFormatter::date((int)$sub->end_date) . '</span>';
            }

            $price = ((float)($sub->pricepaid ?? 0) > 0)
                ? AdminFormatter::price($sub->pricepaid ?? 0, $sub->currency ?? '')
                : '-';

            $table->data[] = [
                format_string($sub->planname ?: get_string('unknown_plan', 'local_subscriptions')),
                $period,
                $price,
                SubsPresenter::render_status_badge($sub->status),
                self::subscription_actions($sub),
            ];
        }

        return html_writer::table($table);
    }

    private static function digital_purchases_content(array $digitalpayments): string {
        global $OUTPUT;

        if (!$digitalpayments) {
            return $OUTPUT->notification(get_string('crm_no_digital_purchases', 'local_subscriptions'), 'info');
        }

        $table = new html_table();
        $table->head = [
            get_string('product', 'local_subscriptions'),
            get_string('email'),
            get_string('price', 'local_subscriptions'),
            get_string('status', 'local_subscriptions'),
            get_string('creation_date', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions'),
        ];

        foreach ($digitalpayments as $payment) {
            $price = ((float)($payment->price ?? 0) > 0)
                ? AdminFormatter::price($payment->price ?? 0, $payment->currency ?? '')
                : '-';

            $table->data[] = [
                format_string($payment->productname ?: '-'),
                s($payment->email),
                $price,
                DigitalPresenter::render_status_badge($payment->status),
                !empty($payment->creation_date) ? AdminFormatter::date((int)$payment->creation_date) : '-',
                self::digital_purchase_actions($payment),
            ];
        }

        return html_writer::table($table);
    }

    private static function digital_purchase_actions(\stdClass $payment): string {
        global $PAGE;

        if (!has_capability(Capabilities::MANAGE_DIGITAL, \context_system::instance())) {
            return '-';
        }

        $status = strtoupper(trim((string)($payment->status ?? '')));
        if (empty($payment->id) || !in_array($status, ['PAID', 'COMPLETED'], true)) {
            return '-';
        }

        $returnurl = $PAGE->url->out_as_local_url(false);
        $url = new moodle_url(
            subscription_config::digital_purchase_resend_email_admin_page(),
            [
                'id' => (int)$payment->id,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]
        );

        return html_writer::link(
            $url,
            '🔐 ' . get_string('crm_resend_access_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );
    }

    private static function quick_actions(\stdClass $profile): string {
        $user = $profile->user;
        $items = [];

        foreach (($profile->actions ?? []) as $action) {
            $classes = 'btn btn-' . ($action->style ?? 'secondary') . ' mr-1 mb-1';

            if (!empty($action->danger)) {
                $classes .= ' crm-action-danger';
            }

            $items[] = html_writer::link(
                new moodle_url($action->url),
                s($action->label),
                ['class' => $classes]
            );
        }

        if (!empty($profile->iscommerceguest)) {
            if ($items === []) {
                return html_writer::div(
                    get_string('crm_commerce_guest_no_actions', 'local_subscriptions'),
                    'text-muted small'
                );
            }
            return html_writer::div(implode('', $items), 'crm-quick-actions');
        }

        $noteform = html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new moodle_url(subscription_config::admin_user_add_note_page()),
            'class' => 'crm-quick-note-form mt-3',
        ]);

        $noteform .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $noteform .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'id',
            'value' => $user->id,
        ]);

        $types = [
            'general' => get_string('crm_note_type_general', 'local_subscriptions'),
            'followup' => get_string('crm_note_type_followup', 'local_subscriptions'),
            'payment' => get_string('crm_note_type_payment', 'local_subscriptions'),
            'access' => get_string('crm_note_type_access', 'local_subscriptions'),
            'sensitive' => get_string('crm_note_type_sensitive', 'local_subscriptions'),
        ];

        $typeoptions = '';

        foreach ($types as $value => $label) {
            $typeoptions .= html_writer::tag('option', $label, ['value' => $value]);
        }

        $noteform .= html_writer::tag('select', $typeoptions, [
            'name' => 'type',
            'class' => 'custom-select mb-2',
        ]);

        $noteform .= html_writer::tag('textarea', '', [
            'name' => 'note',
            'class' => 'form-control mb-2',
            'rows' => 2,
            'placeholder' => get_string('crm_note_placeholder', 'local_subscriptions'),
            'required' => 'required',
        ]);

        $noteform .= html_writer::tag('button', '📝 ' . get_string('crm_add_note', 'local_subscriptions'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-outline-primary',
        ]);

        $noteform .= html_writer::end_tag('form');

        return html_writer::div(implode(' ', $items), 'crm-quick-actions-buttons mb-3') . $noteform;

    }
    private static function timeline_content(
        \stdClass $profile
    ): string {

        $items = $profile->timeline ?? [];

        if ($items === []) {
            return html_writer::div(
                get_string(
                    'crm_timeline_empty',
                    'local_subscriptions'
                ),
                'crm-timeline-empty alert alert-info',
                [
                    'role' => 'status',
                ]
            );
        }

        $groups = self::group_timeline_items(
            $items
        );

        $counts = self::timeline_category_counts(
            $items
        );

        $timelineid = 'crm-user-timeline';

        $out = html_writer::start_div(
            'crm-timeline',
            [
                'id' => $timelineid,
                'data-user-timeline' => '1',
            ]
        );

        $out .= self::timeline_toolbar(
            $timelineid,
            $counts,
            count($items)
        );

        $out .= html_writer::div(
            get_string(
                'crm_timeline_results_count',
                'local_subscriptions',
                count($items)
            ),
            'crm-timeline-results mb-3',
            [
                'data-timeline-results' =>
                    '1',

                'data-count-template' =>
                    get_string(
                        'crm_timeline_results_count',
                        'local_subscriptions',
                        '__COUNT__'
                    ),

                'aria-live' =>
                    'polite',

                'aria-atomic' =>
                    'true',
            ]
        );

        $out .= html_writer::start_div(
            'crm-timeline-groups',
            [
                'data-timeline-groups' => '1',
            ]
        );

        foreach ($groups as $groupkey => $group) {
            if ($group['items'] === []) {
                continue;
            }

            $out .= html_writer::start_tag(
                'section',
                [
                    'class' =>
                        'crm-timeline-group',

                    'data-timeline-group' =>
                        $groupkey,
                ]
            );

            $out .= html_writer::tag(
                'h3',
                s($group['label']),
                [
                    'class' =>
                        'crm-timeline-date-heading',
                ]
            );

            $out .= html_writer::start_div(
                'crm-timeline-group-body',
                [
                    'data-timeline-group-body' =>
                        '1',
                ]
            );

            foreach ($group['items'] as $item) {
                $out .= self::timeline_item(
                    $item
                );
            }

            $out .= html_writer::end_div();
            $out .= html_writer::end_tag(
                'section'
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::div(
            get_string(
                'crm_timeline_no_filtered_results',
                'local_subscriptions'
            ),
            'crm-timeline-filter-empty alert alert-light d-none',
            [
                'data-timeline-empty' => '1',
                'role' => 'status',
            ]
        );

        if (
            !empty($profile->timelinehasmore)
        ) {
            $out .= html_writer::div(
                html_writer::tag(
                    'button',
                    get_string(
                        'crm_timeline_load_more',
                        'local_subscriptions'
                    ),
                    [
                        'type' =>
                            'button',

                        'class' =>
                            'btn btn-outline-secondary',

                        'data-timeline-load-more' =>
                            '1',

                        'data-userid' =>
                            (int)$profile->user->id,

                        'data-offset' =>
                            (int)$profile->timelinenextoffset,

                        'data-limit' =>
                            20,

                        'data-sesskey' => sesskey(),

                        'data-url' =>
                            (
                                new moodle_url(
                                    subscription_config::
                                        admin_user_timeline_ajax_page()
                                )
                            )->out(false),

                        'data-loading-label' =>
                            get_string(
                                'crm_timeline_loading',
                                'local_subscriptions'
                            ),

                        'data-error-label' =>
                            get_string(
                                'crm_timeline_loading_error',
                                'local_subscriptions'
                            ),
                    ]
                ),
                'crm-timeline-load-more text-center'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Renders the Timeline filtering toolbar.
     *
     * @param array<string, int> $counts
     */
    private static function timeline_toolbar(
        string $timelineid,
        array $counts,
        int $total
    ): string {
        $categorybuttons = '';

        $categorybuttons .=
            self::timeline_category_button(
                UserProfileTimelineCategory::ALL,
                get_string('all'),
                $total,
                true
            );

        foreach (
            UserProfileTimelineCategory::values()
            as $category
        ) {
            $categorybuttons .=
                self::timeline_category_button(
                    $category,
                    UserProfileTimelineCategory::label(
                        $category
                    ),
                    $counts[$category] ?? 0
                );
        }

        $searchlabel = get_string(
            'crm_timeline_search',
            'local_subscriptions'
        );

        $search = html_writer::tag(
            'label',
            s($searchlabel),
            [
                'for' =>
                    $timelineid . '-search',

                'class' =>
                    'visually-hidden',
            ]
        );

        $search .= html_writer::empty_tag(
            'input',
            [
                'id' =>
                    $timelineid . '-search',

                'type' =>
                    'search',

                'class' =>
                    'form-control form-control-sm',

                'placeholder' =>
                    $searchlabel,

                'data-timeline-search' =>
                    '1',

                'autocomplete' =>
                    'off',
            ]
        );

        $period = html_writer::select(
            [
                'all' =>
                    get_string(
                        'crm_timeline_period_all',
                        'local_subscriptions'
                    ),

                '7' =>
                    get_string(
                        'crm_timeline_period_7_days',
                        'local_subscriptions'
                    ),

                '30' =>
                    get_string(
                        'crm_timeline_period_30_days',
                        'local_subscriptions'
                    ),

                '90' =>
                    get_string(
                        'crm_timeline_period_90_days',
                        'local_subscriptions'
                    ),

                '365' =>
                    get_string(
                        'crm_timeline_period_year',
                        'local_subscriptions'
                    ),
            ],
            'timelineperiod',
            'all',
            false,
            [
                'class' =>
                    'form-select form-select-sm',

                'data-timeline-period' =>
                    '1',

                'aria-label' =>
                    get_string(
                        'crm_timeline_period',
                        'local_subscriptions'
                    ),
            ]
        );

        $importance = html_writer::tag(
            'label',
            html_writer::empty_tag(
                'input',
                [
                    'type' =>
                        'checkbox',

                    'class' =>
                        'form-check-input',

                    'data-timeline-important' =>
                        '1',
                ]
            )
            . html_writer::span(
                get_string(
                    'crm_timeline_important_only',
                    'local_subscriptions'
                ),
                'form-check-label'
            ),
            [
                'class' =>
                    'form-check crm-timeline-important-filter',
            ]
        );

        $reset = html_writer::tag(
            'button',
            get_string(
                'reset'
            ),
            [
                'type' =>
                    'button',

                'class' =>
                    'btn btn-sm btn-outline-secondary',

                'data-timeline-reset' =>
                    '1',
            ]
        );

        $expand = html_writer::tag(
            'button',
            get_string(
                'crm_timeline_expand_all',
                'local_subscriptions'
            ),
            [
                'type' =>
                    'button',

                'class' =>
                    'btn btn-sm btn-outline-secondary',

                'data-timeline-expand-all' =>
                    '1',
            ]
        );

        $collapse = html_writer::tag(
            'button',
            get_string(
                'crm_timeline_collapse_all',
                'local_subscriptions'
            ),
            [
                'type' =>
                    'button',

                'class' =>
                    'btn btn-sm btn-outline-secondary',

                'data-timeline-collapse-all' =>
                    '1',
            ]
        );

        $filters = html_writer::div(
            $categorybuttons,
            'crm-timeline-category-filters',
            [
                'role' =>
                    'group',

                'aria-label' =>
                    get_string(
                        'crm_timeline_filter_categories',
                        'local_subscriptions'
                    ),
            ]
        );

        $controls = html_writer::div(
            html_writer::div(
                $search,
                'crm-timeline-search'
            )
            . html_writer::div(
                $period,
                'crm-timeline-period'
            )
            . $importance
            . $reset,
            'crm-timeline-secondary-filters'
        );

        $displayactions = html_writer::div(
            $expand . $collapse,
            'crm-timeline-display-actions'
        );

        return html_writer::tag(
            'div',
            $filters .
                $controls .
                $displayactions,
            [
                'class' =>
                    'crm-timeline-toolbar mb-3',
            ]
        );
    }

    /**
     * Renders one Timeline category button.
     */
    private static function timeline_category_button(
        string $category,
        string $label,
        int $count,
        bool $active = false
    ): string {
        $buttonlabel =
            html_writer::span(
                s($label),
                'crm-timeline-filter-label'
            );

        $buttonlabel .=
            html_writer::span(
                (string)$count,
                'crm-timeline-filter-count badge rounded-pill'
            );

        return html_writer::tag(
            'button',
            $buttonlabel,
            [
                'type' =>
                    'button',

                'class' =>
                    'btn btn-sm ' .
                    (
                        $active
                            ? 'btn-primary'
                            : 'btn-outline-secondary'
                    ),

                'data-timeline-category-filter' =>
                    $category,

                'aria-pressed' =>
                    $active
                        ? 'true'
                        : 'false',
            ]
        );
    }

    /**
     * Counts Timeline events by functional category.
     *
     * @return array<string, int>
     */
    private static function timeline_category_counts(
        array $items
    ): array {
        $counts = array_fill_keys(
            UserProfileTimelineCategory::values(),
            0
        );

        foreach ($items as $item) {
            $category =
                UserProfileTimelineCategory::resolve(
                    $item
                );

            if (!isset($counts[$category])) {
                $counts[$category] = 0;
            }

            $counts[$category]++;
        }

        return $counts;
    }

    private static function render_timeline_body(\stdClass $item): string {
        $details = $item->detailsraw ?? [];

        if (($item->action ?? '') === 'email.custom.sent') {
            $content = '';

            if (!empty($details['subject'])) {
                $content .= html_writer::div(
                    html_writer::tag('strong', get_string('subject', 'local_subscriptions') . ': ') .
                    s((string)$details['subject'])
                );
            }

            if (!empty($details['to'])) {
                $content .= html_writer::div(
                    html_writer::tag('strong', get_string('recipient', 'local_subscriptions') . ': ') .
                    s((string)$details['to']),
                    'small text-muted mt-1'
                );
            }

            if (!empty($details['body'])) {
                $content .= html_writer::div(
                    format_text((string)$details['body'], FORMAT_HTML),
                    'mt-2'
                );
            }

            if (!empty($item->logid)) {
                $content .= html_writer::div(
                    html_writer::link(
                        new moodle_url(subscription_config::admin_user_email_preview_page(), [
                            'logid' => (int)$item->logid,
                        ]),
                        '👁️ ' . get_string('crm_email_preview', 'local_subscriptions'),
                        [
                            'class' => 'btn btn-sm btn-outline-secondary mt-2',
                            'target' => '_blank',
                        ]
                    )
                );
            }

            return html_writer::div(
                $content,
                'crm-timeline-email-card crm-timeline-card'
            );
        }        

        if (($item->type ?? '') === 'admin_log' && str_starts_with((string)($item->action ?? ''), 'email.')) {
            return self::render_email_timeline_body($item, $details);
        }

        if (($item->objecttype ?? '') === 'subscription') {
            return self::render_subscription_timeline_body($details);
        }

        if (($item->type ?? '') === 'subscription_payment') {
            return self::render_subscription_payment_timeline_body($details);
        }

        if (($item->type ?? '') === 'admin_log' && ($item->objecttype ?? '') === 'digital_purchase') {
            return self::render_digital_action_timeline_body($details);
        }

        if (($item->type ?? '') === 'digital_purchase') {
            return self::render_digital_purchase_timeline_body($details);
        }

        if (!empty($details['subject'])) {
            $content = '';

            $content .= html_writer::div(
                html_writer::tag('strong', get_string('subject', 'local_subscriptions') . ': ') .
                s((string)$details['subject'])
            );

            if (!empty($details['body'])) {
                $content .= html_writer::div(
                    format_text((string)$details['body'], FORMAT_HTML),
                    'mt-2'
                );
            }

            return html_writer::div(
                $content,
                'crm-timeline-email-card crm-timeline-card'
            );
        }

        // On masque les détails purement techniques.
        if (array_key_exists('notifyuser', $details)) {
            return '';
        }

        $body = trim((string)($item->body ?? ''));

        return $body !== ''
            ? html_writer::div(format_text($body, FORMAT_PLAIN), 'crm-timeline-note-card crm-timeline-card')
            : '';
    }

    private static function render_subscription_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $main = [];

        $plan = s((string)($details['plan'] ?? '-'));
        $status = DigitalPresenter::render_status_badge((string)($details['status'] ?? ''));

        $main[] = html_writer::div(
            html_writer::tag('strong', $plan) . ' ' . $status
        );

        $period = '';

        if (!empty($details['start']) || !empty($details['end'])) {
            $period = html_writer::div(
                s(
                    self::normalize_crm_date((string)($details['start'] ?? '-')) .
                    ' → ' .
                    self::normalize_crm_date((string)($details['end'] ?? '-'))
                ),
                'text-muted small mt-1'
            );
        }

        $price = '';

        if (!empty($details['price']) && $details['price'] !== '-') {
            $price = html_writer::div(
                s((string)$details['price']),
                'small mt-1'
            );
        }

        $changes = '';

        if (!empty($details['changes']) && is_array($details['changes'])) {
            $changes .= html_writer::start_div('crm-timeline-changes mt-2');

            foreach ($details['changes'] as $field => $change) {
                if (!is_array($change)) {
                    continue;
                }

                $changes .= html_writer::div(
                    html_writer::tag('span', s($field), ['class' => 'text-muted me-1']) .
                    html_writer::tag('span', s(self::normalize_crm_date((string)($change['from'] ?? '-'))), ['class' => 'crm-change-old']) .
                    html_writer::tag('span', ' → ', ['class' => 'text-muted mx-1']) .
                    html_writer::tag('span', s(self::normalize_crm_date((string)($change['to'] ?? '-'))), ['class' => 'crm-change-new']),
                    'small'
                );
            }

            $changes .= html_writer::end_div();
        }

        return html_writer::div(
            html_writer::div(implode('', $main)) .
            $period .
            $price .
            $changes,
            'crm-timeline-subscription-card crm-timeline-card'
        );
    }

    private static function normalize_crm_date(string $value): string {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $value, $m)) {
            return sprintf('%02d/%02d/%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }

        return $value;
    }

    private static function courses_content(array $courses): string {
        global $OUTPUT;

        if (!$courses) {
            return $OUTPUT->notification(get_string('crm_no_accessible_courses', 'local_subscriptions'), 'info');
        }

        $table = new html_table();
        $table->head = [
            get_string('course'),
            get_string('shortnamecourse'),
            get_string('access', 'local_subscriptions'),
        ];

        foreach ($courses as $course) {
            $access = get_string('active', 'local_subscriptions');

            if (!empty($course->timeend)) {
                $access .= ' · ' . get_string('until', 'local_subscriptions') . ' ' .
                    AdminFormatter::date((int)$course->timeend);
            }

            $table->data[] = [
                AdminEntityLinks::course(
                    (int)$course->id,
                    format_string($course->fullname)
                ),
                s($course->shortname),
                $access,
            ];
        }

        return html_writer::table($table);
    }

    private static function render_digital_purchase_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $product = !empty($details['productid'])
            ? AdminEntityLinks::digital_product(
                (int)$details['productid'],
                s((string)($details['product'] ?? '-'))
            )
            : s((string)($details['product'] ?? '-'));

        $status = DigitalPresenter::render_status_badge((string)($details['status'] ?? ''));

        $price = AdminFormatter::price(
            $details['price'] ?? 0,
            $details['currency'] ?? ''
        );

        return html_writer::div(
            html_writer::div(
                html_writer::tag('strong', $product) . ' ' . $status
            ) .
            html_writer::div($price, 'small mt-1') .
            html_writer::div(s((string)($details['email'] ?? '')), 'text-muted small mt-1'),
            'crm-timeline-digital-card crm-timeline-card'
        );
    }

    private static function render_digital_action_timeline_body(array $details): string {
        $email = s((string)($details['email'] ?? ''));
        $purchaseid = (int)($details['purchaseid'] ?? 0);
        $productid = (int)($details['productid'] ?? 0);

        $purchase = $purchaseid > 0
            ? html_writer::link(
                new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $purchaseid]),
                get_string('digital_purchase', 'local_subscriptions') . ' #' . $purchaseid,
                ['class' => 'crm-entity-link']
            )
            : '';

        $product = $productid > 0
            ? AdminEntityLinks::digital_product(
                $productid,
                get_string('product', 'local_subscriptions') . ' #' . $productid
            )
            : '';

        $expires = !empty($details['expires'])
            ? html_writer::div(
                get_string('digital_purchase_link_expires', 'local_subscriptions') . ': ' . s((string)$details['expires']),
                'small text-muted mt-1'
            )
            : '';

        $oldtoken = !empty($details['oldtoken'])
            ? html_writer::div(
                get_string('digital_purchase_old_token', 'local_subscriptions') . ': ' . s((string)$details['oldtoken']),
                'small text-muted mt-1'
            )
            : '';

        $lines = array_filter([$purchase, $product]);

        if ($email !== '') {
            $lines[] = html_writer::span($email, 'text-muted small');
        }

        return html_writer::div(
            implode(html_writer::empty_tag('br'), $lines) .
            $expires .
            $oldtoken,
            'crm-timeline-digital-action-card crm-timeline-card'
        );
    }

    private static function subscription_actions(\stdClass $sub): string {
        $userid = (int)$sub->userid;
        $subscriptionid = (int)$sub->id;

        $baseparams = [
            'userid' => $userid,
            'subscriptionid' => $subscriptionid,
            'sesskey' => sesskey(),
        ];

        $items = [];

        $items[] = html_writer::link(
            new moodle_url(subscription_config::user_subscription_edit_page(), ['id' => $subscriptionid]),
            '✏️ ' . get_string('edit'),
            ['class' => 'btn btn-sm btn-outline-primary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::user_subscription_view_page(), ['id' => $subscriptionid]),
            '👁️ ' . get_string('view_details', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'welcome',
            ]),
            '👋 ' . get_string('crm_resend_welcome_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'access',
            ]),
            '🔐 ' . get_string('crm_resend_access_email', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'receipt',
            ]),
            '🧾 ' . get_string('crm_resend_receipt', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary me-1 mb-1']
        );

        $items[] = html_writer::link(
            new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), $baseparams + [
                'action' => 'extend',
                'days' => 30,
            ]),
            '➕30j',
            ['class' => 'btn btn-sm btn-outline-success me-1 mb-1']
        );

        return html_writer::div(implode('', $items), 'crm-subscription-actions');
    }

    private static function render_subscription_payment_timeline_body(array $details): string {
        if (!$details) {
            return '';
        }

        $plan = s((string)($details['plan'] ?? '-'));
        $status = DigitalPresenter::render_status_badge((string)($details['status'] ?? ''));

        $price = AdminFormatter::price(
            $details['price'] ?? 0,
            $details['currency'] ?? ''
        );

        $provider = trim((string)($details['provider'] ?? ''));
        $transactionid = trim((string)($details['transactionid'] ?? ''));
        $email = trim((string)($details['email'] ?? ''));

        $lines = [];

        $lines[] = html_writer::div(
            html_writer::tag('strong', $plan) . ' ' . $status
        );

        if ($price !== '-') {
            $lines[] = html_writer::div($price, 'small mt-1');
        }

        if ($provider !== '') {
            $lines[] = html_writer::div(
                \local_subscriptions\payment\Provider::label_with_icon($provider),
                'small mt-1'
            );
        }

        if ($transactionid !== '') {
            $lines[] = html_writer::div(
                get_string('transactionid', 'local_subscriptions') . ': ' . s($transactionid),
                'text-muted small mt-1'
            );
        }

        if ($email !== '') {
            $lines[] = html_writer::div(s($email), 'text-muted small mt-1');
        }

        return html_writer::div(
            implode('', $lines),
            'crm-timeline-subscription-payment-card crm-timeline-card'
        );
    }

    private static function render_email_timeline_body(\stdClass $item, array $details): string {
        $action = (string)($item->action ?? '');

        $type = match ($action) {
            'email.receipt.sent' => get_string('crm_email_type_receipt', 'local_subscriptions'),
            'email.subscription_access.sent' => get_string('crm_email_type_access', 'local_subscriptions'),
            'email.welcome.sent' => get_string('crm_email_type_welcome', 'local_subscriptions'),
            default => get_string('email', 'local_subscriptions'),
        };

        $lines = [];

        $lines[] = html_writer::div(
            html_writer::tag('strong', get_string('type', 'local_subscriptions') . ': ') . s($type)
        );

        if (!empty($details['plan'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('plan', 'local_subscriptions') . ': ') . s((string)$details['plan']),
                'small'
            );
        }

        if (!empty($details['subscriptionid'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('subscription', 'local_subscriptions') . ': ') . '#' . (int)$details['subscriptionid'],
                'small'
            );
        }

        if (!empty($details['paymentrequest'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('payment_request', 'local_subscriptions') . ': ') . s((string)$details['paymentrequest']),
                'small'
            );
        }

        if (!empty($details['provider'])) {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('payment_provider', 'local_subscriptions') . ': ') .
                \local_subscriptions\payment\Provider::label_with_icon((string)$details['provider']),
                'small'
            );
        }

        if (!empty($details['transactionid']) && $details['transactionid'] !== '-') {
            $lines[] = html_writer::div(
                html_writer::tag('strong', get_string('transactionid', 'local_subscriptions') . ': ') .
                s((string)$details['transactionid']),
                'small text-muted'
            );
        }

        return html_writer::div(
            implode('', $lines),
            'crm-timeline-email-card crm-timeline-card'
        );
    }

    private static function timeline_filter_button(string $filter, string $label): string {
        return html_writer::tag('button', $label, [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-primary me-1',
            'onclick' => "
                document.querySelectorAll('.crm-timeline-item').forEach(function(item){
                    item.classList.toggle('d-none', '$filter' !== 'all' && item.dataset.category !== '$filter');
                });
            ",
        ]);
    }

    /**
     * Groups Timeline items by calendar day.
     *
     * @return array<string, array{
     *     label: string,
     *     items: array
     * }>
     */
    private static function group_timeline_items(
        array $items
    ): array {
        $groups = [];

        foreach ($items as $item) {
            $timestamp = (int)(
                $item->timecreated
                ?? 0
            );

            if ($timestamp <= 0) {
                continue;
            }

            $key = userdate(
                $timestamp,
                '%Y-%m-%d'
            );

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' =>
                        self::timeline_day_label(
                            $timestamp
                        ),

                    'items' =>
                        [],
                ];
            }

            $groups[$key]['items'][] =
                $item;
        }

        return $groups;
    }

    /**
     * Returns a readable label for one Timeline day.
     */
    private static function timeline_day_label(
        int $timestamp
    ): string {
        $today = usergetmidnight(
            time()
        );

        $eventday = usergetmidnight(
            $timestamp
        );

        if ($eventday === $today) {
            return get_string(
                'today'
            );
        }

        if (
            $eventday ===
            $today - DAYSECS
        ) {
            return get_string(
                'crm_timeline_yesterday',
                'local_subscriptions'
            );
        }

        return userdate(
            $timestamp,
            get_string(
                'strftimedatefullshort',
                'langconfig'
            )
        );
    }


    private static function timeline_item(
        \stdClass $item
    ): string {
        $body = self::render_timeline_body(
            $item
        );

        $category =
            UserProfileTimelineCategory::resolve(
                $item
            );

        $timestamp = (int)(
            $item->timecreated
            ?? 0
        );

        $importance = strtolower(
            trim(
                (string)(
                    $item->importance
                    ?? 'normal'
                )
            )
        );

        if (
            !in_array(
                $importance,
                [
                    'normal',
                    'medium',
                    'high',
                ],
                true
            )
        ) {
            $importance = 'normal';
        }

        $meta = AdminFormatter::datetime(
            $timestamp
        );

        $actor =
            self::timeline_actor_label(
                $item
            );

        if ($actor !== '') {
            $meta .= ' · ' . $actor;
        }

        $title = trim(
            (string)(
                $item->title
                ?? ''
            )
        );

        if ($title === '') {
            $title = get_string(
                'crm_timeline_event',
                'local_subscriptions'
            );
        }

        $description = trim(
            strip_tags(
                (string)(
                    $item->description
                    ?? $item->body
                    ?? ''
                )
            )
        );

        $searchtext = \core_text::strtolower(
            implode(
                ' ',
                [
                    $title,
                    $description,
                    $actor,
                    UserProfileTimelineCategory::label(
                        $category
                    ),
                ]
            )
        );

        $eventid = self::timeline_html_id(
            'crm-timeline-event-'
        );

        $bodyid =
            $eventid . '-body';

        $hasbody =
            $body !== '';

        $icon = html_writer::span(
            s(
                (string)(
                    $item->icon
                    ?? UserProfileTimelineCategory::icon(
                        $category
                    )
                )
            ),
            'crm-timeline-icon',
            [
                'aria-hidden' =>
                    'true',
            ]
        );

        $heading = html_writer::div(
            html_writer::tag(
                'strong',
                s($title),
                [
                    'class' =>
                        'crm-timeline-title',
                ]
            )
            . html_writer::span(
                s($meta),
                'crm-timeline-meta'
            ),
            'crm-timeline-heading'
        );

        $categorybadge = html_writer::span(
            s(
                UserProfileTimelineCategory::label(
                    $category
                )
            ),
            'crm-timeline-category-badge'
        );

        $summary = html_writer::div(
            $heading . $categorybadge,
            'crm-timeline-summary'
        );

        $toggle = '';

        if ($hasbody) {
            $toggle = html_writer::tag(
                'button',
                html_writer::span(
                    get_string(
                        'crm_timeline_view_details',
                        'local_subscriptions'
                    ),
                    'crm-timeline-toggle-label'
                )
                . html_writer::span(
                    '⌄',
                    'crm-timeline-toggle-icon',
                    [
                        'aria-hidden' =>
                            'true',
                    ]
                ),
                [
                    'type' =>
                        'button',

                    'class' =>
                        'btn btn-sm btn-link crm-timeline-toggle',

                    'data-timeline-toggle' =>
                        '1',

                    'aria-expanded' =>
                        'false',

                    'aria-controls' =>
                        $bodyid,
                ]
            );
        }

        $action = '';

        if (
            !empty($item->actionurl)
        ) {
            $action = html_writer::link(
                new moodle_url(
                    (string)$item->actionurl
                ),
                get_string(
                    'crm_timeline_open_event',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-secondary crm-timeline-action',
                ]
            );
        }

        $header = html_writer::div(
            $icon .
                $summary .
                $action .
                $toggle,
            'crm-timeline-line'
        );

        $content = '';

        if ($hasbody) {
            $content = html_writer::div(
                $body,
                'crm-timeline-body d-none',
                [
                    'id' =>
                        $bodyid,

                    'data-timeline-body' =>
                        '1',
                ]
            );
        }

        return html_writer::tag(
            'article',
            $header . $content,
            [
                'id' =>
                    $eventid,

                'class' =>
                    'crm-timeline-item',

                'data-timeline-item' =>
                    '1',

                'data-category' =>
                    $category,

                'data-importance' =>
                    $importance,

                'data-timecreated' =>
                    $timestamp,

                'data-search-text' =>
                    $searchtext,
            ]
        );
    }

    private static function timeline_actor_label(
        \stdClass $item
    ): string {
        $actorid = (int)(
            $item->actorid
            ?? 0
        );

        if ($actorid <= 0) {
            return '';
        }

        $actorname = trim(
            (string)(
                $item->actorname
                ?? ''
            )
        );

        if ($actorname === '') {
            return get_string(
                'crm_timeline_by_admin',
                'local_subscriptions'
            );
        }

        return get_string(
            'crm_timeline_by_actor',
            'local_subscriptions',
            $actorname
        );
    }

    private static function notes(array $notes): string {
        global $OUTPUT;

        if (!$notes) {
            return $OUTPUT->notification(get_string('crm_no_notes', 'local_subscriptions'), 'info');
        }

        $out = html_writer::start_div('crm-notes-list');

        foreach ($notes as $note) {
            $body = trim((string)($note->body ?? $note->note ?? ''));

            if ($body === '') {
                continue;
            }

            $date = !empty($note->timecreated)
                ? AdminFormatter::datetime((int)$note->timecreated)
                : '';

            $author = '';

            if (!empty($note->authorname)) {
                $author = s((string)$note->authorname);
            } else if (!empty($note->actorname)) {
                $author = s((string)$note->actorname);
            }

            $type = (string)($note->type ?? 'general');

            $key = 'crm_note_type_' . $type;

            $label = get_string_manager()->string_exists($key, 'local_subscriptions')
                ? get_string($key, 'local_subscriptions')
                : get_string('crm_note_type_general', 'local_subscriptions');

            $typebadge = html_writer::span(
                $label,
                'badge badge-light crm-note-type crm-note-type-' . s($type)
            );

            $meta = trim($date . ($author !== '' ? ' · ' . $author : ''));

            $out .= html_writer::div(
                html_writer::div($typebadge, 'mb-2') .
                html_writer::div(format_text($body, FORMAT_PLAIN), 'crm-note-body') .
                ($meta !== '' ? html_writer::div($meta, 'crm-note-meta text-muted small mt-2') : ''),
                'crm-note-item'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function intelligence(\stdClass $profile): string {
        if (empty($profile->intelligence) || empty($profile->intelligence->leadscore)) {
            return '';
        }

        $score = $profile->intelligence->leadscore;
        $levelkey = 'crm_intelligence_level_' . clean_param((string)($score->level ?? 'very_low'), PARAM_ALPHANUMEXT);
        $level = get_string_manager()->string_exists($levelkey, 'local_subscriptions')
            ? get_string($levelkey, 'local_subscriptions')
            : get_string('crm_intelligence_level_very_low', 'local_subscriptions');

        $summarykey = 'crm_intelligence_summary_' . clean_param((string)($score->level ?? 'very_low'), PARAM_ALPHANUMEXT);
        $summary = get_string_manager()->string_exists($summarykey, 'local_subscriptions')
            ? get_string($summarykey, 'local_subscriptions')
            : '';
        
        $cards = [
            [
                'icon' => '💼',
                'label' => get_string('crm_intelligence_commercial_score', 'local_subscriptions'),
                'value' => (int)$score->commercial . '/100',
            ],
            [
                'icon' => '⚡',
                'label' => get_string('crm_intelligence_engagement_score', 'local_subscriptions'),
                'value' => (int)$score->engagement . '/100',
            ],
            [
                'icon' => '⚠️',
                'label' => get_string('crm_intelligence_risk_score', 'local_subscriptions'),
                'value' => (int)$score->risk . '/100',
            ],
            [
                'icon' => '🎯',
                'label' => get_string('crm_intelligence_global_score', 'local_subscriptions'),
                'value' => (int)$score->global . '/100',
            ],
        ];

        $out = html_writer::tag('h3', get_string('crm_section_intelligence', 'local_subscriptions'), [
            'class' => 'crm-section-title mb-3',
        ]);

        $out .= html_writer::div(
            html_writer::tag('div', s($level), ['class' => 'crm-intelligence-level']) .
            html_writer::tag('div', s($summary), ['class' => 'crm-intelligence-summary text-muted']),
            'crm-intelligence-header mb-3'
        );

        if (!empty($profile->intelligence->trend)) {
            $out .= self::intelligence_trend($profile->intelligence->trend);
        }

        $out .= html_writer::start_div('row mb-3 crm-intelligence-grid');

        foreach ($cards as $card) {
            $out .= html_writer::div(
                html_writer::div(
                    html_writer::div(
                        html_writer::span($card['icon'], 'crm-stat-icon') .
                        html_writer::tag('div', s($card['value']), ['class' => 'crm-stat-number']) .
                        html_writer::tag('div', s($card['label']), ['class' => 'crm-stat-label']),
                        'card card-body crm-stat-card h-100'
                    )
                ),
                'col-md-3 mb-3'
            );
        }

        $out .= html_writer::end_div();

        if (!empty($score->reasons)) {
            $out .= html_writer::div(
                self::intelligence_reasons($score->reasons),
                'crm-intelligence-reasons mt-2'
            );
        }

        if (!empty($profile->intelligence->explanations)) {
            $out .= self::intelligence_explanations($profile->intelligence->explanations);
        }        

        $out .= self::intelligence_badges(
            get_string('crm_intelligence_segments', 'local_subscriptions'),
            $profile->intelligence->segments ?? [],
            'crm_intelligence_segment_'
        );

        $out .= self::intelligence_badges(
            get_string('crm_intelligence_opportunities', 'local_subscriptions'),
            $profile->intelligence->opportunities ?? [],
            'crm_intelligence_opportunity_'
        );

        $out .= self::intelligence_badges(
            get_string('crm_intelligence_recommendations', 'local_subscriptions'),
            $profile->intelligence->recommendations ?? [],
            'crm_intelligence_recommendation_'
        );        

        return html_writer::div(
            $out,
            'crm-section card card-body mb-4 crm-section-intelligence'
        );
    }

    private static function intelligence_reasons(array $reasons): string {
        $out = '';

        foreach ($reasons as $reason) {
            $key = 'crm_intelligence_reason_' . clean_param((string)$reason, PARAM_ALPHANUMEXT);

            $label = get_string_manager()->string_exists($key, 'local_subscriptions')
                ? get_string($key, 'local_subscriptions')
                : $reason;

            $out .= html_writer::span(
                s($label),
                'badge bg-light text-dark border mr-1 mb-1'
            );
        }

        return $out;
    }

    private static function intelligence_badges(string $title, array $items, string $prefix): string {
        if (empty($items)) {
            return '';
        }

        $out = html_writer::tag('h4', s($title), ['class' => 'h6 mt-3 mb-2']);

        foreach ($items as $item) {
            $key = $prefix . clean_param((string)($item->key ?? ''), PARAM_ALPHANUMEXT);

            $label = get_string_manager()->string_exists($key, 'local_subscriptions')
                ? get_string($key, 'local_subscriptions')
                : ($item->key ?? '');

            $out .= html_writer::span(
                s($label),
                'badge bg-light text-dark border mr-1 mb-1'
            );
        }

        return html_writer::div($out, 'crm-intelligence-badge-group');
    }

    private static function intelligence_trend(\stdClass $trend): string {
        $direction = (string)($trend->direction ?? 'stable');
        $delta = (int)($trend->delta ?? 0);

        $key = 'crm_trend_direction_' . clean_param($direction, PARAM_ALPHANUMEXT);

        $label = get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : $direction;

        $value = $delta > 0 ? '+' . $delta : (string)$delta;

        return html_writer::div(
            html_writer::span(get_string('crm_trend_label', 'local_subscriptions'), 'text-muted mr-2') .
            html_writer::span(s($label), 'badge bg-light text-dark border mr-1') .
            html_writer::span(s($value), 'fw-bold'),
            'crm-intelligence-trend mb-3'
        );
    }

    private static function intelligence_explanations(array $explanations): string {
        if (empty($explanations)) {
            return '';
        }

        $out = html_writer::tag('h4', get_string('crm_explanations_title', 'local_subscriptions'), [
            'class' => 'h6 mt-3 mb-2',
        ]);

        foreach ($explanations as $explanation) {
            $key = 'crm_explanation_' . clean_param((string)($explanation->key ?? ''), PARAM_ALPHANUMEXT);

            $label = get_string_manager()->string_exists($key, 'local_subscriptions')
                ? get_string($key, 'local_subscriptions')
                : ($explanation->key ?? '');

            $impact = (int)($explanation->impact ?? 0);
            $impactlabel = $impact > 0 ? '+' . $impact : (string)$impact;

            $out .= html_writer::div(
                html_writer::span(s($label), 'mr-2') .
                html_writer::span(s($impactlabel), 'badge bg-light text-dark border'),
                'd-flex justify-content-between border-bottom py-1'
            );
        }

        return html_writer::div($out, 'crm-intelligence-explanations mt-3');
    }

    /**
     * Renders a compact Timeline summary for the User360 summary zone.
     */
    public static function render_timeline_summary_panel(
        \stdClass $profile
    ): string {
        $events = array_slice(
            $profile->timeline ?? [],
            0,
            3
        );

        $importantcount = 0;

        foreach (
            $profile->timeline ?? []
            as $event
        ) {
            if (
                in_array(
                    strtolower(
                        (string)(
                            $event->importance
                            ?? 'normal'
                        )
                    ),
                    [
                        'medium',
                        'high',
                    ],
                    true
                )
            ) {
                $importantcount++;
            }
        }

        $content = html_writer::div(
            html_writer::span(
                (string)count(
                    $profile->timeline ?? []
                ),
                'crm-timeline-summary-value'
            )
            . html_writer::span(
                get_string(
                    'crm_timeline_loaded_events',
                    'local_subscriptions'
                ),
                'crm-timeline-summary-label'
            ),
            'crm-timeline-summary-stat'
        );

        $content .= html_writer::div(
            html_writer::span(
                (string)$importantcount,
                'crm-timeline-summary-value'
            )
            . html_writer::span(
                get_string(
                    'crm_timeline_important_events',
                    'local_subscriptions'
                ),
                'crm-timeline-summary-label'
            ),
            'crm-timeline-summary-stat'
        );

        if ($events !== []) {
            $latest = $events[0];

            $content .= html_writer::div(
                html_writer::span(
                    get_string(
                        'crm_timeline_latest_event',
                        'local_subscriptions'
                    ),
                    'crm-timeline-summary-label'
                )
                . html_writer::tag(
                    'strong',
                    s(
                        (string)(
                            $latest->title
                            ?? get_string(
                                'crm_timeline_event',
                                'local_subscriptions'
                            )
                        )
                    ),
                    [
                        'class' =>
                            'crm-timeline-summary-latest-title',
                    ]
                )
                . html_writer::span(
                    AdminFormatter::datetime(
                        (int)$latest->timecreated
                    ),
                    'crm-timeline-summary-latest-date'
                ),
                'crm-timeline-summary-latest'
            );
        }

        $content .= html_writer::link(
            '#crm-user-timeline',
            get_string(
                'crm_timeline_view_full',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary',

                'data-timeline-scroll' =>
                    '1',
            ]
        );

        return self::section(
            get_string(
                'crm_section_timeline',
                'local_subscriptions'
            ),
            $content,
            'crm-section-timeline-summary'
        );
    }

    /**
     * Renders the read-only Timeline of a deleted user.
     */
    public static function render_historical_timeline_panel(
        \stdClass $profile
    ): string {
        return self::section(
            get_string(
                'crm_section_timeline',
                'local_subscriptions'
            ),
            self::timeline_content(
                $profile
            ),
            'crm-section-timeline crm-section-timeline-historical'
        );
    }

    /**
     * Generates a sufficiently unique HTML identifier.
     */
    private static function timeline_html_id(
        string $prefix
    ): string {
        static $sequence = 0;

        $sequence++;

        return $prefix . $sequence;
    }

}