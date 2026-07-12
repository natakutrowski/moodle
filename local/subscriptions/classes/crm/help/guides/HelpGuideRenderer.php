<?php

namespace local_subscriptions\crm\help\guides;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;

final class HelpGuideRenderer {

    public static function render_cards(
        array $guides
    ): string {
        $out = html_writer::start_div(
            'crm-help-guides-grid'
        );

        foreach ($guides as $guide) {
            $out .= self::render_card($guide);
        }

        $out .= html_writer::end_div();

        return $out;
    }

    public static function render_guide(
        \stdClass $state,
        string $returnurl
    ): string {
        /** @var HelpGuide $guide */
        $guide = $state->guide;

        $out = html_writer::start_div(
            'crm-help-guide'
        );

        $out .= html_writer::start_div(
            'crm-help-guide-header'
        );

        $out .= html_writer::div(
            $guide->icon,
            'crm-help-guide-icon'
        );

        $out .= html_writer::start_div(
            'crm-help-guide-heading'
        );

        $out .= html_writer::tag(
            'h1',
            s($guide->title),
            [
                'class' => 'crm-help-guide-title',
            ]
        );

        $out .= html_writer::div(
            s($guide->description),
            'crm-help-guide-description'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'crm-help-guide-progress'
        );

        $out .= html_writer::div(
            '',
            'crm-help-guide-progress-bar',
            [
                'style' =>
                    'width:' .
                    min(100, max(0, $state->percentage)) .
                    '%;',
            ]
        );

        $out .= html_writer::end_div();

        $out .= html_writer::div(
            get_string(
                'crm_help_guide_progress',
                'local_subscriptions',
                (object)[
                    'completed' => $state->completed,
                    'total' => $state->total,
                ]
            ),
            'crm-help-guide-progress-label'
        );

        $out .= html_writer::start_div(
            'crm-help-guide-steps'
        );

        foreach ($state->items as $index => $item) {
            $out .= self::render_step(
                $guide,
                $item,
                $index + 1,
                $returnurl
            );
        }

        $out .= html_writer::end_div();

        if ($state->finished) {
            $out .= html_writer::div(
                '🎉 ' .
                get_string(
                    'crm_help_guide_complete',
                    'local_subscriptions'
                ),
                'crm-help-guide-complete'
            );
        }

        $reseturl = new moodle_url(
            subscription_config::admin_help_guide_action_page(),
            [
                'action' => 'reset',
                'guide' => $guide->id,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]
        );

        $out .= html_writer::link(
            $reseturl,
            get_string(
                'crm_help_guide_reset',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary mt-4',
                'onclick' =>
                    "return confirm('" .
                    addslashes(
                        get_string(
                            'crm_help_guide_reset_confirm',
                            'local_subscriptions'
                        )
                    ) .
                    "');",
            ]
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_card(
        HelpGuide $guide
    ): string {
        return html_writer::link(
            new moodle_url(
                subscription_config::admin_help_guide_page(),
                ['id' => $guide->id]
            ),
            html_writer::div(
                $guide->icon,
                'crm-help-guide-card-icon'
            ) .
            html_writer::tag(
                'h3',
                s($guide->title),
                [
                    'class' =>
                        'crm-help-guide-card-title',
                ]
            ) .
            html_writer::div(
                s($guide->description),
                'crm-help-guide-card-description'
            ) .
            html_writer::div(
                get_string(
                    'crm_help_guide_step_count',
                    'local_subscriptions',
                    count($guide->steps)
                ),
                'crm-help-guide-card-meta'
            ),
            [
                'class' => 'crm-help-guide-card',
            ]
        );
    }

    private static function render_step(
        HelpGuide $guide,
        \stdClass $item,
        int $number,
        string $returnurl
    ): string {
        /** @var HelpGuideStep $step */
        $step = $item->step;

        $classes = 'crm-help-guide-step';

        if ($item->completed) {
            $classes .= ' completed';
        }

        $toggleurl = new moodle_url(
            subscription_config::admin_help_guide_action_page(),
            [
                'action' => 'toggle',
                'guide' => $guide->id,
                'step' => $step->id,
                'sesskey' => sesskey(),
                'returnurl' => $returnurl,
            ]
        );

        $out = html_writer::start_div($classes);

        $out .= html_writer::div(
            $item->completed ? '✓' : (string)$number,
            'crm-help-guide-step-number'
        );

        $out .= html_writer::start_div(
            'crm-help-guide-step-content'
        );

        $out .= html_writer::tag(
            'h3',
            s($step->title),
            [
                'class' =>
                    'crm-help-guide-step-title',
            ]
        );

        $out .= html_writer::div(
            s($step->description),
            'crm-help-guide-step-description'
        );

        if ($step->has_action()) {
            $out .= html_writer::link(
                $step->url,
                s($step->actionlabel) . ' →',
                [
                    'class' =>
                        'crm-help-guide-step-action',
                ]
            );
        }

        $out .= html_writer::end_div();

        $out .= html_writer::link(
            $toggleurl,
            $item->completed
                ? get_string(
                    'crm_help_guide_reopen_step',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_help_guide_complete_step',
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
}