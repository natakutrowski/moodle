<?php

namespace local_subscriptions\crm\assistant\ai\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\subscription_config;

/**
 * Renders the conversational CRM Assistant panel.
 */
final class CrmAssistantConversationRenderer {

    public static function render(
        string $scope =
            CrmAssistantQuestion::SCOPE_GLOBAL,
        ?int $userid = null,
        ?int $recommendationid = null
    ): string {
        $endpoint =
            subscription_config::
                crm_assistant_ai_endpoint();

        $out = html_writer::start_div(
            'card card-body mb-4 crm-assistant-ai',
            [
                'data-crm-assistant-ai' => '1',
                'data-endpoint' => $endpoint,
                'data-scope' => $scope,
                'data-userid' =>
                    $userid ?? 0,
                'data-recommendationid' =>
                    $recommendationid ?? 0,
                'data-sesskey' => sesskey(),
            ]
        );

        $out .= html_writer::tag(
            'h2',
            '✨ ' .
            get_string(
                'crm_assistant_ai_title',
                'local_subscriptions'
            ),
            [
                'class' => 'h4 mb-2',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'crm_assistant_ai_description',
                'local_subscriptions'
            ),
            'text-muted mb-3'
        );

        $out .= html_writer::tag(
            'label',
            get_string(
                'crm_assistant_ai_question',
                'local_subscriptions'
            ),
            [
                'for' =>
                    'crm-assistant-ai-question',
                'class' => 'form-label',
            ]
        );

        $out .= html_writer::tag(
            'textarea',
            '',
            [
                'id' =>
                    'crm-assistant-ai-question',
                'class' =>
                    'form-control crm-assistant-ai-question',
                'rows' => 3,
                'maxlength' => 1000,
                'placeholder' =>
                    get_string(
                        'crm_assistant_ai_placeholder',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= html_writer::div(
            self::example_button(
                get_string(
                    'crm_assistant_ai_example_priorities',
                    'local_subscriptions'
                )
            ) .
            self::example_button(
                get_string(
                    'crm_assistant_ai_example_risks',
                    'local_subscriptions'
                )
            ) .
            self::example_button(
                get_string(
                    'crm_assistant_ai_example_work',
                    'local_subscriptions'
                )
            ),
            'crm-assistant-ai-examples mt-2'
        );

        $out .= html_writer::tag(
            'button',
            get_string(
                'crm_assistant_ai_ask',
                'local_subscriptions'
            ),
            [
                'type' => 'button',
                'class' =>
                    'btn btn-primary mt-3 crm-assistant-ai-submit',
            ]
        );

        $out .= html_writer::div(
            '',
            'crm-assistant-ai-status mt-3',
            [
                'role' => 'status',
                'aria-live' => 'polite',
            ]
        );

        $out .= html_writer::div(
            '',
            'crm-assistant-ai-answer mt-3',
            [
                'aria-live' => 'polite',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'crm_assistant_ai_human_review',
                'local_subscriptions'
            ),
            'small text-muted mt-3'
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function example_button(
        string $label
    ): string {
        return html_writer::tag(
            'button',
            s($label),
            [
                'type' => 'button',
                'class' =>
                    'btn btn-sm btn-outline-secondary me-2 mb-2 crm-assistant-ai-example',
                'data-question' => $label,
            ]
        );
    }
}