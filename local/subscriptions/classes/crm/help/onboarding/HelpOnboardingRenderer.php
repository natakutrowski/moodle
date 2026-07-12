<?php

namespace local_subscriptions\crm\help\onboarding;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;

final class HelpOnboardingRenderer {

    public static function render(
        \stdClass $state,
        string $returnurl,
        bool $compact = false
    ): string {
        $classes = 'crm-onboarding';

        if ($compact) {
            $classes .= ' crm-onboarding-compact';
        }

        if ($state->finished) {
            $classes .= ' crm-onboarding-complete';
        }

        $out = html_writer::start_div($classes);

        $out .= self::render_header(
            $state,
            $returnurl
        );

        if ($state->finished) {
            $out .= self::render_complete_state(
                $returnurl
            );

            $out .= html_writer::end_div();

            return $out;
        }

        $out .= html_writer::start_div(
            'crm-onboarding-steps'
        );

        foreach ($state->items as $item) {
            $out .= self::render_step(
                $item,
                $returnurl
            );
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_header(
        \stdClass $state,
        string $returnurl
    ): string {
        $out = html_writer::start_div(
            'crm-onboarding-header'
        );

        $out .= html_writer::start_div(
            'crm-onboarding-heading'
        );

        $out .= html_writer::tag(
            'h3',
            get_string(
                'crm_onboarding_title',
                'local_subscriptions'
            ),
            [
                'class' => 'crm-onboarding-title',
            ]
        );

        $out .= html_writer::div(
            get_string(
                'crm_onboarding_description',
                'local_subscriptions'
            ),
            'crm-onboarding-description'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::div(
            get_string(
                'crm_onboarding_progress_label',
                'local_subscriptions',
                (object)[
                    'completed' => $state->completed,
                    'total' => $state->total,
                ]
            ),
            'crm-onboarding-progress-text'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'crm-onboarding-progress'
        );

        $out .= html_writer::div(
            '',
            'crm-onboarding-progress-bar',
            [
                'style' =>
                    'width: ' .
                    min(100, max(0, $state->percentage)) .
                    '%;',
                'role' => 'progressbar',
                'aria-valuemin' => '0',
                'aria-valuemax' => '100',
                'aria-valuenow' =>
                    (string)$state->percentage,
            ]
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_step(
        \stdClass $item,
        string $returnurl
    ): string {
        /** @var HelpOnboardingStep $step */
        $step = $item->step;

        $classes = 'crm-onboarding-step';

        if ($item->completed) {
            $classes .= ' completed';
        }

        $toggleurl = new moodle_url(
            subscription_config::admin_help_onboarding_action_page(),
            [
                'action' => 'toggle',
                'step' => $step->id,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]
        );

        $out = html_writer::start_div($classes);

        $out .= html_writer::div(
            $item->completed ? '✓' : $step->icon,
            'crm-onboarding-step-icon'
        );

        $out .= html_writer::start_div(
            'crm-onboarding-step-content'
        );

        $out .= html_writer::link(
            $step->url,
            s($step->title),
            [
                'class' =>
                    'crm-onboarding-step-title',
            ]
        );

        $out .= html_writer::div(
            s($step->description),
            'crm-onboarding-step-description'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::link(
            $toggleurl,
            $item->completed
                ? get_string(
                    'crm_onboarding_mark_incomplete',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_onboarding_mark_complete',
                    'local_subscriptions'
                ),
            [
                'class' =>
                    'btn btn-sm ' .
                    ($item->completed
                        ? 'btn-outline-secondary'
                        : 'btn-outline-primary'),
            ]
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_complete_state(
        string $returnurl
    ): string {
        $reseturl = new moodle_url(
            subscription_config::admin_help_onboarding_action_page(),
            [
                'action' => 'reset',
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]
        );

        return html_writer::div(
            html_writer::div(
                '🎉',
                'crm-onboarding-complete-icon'
            ) .
            html_writer::tag(
                'h4',
                get_string(
                    'crm_onboarding_complete_title',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'crm-onboarding-complete-title',
                ]
            ) .
            html_writer::div(
                get_string(
                    'crm_onboarding_complete_desc',
                    'local_subscriptions'
                ),
                'crm-onboarding-complete-description'
            ) .
            html_writer::link(
                $reseturl,
                get_string(
                    'crm_onboarding_restart',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-secondary mt-3',
                    'onclick' =>
                        "return confirm('" .
                        addslashes(
                            get_string(
                                'crm_onboarding_restart_confirm',
                                'local_subscriptions'
                            )
                        ) .
                        "');",
                ]
            ),
            'crm-onboarding-complete-state'
        );
    }
}