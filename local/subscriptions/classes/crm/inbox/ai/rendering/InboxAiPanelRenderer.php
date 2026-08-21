<?php

namespace local_subscriptions\crm\inbox\ai\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\subscription_config;

final class InboxAiPanelRenderer {

    public static function render(
        int $threadid,
        ?array $result,
        bool $canuseai,
        bool $canmanageinbox
    ): string {
        $headingid = 'crm-inbox-ai-panel-title-' . $threadid;

        $out = html_writer::start_tag(
            'section',
            [
                'id' => 'crm-inbox-ai',
                'class' => 'crm-inbox-ai-panel',
                'aria-labelledby' => $headingid,
            ]
        );

        $out .= html_writer::div(
            html_writer::div(
                html_writer::span(
                    html_writer::tag(
                        'i',
                        '',
                        [
                            'class' => 'fa fa-magic',
                            'aria-hidden' => 'true',
                        ]
                    ),
                    'crm-inbox-ai-panel-icon'
                )
                . html_writer::div(
                    html_writer::tag(
                        'h2',
                        get_string(
                            'crm_inbox_ai_panel_title',
                            'local_subscriptions'
                        ),
                        [
                            'id' => $headingid,
                            'class' => 'crm-inbox-ai-panel-title',
                        ]
                    )
                    . html_writer::div(
                        get_string(
                            'crm_inbox_ai_panel_description_n124',
                            'local_subscriptions'
                        ),
                        'crm-inbox-ai-panel-description'
                    ),
                    'crm-inbox-ai-panel-heading-copy'
                ),
                'crm-inbox-ai-panel-heading'
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_ai_human_review_badge',
                    'local_subscriptions'
                ),
                'badge bg-warning text-dark'
            ),
            'crm-inbox-ai-panel-header'
        );

        $out .= html_writer::div(
            '',
            'visually-hidden',
            [
                'role' => 'status',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
                'data-inbox-live-region' => '1',
            ]
        );

        if (!$canuseai) {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_ai_permission_required',
                    'local_subscriptions'
                ),
                'alert alert-light border m-3'
            );
            $out .= html_writer::end_tag('section');
            return $out;
        }

        $out .= html_writer::div(
            self::translation_action($threadid)
            . self::analysis_action($threadid)
            . (
                $canmanageinbox
                    ? self::reply_action($threadid)
                    : ''
            ),
            'crm-inbox-ai-tools'
        );

        $out .= html_writer::div(
            $result !== null
                ? self::result($result)
                : self::empty_state(),
            'crm-inbox-ai-output'
        );

        $out .= html_writer::end_tag('section');

        return $out;
    }

    private static function translation_action(
        int $threadid
    ): string {
        return self::tool_card(
            'fa fa-language',
            get_string(
                'crm_inbox_ai_translate_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_inbox_ai_translate_help',
                'local_subscriptions'
            ),
            self::action_form(
                $threadid,
                'translate',
                get_string(
                    'crm_inbox_ai_translate_action',
                    'local_subscriptions'
                ),
                true,
                false
            )
        );
    }

    private static function analysis_action(
        int $threadid
    ): string {
        return self::tool_card(
            'fa fa-search',
            get_string(
                'crm_inbox_ai_analysis_title_n124',
                'local_subscriptions'
            ),
            get_string(
                'crm_inbox_ai_analysis_help_n124',
                'local_subscriptions'
            ),
            self::action_form(
                $threadid,
                'analyse',
                get_string(
                    'crm_inbox_ai_analyse',
                    'local_subscriptions'
                ),
                true,
                false
            )
        );
    }

    private static function reply_action(
        int $threadid
    ): string {
        return self::tool_card(
            'fa fa-reply',
            get_string(
                'crm_inbox_ai_reply_title_n124',
                'local_subscriptions'
            ),
            get_string(
                'crm_inbox_ai_reply_help_n124',
                'local_subscriptions'
            ),
            self::action_form(
                $threadid,
                'reply',
                get_string(
                    'crm_inbox_ai_suggest_reply',
                    'local_subscriptions'
                ),
                true,
                true
            )
        );
    }

    private static function tool_card(
        string $icon,
        string $title,
        string $help,
        string $form
    ): string {
        return html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => $icon,
                        'aria-hidden' => 'true',
                    ]
                ),
                'crm-inbox-ai-tool-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'h3',
                    s($title),
                    ['class' => 'crm-inbox-ai-tool-title']
                )
                . html_writer::div(
                    s($help),
                    'crm-inbox-ai-tool-help'
                )
                . $form,
                'crm-inbox-ai-tool-copy'
            ),
            'crm-inbox-ai-tool'
        );
    }

    private static function action_form(
        int $threadid,
        string $action,
        string $label,
        bool $showlanguage,
        bool $showtone
    ): string {
        $out = html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' =>
                    subscription_config::
                        admin_inbox_ai_action_page(),
                'class' => 'crm-inbox-ai-action-form',
                'data-inbox-busy-form' => '1',
                'data-busy-announcement' =>
                    get_string(
                        'crm_inbox_ai_processing_n124',
                        'local_subscriptions'
                    ),
            ]
        );

        foreach ([
            'sesskey' => sesskey(),
            'threadid' => $threadid,
            'action' => $action,
        ] as $name => $value) {
            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value,
                ]
            );
        }

        if ($showlanguage) {
            $out .= html_writer::select(
                [
                    'fr' => 'Français',
                    'en' => 'English',
                    'ru' => 'Русский',
                ],
                'language',
                'fr',
                false,
                [
                    'class' =>
                        'form-select form-select-sm '
                        . 'crm-inbox-ai-language',
                    'aria-label' =>
                        get_string(
                            'crm_inbox_ai_reply_language',
                            'local_subscriptions'
                        ),
                ]
            );
        }

        if ($showtone) {
            $out .= html_writer::select(
                [
                    'professional' =>
                        get_string(
                            'crm_inbox_ai_tone_professional',
                            'local_subscriptions'
                        ),
                    'friendly' =>
                        get_string(
                            'crm_inbox_ai_tone_friendly',
                            'local_subscriptions'
                        ),
                    'empathetic' =>
                        get_string(
                            'crm_inbox_ai_tone_empathetic',
                            'local_subscriptions'
                        ),
                    'concise' =>
                        get_string(
                            'crm_inbox_ai_tone_concise',
                            'local_subscriptions'
                        ),
                ],
                'tone',
                'professional',
                false,
                [
                    'class' =>
                        'form-select form-select-sm '
                        . 'crm-inbox-ai-tone',
                    'aria-label' =>
                        get_string(
                            'crm_inbox_ai_reply_tone',
                            'local_subscriptions'
                        ),
                ]
            );
        }

        $out .= html_writer::tag(
            'button',
            s($label),
            [
                'type' => 'submit',
                'class' => 'btn btn-primary btn-sm',
                'data-loading-label' =>
                    get_string(
                        'crm_inbox_ai_processing_short_n124',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= html_writer::end_tag('form');

        return $out;
    }

    private static function empty_state(): string {
        return html_writer::div(
            html_writer::tag(
                'i',
                '',
                [
                    'class' => 'fa fa-lightbulb-o',
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string(
                        'crm_inbox_ai_empty_title_n124',
                        'local_subscriptions'
                    )
                )
                . html_writer::div(
                    get_string(
                        'crm_inbox_ai_empty_help_n124',
                        'local_subscriptions'
                    ),
                    'crm-inbox-ai-empty-help'
                )
            ),
            'crm-inbox-ai-empty'
        );
    }

    private static function result(
        array $result
    ): string {
        return match (
            (string)($result['type'] ?? '')
        ) {
            'analysis' =>
                self::analysis_result($result),
            'translation' =>
                self::translation_result($result),
            'reply' =>
                self::reply_result($result),
            default =>
                self::empty_state(),
        };
    }

    private static function translation_result(
        array $result
    ): string {
        $translation = $result['translation'] ?? [];
        $text = trim(
            (string)($translation['text'] ?? '')
        );

        $out = html_writer::start_div(
            'crm-inbox-ai-result crm-inbox-ai-translation-result'
        );

        $out .= self::result_heading(
            'fa fa-language',
            get_string(
                'crm_inbox_ai_translation_result_title',
                'local_subscriptions'
            )
        );

        if (
            empty($translation['successful'])
            || $text === ''
        ) {
            $provider = trim(
                (string)(
                    $translation['provider']
                    ?? ''
                )
            );
            $error = trim(
                (string)(
                    $translation['error']
                    ?? ''
                )
            );

            $message = get_string(
                'crm_inbox_ai_translation_failed',
                'local_subscriptions'
            );

            if (
                $provider === 'none'
                || str_contains(
                    \core_text::strtolower($error),
                    'no available provider'
                )
            ) {
                $message = get_string(
                    'crm_inbox_ai_translation_provider_missing_n124b',
                    'local_subscriptions'
                );
            } elseif ($error !== '') {
                $message .= ' ' . s($error);
            }

            $out .= html_writer::div(
                $message,
                'alert alert-warning mb-0'
            );
        } else {
            $source = strtoupper(
                (string)(
                    $translation['sourcelanguage']
                    ?? 'unknown'
                )
            );
            $target = strtoupper(
                (string)(
                    $translation['targetlanguage']
                    ?? ''
                )
            );

            $out .= html_writer::div(
                html_writer::span(
                    $source,
                    'crm-inbox-ai-language-pill'
                )
                . html_writer::span(
                    '→',
                    'crm-inbox-ai-language-arrow'
                )
                . html_writer::span(
                    $target,
                    'crm-inbox-ai-language-pill'
                ),
                'crm-inbox-ai-translation-meta'
            );

            $out .= html_writer::div(
                nl2br(s($text)),
                'crm-inbox-ai-translation-text'
            );
        }

        $out .= self::warnings(
            $translation['warnings'] ?? []
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function analysis_result(
        array $result
    ): string {
        $out = html_writer::start_div(
            'crm-inbox-ai-result crm-inbox-ai-analysis-result'
        );

        $out .= self::result_heading(
            'fa fa-search',
            get_string(
                'crm_inbox_ai_analysis_result_title_n124',
                'local_subscriptions'
            )
        );

        $language = strtoupper(
            (string)(
                $result['language']['value']
                ?? 'unknown'
            )
        );

        $urgency = (string)(
            $result['urgency']['value']
            ?? 'normal'
        );

        $category = (string)(
            $result['category']['value']
            ?? 'other'
        );


        $summary = trim(
            (string)(
                $result['summary']['text']
                ?? ''
            )
        );

        $left = '';

        if ($summary !== '') {
            $left .= html_writer::tag(
                'h4',
                get_string(
                    'crm_inbox_ai_summary',
                    'local_subscriptions'
                ),
                ['class' => 'crm-inbox-ai-section-title']
            );
            $left .= html_writer::div(
                nl2br(s($summary)),
                'crm-inbox-ai-summary'
            );
        }

        $right =
            self::list_section(
                get_string(
                    'crm_inbox_ai_customer_requests',
                    'local_subscriptions'
                ),
                $result['summary']['customerrequests']
                    ?? []
            )
            . self::list_section(
                get_string(
                    'crm_inbox_ai_pending_questions',
                    'local_subscriptions'
                ),
                $result['summary']['pendingquestions']
                    ?? []
            )
            . self::list_section(
                get_string(
                    'crm_inbox_ai_key_points',
                    'local_subscriptions'
                ),
                $result['summary']['keypoints']
                    ?? []
            );

        $out .= html_writer::div(
            html_writer::div(
                $left !== ''
                    ? $left
                    : html_writer::div(
                        get_string(
                            'crm_inbox_ai_analysis_no_summary_n124',
                            'local_subscriptions'
                        ),
                        'text-muted'
                    ),
                'crm-inbox-ai-analysis-main'
            )
            . html_writer::div(
                $right !== ''
                    ? $right
                    : html_writer::div(
                        get_string(
                            'crm_inbox_ai_analysis_no_details_n124',
                            'local_subscriptions'
                        ),
                        'text-muted'
                    ),
                'crm-inbox-ai-analysis-details'
            ),
            'crm-inbox-ai-analysis-grid'
        );

        $out .= html_writer::div(
            html_writer::span(
                get_string(
                    'crm_inbox_ai_detected_language',
                    'local_subscriptions'
                ) . ': ' . $language,
                'crm-inbox-ai-analysis-meta-item'
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_ai_urgency',
                    'local_subscriptions'
                ) . ': '
                . get_string(
                    'crm_inbox_ai_urgency_' . $urgency,
                    'local_subscriptions'
                ),
                'crm-inbox-ai-analysis-meta-item'
            )
            . html_writer::span(
                get_string(
                    'crm_inbox_ai_category',
                    'local_subscriptions'
                ) . ': '
                . get_string(
                    'crm_inbox_ai_category_' . $category,
                    'local_subscriptions'
                ),
                'crm-inbox-ai-analysis-meta-item'
            ),
            'crm-inbox-ai-analysis-meta'
        );

        $providers = [
            (string)($result['language']['provider'] ?? ''),
            (string)($result['urgency']['provider'] ?? ''),
            (string)($result['category']['provider'] ?? ''),
            (string)($result['summary']['provider'] ?? ''),
        ];

        if (
            array_filter(
                $providers,
                static fn(string $provider): bool =>
                    $provider === 'fallback'
            ) !== []
        ) {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_ai_fallback_notice_n124',
                    'local_subscriptions'
                ),
                'crm-inbox-ai-fallback-notice'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function reply_result(
        array $result
    ): string {
        $reply = $result['reply'] ?? [];

        $out = html_writer::start_div(
            'crm-inbox-ai-result crm-inbox-ai-reply-result'
        );

        $out .= self::result_heading(
            'fa fa-reply',
            get_string(
                'crm_inbox_ai_suggested_reply',
                'local_subscriptions'
            )
        );

        if (empty($reply['generated'])) {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_ai_reply_unavailable',
                    'local_subscriptions'
                ),
                'alert alert-light border'
            );
        } else {
            if (!empty($reply['subject'])) {
                $out .= html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string(
                            'subject',
                            'core'
                        ) . ': '
                    )
                    . s((string)$reply['subject']),
                    'crm-inbox-ai-reply-subject'
                );
            }

            $out .= html_writer::tag(
                'textarea',
                s((string)($reply['body'] ?? '')),
                [
                    'class' =>
                        'form-control crm-inbox-ai-reply-text',
                    'rows' => 10,
                    'readonly' => 'readonly',
                ]
            );

            $out .= html_writer::div(
                get_string(
                    'crm_inbox_ai_reply_requires_review',
                    'local_subscriptions'
                ),
                'crm-inbox-ai-review-note'
            );
        }

        $out .= self::warnings(
            $reply['warnings'] ?? []
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function result_heading(
        string $icon,
        string $title
    ): string {
        return html_writer::div(
            html_writer::tag(
                'i',
                '',
                [
                    'class' => $icon,
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::tag(
                'h3',
                s($title),
                ['class' => 'crm-inbox-ai-result-title']
            ),
            'crm-inbox-ai-result-heading'
        );
    }

    private static function metric(
        string $label,
        string $value
    ): string {
        return html_writer::div(
            html_writer::div(
                s($label),
                'crm-inbox-ai-metric-label'
            )
            . html_writer::div(
                s($value),
                'crm-inbox-ai-metric-value'
            ),
            'crm-inbox-ai-metric'
        );
    }

    private static function list_section(
        string $title,
        mixed $items
    ): string {
        if (!is_array($items) || !$items) {
            return '';
        }

        $content = '';

        foreach ($items as $item) {
            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            $content .= html_writer::tag(
                'li',
                s($item)
            );
        }

        if ($content === '') {
            return '';
        }

        return html_writer::div(
            html_writer::tag(
                'h4',
                s($title),
                ['class' => 'crm-inbox-ai-section-title']
            )
            . html_writer::tag(
                'ul',
                $content,
                ['class' => 'crm-inbox-ai-list']
            ),
            'crm-inbox-ai-list-section'
        );
    }

    private static function warnings(
        mixed $warnings
    ): string {
        if (!is_array($warnings) || !$warnings) {
            return '';
        }

        $clean = array_values(
            array_filter(
                array_map(
                    static fn(mixed $warning): string =>
                        trim((string)$warning),
                    $warnings
                )
            )
        );

        if (!$clean) {
            return '';
        }

        return html_writer::div(
            s(implode(' · ', $clean)),
            'crm-inbox-ai-warnings'
        );
    }
}
