<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

use html_table;
use html_writer;
use moodle_url;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\crm\user\UserExplorerFilter;
use local_subscriptions\subscription_config;

final class UserExplorerRenderer {

    public static function render(
        UserExplorerResult $result
    ): string {
        $out = html_writer::start_div(
            'crm-user-explorer'
        );

        if ($result->criteria->has_funnel_filter()) {
            $out .= self::render_funnel_filter(
                $result->criteria
            );
        }

        $out .= self::render_summary($result);
        $out .= self::render_workspace_toolbar($result);
        $out .= self::render_filters($result);
        $out .= self::render_trend_context($result);
        $out .= self::render_intelligence_pills($result);

        if (!$result->has_results()) {
            $out .= self::render_empty_state($result);
            $out .= html_writer::end_div();

            return $out;
        }

        $out .= self::render_table($result);
        $out .= self::render_pagination($result);

        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Render the active Funnel drill-down context.
     *
     * @param UserExplorerCriteria $criteria
     * @return string
     */
    private static function render_funnel_filter(
        UserExplorerCriteria $criteria
    ): string {
        $label = match ($criteria->funnelstage) {
            UserExplorerCriteria::FUNNEL_NEW_USERS =>
                get_string(
                    'dashboard_funnel_explorer_new_users',
                    'local_subscriptions'
                ),

            UserExplorerCriteria::FUNNEL_TRIAL_USERS =>
                get_string(
                    'dashboard_funnel_explorer_trial_users',
                    'local_subscriptions'
                ),

            UserExplorerCriteria::FUNNEL_NEW_CUSTOMERS =>
                get_string(
                    'dashboard_funnel_explorer_new_customers',
                    'local_subscriptions'
                ),

            UserExplorerCriteria::FUNNEL_DIGITAL_BUYERS =>
                get_string(
                    'dashboard_funnel_explorer_digital_buyers',
                    'local_subscriptions'
                ),

            UserExplorerCriteria::FUNNEL_CONVERTED_TRIALS =>
                get_string(
                    'dashboard_funnel_explorer_converted_trials',
                    'local_subscriptions',
                    $criteria->funnelwindow
                ),

            default => '',
        };

        if ($label === '') {
            return '';
        }

        $periodlabel =
            userdate(
                $criteria->funnelstart,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            )
            . ' – '
            . userdate(
                $criteria->funnelend - 1,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            );

        return html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string(
                        'dashboard_funnel_explorer_active',
                        'local_subscriptions'
                    )
                )
                . html_writer::span(
                    s($label),
                    'd-block'
                )
                . html_writer::span(
                    s($periodlabel),
                    'd-block small text-muted'
                ),
                'crm-user-explorer-funnel-filter-content'
            ),
            'alert alert-info crm-user-explorer-funnel-filter'
        );
    }
    private static function render_summary(
        UserExplorerResult $result
    ): string {
        $out = html_writer::start_div(
            'crm-user-explorer-summary'
        );

        $out .= html_writer::div(
            html_writer::tag(
                'strong',
                (string)$result->total,
                [
                    'class' => 'crm-user-explorer-summary-value',
                ]
            ) .
            html_writer::span(
                get_string(
                    'crm_user_explorer_result_count',
                    'local_subscriptions',
                    $result->total
                ),
                'crm-user-explorer-summary-label'
            ),
            'crm-user-explorer-summary-stat'
        );

        if ($result->active_filter_count() > 0) {
            $out .= html_writer::div(
                get_string(
                    'crm_user_explorer_active_filters',
                    'local_subscriptions',
                    $result->active_filter_count()
                ),
                'crm-user-explorer-active-filters'
            );

            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::admin_users_page()
                ),
                get_string(
                    'crm_user_explorer_clear_filters',
                    'local_subscriptions'
                ),
                [
                    'class' => 'btn btn-sm btn-outline-secondary',
                ]
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_workspace_toolbar(
        UserExplorerResult $result
    ): string {
        $criteria = $result->criteria;

        $returnurl = new moodle_url(
            subscription_config::admin_users_page(),
            $criteria->url_params()
        );

        $out = html_writer::start_div(
            'crm-user-explorer-workspace-toolbar'
        );

        /*
        * Saved views.
        */
        $out .= html_writer::start_div(
            'crm-user-explorer-saved-views'
        );

        foreach ($result->savedviews as $view) {
            $out .= html_writer::start_div(
                'crm-user-saved-view'
            );

            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::admin_users_page(),
                    $view->criteria
                ),
                s($view->name),
                [
                    'class' => 'crm-user-saved-view-link',
                ]
            );

            $out .= html_writer::start_tag(
                'form',
                [
                    'method' => 'post',
                    'action' => new moodle_url(
                        subscription_config::
                            admin_user_explorer_action_page()
                    ),
                    'class' =>
                        'crm-user-saved-view-delete-form',
                        'data-inbox-confirm' =>
                            get_string(
                                'crm_user_view_delete_confirm',
                                'local_subscriptions'
                            ),

                        'data-inbox-busy-form' => '1',

                        'data-busy-announcement' =>
                            get_string(
                                'crm_user_view_delete_processing',
                                'local_subscriptions'
                            ),
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'action',
                    'value' => 'delete_view',
                ]
            );

            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'view',
                    'value' => $view->id,
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
                    'name' => 'returnurl',
                    'value' =>
                        $returnurl
                            ->out_as_local_url(
                                false
                            ),
                ]
            );

            $out .= html_writer::tag(
                'button',
                html_writer::span(
                    '×',
                    '',
                    [
                        'aria-hidden' => 'true',
                    ]
                ),
                [
                    'type' => 'submit',

                    'class' =>
                        'crm-user-saved-view-delete',

                    'title' =>
                        get_string(
                            'crm_user_view_delete',
                            'local_subscriptions'
                        ),

                    'aria-label' =>
                        get_string(
                            'crm_user_view_delete',
                            'local_subscriptions'
                        ),

                    'data-loading-label' =>
                        get_string(
                            'crm_user_view_delete_processing_short',
                            'local_subscriptions'
                        ),
                ]
            );

            $out .= html_writer::end_tag(
                'form'
            );

            $out .= html_writer::end_div();
        }

        $out .= html_writer::end_div();

        /*
        * Export.
        */
        $out .= html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => new moodle_url(
                    subscription_config::
                        admin_user_explorer_export_page()
                ),
                'class' =>
                    'crm-user-explorer-export-form',
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

        foreach (
            $criteria->url_params(false)
            as $key => $value
        ) {
            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => $key,
                    'value' => (string)$value,
                ]
            );
        }

        $out .= html_writer::tag(
            'button',
            get_string(
                'crm_user_export_csv',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-sm btn-outline-secondary',
            ]
        );

        $out .= html_writer::end_tag(
            'form'
        );

        $out .= html_writer::end_div();

        /*
        * Save-current-view form.
        */
        $out .= html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => new moodle_url(
                    subscription_config::admin_user_explorer_action_page()
                ),
                'class' =>
                    'crm-user-save-view-form',
            ]
        );

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'save_view',
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'returnurl',
            'value' => $returnurl->out_as_local_url(false),
        ]);

        foreach ($criteria->saved_params() as $key => $value) {
            $out .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $key,
                'value' => (string)$value,
            ]);
        }

        $out .= html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'name',
            'required' => 'required',
            'maxlength' => 80,
            'class' => 'form-control form-control-sm',
            'placeholder' => get_string(
                'crm_user_view_name_placeholder',
                'local_subscriptions'
            ),
        ]);

        $out .= html_writer::tag(
            'button',
            get_string(
                'crm_user_save_view',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'class' => 'btn btn-sm btn-primary',
            ]
        );

        $out .= html_writer::end_tag('form');

        $out .= self::render_column_manager(
            $result,
            $returnurl
        );

        return $out;
    }    

    private static function render_column_manager(
        UserExplorerResult $result,
        moodle_url $returnurl
    ): string {
        $out = html_writer::start_tag(
            'details',
            [
                'class' => 'crm-user-column-manager',
            ]
        );

        $out .= html_writer::tag(
            'summary',
            get_string(
                'crm_user_configure_columns',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary',
            ]
        );

        $out .= html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => new moodle_url(
                    subscription_config::admin_user_explorer_action_page()
                ),
                'class' =>
                    'crm-user-column-manager-panel',
            ]
        );

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'save_columns',
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);

        $out .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'returnurl',
            'value' => $returnurl->out_as_local_url(false),
        ]);

        foreach (
            UserExplorerColumn::allowed(
                $result->canviewinbox
            )
            as $column
        ) {
            $id = 'crm-user-column-' . $column;

            $attributes = [
                'type' => 'checkbox',
                'id' => $id,
                'name' => 'columns[]',
                'value' => $column,
            ];

            if (in_array(
                $column,
                $result->visiblecolumns,
                true
            )) {
                $attributes['checked'] = 'checked';
            }

            if (in_array(
                $column,
                UserExplorerColumn::required(),
                true
            )) {
                $attributes['disabled'] = 'disabled';

                $out .= html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'hidden',
                        'name' => 'columns[]',
                        'value' => $column,
                    ]
                );
            }

            $out .= html_writer::div(
                html_writer::empty_tag(
                    'input',
                    $attributes
                ) .
                html_writer::tag(
                    'label',
                    UserExplorerColumn::label($column),
                    ['for' => $id]
                ),
                'crm-user-column-option'
            );
        }

        $out .= html_writer::tag(
            'button',
            get_string(
                'savechanges'
            ),
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-sm btn-primary mt-2',
            ]
        );

        $out .= html_writer::end_tag('form');
        $out .= html_writer::end_tag('details');

        return $out;
    }

    private static function render_filters(
        UserExplorerResult $result
    ): string {
        $criteria = $result->criteria;

        $out = html_writer::start_tag(
            'form',
            [
                'method' => 'get',
                'action' => new moodle_url(
                    subscription_config::admin_users_page()
                ),
                'class' => 'crm-user-explorer-filters',
            ]
        );

        if (
            $result->criteria
                ->trendfilter
                ->is_active()
        ) {
            foreach (
                $result->criteria
                    ->trendfilter
                    ->params()
                as $name => $value
            ) {
                $out .= html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'hidden',
                        'name' => $name,
                        'value' => $value,
                    ]
                );
            }
        }

        $out .= html_writer::start_div(
            'crm-user-explorer-filter-grid'
        );

        $out .= self::field(
            get_string(
                'crm_user_explorer_search_label',
                'local_subscriptions'
            ),
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'search',
                    'name' => 'q',
                    'value' => $criteria->query,
                    'class' => 'form-control',
                    'placeholder' => get_string(
                        'crm_search_user_placeholder',
                        'local_subscriptions'
                    ),
                ]
            ),
            'crm-user-explorer-filter-search'
        );

        $countryoptions = [
            '' => get_string(
                'crm_user_country_all',
                'local_subscriptions'
            ),
        ];

        foreach ($result->countries as $country) {
            $countryoptions[$country] = $country;
        }

        $out .= self::field(
            get_string(
                'country',
                'local_subscriptions'
            ),
            html_writer::select(
                $countryoptions,
                'country',
                $criteria->country,
                false,
                [
                    'class' => 'custom-select',
                ]
            )
        );

        $tagoptions = [
            '' => get_string(
                'crm_user_tag_all',
                'local_subscriptions'
            ),
        ];

        foreach ($result->tags as $tag) {
            $tagoptions[$tag] =
                \local_subscriptions\crm\user\UserProfileTag::label(
                    $tag
                );
        }

        $out .= self::field(
            get_string(
                'crm_user_tags',
                'local_subscriptions'
            ),
            html_writer::select(
                $tagoptions,
                'tag',
                $criteria->tag,
                false,
                [
                    'class' => 'custom-select',
                ]
            )
        );

        $accountoptions = [];

        foreach (
            UserExplorerCriteria::account_statuses()
            as $status
        ) {
            $accountoptions[$status] =
                UserExplorerCriteria::account_status_label(
                    $status
                );
        }

        $out .= self::field(
            get_string(
                'crm_user_account_status',
                'local_subscriptions'
            ),
            html_writer::select(
                $accountoptions,
                'accountstatus',
                $criteria->accountstatus,
                false,
                [
                    'class' => 'custom-select',
                ]
            )
        );

        $sortoptions = [];

        foreach (UserExplorerSort::allowed() as $sort) {
            $sortoptions[$sort] =
                UserExplorerSort::label($sort);
        }

        $out .= self::field(
            get_string(
                'crm_user_sort_label',
                'local_subscriptions'
            ),
            html_writer::select(
                $sortoptions,
                'sort',
                $criteria->sort,
                false,
                [
                    'class' => 'custom-select',
                ]
            )
        );

        $out .= self::field(
            get_string(
                'crm_user_per_page',
                'local_subscriptions'
            ),
            html_writer::select(
                [
                    25 => '25',
                    50 => '50',
                    100 => '100',
                ],
                'perpage',
                $criteria->perpage,
                false,
                [
                    'class' => 'custom-select',
                ]
            )
        );

        $out .= html_writer::end_div();

        $out .= html_writer::start_tag(
            'details',
            [
                'class' => 'crm-user-advanced-filters',
                'open' => (
                    $criteria->scoremin !== null ||
                    $criteria->scoremax !== null ||
                    $criteria->riskmin !== null ||
                    $criteria->riskmax !== null ||
                    $criteria->hassubscription !== '' ||
                    $criteria->haspurchase !== '' ||
                    $criteria->activity !== '' ||
                    $criteria->hasinbox !== '' ||
                    $criteria->hasinboxunread !== '' ||
                    $criteria->hascustomer_success_plan !== '' ||
                    $criteria->customer_success_plan_blocked !== '' ||
                    $criteria->customer_success_plan_status !== ''
                ) ? 'open' : null,
            ]
        );

        $out .= html_writer::tag(
            'summary',
            get_string(
                'crm_user_advanced_filters',
                'local_subscriptions'
            )
        );

        $out .= html_writer::start_div(
            'crm-user-advanced-filter-grid'
        );

        foreach ([
            'scoremin' => [
                get_string('crm_user_score_min', 'local_subscriptions'),
                $criteria->scoremin,
            ],
            'scoremax' => [
                get_string('crm_user_score_max', 'local_subscriptions'),
                $criteria->scoremax,
            ],
            'riskmin' => [
                get_string('crm_user_risk_min', 'local_subscriptions'),
                $criteria->riskmin,
            ],
            'riskmax' => [
                get_string('crm_user_risk_max', 'local_subscriptions'),
                $criteria->riskmax,
            ],
        ] as $name => [$label, $value]) {
            $out .= self::field(
                $label,
                html_writer::empty_tag('input', [
                    'type' => 'number',
                    'name' => $name,
                    'min' => 0,
                    'max' => 100,
                    'value' => $value ?? '',
                    'class' => 'form-control',
                ])
            );
        }

        $presenceoptions = [];

        foreach (
            UserExplorerCriteria::presence_options()
            as $presence
        ) {
            $presenceoptions[$presence] =
                UserExplorerCriteria::presence_label(
                    $presence
                );
        }

        $out .= self::field(
            get_string(
                'crm_user_has_subscription',
                'local_subscriptions'
            ),
            html_writer::select(
                $presenceoptions,
                'hassubscription',
                $criteria->hassubscription,
                false,
                ['class' => 'custom-select']
            )
        );

        $out .= self::field(
            get_string(
                'crm_user_has_purchase',
                'local_subscriptions'
            ),
            html_writer::select(
                $presenceoptions,
                'haspurchase',
                $criteria->haspurchase,
                false,
                ['class' => 'custom-select']
            )
        );

        if ($result->canviewinbox) {
            $out .= self::field(
                get_string(
                    'crm_user_has_inbox',
                    'local_subscriptions'
                ),
                html_writer::select(
                    $presenceoptions,
                    'hasinbox',
                    $criteria->hasinbox,
                    false,
                    [
                        'class' =>
                            'custom-select',
                    ]
                )
            );

            $out .= self::field(
                get_string(
                    'crm_user_has_inbox_unread',
                    'local_subscriptions'
                ),
                html_writer::select(
                    $presenceoptions,
                    'hasinboxunread',
                    $criteria
                        ->hasinboxunread,
                    false,
                    [
                        'class' =>
                            'custom-select',
                    ]
                )
            );

            $out .= self::field(
                get_string(
                    'crm_user_has_customer_success_plan',
                    'local_subscriptions'
                ),
                html_writer::select(
                    array_combine(
                        UserExplorerCriteria::presence_options(),
                        array_map(
                            [
                                UserExplorerCriteria::class,
                                'presence_label',
                            ],
                            UserExplorerCriteria::presence_options()
                        )
                    ),
                    'hascustomer_success_plan',
                    $criteria->hascustomer_success_plan,
                    false,
                    [
                        'class' => 'form-control',
                    ]
                )
            );

            $out .= self::field(
                get_string(
                    'crm_user_customer_success_plan_blocked',
                    'local_subscriptions'
                ),
                html_writer::select(
                    array_combine(
                        UserExplorerCriteria::presence_options(),
                        array_map(
                            [
                                UserExplorerCriteria::class,
                                'presence_label',
                            ],
                            UserExplorerCriteria::presence_options()
                        )
                    ),
                    'customer_success_plan_blocked',
                    $criteria->customer_success_plan_blocked,
                    false,
                    [
                        'class' => 'form-control',
                    ]
                )
            );

            $out .= self::field(
                get_string(
                    'crm_user_customer_success_plan_status',
                    'local_subscriptions'
                ),
                html_writer::select(
                    [
                        '' =>
                            get_string(
                                'crm_user_customer_success_plan_status_all',
                                'local_subscriptions'
                            ),

                        'draft' =>
                            get_string(
                                'csplanstatus_draft',
                                'local_subscriptions'
                            ),

                        'active' =>
                            get_string(
                                'csplanstatus_active',
                                'local_subscriptions'
                            ),

                        'paused' =>
                            get_string(
                                'csplanstatus_paused',
                                'local_subscriptions'
                            ),

                        'completed' =>
                            get_string(
                                'csplanstatus_completed',
                                'local_subscriptions'
                            ),

                        'cancelled' =>
                            get_string(
                                'csplanstatus_cancelled',
                                'local_subscriptions'
                            ),
                    ],
                    'customer_success_plan_status',
                    $criteria->customer_success_plan_status,
                    false,
                    [
                        'class' => 'form-control',
                    ]
                )
            );

        }
        
        $activityoptions = [];

        foreach (
            UserExplorerCriteria::activity_options()
            as $activity
        ) {
            $activityoptions[$activity] =
                UserExplorerCriteria::activity_label(
                    $activity
                );
        }

        $out .= self::field(
            get_string(
                'crm_user_activity_filter',
                'local_subscriptions'
            ),
            html_writer::select(
                $activityoptions,
                'activity',
                $criteria->activity,
                false,
                ['class' => 'custom-select']
            )
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('details');


        if ($criteria->intelligence !== '') {
            $out .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => 'intelligence',
                    'value' => $criteria->intelligence,
                ]
            );
        }

        $out .= html_writer::div(
            html_writer::tag(
                'button',
                get_string(
                    'crm_user_apply_filters',
                    'local_subscriptions'
                ),
                [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                ]
            ),
            'crm-user-explorer-filter-actions'
        );

        $out .= html_writer::end_tag('form');

        return $out;
    }

    private static function render_intelligence_pills(
        UserExplorerResult $result
    ): string {
        $criteria = $result->criteria;

        $out = html_writer::start_div(
            'crm-user-filter-pills ' .
            'crm-user-explorer-intelligence-pills'
        );

        foreach (UserExplorerFilter::allowed() as $filter) {
            $classes = 'crm-user-filter-pill';

            if ($filter === $criteria->intelligence) {
                $classes .= ' active';
            }

            $params = $criteria->url_params(false);
            $params['intelligence'] = $filter;

            if ($filter === '') {
                unset($params['intelligence']);
            }

            $out .= html_writer::link(
                new moodle_url(
                    subscription_config::admin_users_page(),
                    $params
                ),
                UserExplorerFilter::label($filter),
                [
                    'class' => $classes,
                ]
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function render_table(
        UserExplorerResult $result
    ): string {
        $table = new html_table();

        $table->attributes = [
            'class' => 'generaltable crm-user-explorer-table',
        ];

        $table->head = array_map(
            static fn(string $column): string =>
                UserExplorerColumn::label($column),
            $result->visiblecolumns
        );

        foreach ($result->users as $viewmodel) {
            $table->data[] = self::render_user_row(
                $viewmodel,
                $result->visiblecolumns
            );
        }

        return html_writer::div(
            html_writer::table($table),
            'table-responsive crm-user-explorer-table-wrapper'
        );
    }

    private static function render_user_row(
        UserExplorerUserViewModel $viewmodel,
        array $columns
    ): array {
        $user = $viewmodel->user;

        $identity =
            AdminEntityLinks::user(
                (int)$user->id,
                s(fullname($user)),
                [
                    'class' => 'crm-user-explorer-user-link',
                ]
            ) .
            html_writer::div(
                s((string)$user->email),
                'crm-user-explorer-email'
            ) .
            html_writer::div(
                self::render_account_status($viewmodel) .
                (
                    !empty($user->country)
                        ? html_writer::span(
                            s((string)$user->country),
                            'crm-user-country'
                        )
                        : ''
                ),
                'crm-user-explorer-user-meta'
            );

        $cells = [];

        foreach ($columns as $column) {
            $cells[] = match ($column) {
                UserExplorerColumn::USER =>
                    $identity,

                UserExplorerColumn::TAGS =>
                    self::render_tags($viewmodel->tags),

                UserExplorerColumn::SCORE =>
                    self::render_score($viewmodel),

                UserExplorerColumn::RISK =>
                    self::render_risk($viewmodel),

                UserExplorerColumn::INTELLIGENCE =>
                    self::render_intelligence($viewmodel),

                UserExplorerColumn::SUBSCRIPTIONS =>
                    html_writer::span(
                        (string)$user->subscriptioncount,
                        'crm-user-explorer-count'
                    ),

                UserExplorerColumn::PURCHASES =>
                    html_writer::span(
                        (string)$user->purchasecount,
                        'crm-user-explorer-count'
                    ),

                UserExplorerColumn::INBOX =>
                    self::render_inbox($viewmodel),

                UserExplorerColumn::CUSTOMER_SUCCESS_PLANS =>
                    self::render_customer_success_plans(
                        $viewmodel
                    ),

                UserExplorerColumn::COUNTRY =>
                    !empty($user->country)
                        ? s((string)$user->country)
                        : '—',

                UserExplorerColumn::REGISTERED =>
                    !empty($user->timecreated)
                        ? AdminFormatter::datetime(
                            (int)$user->timecreated
                        )
                        : '—',

                UserExplorerColumn::LAST_ACCESS =>
                    !empty($user->lastaccess)
                        ? AdminFormatter::datetime(
                            (int)$user->lastaccess
                        )
                        : '—',

                default => '—',
            };
        }

        return $cells;
    }

    private static function render_customer_success_plans(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $user = $viewmodel->user;

        $opencount = (int)(
            $user->customer_success_open_count
            ?? 0
        );

        $blockedcount = (int)(
            $user->customer_success_blocked_count
            ?? 0
        );

        $priority = trim(
            (string)(
                $user->customer_success_highest_priority
                ?? ''
            )
        );

        if ($opencount <= 0) {
            return html_writer::span(
                get_string(
                    'crm_user_customer_success_none',
                    'local_subscriptions'
                ),
                'text-muted'
            );
        }

        $out = html_writer::span(
            get_string(
                'crm_user_customer_success_open_count',
                'local_subscriptions',
                $opencount
            ),
            'crm-user-explorer-count'
        );

        if ($blockedcount > 0) {
            $out .= html_writer::span(
                get_string(
                    'crm_user_customer_success_blocked_count',
                    'local_subscriptions',
                    $blockedcount
                ),
                'badge badge-warning ml-1'
            );
        }

        if ($priority !== '') {
            $out .= html_writer::span(
                get_string(
                    'csplanpriority_' . $priority,
                    'local_subscriptions'
                ),
                'badge badge-secondary ml-1'
            );
        }

        return $out;
    }

    private static function render_inbox(
        UserExplorerUserViewModel $viewmodel
    ): string {

        $user = $viewmodel->user;

        $conversationcount = (int)(
            $user->inboxconversationcount
            ?? 0
        );

        $opencount = (int)(
            $user->inboxopenconversationcount
            ?? 0
        );

        $unreadcount = (int)(
            $user->inboxunreadcount
            ?? 0
        );

        $urgentcount = (int)(
            $user->inboxurgentcount
            ?? 0
        );

        if ($conversationcount <= 0) {
            return html_writer::span(
                get_string(
                    'crm_user_inbox_none',
                    'local_subscriptions'
                ),
                'text-muted'
            );
        }

        $url = new moodle_url(
            subscription_config::
                admin_inbox_page(),
            [
                'q' => (string)$user->email,
            ]
        );

        $summary = html_writer::link(
            $url,
            get_string(
                'crm_user_inbox_conversation_count',
                'local_subscriptions',
                $conversationcount
            ),
            [
                'class' =>
                    'crm-user-explorer-inbox-link',
            ]
        );

        $badges = '';

        if ($opencount > 0) {
            $badges .= html_writer::span(
                get_string(
                    'crm_user_inbox_open_count',
                    'local_subscriptions',
                    $opencount
                ),
                'badge bg-primary'
            );
        }

        if ($unreadcount > 0) {
            $badges .= html_writer::span(
                get_string(
                    'crm_user_inbox_unread_count',
                    'local_subscriptions',
                    $unreadcount
                ),
                'badge bg-danger'
            );
        }

        if ($urgentcount > 0) {
            $badges .= html_writer::span(
                get_string(
                    'crm_user_inbox_urgent_count',
                    'local_subscriptions',
                    $urgentcount
                ),
                'badge bg-warning text-dark'
            );
        }

        $lastmessage = '';

        if (
            !empty(
                $user->inboxlastmessageat
            )
        ) {
            $lastmessage = html_writer::div(
                AdminFormatter::datetime(
                    (int)$user->inboxlastmessageat
                ),
                'crm-user-explorer-inbox-last text-muted small'
            );
        }

        return html_writer::div(
            $summary .
            html_writer::div(
                $badges,
                'crm-user-explorer-inbox-badges'
            ) .
            $lastmessage,
            'crm-user-explorer-inbox'
        );
    }

    private static function render_account_status(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $class = !empty($viewmodel->user->suspended)
            ? 'suspended'
            : 'active';

        return html_writer::span(
            s($viewmodel->account_status_label()),
            'crm-user-account-badge ' . $class
        );
    }

    private static function render_tags(
        array $tags
    ): string {
        if (!$tags) {
            return '—';
        }

        return html_writer::div(
            implode(
                '',
                array_map(
                    static function(\stdClass $tag): string {
                        return html_writer::span(
                            s((string)$tag->label),
                            'crm-user-tag-badge'
                        );
                    },
                    $tags
                )
            ),
            'crm-user-tag-list'
        );
    }

    private static function render_score(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $score = (int)$viewmodel->user->globalscore;

        return html_writer::div(
            html_writer::span(
                (string)$score,
                'crm-user-score-value'
            ) .
            html_writer::span(
                s($viewmodel->score_level_label()),
                'crm-user-score-level'
            ),
            'crm-user-score'
        );
    }

    private static function render_risk(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $risk = (int)$viewmodel->user->riskscore;
        $class = 'low';

        if ($risk >= 61) {
            $class = 'high';
        } else if ($risk >= 31) {
            $class = 'medium';
        }

        return html_writer::span(
            (string)$risk,
            'crm-user-risk-badge ' . $class
        );
    }

    private static function render_intelligence(
        UserExplorerUserViewModel $viewmodel
    ): string {
        $items = array_merge(
            $viewmodel->segments,
            $viewmodel->opportunities
        );

        if (!$items) {
            return '—';
        }

        $items = array_slice($items, 0, 3);

        return html_writer::div(
            implode(
                '',
                array_map(
                    static function(\stdClass $item): string {
                        return html_writer::span(
                            s((string)$item->label),
                            'crm-user-intelligence-badge'
                        );
                    },
                    $items
                )
            ),
            'crm-user-intelligence-list'
        );
    }

    private static function render_pagination(
        UserExplorerResult $result
    ): string {
        global $OUTPUT;

        $url = new moodle_url(
            subscription_config::admin_users_page(),
            $result->criteria->url_params(false)
        );

        return $OUTPUT->paging_bar(
            $result->total,
            $result->criteria->page,
            $result->criteria->perpage,
            $url
        );
    }

    private static function render_empty_state(
        UserExplorerResult $result
    ): string {
        $actions = '';

        if ($result->active_filter_count() > 0) {
            $actions = html_writer::link(
                new moodle_url(
                    subscription_config::admin_users_page()
                ),
                get_string(
                    'crm_user_explorer_clear_filters',
                    'local_subscriptions'
                ),
                [
                    'class' => 'btn btn-outline-secondary mt-3',
                ]
            );
        }

        return html_writer::div(
            html_writer::div(
                '🔎',
                'crm-user-explorer-empty-icon',
                [
                    'aria-hidden' => 'true',
                ]
            ) .
            html_writer::tag(
                'h3',
                get_string(
                    'crm_user_explorer_empty_title',
                    'local_subscriptions'
                ),
                [
                    'class' => 'crm-user-explorer-empty-title',
                ]
            ) .
            html_writer::div(
                get_string(
                    'crm_user_explorer_empty_description',
                    'local_subscriptions'
                ),
                'crm-user-explorer-empty-description'
            ) .
            $actions,
            'crm-user-explorer-empty'
        );
    }

    private static function field(
        string $label,
        string $control,
        string $classes = ''
    ): string {
        return html_writer::div(
            html_writer::tag(
                'label',
                s($label),
                [
                    'class' => 'crm-user-explorer-filter-label',
                ]
            ) .
            $control,
            trim(
                'crm-user-explorer-filter-field ' .
                $classes
            )
        );
    }

    /**
     * Render the active Dashboard trend drill-down context.
     */
    private static function render_trend_context(
        UserExplorerResult $result
    ): string {
        $filter =
            $result->criteria->trendfilter;

        if (!$filter->is_active()) {
            return '';
        }

        $labelkey = match ($filter->trend) {
            \local_subscriptions\dashboard\trends\DashboardTrendsRepository::METRIC_RISK_UP =>
                        'crm_trends_metric_risk_up',

            \local_subscriptions\dashboard\trends\DashboardTrendsRepository::METRIC_RISK_DOWN =>
                        'crm_trends_metric_risk_down',

            \local_subscriptions\dashboard\trends\DashboardTrendsRepository::METRIC_ENGAGEMENT_UP =>
                        'crm_trends_metric_engagement_up',

            \local_subscriptions\dashboard\trends\DashboardTrendsRepository::METRIC_ENGAGEMENT_DOWN =>
                        'crm_trends_metric_engagement_down',

            \local_subscriptions\dashboard\trends\DashboardTrendsRepository::METRIC_GLOBAL_UP =>
                        'crm_trends_metric_global_up',

            \local_subscriptions\dashboard\trends\DashboardTrendsRepository::METRIC_GLOBAL_DOWN =>
                        'crm_trends_metric_global_down',

            default =>
                'crm_trends_metric_unknown',
        };

        $period = get_string(
            'crm_user_explorer_trend_period',
            'local_subscriptions',
            (object)[
                'start' => userdate(
                    $filter->start,
                    get_string(
                        'strftimedate',
                        'langconfig'
                    )
                ),
                'end' => userdate(
                    max(
                        $filter->start,
                        $filter->end - 1
                    ),
                    get_string(
                        'strftimedate',
                        'langconfig'
                    )
                ),
            ]
        );

        $threshold = get_string(
            'crm_user_explorer_trend_threshold',
            'local_subscriptions',
            $filter->delta
        );

        $title = html_writer::tag(
            'strong',
            get_string(
                'crm_user_explorer_trend_active',
                'local_subscriptions'
            )
        );

        $details = html_writer::div(
            html_writer::span(
                get_string(
                    $labelkey,
                    'local_subscriptions'
                ),
                'crm-user-explorer-trend-name'
            )
            . html_writer::span(
                $period,
                'crm-user-explorer-trend-period'
            )
            . html_writer::span(
                $threshold,
                'crm-user-explorer-trend-threshold'
            ),
            'crm-user-explorer-trend-details'
        );

        $clearurl = new moodle_url(
            subscription_config::admin_users_page()
        );

        $clear = html_writer::link(
            $clearurl,
            get_string(
                'crm_user_explorer_trend_clear',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary '
                    . 'crm-user-explorer-trend-clear',
            ]
        );

        return html_writer::div(
            html_writer::div(
                $title . $details,
                'crm-user-explorer-trend-content'
            )
            . $clear,
            'crm-user-explorer-trend-context'
        );
    }

}