<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;

/**
 * N11.4B — Support-first User360 dashboard.
 *
 * The first screen is intentionally non-technical:
 * - who is the customer;
 * - what they bought;
 * - what they can access and how far they progressed;
 * - recent exchanges and activity;
 * - frequent support actions.
 */
final class User360SupportOverviewRenderer {

    public static function render_hero(\stdClass $profile): string {
        $user = $profile->user ?? (object)[];
        $stats = $profile->stats ?? (object)[];
        $learning = $profile->learningprogress ?? [];
        $name = User360OverviewRenderer::display_name($profile);
        $email = trim((string)($user->email ?? ''));

        [$started, $completed] = self::learning_counts($learning);
        $xp = self::xp_summary($learning);

        $identity = html_writer::div(
            html_writer::div(
                html_writer::span(
                    s(self::initials($name, $email)),
                    'crm-user360-n114b-avatar-initials'
                )
                . html_writer::span(
                    '',
                    'crm-user360-n114b-avatar-status'
                ),
                'crm-user360-n114b-avatar'
            )
            . html_writer::div(
                html_writer::tag(
                    'h2',
                    s($name),
                    ['class' => 'crm-user360-n114b-name']
                )
                . ($email !== ''
                    ? html_writer::div(
                        s($email),
                        'crm-user360-n114b-email'
                    )
                    : '')
                . html_writer::div(
                    self::identity_badges($profile),
                    'crm-user360-n114b-badges'
                )
                . html_writer::div(
                    self::identity_meta($profile),
                    'crm-user360-n114b-meta'
                ),
                'crm-user360-n114b-identity-copy'
            ),
            'crm-user360-n114b-identity'
        );

        $metrics = [
            self::hero_metric(
                'fa fa-shopping-cart',
                (string)($stats->purchasecount ?? 0),
                get_string('crm_user360_n114_orders', 'local_subscriptions'),
                self::spent_multiline($stats),
                'commerce'
            ),
            self::hero_metric(
                'fa fa-graduation-cap',
                (string)($stats->accessiblecourses ?? 0),
                get_string('crm_user360_n114_courses', 'local_subscriptions'),
                get_string(
                    'crm_user360_n114b_started_sub',
                    'local_subscriptions',
                    $started
                ),
                'learning'
            ),
            self::hero_metric(
                'fa fa-moon-o',
                $xp['value'],
                get_string('crm_user360_n114b_xp_total', 'local_subscriptions'),
                $xp['sub'],
                'xp'
            ),
            self::hero_metric(
                'fa fa-clock-o',
                !empty($stats->lastactivity)
                    ? AdminFormatter::date((int)$stats->lastactivity)
                    : '—',
                get_string('crm_user360_n114_last_activity', 'local_subscriptions'),
                !empty($stats->lastactivity)
                    ? userdate((int)$stats->lastactivity, '%H:%M')
                    : '',
                'activity'
            ),
        ];

        return html_writer::tag(
            'section',
            $identity
            . html_writer::div(
                implode('', $metrics),
                'crm-user360-n114b-hero-kpis'
            ),
            [
                'class' => 'crm-user360-n114b-hero',
                'aria-label' => get_string(
                    'user360_workspace_hero',
                    'local_subscriptions'
                ),
            ]
        );
    }

    /**
     * Kept as a public compatibility adapter for N11.4A callers/tests.
     * KPI are now embedded in the Hero, so the summary zone intentionally
     * renders nothing.
     */
    public static function render_kpis(\stdClass $profile): string {
        return '';
    }

    public static function render(\stdClass $profile): string {
        $left = self::purchases($profile)
            . self::recent_activity($profile);

        $centre = self::learning($profile)
            . self::digital_products($profile)
            . self::recent_notes($profile);

        $right = self::communication($profile)
            . self::support_actions($profile);

        return html_writer::tag(
            'section',
            html_writer::div(
                html_writer::div(
                    $left,
                    'crm-user360-n114b-column crm-user360-n114b-column-left'
                )
                . html_writer::div(
                    $centre,
                    'crm-user360-n114b-column crm-user360-n114b-column-centre'
                )
                . html_writer::tag(
                    'aside',
                    $right,
                    ['class' => 'crm-user360-n114b-column crm-user360-n114b-column-right']
                ),
                'crm-user360-n114b-dashboard-grid'
            ),
            [
                'class' => 'crm-user360-n114-support-view crm-user360-n114b-support-view',
                'id' => 'user360-support-view',
            ]
        );
    }

    private static function purchases(\stdClass $profile): string {
        $rows = array_slice($profile->commercepurchases ?? [], 0, 5);
        $body = '';

        if ($rows === []) {
            $body = self::empty_text(
                get_string('crm_user360_n114_no_purchases', 'local_subscriptions')
            );
        } else {
            $body .= html_writer::start_div('crm-user360-n114b-table');

            foreach ($rows as $purchase) {
                $label = trim((string)($purchase->label ?? ''));
                if ($label === '') {
                    $label = '—';
                }

                $type = trim((string)($purchase->producttype ?? $purchase->type ?? ''));
                $body .= html_writer::div(
                    html_writer::div(
                        self::purchase_icon($type)
                        . html_writer::div(
                            self::purchase_label_link(
                                $purchase,
                                $label
                            )
                            . html_writer::div(
                                !empty($purchase->timecreated)
                                    ? AdminFormatter::datetime((int)$purchase->timecreated)
                                    : '—',
                                'crm-user360-n114b-item-meta'
                            ),
                            'crm-user360-n114b-item-copy'
                        ),
                        'crm-user360-n114b-item-main'
                    )
                    . html_writer::div(
                        html_writer::span(
                            self::purchase_status((string)($purchase->status ?? '')),
                            'crm-user360-n114b-status ' . self::purchase_status_class(
                                (string)($purchase->status ?? '')
                            )
                        )
                        . html_writer::tag(
                            'strong',
                            s(self::money(
                                (float)($purchase->total ?? 0),
                                (string)($purchase->currency ?? '')
                            )),
                            ['class' => 'crm-user360-n114b-item-value']
                        ),
                        'crm-user360-n114b-item-tail'
                    ),
                    'crm-user360-n114b-list-row'
                );
            }

            $body .= html_writer::end_div();
        }

        $footer = html_writer::link(
            self::advanced_url('commerce'),
            get_string('crm_user360_n114b_view_all_purchases', 'local_subscriptions') . ' →',
            ['class' => 'crm-user360-n114b-card-link']
        );

        return self::card(
            'fa fa-shopping-cart',
            get_string('crm_user360_n114b_recent_purchases', 'local_subscriptions'),
            get_string('crm_user360_n114_purchases_help', 'local_subscriptions'),
            $body,
            'purchases',
            'pink',
            $footer
        );
    }

    private static function learning(\stdClass $profile): string {
        if (!empty($profile->iscommerceguest)) {
            return self::card(
                'fa fa-graduation-cap',
                get_string('crm_user360_n114_learning_title', 'local_subscriptions'),
                get_string('crm_user360_n114_learning_help', 'local_subscriptions'),
                self::empty_text(
                    get_string('crm_user360_n114_no_moodle_learning', 'local_subscriptions')
                ),
                'learning',
                'violet'
            );
        }

        $courses = array_slice($profile->learningprogress ?? [], 0, 5);
        $body = '';

        if ($courses === []) {
            $body = self::empty_text(
                get_string('crm_user360_n114_no_courses', 'local_subscriptions')
            );
        }

        foreach ($courses as $course) {
            $percentage = $course->progresspercentage;
            $pct = $percentage !== null
                ? max(0, min(100, (float)$percentage))
                : null;

            $activity = (int)($course->trackedactivities ?? 0) > 0
                ? get_string(
                    'crm_user360_n114_activity_progress',
                    'local_subscriptions',
                    (object)[
                        'completed' => (int)$course->completedactivities,
                        'tracked' => (int)$course->trackedactivities,
                    ]
                )
                : get_string(
                    'crm_user360_n114_no_tracking',
                    'local_subscriptions'
                );

            $latestactivity = html_writer::div(
                html_writer::tag(
                    'strong',
                    s(
                        trim((string)($course->lastactivityname ?? '')) !== ''
                            ? (string)$course->lastactivityname
                            : get_string(
                                'crm_user360_n114c_no_course_activity',
                                'local_subscriptions'
                            )
                    ),
                    ['class' => 'crm-user360-n114c-last-activity-name']
                )
                . (
                    !empty($course->lastactivityat)
                        ? html_writer::span(
                            AdminFormatter::datetime(
                                (int)$course->lastactivityat
                            ),
                            'crm-user360-n114c-last-activity-date'
                        )
                        : ''
                ),
                'crm-user360-n114c-last-activity'
            );

            $progress = html_writer::div(
                html_writer::div(
                    html_writer::tag(
                        'strong',
                        $pct !== null ? round($pct) . '%' : '—',
                        ['class' => 'crm-user360-n114b-progress-percent']
                    )
                    . ($pct !== null
                        ? html_writer::div(
                            html_writer::div(
                                '',
                                'crm-user360-n114b-progress-bar-value ' .
                                    self::progress_tone($pct),
                                ['style' => 'width:' . $pct . '%']
                            ),
                            'crm-user360-n114b-progress-bar'
                        )
                        : '')
                    . html_writer::span(
                        s($activity),
                        'crm-user360-n114b-progress-caption'
                    )
                    . $latestactivity,
                    'crm-user360-n114b-progress-copy'
                ),
                'crm-user360-n114b-progress'
            );

            $accessend = (int)($course->accessend ?? 0);
            $isexpired = $accessend > 0 && $accessend < time();

            if ($isexpired) {
                $accesslabel = get_string(
                    'crm_user360_n116c_access_expired',
                    'local_subscriptions'
                );
                $accessclass = 'is-expired';
            } else if ($accessend > 0) {
                $accesslabel = get_string(
                    'crm_user360_n116c_access_until',
                    'local_subscriptions',
                    AdminFormatter::date($accessend)
                );
                $accessclass = 'is-limited';
            } else {
                $accesslabel = get_string(
                    'crm_user360_n116c_access_lifetime',
                    'local_subscriptions'
                );
                $accessclass = 'is-lifetime';
            }

            $access = html_writer::div(
                html_writer::span(
                    s($accesslabel),
                    'crm-user360-n116c-access-label ' . $accessclass
                ),
                'crm-user360-n114b-course-access'
            );

            $body .= html_writer::div(
                html_writer::div(
                    html_writer::div(
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-book',
                            'aria-hidden' => 'true',
                        ]),
                        'crm-user360-n114b-course-icon'
                    )
                    . html_writer::div(
                        html_writer::link(
                            new \moodle_url(
                                '/course/view.php',
                                ['id' => (int)$course->courseid]
                            ),
                            s((string)($course->fullname ?: $course->shortname)),
                            ['class' => 'crm-user360-n114b-course-title']
                        ),
                        'crm-user360-n114b-course-name'
                    ),
                    'crm-user360-n114b-course-identity'
                )
                . $progress
                . $access,
                'crm-user360-n114b-course-row'
                    . ($isexpired ? ' is-expired' : '')
            );
        }

        $summary = self::learning_summary($profile->learningprogress ?? []);

        return self::card(
            'fa fa-graduation-cap',
            get_string('crm_user360_n114_learning_title', 'local_subscriptions'),
            get_string('crm_user360_n114_learning_help', 'local_subscriptions'),
            $body . $summary,
            'learning',
            'violet',
            html_writer::link(
                self::advanced_url('commerce'),
                get_string('crm_user360_n114b_view_all_courses', 'local_subscriptions') . ' →',
                ['class' => 'crm-user360-n114b-card-link']
            )
        );
    }

    private static function digital_products(
        \stdClass $profile
    ): string {
        $resources = array_slice(
            $profile->digitalresources ?? [],
            0,
            4
        );

        $body = '';

        if ($resources === []) {
            $body = self::empty_text(
                get_string(
                    'crm_user360_n115c_no_digital_products',
                    'local_subscriptions'
                )
            );
        } else {
            foreach ($resources as $resource) {
                $title = trim(
                    (string)($resource['title'] ?? '')
                );
                if ($title === '') {
                    continue;
                }

                $downloads = array_values(
                    array_filter(
                        $resource['downloads'] ?? [],
                        static fn(array $download): bool =>
                            !empty($download['available'])
                            && !empty($download['url'])
                    )
                );

                $downloadlinks = '';
                foreach ($downloads as $download) {
                    $downloadlinks .= html_writer::link(
                        new \moodle_url(
                            (string)$download['url']
                        ),
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-download',
                            'aria-hidden' => 'true',
                        ])
                        . s(
                            (string)(
                                $download['label']
                                ?? get_string(
                                    'download',
                                    'core'
                                )
                            )
                        ),
                        [
                            'class' =>
                                'crm-user360-n115c-download-link',
                        ]
                    );
                }

                $historyparts = [];
                foreach ($downloads as $download) {
                    if (!empty($download['hasdownloadhistory'])) {
                        $count = (int)(
                            $download['downloadcount'] ?? 0
                        );

                        $historyparts[] =
                            get_string(
                                'crm_user360_n115c_download_count',
                                'local_subscriptions',
                                $count
                            );

                        if (!empty($download['haslastdownload'])) {
                            $historyparts[] =
                                get_string(
                                    'crm_user360_n115c_last_download',
                                    'local_subscriptions',
                                    (string)(
                                        $download['lastdownloaddate']
                                        ?? ''
                                    )
                                );
                        }

                        break;
                    }
                }

                if ($historyparts === []) {
                    $historyparts[] = get_string(
                        'crm_user360_n115c_download_history_unavailable',
                        'local_subscriptions'
                    );
                }

                $titlehtml = !empty(
                    $resource['hasproducturl']
                )
                    ? html_writer::link(
                        new \moodle_url(
                            (string)$resource['producturl']
                        ),
                        s($title),
                        [
                            'class' =>
                                'crm-user360-n115c-digital-title',
                        ]
                    )
                    : html_writer::tag(
                        'strong',
                        s($title),
                        [
                            'class' =>
                                'crm-user360-n115c-digital-title',
                        ]
                    );

                $body .= html_writer::div(
                    html_writer::div(
                        html_writer::div(
                            html_writer::tag('i', '', [
                                'class' => 'fa fa-file-pdf-o',
                                'aria-hidden' => 'true',
                            ]),
                            'crm-user360-n115c-digital-icon'
                        )
                        . html_writer::div(
                            $titlehtml
                            . html_writer::div(
                                get_string(
                                    'crm_user360_n115c_owned_since',
                                    'local_subscriptions',
                                    (string)(
                                        $resource['purchaseddate']
                                        ?? '—'
                                    )
                                ),
                                'crm-user360-n115c-digital-meta'
                            )
                            . html_writer::div(
                                s(implode(' · ', $historyparts)),
                                'crm-user360-n115c-digital-history'
                            ),
                            'crm-user360-n115c-digital-copy'
                        ),
                        'crm-user360-n115c-digital-main'
                    )
                    . html_writer::div(
                        $downloadlinks,
                        'crm-user360-n115c-downloads'
                    ),
                    'crm-user360-n115c-digital-row'
                );
            }
        }

        return self::card(
            'fa fa-cube',
            get_string(
                'crm_user360_n115c_digital_products',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115c_digital_products_help',
                'local_subscriptions'
            ),
            $body,
            'digital-products',
            'blue'
        );
    }

    private static function communication(\stdClass $profile): string {
        $inbox = $profile->inbox ?? null;
        $body = '';

        if ($inbox !== null && !empty($inbox->available)) {
            $threads = array_slice($inbox->recentthreads ?? [], 0, 3);

            $body .= html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-comments-o',
                        'aria-hidden' => 'true',
                    ])
                    . html_writer::tag(
                        'strong',
                        get_string(
                            'crm_user360_n114b_recent_conversations',
                            'local_subscriptions'
                        )
                    )
                    . html_writer::span(
                        get_string(
                            'crm_user360_n114b_unread_badge',
                            'local_subscriptions',
                            (int)($inbox->unreadcount ?? 0)
                        ),
                        'crm-user360-n114b-unread-pill'
                    ),
                    'crm-user360-n114b-subsection-heading'
                ),
                'crm-user360-n114b-subsection'
            );

            if ($threads === []) {
                $body .= self::empty_text(
                    get_string('crm_user360_n114b_no_conversations', 'local_subscriptions')
                );
            } else {
                foreach ($threads as $thread) {
                    $subject = trim((string)($thread->lastmessagesubject ?? $thread->subject ?? ''));
                    if ($subject === '') {
                        $subject = get_string('crm_inbox_no_subject', 'local_subscriptions');
                    }

                    $url = new \moodle_url(
                        subscription_config::admin_inbox_thread_page(),
                        ['id' => (int)$thread->id]
                    );

                    $body .= html_writer::link(
                        $url,
                        html_writer::span(
                            s($subject),
                            'crm-user360-n114b-thread-subject'
                        )
                        . html_writer::span(
                            !empty($thread->lastmessageat)
                                ? AdminFormatter::datetime((int)$thread->lastmessageat)
                                : '—',
                            'crm-user360-n114b-thread-date'
                        )
                        . html_writer::tag('i', '', [
                            'class' => 'fa fa-chevron-right',
                            'aria-hidden' => 'true',
                        ]),
                        ['class' => 'crm-user360-n114b-thread-row']
                    );
                }
            }
        } else {
            $body .= self::empty_text(
                get_string('crm_user360_n114b_no_conversations', 'local_subscriptions')
            );
        }

        $notes = array_slice($profile->notes ?? [], 0, 1);
        $body .= html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-sticky-note-o',
                    'aria-hidden' => 'true',
                ])
                . html_writer::tag(
                    'strong',
                    get_string('crm_user360_n114b_last_note', 'local_subscriptions')
                ),
                'crm-user360-n114b-subsection-heading'
            ),
            'crm-user360-n114b-subsection'
        );

        if ($notes === []) {
            $body .= self::empty_text(
                get_string('crm_user360_n114b_no_notes', 'local_subscriptions')
            );
        } else {
            $note = $notes[0];
            $body .= html_writer::div(
                html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string('crm_user360_n114b_internal_note', 'local_subscriptions'),
                        ['class' => 'crm-user360-n114b-note-title']
                    )
                    . html_writer::div(
                        s(self::excerpt((string)($note->note ?? $note->body ?? ''), 110)),
                        'crm-user360-n114b-note-copy'
                    ),
                    'crm-user360-n114b-note-main'
                )
                . html_writer::span(
                    !empty($note->timecreated)
                        ? AdminFormatter::datetime((int)$note->timecreated)
                        : '—',
                    'crm-user360-n114b-note-date'
                ),
                'crm-user360-n114b-note-row'
            );
        }

        $footer = '';
        if ($inbox !== null && !empty($inbox->available)) {
            $footer = html_writer::link(
                new \moodle_url(
                    subscription_config::admin_inbox_page(),
                    ['q' => (string)($profile->user->email ?? '')]
                ),
                get_string('crm_user360_n114b_open_exchanges', 'local_subscriptions') . ' →',
                ['class' => 'crm-user360-n114b-card-link']
            );
        }

        return self::card(
            'fa fa-envelope-o',
            get_string('crm_user360_n114_communication_title', 'local_subscriptions'),
            get_string('crm_user360_n114_communication_help', 'local_subscriptions'),
            $body,
            'communication',
            'blue',
            $footer
        );
    }

    private static function recent_notes(\stdClass $profile): string {
        $notes = array_slice($profile->notes ?? [], 0, 3);
        $body = '';

        if ($notes === []) {
            $body = self::empty_text(
                get_string('crm_user360_n114b_no_notes', 'local_subscriptions')
            );
        } else {
            foreach ($notes as $note) {
                $body .= html_writer::div(
                    html_writer::div(
                        html_writer::div(
                            html_writer::tag('i', '', [
                                'class' => 'fa fa-sticky-note-o',
                                'aria-hidden' => 'true',
                            ]),
                            'crm-user360-n114b-note-icon'
                        )
                        . html_writer::div(
                            html_writer::tag(
                                'strong',
                                s(self::note_type_label((string)($note->type ?? 'general'))),
                                ['class' => 'crm-user360-n114b-note-title']
                            )
                            . html_writer::div(
                                s(self::excerpt((string)($note->note ?? $note->body ?? ''), 105)),
                                'crm-user360-n114b-note-copy'
                            ),
                            'crm-user360-n114b-note-main'
                        ),
                        'crm-user360-n114b-note-left'
                    )
                    . html_writer::span(
                        !empty($note->timecreated)
                            ? AdminFormatter::datetime((int)$note->timecreated)
                            : '—',
                        'crm-user360-n114b-note-date'
                    ),
                    'crm-user360-n114b-note-row'
                );
            }
        }

        return self::card(
            'fa fa-sticky-note-o',
            get_string('crm_user360_n114b_recent_notes', 'local_subscriptions'),
            get_string('crm_user360_n114b_recent_notes_help', 'local_subscriptions'),
            $body,
            'notes',
            'orange',
            html_writer::link(
                self::advanced_url('relation'),
                get_string('crm_user360_n114b_view_all_notes', 'local_subscriptions') . ' →',
                ['class' => 'crm-user360-n114b-card-link']
            )
        );
    }

    private static function recent_activity(\stdClass $profile): string {
        $events = array_slice($profile->timeline ?? [], 0, 6);
        $body = '';

        if ($events === []) {
            $body = self::empty_text(
                get_string('crm_user360_n114_no_activity', 'local_subscriptions')
            );
        } else {
            foreach ($events as $event) {
                $category = self::event_category((string)($event->category ?? $event->source ?? ''));
                $body .= html_writer::div(
                    html_writer::span(
                        '',
                        'crm-user360-n114b-event-dot is-' . $category
                    )
                    . html_writer::span(
                        !empty($event->timecreated)
                            ? AdminFormatter::datetime((int)$event->timecreated)
                            : '—',
                        'crm-user360-n114b-event-date'
                    )
                    . html_writer::div(
                        html_writer::tag(
                            'strong',
                            s((string)($event->title ?? '—')),
                            ['class' => 'crm-user360-n114b-event-title']
                        ),
                        'crm-user360-n114b-event-copy'
                    ),
                    'crm-user360-n114b-event-row'
                );
            }
        }

        return self::card(
            'fa fa-clock-o',
            get_string('crm_user360_n114_recent_title', 'local_subscriptions'),
            get_string('crm_user360_n114_recent_help', 'local_subscriptions'),
            $body,
            'recent',
            'purple',
            html_writer::link(
                self::advanced_url('timeline'),
                get_string('crm_user360_n114b_view_all_activity', 'local_subscriptions') . ' →',
                ['class' => 'crm-user360-n114b-card-link']
            )
        );
    }

    private static function support_actions(\stdClass $profile): string {
        $user = $profile->user ?? (object)[];
        $email = trim((string)($user->email ?? ''));
        $items = [];

        if (
            empty($profile->iscommerceguest)
            && !empty($user->id)
            && Capabilities::can_manage_users()
        ) {
            $items[] = self::action_tile(
                new \moodle_url(
                    subscription_config::admin_user_email_page(),
                    ['id' => (int)$user->id]
                ),
                'fa fa-envelope-o',
                get_string('crm_user360_n114_send_email', 'local_subscriptions')
            );
        }

        if ($email !== '') {
            $items[] = self::action_tile(
                new \moodle_url(
                    '/local/subscriptions/admin/commerce/personal-offers/create.php',
                    ['prefillemail' => $email]
                ),
                'fa fa-tag',
                get_string('crm_user360_n114_create_offer', 'local_subscriptions')
            );

            if (Capabilities::can_view_inbox()) {
                $items[] = self::action_tile(
                    new \moodle_url(
                        subscription_config::admin_inbox_page(),
                        ['q' => $email]
                    ),
                    'fa fa-comments-o',
                    get_string('crm_user360_n114b_open_inbox', 'local_subscriptions')
                );
            }
        }

        if (empty($profile->iscommerceguest) && !empty($user->id)) {
            $items[] = self::action_tile(
                new \moodle_url('/user/profile.php', ['id' => (int)$user->id]),
                'fa fa-graduation-cap',
                get_string('view_moodle_profile', 'local_subscriptions')
            );
        }

        $content = html_writer::div(
            implode('', $items),
            'crm-user360-n114b-action-grid'
        );

        return self::card(
            'fa fa-bolt',
            get_string('crm_user360_n114_actions_title', 'local_subscriptions'),
            get_string('crm_user360_n114b_actions_help', 'local_subscriptions'),
            $content,
            'actions',
            'green',
            html_writer::link(
                self::advanced_url('relation'),
                get_string('crm_user360_n114b_all_actions', 'local_subscriptions') . ' →',
                ['class' => 'crm-user360-n114b-card-link']
            )
        );
    }

    private static function advanced_url(
        string $tab
    ): \moodle_url {
        global $PAGE;

        $url = new \moodle_url(
            $PAGE->url
        );

        $url->param(
            'advancedtab',
            $tab
        );

        $url->set_anchor(
            'crm-user360-advanced'
        );

        return $url;
    }

    private static function hero_metric(
        string $icon,
        string $value,
        string $label,
        string $sub,
        string $tone
    ): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]),
                'crm-user360-n114b-hero-kpi-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'strong',
                    s($value),
                    ['class' => 'crm-user360-n114b-hero-kpi-value']
                )
                . html_writer::span(
                    s($label),
                    'crm-user360-n114b-hero-kpi-label'
                )
                . ($sub !== ''
                    ? html_writer::span(
                        nl2br(s($sub)),
                        'crm-user360-n114b-hero-kpi-sub'
                    )
                    : ''),
                'crm-user360-n114b-hero-kpi-copy'
            ),
            'crm-user360-n114b-hero-kpi is-' . $tone
        );
    }

    private static function identity_badges(\stdClass $profile): string {
        $stats = $profile->stats ?? (object)[];
        $items = [];

        $status = (string)($stats->crmstatus ?? '');
        $statuslabels = [
            'active_customer' => 'crm_status_active_customer',
            'trial' => 'crm_status_trial',
            'former_customer' => 'crm_status_former_customer',
            'suspended' => 'crm_status_suspended',
            'lead' => 'crm_status_lead',
        ];
        $statuskey = $statuslabels[$status] ?? 'crm_status_unknown';

        $items[] = html_writer::span(
            get_string($statuskey, 'local_subscriptions'),
            'crm-user360-n114b-badge is-client'
        );

        if (!empty($profile->iscommerceguest)) {
            $items[] = html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-shopping-bag',
                    'aria-hidden' => 'true',
                ])
                . get_string('crm_no_moodle_account', 'local_subscriptions'),
                'crm-user360-n114b-badge is-commerce'
            );
        } else {
            $items[] = html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-graduation-cap',
                    'aria-hidden' => 'true',
                ])
                . get_string('crm_user360_n113_moodle_account', 'local_subscriptions'),
                'crm-user360-n114b-badge is-moodle'
            );
        }

        return implode('', $items);
    }

    private static function identity_meta(\stdClass $profile): string {
        $user = $profile->user ?? (object)[];
        $stats = $profile->stats ?? (object)[];
        $parts = [];

        if (!empty($user->country)) {
            $parts[] = '🌍 ' . s((string)$user->country);
        }

        if (empty($profile->iscommerceguest) && !empty($user->id)) {
            $parts[] = '#' . (int)$user->id;
        }

        if (!empty($user->timecreated)) {
            $parts[] = get_string('crm_user360_n113_created', 'local_subscriptions')
                . ' ' . AdminFormatter::date((int)$user->timecreated);
        }

        if (!empty($stats->lastactivity)) {
            $parts[] = get_string('crm_last_activity', 'local_subscriptions')
                . ' ' . AdminFormatter::datetime((int)$stats->lastactivity);
        }

        return implode(
            html_writer::span('·', 'crm-user360-n114b-meta-separator'),
            array_map(
                static fn(string $part): string =>
                    html_writer::span($part, 'crm-user360-n114b-meta-item'),
                $parts
            )
        );
    }

    private static function card(
        string $icon,
        string $title,
        string $help,
        string $content,
        string $key,
        string $tone,
        string $footer = ''
    ): string {
        $heading = html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]),
                'crm-user360-n114b-card-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'h2',
                    s($title),
                    ['class' => 'crm-user360-n114b-card-title']
                )
                . ($help !== ''
                    ? html_writer::div(
                        s($help),
                        'crm-user360-n114b-card-help'
                    )
                    : ''),
                'crm-user360-n114b-card-heading-copy'
            ),
            'crm-user360-n114b-card-heading'
        );

        return html_writer::tag(
            'section',
            $heading
            . html_writer::div(
                $content,
                'crm-user360-n114b-card-body'
            )
            . ($footer !== ''
                ? html_writer::div(
                    $footer,
                    'crm-user360-n114b-card-footer'
                )
                : ''),
            [
                'class' =>
                    'crm-user360-n114b-card ' .
                    'crm-user360-n114b-' . $key . ' ' .
                    'is-' . $tone,
            ]
        );
    }

    private static function action_tile(
        \moodle_url $url,
        string $icon,
        string $label
    ): string {
        return html_writer::link(
            $url,
            html_writer::tag('i', '', [
                'class' => $icon,
                'aria-hidden' => 'true',
            ])
            . html_writer::span(s($label)),
            ['class' => 'crm-user360-n114b-action-tile']
        );
    }

    private static function learning_summary(array $courses): string {
        if ($courses === []) {
            return '';
        }

        $completed = 0;
        $tracked = 0;
        $percentages = [];
        $xp = self::xp_summary($courses);

        foreach ($courses as $course) {
            $completed += (int)($course->completedactivities ?? 0);
            $tracked += (int)($course->trackedactivities ?? 0);
            if ($course->progresspercentage !== null) {
                $percentages[] = (float)$course->progresspercentage;
            }
        }

        $average = $percentages !== []
            ? round(array_sum($percentages) / count($percentages))
            : 0;

        $items = [
            [
                get_string('crm_user360_n114b_total_activities', 'local_subscriptions'),
                $completed . ' / ' . $tracked,
            ],
            [
                get_string('crm_user360_n114b_average_progress', 'local_subscriptions'),
                $average . '%',
            ],
            [
                get_string('crm_user360_n114b_xp_total', 'local_subscriptions'),
                $xp['value'],
            ],
        ];

        $out = '';
        foreach ($items as [$label, $value]) {
            $out .= html_writer::div(
                html_writer::span(
                    s($label),
                    'crm-user360-n114b-learning-summary-label'
                )
                . html_writer::tag(
                    'strong',
                    s($value),
                    ['class' => 'crm-user360-n114b-learning-summary-value']
                ),
                'crm-user360-n114b-learning-summary-item'
            );
        }

        return html_writer::div(
            $out,
            'crm-user360-n114b-learning-summary'
        );
    }

    private static function learning_counts(array $learning): array {
        $started = 0;
        $completed = 0;

        foreach ($learning as $course) {
            if ((int)($course->completedactivities ?? 0) > 0) {
                $started++;
            }
            if (!empty($course->coursecompleted)) {
                $completed++;
            }
        }

        return [$started, $completed];
    }

    private static function xp_summary(array $learning): array {
        $site = null;
        $sum = 0;
        $maxlevel = 0;
        $enabledcourses = 0;

        foreach ($learning as $course) {
            if (empty($course->xpenabled)) {
                continue;
            }

            $enabledcourses++;
            $maxlevel = max($maxlevel, (int)($course->xplevel ?? 0));

            if ((string)($course->xpscope ?? '') === 'site') {
                if ($site === null) {
                    $site = $course;
                }
                continue;
            }

            $sum += (int)($course->xp ?? 0);
        }

        if ($site !== null) {
            return [
                'value' => number_format((int)($site->xp ?? 0), 0, ',', ' ') . ' XP',
                'sub' => (int)($site->xplevel ?? 0) > 0
                    ? get_string(
                        'crm_user360_n114_xp_level',
                        'local_subscriptions',
                        (int)$site->xplevel
                    )
                    : get_string('crm_user360_n114_xp_site_scope', 'local_subscriptions'),
            ];
        }

        return [
            'value' => number_format($sum, 0, ',', ' ') . ' XP',
            'sub' => $enabledcourses > 0
                ? get_string(
                    'crm_user360_n114b_xp_courses_sub',
                    'local_subscriptions',
                    $enabledcourses
                )
                : '—',
        ];
    }


    private static function progress_tone(float $percentage): string {
        if ($percentage >= 70) {
            return 'is-high';
        }
        if ($percentage > 0) {
            return 'is-medium';
        }
        return 'is-zero';
    }

    private static function purchase_label_link(
        \stdClass $purchase,
        string $label
    ): string {
        $url = self::purchase_target_url($purchase);

        if ($url === null) {
            return html_writer::tag(
                'strong',
                s($label),
                ['class' => 'crm-user360-n114b-item-title']
            );
        }

        return html_writer::link(
            $url,
            s($label),
            ['class' => 'crm-user360-n114b-item-title']
        );
    }

    private static function purchase_target_url(
        \stdClass $purchase
    ): ?\moodle_url {
        $items = $purchase->items ?? [];

        if (is_array($items) && $items !== []) {
            $item = reset($items);

            if (is_array($item)) {
                $metadata = is_array($item['metadata'] ?? null)
                    ? $item['metadata']
                    : [];
                $fulfillment = is_array($item['fulfillment'] ?? null)
                    ? $item['fulfillment']
                    : [];

                $courseid = (int)(
                    $fulfillment['courseid']
                    ?? $metadata['courseid']
                    ?? 0
                );

                if ($courseid > 0) {
                    return new \moodle_url(
                        '/course/view.php',
                        ['id' => $courseid]
                    );
                }

                $reference = trim(
                    (string)($item['reference'] ?? '')
                );

                if ($reference !== '') {
                    return new \moodle_url(
                        '/local/subscriptions/admin/commerce/products/view.php',
                        ['sku' => $reference]
                    );
                }
            }
        }

        $orderurl = trim((string)($purchase->orderurl ?? ''));
        if ($orderurl !== '') {
            return new \moodle_url($orderurl);
        }

        return null;
    }

    private static function purchase_icon(string $type): string {
        $type = strtolower($type);
        $icon = str_contains($type, 'digital') || str_contains($type, 'pdf')
            ? 'fa fa-file-text-o'
            : (str_contains($type, 'subscription')
                ? 'fa fa-calendar'
                : 'fa fa-graduation-cap');

        return html_writer::div(
            html_writer::tag('i', '', [
                'class' => $icon,
                'aria-hidden' => 'true',
            ]),
            'crm-user360-n114b-purchase-icon'
        );
    }

    private static function purchase_status_class(string $status): string {
        $status = strtolower(trim($status));
        return match ($status) {
            'paid', 'completed', 'delivered', 'fulfilled' => 'is-success',
            'failed', 'cancelled', 'canceled' => 'is-danger',
            'pending' => 'is-warning',
            default => 'is-neutral',
        };
    }

    private static function purchase_status(string $status): string {
        $status = strtolower(trim($status));
        return match ($status) {
            'paid', 'completed', 'delivered', 'fulfilled' =>
                get_string('crm_user360_n114_status_paid', 'local_subscriptions'),
            'failed', 'cancelled', 'canceled' =>
                get_string('crm_user360_n114_status_failed', 'local_subscriptions'),
            'pending' =>
                get_string('crm_user360_n114_status_pending', 'local_subscriptions'),
            default => $status !== '' ? $status : '—',
        };
    }

    private static function event_category(string $value): string {
        $value = strtolower($value);
        if (str_contains($value, 'inbox') || str_contains($value, 'mail')) {
            return 'inbox';
        }
        if (str_contains($value, 'commerce') || str_contains($value, 'payment')) {
            return 'commerce';
        }
        if (str_contains($value, 'course') || str_contains($value, 'moodle')) {
            return 'learning';
        }
        if (str_contains($value, 'note')) {
            return 'note';
        }
        return 'default';
    }

    private static function note_type_label(string $type): string {
        $type = trim($type);
        return $type !== ''
            ? \core_text::strtoupper($type)
            : get_string('crm_user360_n114b_internal_note', 'local_subscriptions');
    }

    private static function excerpt(string $text, int $limit): string {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if (\core_text::strlen($text) <= $limit) {
            return $text;
        }
        return \core_text::substr($text, 0, $limit - 1) . '…';
    }

    private static function empty_text(string $text): string {
        return html_writer::div(
            s($text),
            'crm-user360-n114b-empty'
        );
    }

    private static function money(float $amount, string $currency): string {
        return number_format($amount, 2, ',', ' ')
            . ($currency !== '' ? ' ' . $currency : '');
    }

    private static function spent_multiline(\stdClass $stats): string {
        $parts = [];
        if (!empty($stats->spent_eur)) {
            $parts[] = number_format((float)$stats->spent_eur, 2, ',', ' ') . ' EUR';
        }
        if (!empty($stats->spent_rub)) {
            $parts[] = number_format((float)$stats->spent_rub, 2, ',', ' ') . ' RUB';
        }
        return implode("\n", $parts);
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
