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
        $headingid =
            'crm-inbox-ai-panel-title-' .
            $threadid;

        $out = html_writer::start_tag(
            'section',
            [
                'class' =>
                    'crm-inbox-ai-panel card mt-4',

                'aria-labelledby' =>
                    $headingid,
            ]
        );

        $out .= html_writer::start_tag(
            'header',
            [
                'class' =>
                    'card-header d-flex ' .
                    'justify-content-between ' .
                    'align-items-center gap-3',
            ]
        );

        $out .= html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'crm_inbox_ai_panel_title',
                    'local_subscriptions'
                ),
                [
                    'id' => $headingid,
                    'class' => 'h5 mb-1',
                ]
            ) .
            html_writer::div(
                get_string(
                    'crm_inbox_ai_panel_description',
                    'local_subscriptions'
                ),
                'small text-muted'
            )
        );

        $out .= html_writer::span(
            get_string(
                'crm_inbox_ai_human_review_badge',
                'local_subscriptions'
            ),
            'badge bg-warning text-dark'
        );

        $out .= html_writer::end_tag(
            'header'
        );

        $out .= html_writer::start_div(
            'card-body'
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
                'alert alert-light border mb-0'
            );

            $out .= html_writer::end_div();

            $out .= html_writer::end_tag(
                'section'
            );

            return $out;
        }

        $out .= self::actions(
            $threadid,
            $canmanageinbox
        );

        if ($result !== null) {
            $out .= self::result($result);
        } else {
            $out .= html_writer::div(
                get_string(
                    'crm_inbox_ai_no_analysis',
                    'local_subscriptions'
                ),
                'crm-inbox-ai-empty text-muted mt-3'
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::end_tag(
            'section'
        );

        return $out;
    }

    private static function actions(
        int $threadid,
        bool $canmanageinbox
    ): string {
        $out = html_writer::start_tag(
            'div',
            [
                'class' =>
                    'crm-inbox-ai-actions ' .
                    'd-flex flex-wrap gap-2',

                'role' => 'group',

                'aria-label' =>
                    get_string(
                        'crm_inbox_ai_actions_label',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= self::form(
            $threadid,
            'analyse',
            get_string(
                'crm_inbox_ai_analyse',
                'local_subscriptions'
            ),
            'btn btn-outline-primary'
        );

        if ($canmanageinbox) {
            $out .= self::form(
                $threadid,
                'reply',
                get_string(
                    'crm_inbox_ai_suggest_reply',
                    'local_subscriptions'
                ),
                'btn btn-primary',
                true
            );
        }

        $out .= html_writer::end_tag(
            'div'
        );

        return $out;
    }

    private static function form(
        int $threadid,
        string $action,
        string $label,
        string $buttonclass,
        bool $showoptions = false
    ): string {
        $out = html_writer::start_tag(
            'form',
            [
                'method' => 'post',

                'action' =>
                    subscription_config::
                        admin_inbox_ai_action_page(),

                'class' =>
                    'crm-inbox-ai-action-form',

                'data-inbox-busy-form' => '1',

                'data-busy-announcement' =>
                    $action === 'reply'
                        ? get_string(
                            'crm_inbox_ai_reply_processing',
                            'local_subscriptions'
                        )
                        : get_string(
                            'crm_inbox_ai_analysis_processing',
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

        $languageid =
            'id_ai_language_' .
            $threadid;

        $toneid =
            'id_ai_tone_' .
            $threadid;

        if ($showoptions) {
            $out .= html_writer::label(
                get_string(
                    'crm_inbox_ai_reply_language',
                    'local_subscriptions'
                ),
                $languageid,
                false,
                ['class' => 'visually-hidden']
            );

            $out .= html_writer::select(
                [
                    'fr' => 'FR',
                    'en' => 'EN',
                    'ru' => 'RU',
                ],
                'language',
                'fr',
                false,
                [
                    'id' => $languageid,
                    'class' =>
                        'form-select form-select-sm d-inline-block w-auto',
                    'aria-label' =>
                        get_string(
                            'crm_inbox_ai_reply_language',
                            'local_subscriptions'
                        ),
                ]
            );

            $out .= html_writer::label(
                get_string(
                    'crm_inbox_ai_reply_tone',
                    'local_subscriptions'
                ),
                $toneid,
                false,
                ['class' => 'visually-hidden']
            );

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
                    'id' => $toneid,
                    'class' =>
                        'form-select form-select-sm d-inline-block w-auto',
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
            $label,
            [
                'type' => 'submit',

                'class' =>
                    $buttonclass,

                'data-loading-label' =>
                    $action === 'reply'
                        ? get_string(
                            'crm_inbox_ai_reply_processing_short',
                            'local_subscriptions'
                        )
                        : get_string(
                            'crm_inbox_ai_analysis_processing_short',
                            'local_subscriptions'
                        ),
            ]
        );

        $out .= html_writer::end_tag('form');

        return $out;
    }

    private static function result(
        array $result
    ): string {
        return match (
            (string)($result['type'] ?? '')
        ) {
            'analysis' =>
                self::analysis_result($result),
            'reply' =>
                self::reply_result($result),
            default =>
                '',
        };
    }

    private static function analysis_result(
        array $result
    ): string {
        $out = html_writer::start_div(
            'crm-inbox-ai-result mt-4'
        );

        $out .= self::metric(
            get_string(
                'crm_inbox_ai_detected_language',
                'local_subscriptions'
            ),
            strtoupper(
                (string)(
                    $result['language']['value']
                    ?? 'unknown'
                )
            ),
            (float)(
                $result['language']['confidence']
                ?? 0
            )
        );

        $urgency = (string)(
            $result['urgency']['value']
            ?? 'normal'
        );

        $out .= self::metric(
            get_string(
                'crm_inbox_ai_urgency',
                'local_subscriptions'
            ),
            get_string(
                'crm_inbox_ai_urgency_' .
                    $urgency,
                'local_subscriptions'
            ),
            (float)(
                $result['urgency']['confidence']
                ?? 0
            )
        );

        $category = (string)(
            $result['category']['value']
            ?? 'other'
        );

        $out .= self::metric(
            get_string(
                'crm_inbox_ai_category',
                'local_subscriptions'
            ),
            get_string(
                'crm_inbox_ai_category_' .
                    $category,
                'local_subscriptions'
            ),
            (float)(
                $result['category']['confidence']
                ?? 0
            )
        );

        $summary = trim(
            (string)(
                $result['summary']['text']
                ?? ''
            )
        );

        if ($summary !== '') {
            $out .= html_writer::tag(
                'h3',
                get_string(
                    'crm_inbox_ai_summary',
                    'local_subscriptions'
                ),
                ['class' => 'h6 mt-4']
            );

            $out .= html_writer::div(
                nl2br(s($summary)),
                'crm-inbox-ai-summary'
            );
        }

        $out .= self::list_section(
            get_string(
                'crm_inbox_ai_key_points',
                'local_subscriptions'
            ),
            $result['summary']['keypoints']
                ?? []
        );

        $out .= self::list_section(
            get_string(
                'crm_inbox_ai_pending_questions',
                'local_subscriptions'
            ),
            $result['summary']['pendingquestions']
                ?? []
        );

        $out .= self::list_section(
            get_string(
                'crm_inbox_ai_customer_requests',
                'local_subscriptions'
            ),
            $result['summary']['customerrequests']
                ?? []
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function reply_result(
        array $result
    ): string {
        $reply = $result['reply'] ?? [];

        $out = html_writer::start_div(
            'crm-inbox-ai-result mt-4'
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
            $out .= html_writer::tag(
                'h3',
                get_string(
                    'crm_inbox_ai_suggested_reply',
                    'local_subscriptions'
                ),
                ['class' => 'h6']
            );

            if (!empty($reply['subject'])) {
                $out .= html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string(
                            'subject',
                            'core'
                        ) . ': '
                    ) .
                    s((string)$reply['subject']),
                    'mb-3'
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
                    'aria-label' =>
                        get_string(
                            'crm_inbox_ai_suggested_reply',
                            'local_subscriptions'
                        ),
                ]
            );

            $out .= html_writer::div(
                get_string(
                    'crm_inbox_ai_reply_requires_review',
                    'local_subscriptions'
                ),
                'alert alert-warning mt-3 mb-0'
            );
        }

        $out .= self::warnings(
            $reply['warnings'] ?? []
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function metric(
        string $label,
        string $value,
        float $confidence
    ): string {
        $percentage = (int)round(
            max(0.0, min(1.0, $confidence)) *
            100
        );

        return html_writer::div(
            html_writer::div(
                s($label),
                'small text-muted'
            ) .
            html_writer::div(
                s($value),
                'fw-semibold'
            ) .
            html_writer::div(
                get_string(
                    'crm_inbox_ai_confidence',
                    'local_subscriptions',
                    $percentage
                ),
                'small text-muted'
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

        return html_writer::tag(
            'h3',
            $title,
            ['class' => 'h6 mt-4']
        ) .
        html_writer::tag(
            'ul',
            $content,
            ['class' => 'mb-0']
        );
    }

    private static function warnings(
        mixed $warnings
    ): string {
        if (!is_array($warnings) || !$warnings) {
            return '';
        }

        return html_writer::div(
            implode(
                '<br>',
                array_map(
                    static fn(mixed $warning): string =>
                        s((string)$warning),
                    $warnings
                )
            ),
            'alert alert-warning mt-3'
        );
    }
}