<?php

namespace local_subscriptions\crm\assistant\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\assistant\dto\AssistantOverview;
use local_subscriptions\crm\assistant\dto\AssistantRecommendation;
use local_subscriptions\crm\assistant\dto\AssistantWorkspace;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Server-side renderer for the CRM Assistant.
 *
 * No recommendation calculation or business transition occurs here.
 */
final class CrmAssistantRenderer {

    public static function workspace(
        AssistantWorkspace $workspace,
        string $pagination = '',
        int $perpage = 20
    ): string {
        $out = '';

        $out .= self::filters(
            $workspace,
            $perpage
        );

        $out .= html_writer::div(
            html_writer::span(
                get_string(
                    'crm_assistant_results_count_n129a',
                    'local_subscriptions',
                    $workspace->total
                ),
                'crm-assistant-results-count'
            )
            . (
                $pagination !== ''
                    ? html_writer::div(
                        $pagination,
                        'crm-assistant-pagination'
                    )
                    : ''
            ),
            'crm-assistant-results-toolbar'
        );

        if ($workspace->recommendations === []) {
            $out .= html_writer::div(
                get_string(
                    'crm_assistant_empty',
                    'local_subscriptions'
                ),
                'alert alert-light border'
            );

            return $out;
        }

        $out .= html_writer::start_div(
            'crm-assistant-recommendation-list'
        );

        foreach (
            $workspace->recommendations
            as $recommendation
        ) {
            $out .= self::recommendation(
                $recommendation,
                true
            );
        }

        $out .= html_writer::end_div();

        if ($pagination !== '') {
            $out .= html_writer::div(
                $pagination,
                'crm-assistant-pagination '
                . 'crm-assistant-pagination-bottom'
            );
        }

        return $out;
    }


    public static function user_section(
        array $recommendations
    ): string {
        if ($recommendations === []) {
            return '';
        }

        $out = html_writer::start_div(
            'crm-assistant-user-recommendations'
        );

        foreach ($recommendations as $recommendation) {
            if (
                !$recommendation instanceof
                AssistantRecommendation
            ) {
                continue;
            }

            $out .= self::recommendation(
                $recommendation,
                false
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    public static function overview_panel(
        AssistantOverview $overview
    ): string {
        return self::overview(
            $overview
        );
    }

    public static function dashboard_summary(
        AssistantOverview $overview
    ): string {
        $assistanturl = new moodle_url(
            subscription_config::
                admin_crm_assistant_page()
        );

        $out = html_writer::tag(
            'h3',
            '🧭 ' .
                get_string(
                    'crm_assistant_title',
                    'local_subscriptions'
                ),
            [
                'class' => 'h4 mb-3',
            ]
        );

        $out .= html_writer::div(
            self::metric(
                get_string(
                    'crm_assistant_metric_active',
                    'local_subscriptions'
                ),
                $overview->active
            ) .
            self::metric(
                get_string(
                    'crm_assistant_metric_critical',
                    'local_subscriptions'
                ),
                $overview->critical
            ) .
            self::metric(
                get_string(
                    'crm_assistant_metric_urgent',
                    'local_subscriptions'
                ),
                $overview->urgent
            ) .
            self::metric(
                get_string(
                    'crm_assistant_metric_crossdomain',
                    'local_subscriptions'
                ),
                $overview->crossdomain
            ),
            'crm-assistant-dashboard-metrics'
        );

        $out .= html_writer::div(
            html_writer::link(
                $assistanturl,
                get_string(
                    'crm_assistant_open',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-outline-primary btn-sm',
                ]
            ),
            'mt-3'
        );

        return html_writer::div(
            $out,
            'card card-body local-subscriptions-dashboard-card mb-4'
        );
    }

    private static function overview(
        AssistantOverview $overview
    ): string {
        $cards = [
            [
                'value' => $overview->active,
                'label' => get_string(
                    'crm_assistant_metric_active',
                    'local_subscriptions'
                ),
            ],
            [
                'value' => $overview->critical,
                'label' => get_string(
                    'crm_assistant_metric_critical',
                    'local_subscriptions'
                ),
            ],
            [
                'value' => $overview->urgent,
                'label' => get_string(
                    'crm_assistant_metric_urgent',
                    'local_subscriptions'
                ),
            ],
            [
                'value' => $overview->accepted,
                'label' => get_string(
                    'crm_assistant_metric_accepted',
                    'local_subscriptions'
                ),
            ],
            [
                'value' => $overview->crossdomain,
                'label' => get_string(
                    'crm_assistant_metric_crossdomain',
                    'local_subscriptions'
                ),
            ],
            [
                'value' => $overview->users,
                'label' => get_string(
                    'crm_assistant_metric_users',
                    'local_subscriptions'
                ),
            ],
        ];

        $items = '';

        foreach ($cards as $card) {
            $items .= html_writer::div(
                html_writer::tag(
                    'strong',
                    (string)$card['value'],
                    [
                        'class' =>
                            'crm-assistant-overview-value',
                    ]
                )
                . html_writer::span(
                    s($card['label']),
                    'crm-assistant-overview-label'
                ),
                'crm-assistant-overview-item'
            );
        }

        return html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'crm_assistant_overview_title_n129a',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'crm-assistant-overview-title',
                ]
            )
            . html_writer::div(
                $items,
                'crm-assistant-overview-grid'
            ),
            'crm-assistant-overview'
        );
    }


    private static function filters(
        AssistantWorkspace $workspace,
        int $perpage
    ): string {
        $criteria = $workspace->criteria;

        $url = new moodle_url(
            subscription_config::
                admin_crm_assistant_page()
        );

        $out = html_writer::start_tag(
            'form',
            [
                'method' => 'get',
                'action' => $url->out(false),
                'class' =>
                    'crm-assistant-filters',
            ]
        );

        $out .= html_writer::start_div('crm-assistant-filter-grid');

        $out .= self::select_filter(
            'scope',
            get_string(
                'crm_assistant_filter_scope',
                'local_subscriptions'
            ),
            [
                'active' => get_string(
                    'crm_assistant_scope_active',
                    'local_subscriptions'
                ),
                'all' => get_string(
                    'crm_assistant_scope_all',
                    'local_subscriptions'
                ),
            ],
            $criteria->scope
        );

        $out .= self::select_filter(
            'priority',
            get_string(
                'crm_assistant_filter_priority',
                'local_subscriptions'
            ),
            [
                '' => get_string(
                    'crm_assistant_filter_any',
                    'local_subscriptions'
                ),
                'critical' => get_string(
                    'crm_assistant_priority_critical',
                    'local_subscriptions'
                ),
                'urgent' => get_string(
                    'crm_assistant_priority_urgent',
                    'local_subscriptions'
                ),
                'high' => get_string(
                    'crm_assistant_priority_high',
                    'local_subscriptions'
                ),
                'normal' => get_string(
                    'crm_assistant_priority_normal',
                    'local_subscriptions'
                ),
                'low' => get_string(
                    'crm_assistant_priority_low',
                    'local_subscriptions'
                ),
            ],
            $criteria->prioritylevel ?? ''
        );

        $out .= self::select_filter(
            'status',
            get_string(
                'crm_assistant_filter_status',
                'local_subscriptions'
            ),
            [
                '' => get_string(
                    'crm_assistant_filter_any',
                    'local_subscriptions'
                ),
                'proposed' => get_string(
                    'crm_assistant_status_proposed',
                    'local_subscriptions'
                ),
                'accepted' => get_string(
                    'crm_assistant_status_accepted',
                    'local_subscriptions'
                ),
                'dismissed' => get_string(
                    'crm_assistant_status_dismissed',
                    'local_subscriptions'
                ),
                'completed' => get_string(
                    'crm_assistant_status_completed',
                    'local_subscriptions'
                ),
                'expired' => get_string(
                    'crm_assistant_status_expired',
                    'local_subscriptions'
                ),
            ],
            $criteria->status ?? ''
        );

        $out .= self::select_filter(
            'perpage',
            get_string(
                'crm_assistant_per_page_n129a',
                'local_subscriptions'
            ),
            [
                10 => '10',
                20 => '20',
                50 => '50',
            ],
            (string)$perpage
        );

        $out .= html_writer::div(
            html_writer::tag(
                'button',
                get_string(
                    'filter',
                    'local_subscriptions'
                ),
                [
                    'type' => 'submit',
                    'class' =>
                        'btn btn-primary',
                ]
            ),
            'crm-assistant-filter-submit'
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_tag('form');

        return $out;
    }

    private static function select_filter(
        string $name,
        string $label,
        array $options,
        string $selected
    ): string {
        $select = html_writer::select(
            $options,
            $name,
            $selected,
            false,
            [
                'class' => 'form-control',
                'id' => 'crm-assistant-' . $name,
            ]
        );

        return html_writer::div(
            html_writer::label(
                $label,
                'crm-assistant-' . $name,
                false,
                [
                    'class' => 'form-label',
                ]
            ) .
            $select,
            'crm-assistant-filter-field'
        );
    }

    private static function recommendation(
        AssistantRecommendation $recommendation,
        bool $showtarget
    ): string {
        $title = self::recommendation_label(
            $recommendation->key
        );

        $description =
            self::recommendation_description(
                $recommendation->key
            );

        $header = html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'h3',
                    s($title),
                    [
                        'class' =>
                            'crm-assistant-recommendation-title',
                    ]
                ) .
                (
                    $description !== ''
                        ? html_writer::div(
                            s($description),
                            'text-muted'
                        )
                        : ''
                ),
                'crm-assistant-recommendation-heading'
            ) .
            html_writer::div(
                self::priority_badge(
                    $recommendation
                ) .
                ' ' .
                self::status_badge(
                    $recommendation->status
                ),
                'crm-assistant-recommendation-badges'
            ),
            'crm-assistant-recommendation-header'
        );

        $body = '';

        if (
            $showtarget &&
            $recommendation->is_user_target()
        ) {
            $userurl = new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                [
                    'id' =>
                        $recommendation->targetid,
                ]
            );

            $body .= html_writer::div(
                html_writer::span(
                    get_string(
                        'crm_assistant_target',
                        'local_subscriptions'
                    ) . ': ',
                    'text-muted'
                ) .
                html_writer::link(
                    $userurl,
                    s(
                        $recommendation->targetname ??
                        ('#' . $recommendation->targetid)
                    ),
                    [
                        'class' => 'fw-bold',
                    ]
                ),
                'mb-3'
            );
        }

        $body .= self::evidence(
            $recommendation
        );

        $body .= self::metadata(
            $recommendation
        );

        if ($recommendation->is_actionable()) {
            $body .= self::lifecycle_actions(
                $recommendation
            );
        }

        return html_writer::div(
            $header .
            html_writer::div(
                $body,
                'crm-assistant-recommendation-body'
            ),
            'crm-assistant-recommendation ' .
            'crm-assistant-priority-' .
            s($recommendation->prioritylevel)
        );
    }

    private static function evidence(
        AssistantRecommendation $recommendation
    ): string {
        if ($recommendation->evidence === []) {
            return '';
        }

        $out = html_writer::tag(
            'h4',
            get_string(
                'crm_assistant_why',
                'local_subscriptions'
            ),
            [
                'class' => 'crm-assistant-why-title',
            ]
        );

        $out .= html_writer::start_tag(
            'ul',
            [
                'class' =>
                    'crm-assistant-evidence-list mb-3',
            ]
        );

        foreach (
            array_slice(
                $recommendation->evidence,
                0,
                4
            )
            as $evidence
        ) {
            $key = (string)(
                $evidence['key'] ?? ''
            );

            $label =
                self::evidence_label($key);

            $value =
                $evidence['value'] ?? null;

            $valuetext =
                self::evidence_value(
                    $key,
                    $value
                );

            $content = s($label);

            if ($valuetext !== '') {
                $content .=
                    html_writer::span(
                        ' — ' . s($valuetext),
                        'text-muted'
                    );
            }

            $out .= html_writer::tag(
                'li',
                $content
            );
        }

        $out .= html_writer::end_tag('ul');

        return $out;
    }

    private static function metadata(
        AssistantRecommendation $recommendation
    ): string {
        $items = [];

        $items[] =
            get_string(
                'crm_assistant_priority_score',
                'local_subscriptions',
                $recommendation->priority
            );

        $items[] =
            get_string(
                'crm_assistant_evidence_count',
                'local_subscriptions',
                $recommendation->evidence_count()
            );

        $items[] =
            get_string(
                'crm_assistant_source_count',
                'local_subscriptions',
                $recommendation->source_count()
            );

        if ($recommendation->lastdetectedat > 0) {
            $items[] =
                get_string(
                    'crm_assistant_last_detected',
                    'local_subscriptions',
                    userdate(
                        $recommendation->lastdetectedat
                    )
                );
        }

        return html_writer::div(
            implode(
                html_writer::span(
                    ' · ',
                    'mx-1'
                ),
                array_map(
                    static fn(string $item): string =>
                        html_writer::span(
                            s($item)
                        ),
                    $items
                )
            ),
            'small text-muted mb-3'
        );
    }

    private static function lifecycle_actions(
        AssistantRecommendation $recommendation
    ): string {
        if (!Capabilities::can_manage_users()) {
            return '';
        }

        $actionurl = new moodle_url(
            subscription_config::
                admin_crm_assistant_action_page()
        );

        $out = html_writer::start_div(
            'crm-assistant-actions d-flex flex-wrap gap-2'
        );

        if (
            Capabilities::can_manage_work_items() &&
            in_array(
                $recommendation->status,
                [
                    RecommendationStatus::PROPOSED,
                    RecommendationStatus::ACCEPTED,
                ],
                true
            )
        ) {
            $suggestionurl = new moodle_url(
                subscription_config::
                    admin_crm_assistant_work_item_page(),
                [
                    'recommendationid' =>
                        $recommendation->id,
                ]
            );

            $out .= html_writer::link(
                $suggestionurl,
                get_string(
                    'crm_assistant_action_propose_work_item',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-primary',
                ]
            );
        }

        if (
            $recommendation->status ===
            RecommendationStatus::PROPOSED
        ) {
            $out .= self::post_button(
                $actionurl,
                $recommendation->id,
                'accept',
                get_string(
                    'crm_assistant_action_accept',
                    'local_subscriptions'
                ),
                'btn btn-sm btn-primary'
            );
        }

        $out .= self::post_button(
            $actionurl,
            $recommendation->id,
            'complete',
            get_string(
                'crm_assistant_action_complete',
                'local_subscriptions'
            ),
            'btn btn-sm btn-success'
        );

        $out .= self::post_button(
            $actionurl,
            $recommendation->id,
            'dismiss',
            get_string(
                'crm_assistant_action_dismiss',
                'local_subscriptions'
            ),
            'btn btn-sm btn-outline-secondary',
            [
                'reason' =>
                    'not_relevant',
            ]
        );

        $out .= html_writer::end_div();

        return $out;
    }

    private static function post_button(
        moodle_url $url,
        int $recommendationid,
        string $action,
        string $label,
        string $class,
        array $additionalparams = []
    ): string {
        $fields = [
            'sesskey' => sesskey(),
            'recommendationid' =>
                $recommendationid,
            'action' => $action,
        ] + $additionalparams;

        $form = html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' => $url->out(false),
                'class' => 'd-inline',
            ]
        );

        foreach ($fields as $name => $value) {
            $form .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value,
                ]
            );
        }

        $form .= html_writer::tag(
            'button',
            s($label),
            [
                'type' => 'submit',
                'class' => $class,
            ]
        );

        $form .= html_writer::end_tag('form');

        return $form;
    }

    private static function metric(
        string $label,
        int $value
    ): string {
        return html_writer::div(
            html_writer::tag(
                'strong',
                (string)$value,
                [
                    'class' =>
                        'crm-assistant-dashboard-value',
                ]
            ) .
            html_writer::div(
                s($label),
                'small text-muted'
            ),
            'crm-assistant-dashboard-metric'
        );
    }

    private static function priority_badge(
        AssistantRecommendation $recommendation
    ): string {
        $label = get_string(
            'crm_assistant_priority_' .
                $recommendation->prioritylevel,
            'local_subscriptions'
        );

        return html_writer::span(
            s($label),
            'badge crm-assistant-priority-badge ' .
            'crm-assistant-priority-badge-' .
            s($recommendation->prioritylevel)
        );
    }

    private static function status_badge(
        string $status
    ): string {
        $key =
            'crm_assistant_status_' .
            clean_param(
                $status,
                PARAM_ALPHANUMEXT
            );

        $label =
            get_string_manager()->string_exists(
                $key,
                'local_subscriptions'
            )
                ? get_string(
                    $key,
                    'local_subscriptions'
                )
                : $status;

        return html_writer::span(
            s($label),
            'badge bg-light text-dark border'
        );
    }

    private static function recommendation_label(
        string $key
    ): string {
        $normalizedkey =
            self::normalize_presentation_key(
                $key
            );

        $candidatekeys = [
            'crm_assistant_recommendation_' .
                $normalizedkey,

            'crm_intelligence_recommendation_' .
                $normalizedkey,
        ];

        foreach ($candidatekeys as $stringkey) {
            if (
                get_string_manager()->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )
            ) {
                return get_string(
                    $stringkey,
                    'local_subscriptions'
                );
            }
        }

        return self::fallback_key_label(
            $key
        );
    }

    private static function recommendation_description(
        string $key
    ): string {
        $normalizedkey =
            self::normalize_presentation_key(
                $key
            );

        $candidatekeys = [
            'crm_assistant_recommendation_' .
                $normalizedkey .
                '_desc',

            'crm_intelligence_recommendation_' .
                $normalizedkey .
                '_desc',
        ];

        foreach ($candidatekeys as $stringkey) {
            if (
                get_string_manager()->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )
            ) {
                return get_string(
                    $stringkey,
                    'local_subscriptions'
                );
            }
        }

        return '';
    }

    private static function evidence_label(
        string $key
    ): string {
        $normalizedkey =
            self::normalize_presentation_key(
                $key
            );

        $stringkey =
            'crm_assistant_evidence_' .
            $normalizedkey;

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return self::fallback_key_label(
            $key
        );
    }


    private static function evidence_value(
        string $key,
        mixed $value
    ): string {
        if ($value === null) {
            return '';
        }

        $normalizedkey =
            self::normalize_presentation_key(
                $key
            );

        if (
            is_numeric($value) &&
            self::is_ratio_evidence(
                $normalizedkey
            )
        ) {
            $percentage =
                (float)$value <= 1
                    ? (float)$value * 100
                    : (float)$value;

            $formattedvalue =
                format_float(
                    $percentage,
                    0
                );

            $stringkey =
                'crm_assistant_evidence_value_' .
                $normalizedkey;

            if (
                get_string_manager()->string_exists(
                    $stringkey,
                    'local_subscriptions'
                )
            ) {
                return get_string(
                    $stringkey,
                    'local_subscriptions',
                    $formattedvalue
                );
            }

            return $formattedvalue . ' %';
        }

        if (
            self::is_flag_evidence(
                $normalizedkey
            )
        ) {
            return '';
        }

        if (is_bool($value)) {
            return $value
                ? get_string('yes', 'core')
                : get_string('no', 'core');
        }

        if (!is_scalar($value)) {
            return '';
        }

        $stringkey =
            'crm_assistant_evidence_value_' .
            $normalizedkey;

        if (
            get_string_manager()->string_exists(
                $stringkey,
                'local_subscriptions'
            )
        ) {
            return get_string(
                $stringkey,
                'local_subscriptions',
                $value
            );
        }

        return (string)$value;
    }

    private static function is_flag_evidence(
        string $normalizedkey
    ): bool {
        return in_array(
            $normalizedkey,
            [
                'recommendation_review_customer_success_risk',
                'recommendation_review_learning_difficulty',
                'opportunity_winback_expired_customer',
                'opportunity_upgrade_subscription',
                'opportunity_cross_sell_digital_product',
                'crm_customer_without_notes',
            ],
            true
        );
    }

    private static function normalize_presentation_key(
        string $key
    ): string {
        return clean_param(
            str_replace(
                [
                    '.',
                    '-',
                ],
                '_',
                trim($key)
            ),
            PARAM_ALPHANUMEXT
        );
    }

    private static function fallback_key_label(
        string $key
    ): string {
        $cleanedkey = trim($key);

        if ($cleanedkey === '') {
            return get_string(
                'crm_assistant_unknown_label',
                'local_subscriptions'
            );
        }

        $segments = preg_split(
            '/[._-]+/',
            $cleanedkey
        );

        if (
            is_array($segments) &&
            $segments !== []
        ) {
            $label = end($segments);

            if (
                is_string($label) &&
                trim($label) !== ''
            ) {
                return ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        trim($label)
                    )
                );
            }
        }

        return ucfirst(
            str_replace(
                [
                    '_',
                    '.',
                    '-',
                ],
                ' ',
                $cleanedkey
            )
        );
    }

    private static function is_ratio_evidence(
        string $normalizedkey
    ): bool {
        $ratiosuffixes = [
            '_progress',
            '_progression',
            '_completion',
            '_completion_rate',
            '_risk',
            '_difficulty',
            '_engagement',
            '_confidence',
            '_score_ratio',
        ];

        foreach ($ratiosuffixes as $suffix) {
            if (
                str_ends_with(
                    $normalizedkey,
                    $suffix
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private static function is_boolean_count_evidence(
        string $normalizedkey
    ): bool {
        $booleansuffixes = [
            '_started',
            '_accessed',
            '_completed',
            '_attempted',
            '_visited',
        ];

        foreach ($booleansuffixes as $suffix) {
            if (
                str_ends_with(
                    $normalizedkey,
                    $suffix
                )
            ) {
                return true;
            }
        }

        return false;
    }

}