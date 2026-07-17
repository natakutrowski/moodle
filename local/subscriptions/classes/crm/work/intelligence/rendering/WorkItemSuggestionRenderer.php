<?php

namespace local_subscriptions\crm\work\intelligence\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\work\intelligence\dto\WorkItemSuggestion;
use local_subscriptions\crm\work\rendering\WorkItemRenderer;
use local_subscriptions\crm\work\rendering\WorkItemPresentation;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Renders the human confirmation form for a Work Item suggestion.
 */
final class WorkItemSuggestionRenderer {

    public static function render(
        WorkItemSuggestion $suggestion,
        array $teams,
        array $assignees,
        moodle_url $actionurl
    ): string {
        $out = '';

        $out .= self::summary($suggestion);

        if ($suggestion->duplicates !== []) {
            $out .= self::duplicates(
                $suggestion
            );
        }

        if ($suggestion->teams !== []) {
            $out .= self::team_suggestions(
                $suggestion
            );
        }

        $out .= self::form(
            $suggestion,
            $teams,
            $assignees,
            $actionurl
        );

        return html_writer::div(
            $out,
            'local-subscriptions-work-suggestion'
        );
    }

    private static function summary(
        WorkItemSuggestion $suggestion
    ): string {
        $items = [
            get_string(
                'crm_work_suggestion_confidence',
                'local_subscriptions',
                $suggestion->confidencescore
            ),
            get_string(
                'crm_work_suggestion_suggested_type',
                'local_subscriptions',
                WorkItemPresentation::type_label(
                    $suggestion->type
                )
            ),
            get_string(
                'crm_work_suggestion_suggested_priority',
                'local_subscriptions',
                WorkItemPresentation::priority_label(
                    $suggestion->priority
                )
            ),
        ];

        if ($suggestion->dueat !== null) {
            $items[] = get_string(
                'crm_work_suggestion_suggested_due',
                'local_subscriptions',
                userdate($suggestion->dueat)
            );
        }

        return html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'crm_work_suggestion_summary',
                    'local_subscriptions'
                ),
                [
                    'class' => 'h4',
                ]
            ) .
            html_writer::alist($items),
            'card card-body mb-4'
        );
    }

    private static function duplicates(
        WorkItemSuggestion $suggestion
    ): string {
        $out = html_writer::tag(
            'h2',
            get_string(
                'crm_work_suggestion_duplicates',
                'local_subscriptions'
            ),
            [
                'class' => 'h4',
            ]
        );

        if ($suggestion->has_probable_duplicate()) {
            $out .= html_writer::div(
                get_string(
                    'crm_work_suggestion_probable_duplicate_warning',
                    'local_subscriptions'
                ),
                'alert alert-warning'
            );
        }

        $out .= html_writer::start_div(
            'crm-work-suggestion-duplicates'
        );

        foreach ($suggestion->duplicates as $duplicate) {
            $url = new moodle_url(
                subscription_config::
                    admin_work_item_view_page(),
                [
                    'id' =>
                        $duplicate->workitemid,
                ]
            );

            $out .= html_writer::div(
                html_writer::link(
                    $url,
                    s(
                        $duplicate->reference .
                        ' — ' .
                        $duplicate->title
                    ),
                    [
                        'class' => 'fw-bold',
                    ]
                ) .
                html_writer::div(
                    get_string(
                        'crm_work_suggestion_similarity',
                        'local_subscriptions',
                        $duplicate->similarityscore
                    ),
                    'small text-muted'
                ),
                'border rounded p-3 mb-2'
            );
        }

        $out .= html_writer::end_div();

        return html_writer::div(
            $out,
            'card card-body mb-4'
        );
    }

    private static function team_suggestions(
        WorkItemSuggestion $suggestion
    ): string {
        $out = html_writer::tag(
            'h2',
            get_string(
                'crm_work_suggestion_teams',
                'local_subscriptions'
            ),
            [
                'class' => 'h4',
            ]
        );

        foreach ($suggestion->teams as $team) {
            $out .= html_writer::div(
                html_writer::tag(
                    'strong',
                    s($team->teamname)
                ) .
                html_writer::div(
                    get_string(
                        'crm_work_suggestion_team_score',
                        'local_subscriptions',
                        (object)[
                            'score' => $team->score,
                            'workload' =>
                                $team->activeworkload,
                        ]
                    ),
                    'small text-muted'
                ),
                'border rounded p-3 mb-2'
            );
        }

        return html_writer::div(
            $out,
            'card card-body mb-4'
        );
    }

    private static function form(
        WorkItemSuggestion $suggestion,
        array $teams,
        array $assignees,
        moodle_url $actionurl
    ): string {
        $out = html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => $actionurl->out(false),
                'class' =>
                    'card card-body crm-work-suggestion-form',
            ]
        );

        $out .= html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
            ]
        );

        $out .= html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'recommendationid',
                'value' =>
                    $suggestion->recommendationid,
            ]
        );

        $out .= html_writer::label(
            get_string(
                'crm_work_field_title',
                'local_subscriptions'
            ),
            'id_title'
        );

        $out .= html_writer::empty_tag(
            'input',
            [
                'type' => 'text',
                'name' => 'title',
                'id' => 'id_title',
                'class' =>
                    'form-control mb-3',
                'value' => $suggestion->title,
                'required' => 'required',
            ]
        );

        $out .= html_writer::label(
            get_string(
                'crm_work_field_description',
                'local_subscriptions'
            ),
            'id_description'
        );

        $out .= html_writer::tag(
            'textarea',
            s($suggestion->description),
            [
                'name' => 'description',
                'id' => 'id_description',
                'rows' => 10,
                'class' =>
                    'form-control mb-3',
            ]
        );

        $out .= html_writer::start_div(
            'row g-3'
        );

        $out .= html_writer::div(
            html_writer::select(
                WorkItemRenderer::type_options(),
                'type',
                $suggestion->type,
                false,
                [
                    'class' =>
                        'custom-select w-100',
                ]
            ),
            'col-md-3'
        );

        $out .= html_writer::div(
            html_writer::select(
                WorkItemRenderer::priority_options(),
                'priority',
                $suggestion->priority,
                false,
                [
                    'class' =>
                        'custom-select w-100',
                ]
            ),
            'col-md-3'
        );

        $teamoptions = [
            0 => get_string('none'),
        ];

        foreach ($teams as $team) {
            $teamoptions[(int)$team->id] =
                format_string(
                    $team->name
                );
        }

        $out .= html_writer::div(
            html_writer::select(
                $teamoptions,
                'assignedteamid',
                $suggestion->suggestedteamid ?? 0,
                false,
                [
                    'class' =>
                        'custom-select w-100',
                ]
            ),
            'col-md-3'
        );

        $useroptions = [
            0 => get_string('none'),
        ];

        foreach ($assignees as $user) {
            $useroptions[(int)$user->id] =
                fullname($user);
        }

        $out .= html_writer::div(
            html_writer::select(
                $useroptions,
                'assigneduserid',
                0,
                false,
                [
                    'class' =>
                        'custom-select w-100',
                ]
            ),
            'col-md-3'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::label(
            get_string(
                'crm_work_due',
                'local_subscriptions'
            ),
            'id_dueat',
            false,
            [
                'class' => 'mt-3',
            ]
        );

        $out .= html_writer::empty_tag(
            'input',
            [
                'type' => 'datetime-local',
                'name' => 'dueat',
                'id' => 'id_dueat',
                'class' =>
                    'form-control mb-3',
                'value' =>
                    $suggestion->dueat !== null
                        ? date(
                            'Y-m-d\TH:i',
                            $suggestion->dueat
                        )
                        : '',
            ]
        );

        if (
            $suggestion->has_probable_duplicate()
        ) {
            $out .= html_writer::div(
                html_writer::checkbox(
                    'allowduplicate',
                    1,
                    false,
                    get_string(
                        'crm_work_suggestion_allow_duplicate',
                        'local_subscriptions'
                    )
                ),
                'alert alert-warning'
            );
        }

        $out .= html_writer::empty_tag(
            'input',
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-primary',
                'value' => get_string(
                    'crm_work_suggestion_create',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::end_tag('form');

        return $out;
    }
}