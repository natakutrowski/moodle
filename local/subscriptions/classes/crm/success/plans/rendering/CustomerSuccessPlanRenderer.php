<?php

namespace local_subscriptions\crm\success\plans\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStatus;
use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlan;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders Customer Success plans.
 */
final class CustomerSuccessPlanRenderer {

    public function render_plan(
        CustomerSuccessPlan $plan,
        bool $canmanage
    ): string {
        $content = '';

        $content .= html_writer::start_div(
            'local-subscriptions-cs-plan'
        );

        $content .= html_writer::div(
            '',
            'sr-only',
            [
                'id' =>
                    'local-subscriptions-cs-plan-status',

                'role' =>
                    'status',

                'aria-live' =>
                    'polite',

                'aria-atomic' =>
                    'true',
            ]
        );

        $content .= $this->render_header(
            $plan
        );

        $content .= $this->render_progress(
            $plan
        );

        $blockedreason =
            CustomerSuccessPlanPresentation::
                blocked_reason_label(
                    $plan->blockedreason
                );

        if ($blockedreason !== null) {
            $content .= html_writer::div(
                s($blockedreason),
                'alert alert-warning'
            );
        }

        $content .= html_writer::start_tag(
            'ol',
            [
                'class' =>
                    'local-subscriptions-cs-plan__steps',
            ]
        );

        foreach ($plan->steps as $step) {
            $content .= $this->render_step(
                $step,
                $plan,
                $canmanage
            );
        }

        $content .= html_writer::end_tag('ol');

        if ($canmanage) {
            $content .= $this->render_plan_actions(
                $plan
            );
        }

        $content .= html_writer::end_div();

        return $content;
    }

    private function render_header(
        CustomerSuccessPlan $plan
    ): string {
        $status =
            CustomerSuccessPlanPresentation::
                status_label(
                    $plan->status
                );

        $priority =
            CustomerSuccessPlanPresentation::
                priority_label(
                    $plan->priority
                );

        $badges =
            html_writer::span(
                s($status),
                'badge badge-secondary mr-2'
            ) .
            html_writer::span(
                s($priority),
                'badge badge-info'
            );

        return html_writer::div(
            html_writer::tag(
                'h3',
                s(
                    CustomerSuccessPlanPresentation::
                        title(
                            $plan->objectivekey,
                            $plan->title
                        )
                ),
                [
                    'class' =>
                        'local-subscriptions-cs-plan__title',
                ]
            ) .
            $badges .
            (
                CustomerSuccessPlanPresentation::
                    description(
                        $plan->description
                    ) !== null
                    ? html_writer::div(
                        format_text(
                            CustomerSuccessPlanPresentation::
                                description(
                                    $plan->description
                                ),
                            FORMAT_PLAIN
                        ),
                        'local-subscriptions-cs-plan__description'
                    )
                    : ''
            ),
            'local-subscriptions-cs-plan__header'
        );
    }

    private function render_progress(
        CustomerSuccessPlan $plan
    ): string {
        $progress =
            $plan->progress_percentage();

        return html_writer::div(
            html_writer::div(
                html_writer::div(
                    '',
                    'progress-bar',
                    [
                        'role' => 'progressbar',
                        'style' =>
                            'width: ' . $progress . '%',
                        'aria-label' =>
                            get_string(
                                'csplanprogresslabel',
                                'local_subscriptions'
                            ),    
                        'aria-valuenow' =>
                            (string)$progress,
                        'aria-valuemin' => '0',
                        'aria-valuemax' => '100',
                    ]
                ),
                'progress'
            ) .
            html_writer::div(
                get_string(
                    'csplanprogressvalue',
                    'local_subscriptions',
                    (object)[
                        'completed' =>
                            $plan->completed_step_count(),
                        'total' =>
                            $plan->step_count(),
                        'percentage' =>
                            format_float($progress, 0),
                    ]
                ),
                'small text-muted mt-1'
            ),
            'local-subscriptions-cs-plan__progress'
        );
    }

    private function render_step(
        CustomerSuccessPlanStep $step,
        CustomerSuccessPlan $plan,
        bool $canmanage
    ): string {
        $classes = [
            'local-subscriptions-cs-plan__step',
            'local-subscriptions-cs-plan__step--' .
                $step->status,
        ];

        $body =
            html_writer::tag(
                'h4',
                s($step->title),
                [
                    'class' =>
                        'local-subscriptions-cs-plan__step-title',
                ]
            );

        $body .= html_writer::span(
            s(
                CustomerSuccessPlanPresentation::
                    step_status_label(
                        $step->status
                    )
            ),
            'badge badge-light'
        );

        if ($step->description !== null) {
            $body .= html_writer::div(
                format_text(
                    $step->description,
                    FORMAT_PLAIN
                ),
                'local-subscriptions-cs-plan__step-description'
            );
        }

        $blockedreason =
            CustomerSuccessPlanPresentation::
                blocked_reason_label(
                    $step->blockedreason
                );

        if ($blockedreason !== null) {
            $body .= html_writer::div(
                s($blockedreason),
                'text-danger small'
            );
        }

        if (
            $step->dependsonstepid !== null
        ) {
            $body .= html_writer::div(
                get_string(
                    'csplanstepdependency',
                    'local_subscriptions',
                    $step->dependsonstepid
                ),
                'small text-muted'
            );
        }

        if (
            $canmanage &&
            $plan->status ===
                CustomerSuccessPlanStatus::ACTIVE
        ) {
            $body .= $this->render_step_actions(
                $step,
                $plan
            );
        }

        return html_writer::tag(
            'li',
            $body,
            [
                'id' =>
                    'cs-step-' .
                    $step->id,

                'class' =>
                    implode(
                        ' ',
                        $classes
                    ),

                'data-step-id' =>
                    (string)$step->id,
            ]
        );
    }

    private function render_plan_actions(
        CustomerSuccessPlan $plan
    ): string {
        $actions = [];

        if (
            in_array(
                $plan->status,
                [
                    CustomerSuccessPlanStatus::DRAFT,
                    CustomerSuccessPlanStatus::PAUSED,
                ],
                true
            )
        ) {
            $actions[] = $this->action_form(
                $plan->id,
                null,
                'activate',
                get_string(
                    'csplanaction_activate',
                    'local_subscriptions'
                ),
                'btn btn-primary'
            );
        }

        if (
            $plan->status ===
            CustomerSuccessPlanStatus::ACTIVE
        ) {
            $actions[] = $this->action_form(
                $plan->id,
                null,
                'pause',
                get_string(
                    'csplanaction_pause',
                    'local_subscriptions'
                ),
                'btn btn-secondary'
            );
        }

        if ($plan->is_open()) {
            $actions[] =
                $this->confirmation_link(
                    $plan->id,
                    null,
                    'cancel',
                    get_string(
                        'csplanaction_cancel',
                        'local_subscriptions'
                    ),
                    'btn btn-outline-danger'
                );
        }

        return html_writer::div(
            implode('', $actions),
            'local-subscriptions-cs-plan__actions'
        );
    }

    private function render_step_actions(
        CustomerSuccessPlanStep $step,
        CustomerSuccessPlan $plan
    ): string {
        $actions = [];

        if (
            $step->status ===
            CustomerSuccessPlanStepStatus::READY
        ) {
            $actions[] = $this->action_form(
                $plan->id,
                $step->id,
                'start_step',
                get_string(
                    'csplanaction_startstep',
                    'local_subscriptions'
                ),
                'btn btn-sm btn-primary'
            );
        }

        if (
            in_array(
                $step->status,
                [
                    CustomerSuccessPlanStepStatus::READY,
                    CustomerSuccessPlanStepStatus::IN_PROGRESS,
                ],
                true
            )
        ) {
            $actions[] = $this->action_form(
                $plan->id,
                $step->id,
                'complete_step',
                get_string(
                    'csplanaction_completestep',
                    'local_subscriptions'
                ),
                'btn btn-sm btn-success'
            );
        }

        if (
            in_array(
                $step->status,
                [
                    CustomerSuccessPlanStepStatus::READY,
                    CustomerSuccessPlanStepStatus::IN_PROGRESS,
                    CustomerSuccessPlanStepStatus::PENDING,
                ],
                true
            )
        ) {
            $actions[] =
                $this->block_step_form(
                    $plan->id,
                    $step
                );
        }

        if (
            !CustomerSuccessPlanStepStatus::is_terminal(
                $step->status
            )
        ) {
            $actions[] =
                $this->confirmation_link(
                    $plan->id,
                    $step->id,
                    'skip_step',
                    get_string(
                        'csplanaction_skipstep',
                        'local_subscriptions'
                    ),
                    'btn btn-sm btn-outline-secondary'
                );
        }

        if (
            $step->status ===
            CustomerSuccessPlanStepStatus::BLOCKED
        ) {
            $actions[] = $this->action_form(
                $plan->id,
                $step->id,
                'unblock_step',
                get_string(
                    'csplanaction_unblockstep',
                    'local_subscriptions'
                ),
                'btn btn-sm btn-outline-warning'
            );
        }

        return html_writer::div(
            implode('', $actions),
            'local-subscriptions-cs-plan__step-actions mt-2'
        );
    }

    private function confirmation_link(
        int $planid,
        ?int $stepid,
        string $action,
        string $label,
        string $buttonclass
    ): string {
        $params = [
            'planid' =>
                $planid,

            'action' =>
                $action,
        ];

        if ($stepid !== null) {
            $params['stepid'] =
                $stepid;
        }

        $url = new moodle_url(
            subscription_config::
                admin_customer_success_plan_confirm_page(),
            $params
        );

        return html_writer::link(
            $url,
            s($label),
            [
                'class' =>
                    $buttonclass .
                    ' mr-2 mb-2',

                'role' =>
                    'button',

                'aria-label' =>
                    $label,
            ]
        );
    }

    private function block_step_form(
        int $planid,
        CustomerSuccessPlanStep $step
    ): string {
        $url = new moodle_url(
            subscription_config::
                admin_customer_success_plan_action_page()
        );

        $inputid =
            'cs-block-reason-' .
            $step->id;

        $fields =
            html_writer::empty_tag(
                'input',
                [
                    'type' =>
                        'hidden',

                    'name' =>
                        'sesskey',

                    'value' =>
                        sesskey(),
                ]
            );

        $fields .= html_writer::empty_tag(
            'input',
            [
                'type' =>
                    'hidden',

                'name' =>
                    'planid',

                'value' =>
                    $planid,
            ]
        );

        $fields .= html_writer::empty_tag(
            'input',
            [
                'type' =>
                    'hidden',

                'name' =>
                    'stepid',

                'value' =>
                    $step->id,
            ]
        );

        $fields .= html_writer::empty_tag(
            'input',
            [
                'type' =>
                    'hidden',

                'name' =>
                    'action',

                'value' =>
                    'block_step',
            ]
        );

        $fields .= html_writer::tag(
            'label',
            get_string(
                'csplanblockreasonlabel',
                'local_subscriptions'
            ),
            [
                'for' =>
                    $inputid,

                'class' =>
                    'sr-only',
            ]
        );

        $fields .= html_writer::empty_tag(
            'input',
            [
                'id' =>
                    $inputid,

                'type' =>
                    'text',

                'name' =>
                    'blockreason',

                'class' =>
                    'form-control form-control-sm d-inline-block mr-1',

                'style' =>
                    'width: min(100%, 24rem);',

                'maxlength' =>
                    '500',

                'required' =>
                    'required',

                'placeholder' =>
                    get_string(
                        'csplanblockreasonplaceholder',
                        'local_subscriptions'
                    ),

                'aria-describedby' =>
                    $inputid .
                    '-help',
            ]
        );

        $fields .= html_writer::span(
            get_string(
                'csplanblockreasonhelp',
                'local_subscriptions'
            ),
            'sr-only',
            [
                'id' =>
                    $inputid .
                    '-help',
            ]
        );

        $fields .= html_writer::tag(
            'button',
            get_string(
                'csplanaction_blockstep',
                'local_subscriptions'
            ),
            [
                'type' =>
                    'submit',

                'class' =>
                    'btn btn-sm btn-outline-danger',
            ]
        );

        return html_writer::tag(
            'form',
            $fields,
            [
                'method' =>
                    'post',

                'action' =>
                    $url->out(false),

                'class' =>
                    'd-block mt-2 mb-2 local-subscriptions-cs-plan__block-form',
            ]
        );
    }

    private function action_form(
        int $planid,
        ?int $stepid,
        string $action,
        string $label,
        string $buttonclass
    ): string {
        $url = new moodle_url(
            subscription_config::
                admin_customer_success_plan_action_page()
        );

        $fields =
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'sesskey',
                    'value' => sesskey(),
                ]
            ) .
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'planid',
                    'value' => $planid,
                ]
            ) .
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'action',
                    'value' => $action,
                ]
            );

        if ($stepid !== null) {
            $fields .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'stepid',
                    'value' => $stepid,
                ]
            );
        }

        $fields .= html_writer::tag(
            'button',
            s($label),
            [
                'type' => 'submit',
                'class' => $buttonclass,
                'aria-label' => $label,
            ]
        );

        return html_writer::tag(
            'form',
            $fields,
            [
                'method' => 'post',
                'action' => $url->out(false),
                'class' => 'd-inline-block mr-2 mb-2',
            ]
        );
    }
}