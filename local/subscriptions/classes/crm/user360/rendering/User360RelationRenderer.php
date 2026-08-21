<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\output\UserProfileRenderer;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * N11.5B — Advanced Relation CRM dashboard.
 *
 * This screen is intentionally different from the support-first customer view:
 * it exposes analytical and expert CRM information, but keeps every domain
 * visually identifiable and proportionate to its actual usefulness.
 */
final class User360RelationRenderer {

    public static function render(\stdClass $profile): string {
        if (!empty($profile->iscommerceguest)) {
            return '';
        }

        return html_writer::tag(
            'section',
            self::intelligence_dashboard($profile)
            . html_writer::div(
                html_writer::div(
                    self::actions($profile)
                    . self::inbox($profile)
                    . self::work_items($profile)
                    . self::customer_success($profile),
                    'crm-user360-n115e-side-column'
                )
                . html_writer::div(
                    self::assistant($profile),
                    'crm-user360-n115e-assistant-column'
                ),
                'crm-user360-n115e-main-grid'
            ),
            [
                'id' => 'user360-relation',
                'class' => 'crm-user360-n115b-relation',
            ]
        );
    }

    private static function intelligence_dashboard(\stdClass $profile): string {
        $intelligence = $profile->intelligence ?? null;
        $score = $intelligence->leadscore ?? null;

        if ($score === null) {
            return self::card(
                'fa fa-line-chart',
                get_string(
                    'crm_user360_n115a_intelligence_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user360_n115a_intelligence_help',
                    'local_subscriptions'
                ),
                self::empty_state(
                    get_string(
                        'crm_user360_n115b_no_intelligence',
                        'local_subscriptions'
                    )
                ),
                'intelligence',
                'pink'
            );
        }

        $scoreglobal = max(0, min(100, (int)($score->global ?? 0)));
        $level = self::intelligence_level(
            (string)($score->level ?? 'very_low')
        );
        $summary = self::intelligence_summary(
            (string)($score->level ?? 'very_low')
        );

        $primary = html_writer::div(
            html_writer::div(
                html_writer::div(
                    html_writer::span(
                        (string)$scoreglobal,
                        'crm-user360-n115b-score-ring-value'
                    )
                    . html_writer::span(
                        '/100',
                        'crm-user360-n115b-score-ring-total'
                    ),
                    'crm-user360-n115b-score-ring'
                )
                . html_writer::div(
                    html_writer::span(
                        get_string(
                            'crm_intelligence_global_score',
                            'local_subscriptions'
                        ),
                        'crm-user360-n115b-score-label'
                    )
                    . html_writer::tag(
                        'strong',
                        s($level),
                        ['class' => 'crm-user360-n115b-score-level']
                    ),
                    'crm-user360-n115b-score-copy'
                ),
                'crm-user360-n115b-global-score'
            )
            . html_writer::div(
                self::score_tile(
                    '💼',
                    (int)($score->commercial ?? 0),
                    get_string(
                        'crm_intelligence_commercial_score',
                        'local_subscriptions'
                    )
                )
                . self::score_tile(
                    '⚡',
                    (int)($score->engagement ?? 0),
                    get_string(
                        'crm_intelligence_engagement_score',
                        'local_subscriptions'
                    )
                )
                . self::score_tile(
                    '⚠️',
                    (int)($score->risk ?? 0),
                    get_string(
                        'crm_intelligence_risk_score',
                        'local_subscriptions'
                    )
                ),
                'crm-user360-n115b-score-tiles'
            )
            . self::trend($intelligence->trend ?? null)
            . ($summary !== ''
                ? html_writer::div(
                    s($summary),
                    'crm-user360-n115b-intelligence-summary'
                )
                : ''),
            'crm-user360-n115b-intelligence-main'
        );

        $why = self::card(
            'fa fa-question-circle',
            get_string(
                'crm_user360_n115b_why_score',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_why_score_help',
                'local_subscriptions'
            ),
            self::explanations(
                $intelligence->explanations ?? [],
                $score->reasons ?? []
            ),
            'why-score',
            'orange',
            true
        );

        $signals = self::card(
            'fa fa-code-fork',
            get_string(
                'crm_user360_n115b_segments_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_segments_help',
                'local_subscriptions'
            ),
            self::badges(
                $intelligence->segments ?? [],
                'crm_intelligence_segment_'
            )
            . self::recommendation_list(
                $intelligence->recommendations ?? []
            ),
            'signals',
            'blue',
            true
        );

        return html_writer::div(
            self::card(
                'fa fa-line-chart',
                get_string(
                    'crm_user360_n115a_intelligence_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_user360_n115b_intelligence_dashboard_help',
                    'local_subscriptions'
                ),
                $primary,
                'intelligence',
                'pink',
                true
            )
            . $why
            . $signals,
            'crm-user360-n115b-intelligence-grid'
        );
    }

    private static function actions(\stdClass $profile): string {
        $content = self::expert_actions(
            $profile
        );

        return self::card(
            'fa fa-bolt',
            get_string(
                'crm_user360_n113c_actions',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_actions_help',
                'local_subscriptions'
            ),
            $content,
            'actions',
            'violet'
        );
    }

    private static function inbox(\stdClass $profile): string {
        if (!Capabilities::can_view_inbox()) {
            return '';
        }

        $inbox = $profile->inbox ?? null;
        $content = '';

        if ($inbox === null || empty($inbox->available)) {
            $content = self::empty_state(
                get_string(
                    'crm_user360_n115b_inbox_unavailable',
                    'local_subscriptions'
                )
            );
        } else {
            $threads = array_slice(
                $inbox->recentthreads ?? [],
                0,
                3
            );

            if ($threads === []) {
                $content = self::empty_state(
                    get_string(
                        'crm_user360_n114b_no_conversations',
                        'local_subscriptions'
                    )
                );
            } else {
                foreach ($threads as $thread) {
                    $subject = trim(
                        (string)(
                            $thread->lastmessagesubject
                            ?? $thread->subject
                            ?? ''
                        )
                    );
                    if ($subject === '') {
                        $subject = get_string(
                            'crm_inbox_no_subject',
                            'local_subscriptions'
                        );
                    }

                    $content .= html_writer::link(
                        new moodle_url(
                            subscription_config::
                                admin_inbox_thread_page(),
                            ['id' => (int)$thread->id]
                        ),
                        html_writer::span(
                            s($subject),
                            'crm-user360-n115b-thread-title'
                        )
                        . html_writer::span(
                            !empty($thread->lastmessageat)
                                ? AdminFormatter::datetime(
                                    (int)$thread->lastmessageat
                                )
                                : '—',
                            'crm-user360-n115b-thread-date'
                        )
                        . html_writer::span(
                            !empty($thread->unreadcount)
                                ? get_string(
                                    'crm_user360_n114b_unread_badge',
                                    'local_subscriptions',
                                    (int)$thread->unreadcount
                                )
                                : '',
                            'crm-user360-n115b-thread-unread'
                        ),
                        ['class' => 'crm-user360-n115b-thread']
                    );
                }
            }
        }

        $footer = '';
        if (
            $inbox !== null
            && !empty($inbox->available)
        ) {
            $footer = html_writer::link(
                new moodle_url(
                    subscription_config::admin_inbox_page(),
                    [
                        'q' =>
                            (string)($profile->user->email ?? ''),
                    ]
                ),
                get_string(
                    'crm_user360_n114b_open_exchanges',
                    'local_subscriptions'
                )
                . ' →',
                ['class' => 'crm-user360-n115b-card-link']
            );
        }

        return self::card(
            'fa fa-inbox',
            get_string(
                'crm_user360_n113c_inbox',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_inbox_help',
                'local_subscriptions'
            ),
            $content,
            'inbox',
            'blue',
            false,
            $footer
        );
    }

    private static function expert_actions(
        \stdClass $profile
    ): string {
        $items = '';

        foreach ($profile->actions ?? [] as $action) {
            $key = (string)($action->key ?? '');

            // Already covered by the support-first "Actions rapides".
            if (in_array($key, ['email', 'note'], true)) {
                continue;
            }

            $icon = match (true) {
                $key === 'changeemail' =>
                    'fa fa-at',

                $key === 'resetpassword' =>
                    'fa fa-key',

                default =>
                    'fa fa-wrench',
            };

            $help = match (true) {
                $key === 'changeemail' =>
                    get_string(
                        'crm_user360_n115c_action_change_email_help',
                        'local_subscriptions'
                    ),

                $key === 'resetpassword' =>
                    get_string(
                        'crm_user360_n115c_action_reset_password_help',
                        'local_subscriptions'
                    ),

                default =>
                    get_string(
                        'crm_user360_n115c_action_expert_help',
                        'local_subscriptions'
                    ),
            };

            $items .= html_writer::link(
                new moodle_url(
                    (string)$action->url
                ),
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => $icon,
                        'aria-hidden' => 'true',
                    ]),
                    'crm-user360-n115c-action-icon'
                )
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        s((string)$action->label),
                        [
                            'class' =>
                                'crm-user360-n115c-action-label',
                        ]
                    )
                    . html_writer::span(
                        s($help),
                        'crm-user360-n115c-action-help'
                    ),
                    'crm-user360-n115c-action-copy'
                )
                . html_writer::tag('i', '', [
                    'class' => 'fa fa-chevron-right',
                    'aria-hidden' => 'true',
                ]),
                [
                    'class' =>
                        'crm-user360-n115c-action-row'
                        . (!empty($action->danger)
                            ? ' is-sensitive'
                            : ''),
                ]
            );
        }

        return $items !== ''
            ? $items
            : self::empty_state(
                get_string(
                    'crm_user360_n115c_no_expert_actions',
                    'local_subscriptions'
                )
            );
    }

    private static function assistant(\stdClass $profile): string {
        $recommendations =
            UserProfileRenderer::
                render_assistant_recommendations_content(
                    $profile,
                    2
                );

        $conversation =
            UserProfileRenderer::
                render_assistant_conversation_content(
                    $profile
                );

        if (
            $recommendations === ''
            && $conversation === ''
        ) {
            return '';
        }

        $content = html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'h4',
                    get_string(
                        'crm_user360_n115d_assistant_recommendations',
                        'local_subscriptions'
                    ),
                    [
                        'class' =>
                            'crm-user360-n115d-assistant-column-title',
                    ]
                )
                . ($recommendations !== ''
                    ? $recommendations
                    : self::empty_state(
                        get_string(
                            'crm_user360_n115d_no_assistant_recommendations',
                            'local_subscriptions'
                        )
                    )),
                'crm-user360-n115d-assistant-recommendations'
            )
            . html_writer::div(
                html_writer::tag(
                    'h4',
                    get_string(
                        'crm_user360_n115d_assistant_question',
                        'local_subscriptions'
                    ),
                    [
                        'class' =>
                            'crm-user360-n115d-assistant-column-title',
                    ]
                )
                . ($conversation !== ''
                    ? $conversation
                    : self::empty_state(
                        get_string(
                            'crm_user360_n115d_assistant_unavailable',
                            'local_subscriptions'
                        )
                    )),
                'crm-user360-n115d-assistant-question'
            ),
            'crm-user360-n115d-assistant-layout'
        );

        return self::card(
            'fa fa-magic',
            get_string(
                'crm_user360_n113c_assistant',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_assistant_help',
                'local_subscriptions'
            ),
            $content,
            'assistant',
            'purple'
        );
    }

    private static function work_items(\stdClass $profile): string {
        $content = UserProfileRenderer::render_work_items_panel(
            $profile
        );

        return self::card(
            'fa fa-check-square-o',
            get_string(
                'crm_user360_n113c_work_items',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_workitems_help',
                'local_subscriptions'
            ),
            $content,
            'work-items',
            'blue'
        );
    }

    private static function customer_success(\stdClass $profile): string {
        $content = UserProfileRenderer::render_customer_success_panel(
            $profile
        );

        if ($content === '') {
            $content = self::empty_state(
                get_string(
                    'crm_user360_n115b_no_customer_success',
                    'local_subscriptions'
                )
            );
        }

        return self::card(
            'fa fa-bullseye',
            get_string(
                'crm_user360_n113c_customer_success',
                'local_subscriptions'
            ),
            get_string(
                'crm_user360_n115b_customer_success_help',
                'local_subscriptions'
            ),
            $content,
            'customer-success',
            'green'
        );
    }

    private static function score_tile(
        string $icon,
        int $value,
        string $label
    ): string {
        return html_writer::div(
            html_writer::span(
                $icon,
                'crm-user360-n115b-score-icon'
            )
            . html_writer::tag(
                'strong',
                s($value . '/100'),
                ['class' => 'crm-user360-n115b-score-value']
            )
            . html_writer::span(
                s($label),
                'crm-user360-n115b-score-tile-label'
            ),
            'crm-user360-n115b-score-tile'
        );
    }

    private static function trend(?\stdClass $trend): string {
        if ($trend === null) {
            return '';
        }

        $direction = (string)($trend->direction ?? 'stable');
        $delta = (int)($trend->delta ?? 0);

        $key =
            'crm_trend_direction_'
            . clean_param(
                $direction,
                PARAM_ALPHANUMEXT
            );

        $label = get_string_manager()->string_exists(
            $key,
            'local_subscriptions'
        )
            ? get_string($key, 'local_subscriptions')
            : $direction;

        return html_writer::div(
            html_writer::span(
                get_string(
                    'crm_trend_label',
                    'local_subscriptions'
                ),
                'crm-user360-n115b-trend-label'
            )
            . html_writer::span(
                s($label),
                'crm-user360-n115b-trend-badge'
            )
            . html_writer::span(
                s(
                    $delta > 0
                        ? '+' . $delta
                        : (string)$delta
                ),
                'crm-user360-n115b-trend-delta'
            ),
            'crm-user360-n115b-trend'
        );
    }

    private static function explanations(
        array $explanations,
        array $reasons
    ): string {
        $content = '';

        foreach (
            array_slice($explanations, 0, 6)
            as $explanation
        ) {
            $key =
                'crm_explanation_'
                . clean_param(
                    (string)($explanation->key ?? ''),
                    PARAM_ALPHANUMEXT
                );

            $label = get_string_manager()->string_exists(
                $key,
                'local_subscriptions'
            )
                ? get_string(
                    $key,
                    'local_subscriptions'
                )
                : (string)($explanation->key ?? '');

            $impact = (int)($explanation->impact ?? 0);

            $content .= html_writer::div(
                html_writer::span(
                    s($label),
                    'crm-user360-n115b-factor-label'
                )
                . html_writer::span(
                    s(
                        $impact > 0
                            ? '+' . $impact
                            : (string)$impact
                    ),
                    'crm-user360-n115b-factor-impact '
                    . ($impact >= 0
                        ? 'is-positive'
                        : 'is-negative')
                ),
                'crm-user360-n115b-factor'
            );
        }

        if (
            $content === ''
            && $reasons !== []
        ) {
            foreach (
                array_slice($reasons, 0, 6)
                as $reason
            ) {
                $key =
                    'crm_intelligence_reason_'
                    . clean_param(
                        (string)$reason,
                        PARAM_ALPHANUMEXT
                    );

                $label = get_string_manager()->string_exists(
                    $key,
                    'local_subscriptions'
                )
                    ? get_string(
                        $key,
                        'local_subscriptions'
                    )
                    : (string)$reason;

                $content .= html_writer::div(
                    html_writer::span(
                        s($label),
                        'crm-user360-n115b-factor-label'
                    ),
                    'crm-user360-n115b-factor'
                );
            }
        }

        return $content !== ''
            ? $content
            : self::empty_state(
                get_string(
                    'crm_user360_n115b_no_score_factors',
                    'local_subscriptions'
                )
            );
    }

    private static function badges(
        array $items,
        string $prefix
    ): string {
        if ($items === []) {
            return self::empty_state(
                get_string(
                    'crm_user360_n115b_no_segments',
                    'local_subscriptions'
                )
            );
        }

        $content = '';

        foreach ($items as $item) {
            $key =
                $prefix
                . clean_param(
                    (string)($item->key ?? ''),
                    PARAM_ALPHANUMEXT
                );

            $label = get_string_manager()->string_exists(
                $key,
                'local_subscriptions'
            )
                ? get_string(
                    $key,
                    'local_subscriptions'
                )
                : (string)($item->key ?? '');

            $content .= html_writer::span(
                s($label),
                'crm-user360-n115b-signal-badge'
            );
        }

        return html_writer::div(
            $content,
            'crm-user360-n115b-signal-badges'
        );
    }

    private static function recommendation_list(
        array $items
    ): string {
        if ($items === []) {
            return '';
        }

        $content = html_writer::tag(
            'h4',
            get_string(
                'crm_user360_n115b_recommendations_priority',
                'local_subscriptions'
            ),
            ['class' => 'crm-user360-n115b-subtitle']
        );

        foreach (array_slice($items, 0, 3) as $item) {
            $raw = (string)($item->key ?? '');
            $key =
                'crm_intelligence_recommendation_'
                . clean_param(
                    $raw,
                    PARAM_ALPHANUMEXT
                );

            if (
                get_string_manager()->string_exists(
                    $key,
                    'local_subscriptions'
                )
            ) {
                $label = get_string(
                    $key,
                    'local_subscriptions'
                );
            } else {
                $label = \core_text::strtotitle(
                    str_replace(
                        '_',
                        ' ',
                        $raw
                    )
                );
            }

            $content .= html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-lightbulb-o',
                        'aria-hidden' => 'true',
                    ]),
                    'crm-user360-n115c-recommendation-icon'
                )
                . html_writer::span(
                    s($label),
                    'crm-user360-n115c-recommendation-label'
                ),
                'crm-user360-n115c-recommendation-row'
            );
        }

        return html_writer::div(
            $content,
            'crm-user360-n115c-recommendations'
        );
    }

    private static function badge_group(
        string $title,
        array $items,
        string $prefix
    ): string {
        if ($items === []) {
            return '';
        }

        return html_writer::div(
            html_writer::tag(
                'h4',
                s($title),
                ['class' => 'crm-user360-n115b-subtitle']
            )
            . self::badges(
                $items,
                $prefix
            ),
            'crm-user360-n115b-signal-group'
        );
    }

    private static function intelligence_level(
        string $level
    ): string {
        $key =
            'crm_intelligence_level_'
            . clean_param(
                $level,
                PARAM_ALPHANUMEXT
            );

        return get_string_manager()->string_exists(
            $key,
            'local_subscriptions'
        )
            ? get_string(
                $key,
                'local_subscriptions'
            )
            : get_string(
                'crm_intelligence_level_very_low',
                'local_subscriptions'
            );
    }

    private static function intelligence_summary(
        string $level
    ): string {
        $key =
            'crm_intelligence_summary_'
            . clean_param(
                $level,
                PARAM_ALPHANUMEXT
            );

        return get_string_manager()->string_exists(
            $key,
            'local_subscriptions'
        )
            ? get_string(
                $key,
                'local_subscriptions'
            )
            : '';
    }

    private static function card(
        string $icon,
        string $title,
        string $help,
        string $content,
        string $key,
        string $tone,
        bool $compact = false,
        string $footer = ''
    ): string {
        if (trim($content) === '') {
            return '';
        }

        $class =
            'crm-user360-n115b-card '
            . 'crm-user360-n115b-' . $key
            . ' is-' . $tone;

        if ($compact) {
            $class .= ' is-compact';
        }

        return html_writer::tag(
            'section',
            html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => $icon,
                        'aria-hidden' => 'true',
                    ]),
                    'crm-user360-n115b-card-icon'
                )
                . html_writer::div(
                    html_writer::tag(
                        'h3',
                        s($title),
                        ['class' => 'crm-user360-n115b-card-title']
                    )
                    . html_writer::div(
                        s($help),
                        'crm-user360-n115b-card-help'
                    ),
                    'crm-user360-n115b-card-heading-copy'
                ),
                'crm-user360-n115b-card-heading'
            )
            . html_writer::div(
                $content,
                'crm-user360-n115b-card-body'
            )
            . ($footer !== ''
                ? html_writer::div(
                    $footer,
                    'crm-user360-n115b-card-footer'
                )
                : ''),
            ['class' => $class]
        );
    }

    private static function empty_state(
        string $text
    ): string {
        return html_writer::div(
            s($text),
            'crm-user360-n115b-empty'
        );
    }

    private static function excerpt(
        string $text,
        int $limit
    ): string {
        $text = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($text)
            )
            ?? ''
        );

        if (\core_text::strlen($text) <= $limit) {
            return $text;
        }

        return \core_text::substr(
            $text,
            0,
            $limit - 1
        ) . '…';
    }
}
