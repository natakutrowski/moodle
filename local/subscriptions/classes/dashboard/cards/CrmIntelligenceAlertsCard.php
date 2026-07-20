<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\intelligence\alerts\CrmAlertBuilder;
use local_subscriptions\crm\intelligence\alerts\CrmAlertPresentation;
use local_subscriptions\crm\intelligence\alerts\CrmAlertContext;
use local_subscriptions\crm\intelligence\alerts\CrmAlertContextService;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\crm\work\rendering\WorkItemPresentation;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\ui\DashboardCardUi;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use moodle_url;

final class CrmIntelligenceAlertsCard implements DashboardCard {

    public static function render(): string {

        if (!Capabilities::can_view_users()) {
            return '';
        }

        $alerts = (new CrmAlertBuilder())->build();

        $alertcontexts =
            (new CrmAlertContextService())
                ->load($alerts);

        $content = DashboardCardUi::header(
            title: get_string(
                'crm_intelligence_alerts_title',
                'local_subscriptions'
            ),
            icon: '🚨',
            titleid: 'crm-dashboard-intelligence-alerts-title'
        );

        if (empty($alerts)) {
            $content .= DashboardCardUi::empty_state(
                title: get_string(
                    'crm_intelligence_alerts_empty',
                    'local_subscriptions'
                ),
                icon: '✓',
                tone: DashboardCardUi::TONE_SUCCESS
            );

            return DashboardCardUi::shell(
                content: $content,
                extraclasses:
                    'crm-dashboard-intelligence-alerts-card',
                labelledby:
                    'crm-dashboard-intelligence-alerts-title'
            );
        }

        foreach ($alerts as $alert) {

            $alertcontext = null;

            if (
                $alert->userid !== null &&
                $alert->userid > 0
            ) {
                $alertcontext =
                    $alertcontexts[
                        $alert->userid
                    ] ?? null;
            }

            $label = CrmAlertPresentation::label(
                $alert->key
            );

            $class = CrmAlertPresentation::border_class(
                $alert->key,
                $alert->severity
            );

            $icon = CrmAlertPresentation::icon(
                $alert->key
            );

            $alertcontent = html_writer::div(
                html_writer::span(
                    $icon,
                    'me-2',
                    [
                        'aria-hidden' => 'true',
                    ]
                ) .
                html_writer::span(
                    s($label)
                ),
                'fw-bold d-flex align-items-center'
            );

            if ($alert->has_user_identity()) {
                $identity = html_writer::span(
                    s($alert->displayname),
                    'fw-semibold'
                );

                if ($alert->email !== null) {
                    $identity .= html_writer::span(
                        s($alert->email),
                        'text-muted small ms-2'
                    );
                }

                $alertcontent .= html_writer::div(
                    $identity,
                    'mt-1'
                );
            }

            $scorebadges = [];

            if ($alert->riskscore !== null) {
                $scorebadges[] = html_writer::span(
                    get_string(
                        'crm_intelligence_risk_score',
                        'local_subscriptions'
                    ) .
                    ' : ' .
                    $alert->riskscore,
                    'badge bg-light text-dark border'
                );
            }

            if ($alert->commercialscore !== null) {
                $scorebadges[] = html_writer::span(
                    get_string(
                        'crm_intelligence_commercial_score',
                        'local_subscriptions'
                    ) .
                    ' : ' .
                    $alert->commercialscore,
                    'badge bg-light text-dark border'
                );
            }

            if (!empty($scorebadges)) {
                $alertcontent .= html_writer::div(
                    implode(' ', $scorebadges),
                    'mt-2 d-flex flex-wrap gap-1'
                );
            }

            $prioritylabel =
                CrmAlertPresentation::priority_label(
                    $alert->priority
                );

            $prioritybadge = html_writer::span(
                get_string(
                    'crm_intelligence_alert_priority_label',
                    'local_subscriptions',
                    $prioritylabel
                ),
                'badge ' .
                    CrmAlertPresentation::priority_badge_class(
                        $alert->priority
                    )
            );

            $alertcontent .= html_writer::div(
                $prioritybadge,
                'mt-2'
            );

            $signalmetadata = [];

            $signaldate =
                CrmAlertPresentation::signal_date_label(
                    $alert->snapshottime
                );

            if ($signaldate !== null) {
                $signalmetadata[] = html_writer::div(
                    s($signaldate),
                    'small text-muted'
                );
            }

            $signalage =
                CrmAlertPresentation::signal_age_label(
                    $alert->snapshottime
                );

            if ($signalage !== null) {
                $signalmetadata[] = html_writer::div(
                    s($signalage),
                    'small text-muted'
                );
            }

            if (!empty($signalmetadata)) {
                $alertcontent .= html_writer::div(
                    implode('', $signalmetadata),
                    'mt-2'
                );
            }

            $nextaction =
                CrmAlertPresentation::next_action_label(
                    $alert->key
                );

            $alertcontent .= html_writer::div(
                html_writer::div(
                    get_string(
                        'crm_intelligence_alert_next_action_label',
                        'local_subscriptions'
                    ),
                    'small fw-semibold'
                ) .
                html_writer::div(
                    s($nextaction),
                    'small'
                ),
                'mt-2 p-2 bg-light rounded'
            );

            $alertcontent .=
                self::render_work_item(
                    $alertcontext
                );

            $alertcontent .=
                self::render_customer_success_plan(
                    $alertcontext
                );            

            if (
                $alert->userid !== null &&
                $alert->userid > 0
            ) {
                $actions = [];

                $profileurl = new moodle_url(
                    subscription_config::
                        admin_user_view_page(),
                    [
                        'id' => $alert->userid,
                    ]
                );

                $actions[] = html_writer::link(
                    $profileurl,
                    get_string(
                        'crm_intelligence_alert_open_profile',
                        'local_subscriptions'
                    ),
                    [
                        'class' =>
                            'btn btn-sm btn-outline-primary',
                    ]
                );

                if (
                    $alertcontext !== null &&
                    $alertcontext->has_work_item()
                ) {
                    $workurl = new moodle_url(
                        subscription_config::
                            admin_work_item_view_page(),
                        [
                            'id' =>
                                (int)$alertcontext
                                    ->workitem->id,
                        ]
                    );

                    $actions[] = html_writer::link(
                        $workurl,
                        get_string(
                            'crm_intelligence_alert_open_work_item',
                            'local_subscriptions'
                        ),
                        [
                            'class' =>
                                'btn btn-sm btn-outline-secondary',
                        ]
                    );
                } else if (
                    Capabilities::can_manage_work_items()
                ) {
                    $createworkurl = new moodle_url(
                        subscription_config::
                            admin_work_item_create_page(),
                        [
                            'targetuserid' =>
                                $alert->userid,
                        ]
                    );

                    $actions[] = html_writer::link(
                        $createworkurl,
                        get_string(
                            'crm_intelligence_alert_create_work_item',
                            'local_subscriptions'
                        ),
                        [
                            'class' =>
                                'btn btn-sm btn-outline-secondary',
                        ]
                    );
                }

                if (
                    $alertcontext !== null &&
                    $alertcontext->
                        has_customer_success_plan()
                ) {
                    $planurl = new moodle_url(
                        subscription_config::
                            admin_customer_success_plan_page(),
                        [
                            'id' =>
                                (int)$alertcontext
                                    ->customersuccessplan->id,
                        ]
                    );

                    $actions[] = html_writer::link(
                        $planurl,
                        get_string(
                            'crm_intelligence_alert_open_cs_plan',
                            'local_subscriptions'
                        ),
                        [
                            'class' =>
                                'btn btn-sm btn-outline-success',
                        ]
                    );
                }

                $alertcontent .= html_writer::div(
                    implode('', $actions),
                    'mt-3 d-flex flex-wrap gap-2'
                );
            }

            $content .= DashboardCardUi::item(
                $alertcontent,
                'crm-dashboard-alert-item ' . $class
            );
        }

        return DashboardCardUi::shell(
            content: $content,
            extraclasses:
                'crm-dashboard-intelligence-alerts-card',
            labelledby:
                'crm-dashboard-intelligence-alerts-title'
        );
    }

    private static function render_work_item(
        ?CrmAlertContext $context
    ): string {
        if (
            $context === null ||
            !$context->has_work_item()
        ) {
            return '';
        }

        $item = $context->workitem;

        $url = new moodle_url(
            subscription_config::
                admin_work_item_view_page(),
            [
                'id' => (int)$item->id,
            ]
        );

        $badges =
            html_writer::span(
                s(
                    WorkItemPresentation::
                        status_label(
                            (string)$item->status
                        )
                ),
                'badge ' .
                    WorkItemPresentation::
                        status_class(
                            (string)$item->status
                        )
            );

        $badges .= ' ';

        $badges .=
            html_writer::span(
                s(
                    WorkItemPresentation::
                        priority_label(
                            (string)$item->priority
                        )
                ),
                'badge ' .
                    WorkItemPresentation::
                        priority_class(
                            (string)$item->priority
                        )
            );

        $content =
            html_writer::div(
                get_string(
                    'crm_intelligence_alert_work_item',
                    'local_subscriptions'
                ),
                'small fw-semibold mb-1'
            );

        $content .=
            html_writer::div(
                html_writer::link(
                    $url,
                    s(
                        (string)$item->reference .
                        ' — ' .
                        (string)$item->title
                    )
                ),
                'small'
            );

        $content .=
            html_writer::div(
                $badges,
                'mt-1'
            );

        $assignee =
            self::related_assignee_name(
                $item
            );

        if ($assignee !== null) {
            $content .= html_writer::div(
                get_string(
                    'crm_intelligence_alert_responsible',
                    'local_subscriptions',
                    $assignee
                ),
                'small text-muted mt-1'
            );
        }

        if (
            !empty($item->dueat) &&
            (int)$item->dueat > 0
        ) {
            $content .= html_writer::div(
                get_string(
                    'crm_intelligence_alert_due_date',
                    'local_subscriptions',
                    userdate(
                        (int)$item->dueat,
                        get_string(
                            'strftimedatetimeshort',
                            'langconfig'
                        )
                    )
                ),
                'small text-muted'
            );
        }

        return html_writer::div(
            $content,
            'mt-2 p-2 border rounded'
        );
    }


    private static function render_customer_success_plan(
        ?CrmAlertContext $context
    ): string {
        if (
            $context === null ||
            !$context->
                has_customer_success_plan()
        ) {
            return '';
        }

        $plan =
            $context->customersuccessplan;

        $url = new moodle_url(
            subscription_config::
                admin_customer_success_plan_page(),
            [
                'id' => (int)$plan->id,
            ]
        );

        $title =
            CustomerSuccessPlanPresentation::
                title(
                    (string)$plan->objectivekey,
                    (string)$plan->title
                );

        $content =
            html_writer::div(
                get_string(
                    'crm_intelligence_alert_cs_plan',
                    'local_subscriptions'
                ),
                'small fw-semibold mb-1'
            );

        $content .= html_writer::div(
            html_writer::link(
                $url,
                s(
                    (string)$plan->reference .
                    ' — ' .
                    $title
                )
            ),
            'small'
        );

        $content .= html_writer::div(
            html_writer::span(
                s(
                    CustomerSuccessPlanPresentation::
                        status_label(
                            (string)$plan->status
                        )
                ),
                'badge bg-primary'
            ) .
            ' ' .
            html_writer::span(
                s(
                    CustomerSuccessPlanPresentation::
                        priority_label(
                            (string)$plan->priority
                        )
                ),
                'badge bg-secondary'
            ),
            'mt-1'
        );

        $assignee =
            self::related_assignee_name(
                $plan
            );

        if ($assignee !== null) {
            $content .= html_writer::div(
                get_string(
                    'crm_intelligence_alert_responsible',
                    'local_subscriptions',
                    $assignee
                ),
                'small text-muted mt-1'
            );
        }

        if (
            !empty($plan->targetdate) &&
            (int)$plan->targetdate > 0
        ) {
            $content .= html_writer::div(
                get_string(
                    'crm_intelligence_alert_target_date',
                    'local_subscriptions',
                    userdate(
                        (int)$plan->targetdate,
                        get_string(
                            'strftimedatetimeshort',
                            'langconfig'
                        )
                    )
                ),
                'small text-muted'
            );
        }

        return html_writer::div(
            $content,
            'mt-2 p-2 border rounded'
        );
    }

    private static function related_assignee_name(
        \stdClass $record
    ): ?string {
        if (
            !empty($record->assigneduserid) &&
            !empty(
                $record->assigneefirstname
            )
        ) {
            $user = (object)[
                'firstname' =>
                    $record->assigneefirstname ?? '',
                'lastname' =>
                    $record->assigneelastname ?? '',
                'firstnamephonetic' =>
                    $record->assigneefirstnamephonetic
                        ?? '',
                'lastnamephonetic' =>
                    $record->assigneelastnamephonetic
                        ?? '',
                'middlename' =>
                    $record->assigneemiddlename ?? '',
                'alternatename' =>
                    $record->assigneealternatename
                        ?? '',
            ];

            $name = trim(fullname($user));

            if ($name !== '') {
                return $name;
            }
        }

        if (
            !empty($record->assignedteamid) &&
            !empty($record->teamname)
        ) {
            return format_string(
                (string)$record->teamname
            );
        }

        return null;
    }    

}