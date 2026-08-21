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

        $out .= self::render_kpis($result);
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


    private static function render_kpis(UserExplorerResult $result): string {
        $criteria = $result->criteria;

        $cards = [
            [
                'key' => 'users',
                'label' => UserExplorerFilter::label(''),
                'icon' => 'fa fa-users',
                'tone' => 'users',
                'params' => ['intelligence' => '', 'accountstatus' => ''],
            ],
            [
                'key' => 'hot_leads',
                'label' => UserExplorerFilter::label(UserExplorerFilter::HOT_LEAD),
                'icon' => 'fa fa-fire',
                'tone' => 'hot',
                'params' => ['intelligence' => UserExplorerFilter::HOT_LEAD, 'accountstatus' => ''],
            ],
            [
                'key' => 'at_risk',
                'label' => UserExplorerFilter::label(UserExplorerFilter::AT_RISK),
                'icon' => 'fa fa-exclamation-triangle',
                'tone' => 'risk',
                'params' => ['intelligence' => UserExplorerFilter::AT_RISK, 'accountstatus' => ''],
            ],
            [
                'key' => 'vip',
                'label' => UserExplorerFilter::label(UserExplorerFilter::VIP),
                'icon' => 'fa fa-diamond',
                'tone' => 'vip',
                'params' => ['intelligence' => UserExplorerFilter::VIP, 'accountstatus' => ''],
            ],
            [
                'key' => 'suspended',
                'label' => get_string('crm_user_kpi_suspended', 'local_subscriptions'),
                'icon' => 'fa fa-pause-circle',
                'tone' => 'suspended',
                'params' => [
                    'intelligence' => '',
                    'accountstatus' => UserExplorerCriteria::ACCOUNT_SUSPENDED,
                ],
            ],
            [
                'key' => 'no_moodle',
                'label' => get_string('crm_user_kpi_no_moodle', 'local_subscriptions'),
                'icon' => 'fa fa-user-times',
                'tone' => 'nomoodle',
                'params' => [
                    'intelligence' => '',
                    'accountstatus' => UserExplorerCriteria::ACCOUNT_NO_MOODLE,
                ],
            ],
        ];

        $html = html_writer::start_div('crm-user-explorer-kpi-grid');

        foreach ($cards as $card) {
            $content = html_writer::div(
                html_writer::tag('i', '', [
                    'class' => $card['icon'],
                    'aria-hidden' => 'true',
                ]),
                'crm-user-explorer-kpi-icon'
            )
            . html_writer::div(
                html_writer::tag(
                    'strong',
                    (string)($result->kpis[$card['key']] ?? 0),
                    ['class' => 'crm-user-explorer-kpi-value']
                )
                . html_writer::span(
                    s($card['label']),
                    'crm-user-explorer-kpi-label'
                ),
                'crm-user-explorer-kpi-copy'
            );

            $classes = 'crm-user-explorer-kpi-card crm-user-explorer-kpi-card--'
                . $card['tone'];

            $params = $criteria->url_params(false);
            unset($params['page']);
            foreach ($card['params'] as $key => $value) {
                if ($value === '') {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
            }

            $active = match ($card['key']) {
                'users' => $criteria->intelligence === ''
                    && $criteria->accountstatus === '',
                'suspended' => $criteria->accountstatus
                    === UserExplorerCriteria::ACCOUNT_SUSPENDED,
                'no_moodle' => $criteria->accountstatus
                    === UserExplorerCriteria::ACCOUNT_NO_MOODLE,
                default => $criteria->intelligence
                    === ($card['params']['intelligence'] ?? ''),
            };

            $html .= html_writer::link(
                new moodle_url(
                    subscription_config::admin_users_page(),
                    $params
                ),
                $content,
                ['class' => $classes . ($active ? ' is-active' : '')]
            );
        }

        $html .= html_writer::end_div();

        return $html;
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
            'crm-user-explorer-filter-utilities'
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
            html_writer::tag('i', '', [
                'class' => 'fa fa-download',
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                get_string(
                    'crm_user_export_csv',
                    'local_subscriptions'
                )
            ),
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-sm btn-outline-secondary crm-user-utility-button',
            ]
        );

        $out .= html_writer::end_tag(
            'form'
        );

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
            html_writer::tag('i', '', [
                'class' => 'fa fa-bookmark-o',
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                get_string(
                    'crm_user_save_view',
                    'local_subscriptions'
                )
            ),
            [
                'type' => 'submit',
                'class' => 'btn btn-sm btn-primary crm-user-utility-button',
                'title' => get_string(
                    'crm_user_save_view_help',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= html_writer::end_tag('form');

        $out .= self::render_column_manager(
            $result,
            $returnurl
        );

        $out .= html_writer::end_div();

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
            html_writer::tag('i', '', [
                'class' => 'fa fa-columns',
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                get_string(
                    'crm_user_configure_columns',
                    'local_subscriptions'
                )
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary crm-user-utility-button',
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
        $filtersactive = $result->active_filter_count() > 0;

        $panelstatus = $filtersactive
            ? get_string(
                'crm_user_filter_panel_active',
                'local_subscriptions',
                $result->active_filter_count()
            )
            : get_string(
                'crm_user_filter_panel_hint',
                'local_subscriptions'
            );

        $form = html_writer::start_tag(
            'form',
            [
                'method' => 'get',
                'action' => new moodle_url(
                    subscription_config::admin_users_page()
                ),
                'class' => 'crm-sales-filter-form crm-user-explorer-filters',
            ]
        );

        if ($criteria->trendfilter->is_active()) {
            foreach ($criteria->trendfilter->params() as $name => $value) {
                $form .= html_writer::empty_tag('input', [
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value,
                ]);
            }
        }

        $form .= html_writer::start_div(
            'crm-sales-filter-grid crm-user-explorer-filter-grid'
        );

        $form .= self::field(
            get_string(
                'crm_user_explorer_search_label',
                'local_subscriptions'
            ),
            html_writer::div(
                html_writer::empty_tag('input', [
                    'type' => 'search',
                    'name' => 'q',
                    'value' => $criteria->query,
                    'class' => 'form-control',
                    'placeholder' => get_string(
                        'crm_search_user_placeholder',
                        'local_subscriptions'
                    ),
                ])
                . html_writer::tag('i', '', [
                    'class' => 'fa fa-search crm-sales-search-icon',
                    'aria-hidden' => 'true',
                ]),
                'crm-sales-search-control'
            ),
            'crm-user-explorer-filter-search'
        );

        $countryoptions = [
            '' => get_string('crm_user_country_all', 'local_subscriptions'),
        ];
        foreach ($result->countries as $country) {
            $countryoptions[$country] = $country;
        }
        $form .= self::field(
            get_string('country', 'local_subscriptions'),
            html_writer::select(
                $countryoptions,
                'country',
                $criteria->country,
                false,
                ['class' => 'form-select']
            )
        );

        $tagoptions = [
            '' => get_string('crm_user_tag_all', 'local_subscriptions'),
        ];
        foreach ($result->tags as $tag) {
            $tagoptions[$tag] =
                \local_subscriptions\crm\user\UserProfileTag::label($tag);
        }
        $form .= self::field(
            get_string('crm_user_tags', 'local_subscriptions'),
            html_writer::select(
                $tagoptions,
                'tag',
                $criteria->tag,
                false,
                ['class' => 'form-select']
            )
        );

        $accountoptions = [];
        foreach (UserExplorerCriteria::account_statuses() as $status) {
            $accountoptions[$status] =
                UserExplorerCriteria::account_status_label($status);
        }
        $form .= self::field(
            get_string('crm_user_account_status', 'local_subscriptions'),
            html_writer::select(
                $accountoptions,
                'accountstatus',
                $criteria->accountstatus,
                false,
                ['class' => 'form-select']
            )
        );

        $sortoptions = [];
        foreach (UserExplorerSort::allowed() as $sort) {
            $sortoptions[$sort] = UserExplorerSort::label($sort);
        }
        $form .= self::field(
            get_string('crm_user_sort_label', 'local_subscriptions'),
            html_writer::select(
                $sortoptions,
                'sort',
                $criteria->sort,
                false,
                ['class' => 'form-select']
            )
        );

        $form .= html_writer::end_div();

        $advancedopen =
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
            $criteria->customer_success_plan_status !== '';

        $form .= html_writer::start_tag('details', [
            'class' =>
                'crm-sales-filter-advanced crm-user-advanced-filters',
            'open' => $advancedopen ? 'open' : null,
        ]);
        $form .= html_writer::tag(
            'summary',
            html_writer::tag('i', '', [
                'class' => 'fa fa-sliders',
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                get_string(
                    'crm_user_advanced_filters',
                    'local_subscriptions'
                )
            )
        );
        $form .= html_writer::start_div(
            'crm-sales-filter-advanced-grid crm-user-advanced-filter-grid'
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
            $form .= self::field(
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
        foreach (UserExplorerCriteria::presence_options() as $presence) {
            $presenceoptions[$presence] =
                UserExplorerCriteria::presence_label($presence);
        }

        $presencefields = [
            'hassubscription' => [
                get_string(
                    'crm_user_has_subscription',
                    'local_subscriptions'
                ),
                $criteria->hassubscription,
            ],
            'haspurchase' => [
                get_string(
                    'crm_user_has_purchase',
                    'local_subscriptions'
                ),
                $criteria->haspurchase,
            ],
        ];

        if ($result->canviewinbox) {
            $presencefields += [
                'hasinbox' => [
                    get_string(
                        'crm_user_has_inbox',
                        'local_subscriptions'
                    ),
                    $criteria->hasinbox,
                ],
                'hasinboxunread' => [
                    get_string(
                        'crm_user_has_inbox_unread',
                        'local_subscriptions'
                    ),
                    $criteria->hasinboxunread,
                ],
                'hascustomer_success_plan' => [
                    get_string(
                        'crm_user_has_customer_success_plan',
                        'local_subscriptions'
                    ),
                    $criteria->hascustomer_success_plan,
                ],
                'customer_success_plan_blocked' => [
                    get_string(
                        'crm_user_customer_success_plan_blocked',
                        'local_subscriptions'
                    ),
                    $criteria->customer_success_plan_blocked,
                ],
            ];
        }

        foreach ($presencefields as $name => [$label, $value]) {
            $form .= self::field(
                $label,
                html_writer::select(
                    $presenceoptions,
                    $name,
                    $value,
                    false,
                    ['class' => 'form-select']
                )
            );
        }

        if ($result->canviewinbox) {
            $form .= self::field(
                get_string(
                    'crm_user_customer_success_plan_status',
                    'local_subscriptions'
                ),
                html_writer::select(
                    [
                        '' => get_string(
                            'crm_user_customer_success_plan_status_all',
                            'local_subscriptions'
                        ),
                        'draft' => get_string(
                            'csplanstatus_draft',
                            'local_subscriptions'
                        ),
                        'active' => get_string(
                            'csplanstatus_active',
                            'local_subscriptions'
                        ),
                        'paused' => get_string(
                            'csplanstatus_paused',
                            'local_subscriptions'
                        ),
                        'completed' => get_string(
                            'csplanstatus_completed',
                            'local_subscriptions'
                        ),
                        'cancelled' => get_string(
                            'csplanstatus_cancelled',
                            'local_subscriptions'
                        ),
                    ],
                    'customer_success_plan_status',
                    $criteria->customer_success_plan_status,
                    false,
                    ['class' => 'form-select']
                )
            );
        }

        $activityoptions = [];
        foreach (UserExplorerCriteria::activity_options() as $activity) {
            $activityoptions[$activity] =
                UserExplorerCriteria::activity_label($activity);
        }
        $form .= self::field(
            get_string(
                'crm_user_activity_filter',
                'local_subscriptions'
            ),
            html_writer::select(
                $activityoptions,
                'activity',
                $criteria->activity,
                false,
                ['class' => 'form-select']
            )
        );

        $form .= html_writer::end_div();
        $form .= html_writer::end_tag('details');

        if ($criteria->intelligence !== '') {
            $form .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'intelligence',
                'value' => $criteria->intelligence,
            ]);
        }

        $form .= html_writer::div(
            html_writer::link(
                new moodle_url(
                    subscription_config::admin_users_page()
                ),
                get_string('reset'),
                ['class' => 'btn btn-sm btn-outline-secondary']
            )
            . html_writer::tag(
                'button',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-filter',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string(
                        'crm_user_apply_filters',
                        'local_subscriptions'
                    )
                ),
                [
                    'type' => 'submit',
                    'class' => 'btn btn-sm btn-primary',
                ]
            ),
            'crm-sales-filter-actions crm-user-explorer-filter-actions'
        );

        $form .= html_writer::end_tag('form');

        $summary = html_writer::tag(
            'summary',
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-filter',
                    'aria-hidden' => 'true',
                ])
                . html_writer::tag(
                    'strong',
                    get_string(
                        'crm_user_search_filters_title',
                        'local_subscriptions'
                    )
                )
                . html_writer::span(
                    s($panelstatus),
                    'crm-sales-filter-panel-status'
                ),
                'crm-sales-filter-panel-summary-copy'
            )
            . html_writer::tag('i', '', [
                'class' =>
                    'fa fa-chevron-down crm-sales-filter-panel-chevron',
                'aria-hidden' => 'true',
            ]),
            ['class' => 'crm-sales-filter-panel-summary']
        );

        $utilities = self::render_workspace_toolbar($result);

        return html_writer::tag(
            'details',
            $summary
            . html_writer::div(
                $form . $utilities,
                'crm-sales-filter-card crm-sales-filter-card-collapsible'
            ),
            [
                'class' =>
                    'crm-sales-filter-panel crm-user-explorer-filter-panel',
                'open' => $filtersactive ? 'open' : null,
            ]
        );
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
            'class' =>
                'generaltable crm-sales-table crm-user-explorer-table',
        ];

        $table->head = array_map(
            static fn(string $column): string =>
                self::render_table_header($column, $result->criteria),
            $result->visiblecolumns
        );
        $table->head[] = get_string(
            'crm_user_actions',
            'local_subscriptions'
        );

        foreach ($result->users as $viewmodel) {
            $row = self::render_user_row(
                $viewmodel,
                $result->visiblecolumns
            );
            $row[] = self::render_row_actions(
                $viewmodel,
                $result->canviewinbox
            );
            $table->data[] = $row;
        }

        $toolbar = html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'strong',
                    get_string(
                        'crm_user_explorer_found',
                        'local_subscriptions',
                        $result->total
                    ),
                    ['class' => 'crm-sales-table-count']
                )
                . self::render_active_filter_pills($result),
                'crm-user-explorer-table-meta'
            )
            . self::render_perpage_selector($result),
            'crm-sales-table-toolbar crm-user-explorer-table-toolbar'
        );

        return html_writer::div(
            $toolbar
            . html_writer::div(
                html_writer::table($table),
                'crm-sales-table-scroll'
            ),
            'crm-sales-table-card crm-user-explorer-table-card'
        );
    }

    private static function render_active_filter_pills(
        UserExplorerResult $result
    ): string {
        $criteria = $result->criteria;
        $pills = [];

        $addpill = static function (
            string $label,
            string $value
        ) use (&$pills): void {
            $value = trim($value);
            if ($value === '') {
                return;
            }

            $pills[] = html_writer::span(
                html_writer::span(
                    s($label),
                    'crm-user-explorer-result-pill-label'
                )
                . html_writer::span(
                    s($value),
                    'crm-user-explorer-result-pill-value'
                ),
                'crm-user-explorer-result-pill'
            );
        };

        if ($criteria->query !== '') {
            $addpill(
                get_string(
                    'crm_user_explorer_search_label',
                    'local_subscriptions'
                ),
                $criteria->query
            );
        }

        if ($criteria->country !== '') {
            $addpill(
                get_string('country', 'local_subscriptions'),
                $criteria->country
            );
        }

        if ($criteria->tag !== '') {
            $addpill(
                get_string('crm_user_tags', 'local_subscriptions'),
                \local_subscriptions\crm\user\UserProfileTag::label(
                    $criteria->tag
                )
            );
        }

        if ($criteria->accountstatus !== '') {
            $addpill(
                get_string(
                    'crm_user_account_status',
                    'local_subscriptions'
                ),
                UserExplorerCriteria::account_status_label(
                    $criteria->accountstatus
                )
            );
        }

        if ($criteria->intelligence !== '') {
            $addpill(
                get_string(
                    'crm_user_filter_type_label',
                    'local_subscriptions'
                ),
                UserExplorerFilter::label($criteria->intelligence)
            );
        }

        foreach ([
            [
                get_string('crm_user_score_min', 'local_subscriptions'),
                $criteria->scoremin,
            ],
            [
                get_string('crm_user_score_max', 'local_subscriptions'),
                $criteria->scoremax,
            ],
            [
                get_string('crm_user_risk_min', 'local_subscriptions'),
                $criteria->riskmin,
            ],
            [
                get_string('crm_user_risk_max', 'local_subscriptions'),
                $criteria->riskmax,
            ],
        ] as [$label, $value]) {
            if ($value !== null) {
                $addpill($label, (string)$value);
            }
        }

        foreach ([
            [
                get_string(
                    'crm_user_has_subscription',
                    'local_subscriptions'
                ),
                $criteria->hassubscription,
            ],
            [
                get_string(
                    'crm_user_has_purchase',
                    'local_subscriptions'
                ),
                $criteria->haspurchase,
            ],
            [
                get_string(
                    'crm_user_has_inbox',
                    'local_subscriptions'
                ),
                $criteria->hasinbox,
            ],
            [
                get_string(
                    'crm_user_has_inbox_unread',
                    'local_subscriptions'
                ),
                $criteria->hasinboxunread,
            ],
            [
                get_string(
                    'crm_user_has_customer_success_plan',
                    'local_subscriptions'
                ),
                $criteria->hascustomer_success_plan,
            ],
            [
                get_string(
                    'crm_user_customer_success_plan_blocked',
                    'local_subscriptions'
                ),
                $criteria->customer_success_plan_blocked,
            ],
        ] as [$label, $value]) {
            if ($value !== '') {
                $addpill(
                    $label,
                    UserExplorerCriteria::presence_label($value)
                );
            }
        }

        if ($criteria->customer_success_plan_status !== '') {
            $addpill(
                get_string(
                    'crm_user_customer_success_plan_status',
                    'local_subscriptions'
                ),
                get_string(
                    'csplanstatus_' . $criteria->customer_success_plan_status,
                    'local_subscriptions'
                )
            );
        }

        if ($criteria->activity !== '') {
            $addpill(
                get_string(
                    'crm_user_activity_filter',
                    'local_subscriptions'
                ),
                UserExplorerCriteria::activity_label($criteria->activity)
            );
        }

        if (!$pills) {
            return '';
        }

        return html_writer::div(
            html_writer::span(
                get_string(
                    'crm_user_explorer_active_filters_short',
                    'local_subscriptions'
                ),
                'crm-user-explorer-result-pills-prefix'
            )
            . implode('', $pills),
            'crm-user-explorer-result-pills'
        );
    }

    private static function render_perpage_selector(
        UserExplorerResult $result
    ): string {
        $criteria = $result->criteria;
        $params = $criteria->url_params(false);
        unset($params['page'], $params['perpage']);

        $form = html_writer::start_tag(
            'form',
            [
                'method' => 'get',
                'action' => new moodle_url(
                    subscription_config::admin_users_page()
                ),
                'class' => 'crm-user-explorer-perpage-form',
            ]
        );

        foreach ($params as $name => $value) {
            $form .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => (string)$value,
            ]);
        }

        $form .= html_writer::tag(
            'label',
            get_string(
                'crm_user_per_page',
                'local_subscriptions'
            ),
            [
                'for' => 'crm-user-explorer-perpage',
                'class' => 'crm-user-explorer-perpage-label',
            ]
        );

        $form .= html_writer::select(
            [25 => '25', 50 => '50', 100 => '100'],
            'perpage',
            $criteria->perpage,
            false,
            [
                'id' => 'crm-user-explorer-perpage',
                'class' =>
                    'form-select form-select-sm crm-sales-perpage-select',
                'onchange' => 'this.form.submit()',
            ]
        );

        $form .= html_writer::end_tag('form');

        return html_writer::div(
            $form,
            'crm-sales-table-toolbar-actions'
        );
    }

    private static function render_table_header(
        string $column,
        UserExplorerCriteria $criteria
    ): string {
        $pair = match ($column) {
            UserExplorerColumn::USER => [
                UserExplorerSort::NAME_ASC,
                UserExplorerSort::NAME_DESC,
            ],
            UserExplorerColumn::SCORE => [
                UserExplorerSort::SCORE_ASC,
                UserExplorerSort::SCORE_DESC,
            ],
            UserExplorerColumn::RISK => [
                UserExplorerSort::RISK_ASC,
                UserExplorerSort::RISK_DESC,
            ],
            UserExplorerColumn::SUBSCRIPTIONS => [
                UserExplorerSort::SUBSCRIPTIONS_ASC,
                UserExplorerSort::SUBSCRIPTIONS_DESC,
            ],
            UserExplorerColumn::PURCHASES => [
                UserExplorerSort::PURCHASES_ASC,
                UserExplorerSort::PURCHASES_DESC,
            ],
            UserExplorerColumn::LAST_ACCESS => [
                UserExplorerSort::LAST_ACCESS_ASC,
                UserExplorerSort::LAST_ACCESS_DESC,
            ],
            UserExplorerColumn::REGISTERED => [
                UserExplorerSort::CREATED_ASC,
                UserExplorerSort::CREATED_DESC,
            ],
            default => null,
        };

        $label = UserExplorerColumn::label($column);
        if ($pair === null) {
            return $label;
        }

        [$asc, $desc] = $pair;
        $current = UserExplorerSort::normalize($criteria->sort);
        $active = $current === $asc || $current === $desc;
        $next = $current === $asc ? $desc : $asc;
        $icon = !$active
            ? 'fa fa-sort'
            : ($current === $asc ? 'fa fa-sort-up' : 'fa fa-sort-down');

        $params = $criteria->url_params(false);
        unset($params['page']);
        $params['sort'] = $next;

        return html_writer::link(
            new moodle_url(
                subscription_config::admin_users_page(),
                $params
            ),
            html_writer::span(s($label))
            . html_writer::tag('i', '', [
                'class' => $icon . ' crm-user-explorer-sort-icon',
                'aria-hidden' => 'true',
            ]),
            [
                'class' => 'crm-user-explorer-sort-link'
                    . ($active ? ' is-active' : ''),
                'title' => UserExplorerSort::label($next),
            ]
        );
    }

    private static function render_user_row(
        UserExplorerUserViewModel $viewmodel,
        array $columns
    ): array {
        $user = $viewmodel->user;

        $displayname = trim(fullname($user));
        if ($displayname === '') {
            $displayname = (string)$user->email;
        }

        if (!empty($user->iscommerceguest)) {
            $identitylink = html_writer::link(
                new moodle_url(
                    subscription_config::admin_user_view_page(),
                    ['email' => (string)$user->email]
                ),
                s($displayname),
                ['class' => 'crm-user-explorer-user-link']
            );
        } else {
            $identitylink = AdminEntityLinks::user(
                (int)$user->id,
                s($displayname),
                ['class' => 'crm-user-explorer-user-link']
            );
        }

        $identity =
            $identitylink .
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

    private static function render_row_actions(
        UserExplorerUserViewModel $viewmodel,
        bool $canviewinbox
    ): string {
        $user = $viewmodel->user;
        $viewparams = !empty($user->iscommerceguest)
            ? ['email' => (string)$user->email]
            : ['id' => (int)$user->id];

        $viewurl = new moodle_url(
            subscription_config::admin_user_view_page(),
            $viewparams
        );

        $primary = html_writer::link(
            $viewurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-eye',
                'aria-hidden' => 'true',
            ]) . html_writer::span(get_string('view')),
            [
                'class' => 'btn btn-sm btn-outline-primary crm-user-explorer-view-button',
            ]
        );

        $menuitems = [
            html_writer::div(
                get_string('crm_user_menu_client', 'local_subscriptions'),
                'crm-sales-row-menu-section'
            ),
            html_writer::link(
                $viewurl,
                html_writer::tag('i', '', [
                    'class' => 'fa fa-user-circle-o',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string('crm_user_open_user360', 'local_subscriptions')
                ),
                ['class' => 'crm-sales-row-menu-link']
            ),
        ];

        if (empty($user->iscommerceguest)) {
            $menuitems[] = html_writer::link(
                new moodle_url('/user/profile.php', ['id' => (int)$user->id]),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-user',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string(
                        'crm_user_open_moodle_profile',
                        'local_subscriptions'
                    )
                ),
                ['class' => 'crm-sales-row-menu-link']
            );
        }

        if ($canviewinbox && !empty($user->email)) {
            $menuitems[] = html_writer::div(
                get_string(
                    'crm_user_menu_communication',
                    'local_subscriptions'
                ),
                'crm-sales-row-menu-section'
            );
            $menuitems[] = html_writer::link(
                new moodle_url(
                    subscription_config::admin_inbox_page(),
                    ['q' => (string)$user->email]
                ),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-envelope-o',
                    'aria-hidden' => 'true',
                ])
                . html_writer::span(
                    get_string(
                        'crm_user_open_inbox',
                        'local_subscriptions'
                    )
                ),
                ['class' => 'crm-sales-row-menu-link']
            );
        }

        $menu = html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-ellipsis-h',
                    'aria-hidden' => 'true',
                ]),
                [
                    'class' => 'btn btn-sm btn-outline-secondary crm-sales-row-menu-toggle',
                    'aria-label' => get_string(
                        'crm_user_more_actions',
                        'local_subscriptions'
                    ),
                    'title' => get_string(
                        'crm_user_more_actions',
                        'local_subscriptions'
                    ),
                ]
            )
            . html_writer::div(
                implode('', $menuitems),
                'crm-sales-row-menu'
            ),
            ['class' => 'crm-sales-row-actions-menu']
        );

        return html_writer::div(
            $primary . $menu,
            'crm-sales-actions crm-user-explorer-actions'
        );
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

    private static function field(
        string $label,
        string $control,
        string $classes = ''
    ): string {
        return html_writer::div(
            html_writer::tag(
                'label',
                s($label),
                ['class' => 'form-label']
            )
            . $control,
            trim(
                'crm-sales-filter-field '
                . 'crm-user-explorer-filter-field '
                . $classes
            )
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


    private static function render_pagination(
        UserExplorerResult $result
    ): string {
        global $OUTPUT;

        $url = new moodle_url(
            subscription_config::admin_users_page(),
            $result->criteria->url_params(false)
        );

        return html_writer::div(
            $OUTPUT->paging_bar(
                $result->total,
                $result->criteria->page,
                $result->criteria->perpage,
                $url
            ),
            'crm-user-explorer-pagination'
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