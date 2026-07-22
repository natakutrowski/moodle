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
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
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
                'crm_subscriptions_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    user_subscriptions_page()
            ),
        ],
        [
            'label' =>
                get_string(
                    'subscription_details',
                    'local_subscriptions'
                ) .
                ' #' .
                $subscription->id,
            'url' => new moodle_url(
                subscription_config::
                    user_subscription_view_page(),
                [
                    'id' =>
                        $subscription->id,
                ]
            ),
        ],
        [
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            user_subscription_view_page(),
        [
            'id' =>
                $subscription->id,
        ]
    ),
    get_string(
        'subscription_details',
        'local_subscriptions'
    )
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
    CommerceSectionNavigationRenderer::SUBSCRIPTIONS
);

echo html_writer::div(
    html_writer::tag(
        'h3',
        get_string(
            'subscription_summary',
            'local_subscriptions'
        ),
        [
            'class' => 'h5 mb-3',
        ]
    ) .
    html_writer::tag(
        'p',
        html_writer::tag(
            'strong',
            get_string(
                'user',
                'local_subscriptions'
            ) .
            ': '
        ) .
        html_writer::link(
            new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                [
                    'id' => $user->id,
                ]
            ),
            fullname($user)
        )
    ) .
    html_writer::tag(
        'p',
        html_writer::tag(
            'strong',
            get_string('email') . ': '
        ) .
        s($user->email)
    ) .
    html_writer::tag(
        'p',
        html_writer::tag(
            'strong',
            get_string(
                'plan',
                'local_subscriptions'
            ) .
            ': '
        ) .
        format_string($plan->name)
    ),
    'card card-body mb-4'
);

$form->display();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();