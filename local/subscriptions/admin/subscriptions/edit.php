<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/form/user_subscription_edit_form.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;
use local_subscriptions\form\user_subscription_edit_form;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

global $DB, $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);

$subscription = $DB->get_record('user_subscription', ['id' => $id], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $subscription->userid], '*', MUST_EXIST);
$plan = $DB->get_record('subscription_plan', ['id' => $subscription->planid], '*', MUST_EXIST);

$url = new moodle_url(
    subscription_config::
        user_subscription_edit_page(),
    [
        'id' => $id,
    ]
);

$pagetitle = get_string(
    'edit_user_subscription',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    'local-subscriptions-commerce-subscription-edit-page'
);

$form = new user_subscription_edit_form($url);

$defaultdata = [
    'id' => $subscription->id,
    'start_date' => (int)$subscription->start_date,
    'end_date' => !empty($subscription->end_date) ? (int)$subscription->end_date : time(),
    'no_end_date' => empty($subscription->end_date) || (int)$subscription->end_date > strtotime('2100-01-01'),
    'status' => $subscription->status,
];

$form->set_data($defaultdata);

if ($form->is_cancelled()) {
    redirect(
        new moodle_url(
            subscription_config::
                user_subscription_view_page(),
            [
                'id' => $id,
            ]
        )
    );
}

if ($data = $form->get_data()) {
    $startdate = (int)$data->start_date;
    $enddate = !empty($data->no_end_date) ? 0 : (int)$data->end_date;

    $oldsubscription = clone $subscription;

    $updatedsubscription = subscription_manager::update_subscription_from_admin(
        (int)$data->id,
        $startdate,
        $enddate,
        (string)$data->status
    );

    $changes = [];

    if ((int)$oldsubscription->start_date !== $startdate) {
        $changes['start_date'] = [
            'from' => !empty($oldsubscription->start_date) ? AdminFormatter::date((int)$oldsubscription->start_date) : '-',
            'to' => !empty($startdate) ? AdminFormatter::date($startdate) : '-',
        ];
    }

    if ((int)$oldsubscription->end_date !== $enddate) {
        $changes['end_date'] = [
            'from' => AdminFormatter::subscription_end((int)$oldsubscription->end_date),
            'to' => AdminFormatter::subscription_end((int)$enddate),
        ];
    }

    if ((string)$oldsubscription->status !== (string)$data->status) {
        $changes['status'] = [
            'from' => (string)$oldsubscription->status,
            'to' => (string)$data->status,
        ];
    }

    AdminLog::subscriptionUpdated($updatedsubscription, $plan, $changes);

    redirect(
        new moodle_url(
            subscription_config::
                user_subscription_view_page(),
            [
                'id' =>
                    $updatedsubscription->id,
            ]
        ),
        get_string(
            'subscription_updated_successfully',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::
            NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' => get_string(
                'crm_commerce_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    admin_commerce_page()
            ),
        ],
        [
            'label' => get_string(
                'crm_commerce_nav_purchases',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                '/local/subscriptions/admin/commerce/purchases/index.php'
            ),
        ],
        [
            'label' => get_string(
                'crm_user360_n116a_legacy_subscriptions_title',
                'local_subscriptions'
            ),
            'url' => null,
        ],
        [
            'label' => '#' . $subscription->id,
            'url' => null,
        ],
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscription_edit_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PURCHASES
);

$detailurl = new moodle_url(
    subscription_config::
        user_subscription_view_page(),
    [
        'id' => $subscription->id,
    ]
);

$userurl = new moodle_url(
    subscription_config::
        admin_user_view_page(),
    [
        'id' => $user->id,
    ]
);

$planurl = new moodle_url(
    subscription_config::
        commerce_plan_view_page(),
    [
        'id' => $plan->id,
    ]
);

$statuskey = 'status_' . (string)$subscription->status;
$statuslabel = get_string_manager()->string_exists(
    $statuskey,
    'local_subscriptions'
)
    ? get_string(
        $statuskey,
        'local_subscriptions'
    )
    : ucfirst((string)$subscription->status);

$summarycontent =
    html_writer::div(
        html_writer::div(
            html_writer::span(
                get_string(
                    'crm_subscription_edit_legacy_badge',
                    'local_subscriptions'
                ),
                'crm-legacy-subscription-edit-type'
            )
            . html_writer::tag(
                'h2',
                format_string($plan->name),
                [
                    'class' =>
                        'crm-legacy-subscription-edit-plan',
                ]
            )
            . html_writer::div(
                '#' . (int)$subscription->id,
                'crm-legacy-subscription-edit-id'
            ),
            'crm-legacy-subscription-edit-heading-copy'
        )
        . html_writer::link(
            $detailurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-eye',
                'aria-hidden' => 'true',
            ])
            . html_writer::span(
                get_string(
                    'subscription_details',
                    'local_subscriptions'
                )
            ),
            [
                'class' =>
                    'btn btn-outline-secondary '
                    . 'crm-legacy-subscription-edit-details',
            ]
        ),
        'crm-legacy-subscription-edit-heading'
    )
    . html_writer::div(
        html_writer::div(
            html_writer::span(
                get_string(
                    'user',
                    'local_subscriptions'
                ),
                'crm-legacy-subscription-edit-meta-label'
            )
            . html_writer::link(
                $userurl,
                fullname($user),
                [
                    'class' =>
                        'crm-legacy-subscription-edit-meta-value',
                ]
            )
            . html_writer::span(
                s($user->email),
                'crm-legacy-subscription-edit-meta-help'
            ),
            'crm-legacy-subscription-edit-meta'
        )
        . html_writer::div(
            html_writer::span(
                get_string(
                    'plan',
                    'local_subscriptions'
                ),
                'crm-legacy-subscription-edit-meta-label'
            )
            . html_writer::link(
                $planurl,
                format_string($plan->name),
                [
                    'class' =>
                        'crm-legacy-subscription-edit-meta-value',
                ]
            ),
            'crm-legacy-subscription-edit-meta'
        )
        . html_writer::div(
            html_writer::span(
                get_string(
                    'crm_subscription_edit_current_period',
                    'local_subscriptions'
                ),
                'crm-legacy-subscription-edit-meta-label'
            )
            . html_writer::span(
                AdminFormatter::date(
                    (int)$subscription->start_date
                )
                . ' → '
                . AdminFormatter::subscription_end(
                    (int)$subscription->end_date
                ),
                'crm-legacy-subscription-edit-meta-value'
            ),
            'crm-legacy-subscription-edit-meta'
        )
        . html_writer::div(
            html_writer::span(
                get_string(
                    'status',
                    'local_subscriptions'
                ),
                'crm-legacy-subscription-edit-meta-label'
            )
            . html_writer::span(
                s($statuslabel),
                'crm-legacy-subscription-edit-status '
                    . 'is-'
                    . preg_replace(
                        '/[^a-z0-9_-]+/',
                        '-',
                        strtolower(
                            (string)$subscription->status
                        )
                    )
            ),
            'crm-legacy-subscription-edit-meta'
        ),
        'crm-legacy-subscription-edit-meta-grid'
    );

echo html_writer::tag(
    'section',
    $summarycontent,
    [
        'class' =>
            'crm-legacy-subscription-edit-summary',
        'aria-label' => get_string(
            'subscription_summary',
            'local_subscriptions'
        ),
    ]
);

echo html_writer::start_tag(
    'section',
    [
        'class' =>
            'crm-legacy-subscription-edit-form-card',
        'aria-labelledby' =>
            'crm-legacy-subscription-edit-form-title',
    ]
);

echo html_writer::div(
    html_writer::tag(
        'h2',
        get_string(
            'crm_subscription_edit_access_title',
            'local_subscriptions'
        ),
        [
            'id' =>
                'crm-legacy-subscription-edit-form-title',
            'class' =>
                'crm-legacy-subscription-edit-form-title',
        ]
    )
    . html_writer::div(
        get_string(
            'crm_subscription_edit_access_help',
            'local_subscriptions'
        ),
        'crm-legacy-subscription-edit-form-help'
    ),
    'crm-legacy-subscription-edit-form-header'
);

echo html_writer::start_div(
    'crm-legacy-subscription-edit-form-body'
);

$form->display();

echo html_writer::end_div();
echo html_writer::end_tag('section');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();