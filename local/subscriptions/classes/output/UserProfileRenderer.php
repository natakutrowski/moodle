<?php

namespace local_subscriptions\output;

defined('MOODLE_INTERNAL') || die();

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

final class UserProfileRenderer {

    public static function render(\stdClass $profile): string {
        $out = '';

        $out .= html_writer::start_div('crm-user-profile-360');

        $out .= self::hero($profile);
        $out .= self::section(
            get_string('crm_section_quick_actions', 'local_subscriptions'),
            self::quick_actions($profile),
            'crm-section-actions'
        );

        $out .= self::stats($profile);

        $out .= self::intelligence($profile);

        $out .= self::inbox($profile);

        $workitems = UserWorkItemSection::render((int)$profile->user->id);
        if ($workitems !== '') {
            $out .= self::section(
                get_string('crm_work_user_section', 'local_subscriptions'),
                $workitems,
                'crm-section-work-items'
            );
        }        

        $out .= self::section(
            get_string('crm_section_subscriptions', 'local_subscriptions'),
            self::subscriptions_content($profile->subscriptions),
            'crm-section-subscriptions'
        );

        $out .= self::section(
            get_string('crm_section_digital_purchases', 'local_subscriptions'),
            self::digital_purchases_content($profile->digitalpayments),
            'crm-section-digital'
        );

        $out .= self::section(
            get_string('crm_section_courses', 'local_subscriptions'),
            self::courses_content($profile->courses ?? []),
            'crm-section-courses'
        );

        $out .= self::section(
            get_string('crm_section_notes', 'local_subscriptions'),
            self::notes($profile->notes ?? []),
            'crm-section-notes'
        );

        $out .= self::section(
            get_string('crm_section_timeline', 'local_subscriptions'),
            self::timeline_content($profile->timeline ?? []),
            'crm-section-timeline'
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function hero(\stdClass $profile): string {
        $user = $profile->user;
        $stats = $profile->stats;

        $identity = html_writer::tag('h2', fullname($user), [
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

        if (has_capability(Capabilities::VIEW_USERS, \context_system::instance())) {
            $identity .= self::tag_controls($profile);
        }

        $meta = [];

        $meta[] = self::hero_meta_item(
            get_string('country'),
            s($user->country ?: '-')
        );

        $meta[] = self::hero_meta_item(
            get_string('timecreated'),
            !empty($user->timecreated) ? AdminFormatter::date((int)$user->timecreated) : '-'
        );

        $meta[] = self::hero_meta_item(
            get_string('lastaccess'),
            !empty($user->lastaccess) ? AdminFormatter::datetime((int)$user->lastaccess) : '-'
        );

        $meta[] = self::hero_meta_item(
            get_string('crm_last_activity', 'local_subscriptions'),
            !empty($stats->lastactivity) ? AdminFormatter::datetime((int)$stats->lastactivity) : '-'
        );

        $links = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $user->id]),
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

    private static function section(string $title, string $content, string $class = ''): string {
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
                'icon' => '📚',
                'label' => get_string('subscriptions', 'local_subscriptions'),
                'value' => $stats->subscriptions ?? 0,
                'muted' => get_string('crm_stats_subscriptions_hint', 'local_subscriptions'),
            ],
            [
                'icon' => '📦',
                'label' => get_string('digital_purchases', 'local_subscriptions'),
                'value' => $stats->digitalpayments ?? 0,
                'muted' => get_string('crm_stats_digital_hint', 'local_subscriptions'),
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
            ];
        }

        return html_writer::table($table);
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
    private static function timeline_content(array $items): string {
        if (!$items) {
            return html_writer::div(
                get_string('crm_timeline_empty', 'local_subscriptions'),
                'alert alert-info'
            );
        }

        $groups = self::group_timeline_items($items);

        $out = html_writer::start_div('crm-timeline');

        $out .= html_writer::div(
            html_writer::span(get_string('show') . ' : ', 'me-2') .
            self::timeline_filter_button('all', get_string('all')) .
            self::timeline_filter_button('subscriptions', get_string('subscriptions', 'local_subscriptions')) .
            self::timeline_filter_button('purchases', get_string('crm_filter_purchases', 'local_subscriptions')) .
            self::timeline_filter_button('emails', get_string('crm_filter_emails', 'local_subscriptions')) .
            self::timeline_filter_button('other', get_string('crm_filter_other', 'local_subscriptions')),
            'crm-timeline-filters mb-3'
        );

        $out .= html_writer::div(
            html_writer::tag('button', '▾ ' . get_string('crm_timeline_expand_all', 'local_subscriptions'), [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary me-2',
                'onclick' => "
                    document.querySelectorAll('.crm-timeline-body').forEach(function(e){e.classList.remove('d-none');});
                    document.querySelectorAll('.crm-timeline-toggle').forEach(function(b){b.innerText='▴';});
                ",
            ]) .
            html_writer::tag('button', '▴ ' . get_string('crm_timeline_collapse_all', 'local_subscriptions'), [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary',
                'onclick' => "
                    document.querySelectorAll('.crm-timeline-body').forEach(function(e){e.classList.add('d-none');});
                    document.querySelectorAll('.crm-timeline-toggle').forEach(function(b){b.innerText='▾';});
                ",
            ]),
            'mb-3'
        );

        foreach ($groups as $groupkey => $group) {
            if (!$group['items']) {
                continue;
            }

            $collapseid = 'crm-timeline-group-' . $groupkey;

            $out .= html_writer::start_div('crm-timeline-group card mb-2');

            $out .= html_writer::tag('button',
                html_writer::span($group['icon'] . ' ' . $group['label'], 'fw-bold') .
                html_writer::span(' ' . count($group['items']), 'badge bg-primary ms-2') .
                html_writer::span('▾', 'float-end'),
                [
                    'type' => 'button',
                    'class' => 'btn btn-light text-start w-100 crm-timeline-group-toggle',
                    'onclick' => "
                        var el = document.getElementById('$collapseid');
                        el.classList.toggle('d-none');
                        this.querySelector('.float-end').innerText = el.classList.contains('d-none') ? '▸' : '▾';
                    ",
                ]
            );

            $hidden = $groupkey === 'recent' ? '' : ' d-none';
            $out .= html_writer::start_div('crm-timeline-group-body' . $hidden, ['id' => $collapseid]);

            foreach ($group['items'] as $item) {
                $out .= self::timeline_item($item);
            }

            $out .= html_writer::end_div();
            $out .= html_writer::end_div();
        }

        $out .= html_writer::end_div();

        return $out;
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

    private static function group_timeline_items(array $items): array {
        $now = time();

        $groups = [
            'recent' => [
                'label' => get_string('crm_timeline_recent', 'local_subscriptions'),
                'icon' => '🗓️',
                'items' => [],
            ],
            'middle' => [
                'label' => get_string('crm_timeline_middle', 'local_subscriptions'),
                'icon' => '📦',
                'items' => [],
            ],
            'old' => [
                'label' => get_string('crm_timeline_old', 'local_subscriptions'),
                'icon' => '🗂️',
                'items' => [],
            ],
        ];

        foreach ($items as $item) {
            $age = $now - (int)$item->timecreated;

            if ($age <= 30 * DAYSECS) {
                $groups['recent']['items'][] = $item;
            } else if ($age <= 90 * DAYSECS) {
                $groups['middle']['items'][] = $item;
            } else {
                $groups['old']['items'][] = $item;
            }
        }

        return $groups;
    }

    private static function timeline_category(\stdClass $item): string {

        if (
            str_starts_with(
                (string)($item->type ?? ''),
                'inbox_'
            ) ||
            (string)($item->rawtype ?? '') ===
                'inbox_message'
        ) {
            return 'emails';
        }

        $type = (string)($item->type ?? '');

        if (str_contains($type, 'subscription') || str_contains($type, 'trial')) {
            return 'subscriptions';
        }

        if (str_contains($type, 'digital') || str_contains($type, 'purchase') || str_contains($type, 'payment')) {
            return 'purchases';
        }

        if (str_contains($type, 'email')) {
            return 'emails';
        }

        $objecttype = (string)($item->objecttype ?? '');
        $action = (string)($item->action ?? '');

        if (str_starts_with($action, 'email.')) {
            return 'emails';
        }

        if (
            $type === 'subscription_payment' ||
            $type === 'digital_purchase' ||
            str_contains($objecttype, 'purchase') ||
            str_contains($objecttype, 'payment')
        ) {
            return 'purchases';
        }

        if (
            $type === 'subscription_snapshot' ||
            $objecttype === 'subscription' ||
            str_contains($action, 'subscription') ||
            str_contains($action, 'trial')
        ) {
            return 'subscriptions';
        }

        return 'other';
    }

    private static function timeline_item(\stdClass $item): string {
        $body = self::render_timeline_body($item);
        $category = self::timeline_category($item);

        $meta = AdminFormatter::datetime((int)$item->timecreated);
        $actor = self::timeline_actor_label($item);

        $technical = $meta;
        if ($actor !== '') {
            $technical .= ' · ' . $actor;
        }

        $out = html_writer::start_div('crm-timeline-item border-top p-2 w-100', [
            'data-category' => $category,
            'data-importance' => $item->importance ?? 'normal',
        ]);

        $hasbody = $body !== '';

        $out .= html_writer::start_div('d-flex align-items-center w-100 crm-timeline-line' . ($hasbody ? ' crm-timeline-line-clickable' : ''), [
            'onclick' => $hasbody ? "
                var item = this.closest('.crm-timeline-item');
                var body = item.querySelector('.crm-timeline-body');
                var toggle = item.querySelector('.crm-timeline-toggle');
                body.classList.toggle('d-none');
                toggle.innerText = body.classList.contains('d-none') ? '▾' : '▴';
            " : '',
        ]);

        $out .= html_writer::span($item->icon ?? '•', 'crm-timeline-icon flex-shrink-0');

        $out .= html_writer::start_div('flex-grow-1 w-100');

        $out .= html_writer::start_div('d-flex align-items-center w-100 crm-timeline-line');

        $out .= html_writer::div(
            html_writer::tag('strong', ' ' . s((string)$item->title)) .
            html_writer::span(' (' . s($technical) . ')', 'text-muted small ms-2'),
            'flex-grow-1'
        );

        if ($body !== '') {
            $out .= html_writer::tag('button', '▾', [
                'type' => 'button',
                'class' => 'btn btn-sm btn-link crm-timeline-toggle ms-auto',
                'title' => get_string('crm_timeline_view_details', 'local_subscriptions'),
                'onclick' => "event.stopPropagation();
                    var item = this.closest('.crm-timeline-item');
                    var body = item.querySelector('.crm-timeline-body');
                    body.classList.toggle('d-none');
                    this.innerText = body.classList.contains('d-none') ? '▾' : '▴';
                ",
            ]);
        }

        $out .= html_writer::end_div();

        if ($body !== '') {
            $out .= html_writer::div($body, 'crm-timeline-body d-none mt-2 w-100');
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    private static function timeline_actor_label(\stdClass $item): string {
        global $DB;

        $actorid = (int)($item->actorid ?? 0);

        if ($actorid <= 0) {
            return '';
        }

        $actor = $DB->get_record('user', ['id' => $actorid], 'id, firstname, lastname, email', IGNORE_MISSING);

        if (!$actor) {
            return get_string('crm_timeline_by_admin', 'local_subscriptions');
        }

        $name = trim(($actor->firstname ?? '') . ' ' . ($actor->lastname ?? ''));

        if ($name === '') {
            $name = $actor->email ?? ('#' . $actorid);
        }

        return get_string('crm_timeline_by_actor', 'local_subscriptions', $name);
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

}