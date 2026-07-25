<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\admin\AdminDetailRenderer;
use local_subscriptions\support\SubsPresenter;
use local_subscriptions\payment\Provider;
use local_subscriptions\constants\Status;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\commerce\read\admin\CommerceAdminReadGateway;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$id = required_param('id', PARAM_INT);

$commerceread = (new CommerceAdminReadGateway())->inspect_subscription($id);

$subscription = $DB->get_record_sql("
    SELECT
        us.*,
        sp.name AS planname,
        u.email,
        u.firstname,
        u.lastname
      FROM {user_subscription} us
      JOIN {subscription_plan} sp ON sp.id = us.planid
      JOIN {user} u ON u.id = us.userid
     WHERE us.id = :id
", ['id' => $id], MUST_EXIST);

$paymentrequest = $DB->get_record('subscription_payment_request', [
    'subscriptionid' => $subscription->id,
], '*', IGNORE_MISSING);

if (!$paymentrequest && !empty($subscription->transactionid)) {
    $paymentrequest = $DB->get_record('subscription_payment_request', [
        'transactionid' => $subscription->transactionid,
    ], '*', IGNORE_MISSING);
}

if (!$paymentrequest) {
    $paymentrequest = $DB->get_record_sql("
        SELECT *
          FROM {subscription_payment_request}
         WHERE userid = :userid
           AND planid = :planid
           AND status IN (:paid, :completed)
      ORDER BY payment_date DESC, id DESC
    ", [
        'userid' => $subscription->userid,
        'planid' => $subscription->planid,
        'paid' => Status::PAID,
        'completed' => Status::COMPLETED,
    ], IGNORE_MISSING);
}

$url = new moodle_url(
    subscription_config::
        user_subscription_view_page(),
    [
        'id' => $id,
    ]
);

$pagetitle =
    get_string(
        'subscription_details',
        'local_subscriptions'
    ) .
    ' #' .
    $subscription->id;

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    'local-subscriptions-commerce-subscription-view-page'
);

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
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            user_subscriptions_page()
    ),
    get_string(
        'crm_subscriptions_title',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscription_view_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SUBSCRIPTIONS
);

echo html_writer::start_div(
    'crm-commerce-actionbar mb-4 d-flex flex-wrap gap-2'
);

echo html_writer::link(
    new moodle_url(subscription_config::user_subscription_edit_page(), ['id' => $subscription->id]),
    '✏️ ' . get_string('edit'),
    ['class' => 'btn btn-outline-primary']
);

echo html_writer::link(
    new moodle_url(subscription_config::admin_user_view_page(), ['id' => $subscription->userid]),
    '👤 ' . get_string('crm_user_profile', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::end_div();

$userlabel = trim(($subscription->firstname ?? '') . ' ' . ($subscription->lastname ?? ''));

if ($userlabel === '') {
    $userlabel = $subscription->email ?? ('#' . (int)$subscription->userid);
}

$rows = [
    get_string('user') => AdminEntityLinks::user((int)$subscription->userid, s($userlabel)),
    get_string('email') => s($subscription->email ?? '-'),
    get_string('plan', 'local_subscriptions') => format_string($subscription->planname),
    get_string('status', 'local_subscriptions') => SubsPresenter::render_status_badge($subscription->status ?? ''),

    get_string('subfield_start', 'local_subscriptions') => AdminFormatter::datetime((int)($subscription->start_date ?? 0)),
    get_string('subfield_end', 'local_subscriptions') => AdminFormatter::subscription_end((int)($subscription->end_date ?? 0)),
    get_string('price', 'local_subscriptions') => AdminFormatter::price($subscription->pricepaid ?? 0, $subscription->currency ?? ''),

    get_string('admin_section_discounts', 'local_subscriptions') => '',
    get_string('discount_percent', 'local_subscriptions') => s((string)($subscription->discount_percent ?? 0)) . '%',
    get_string('discount_amount', 'local_subscriptions') => AdminFormatter::price($subscription->discount_amount ?? 0, $subscription->currency ?? ''),
    get_string('discount_reason', 'local_subscriptions') => s($subscription->discount_reason ?? '-'),

    get_string('admin_section_provider', 'local_subscriptions') => '',
    get_string('subfield_provider', 'local_subscriptions') => !empty($subscription->payment_provider)
        ? Provider::label_with_icon_env($subscription->payment_provider)
        : '-',
    get_string('transactionid', 'local_subscriptions') => s($subscription->transactionid ?? '-'),
    get_string('subfield_provider_sub', 'local_subscriptions') => s($subscription->provider_subscription_id ?? '-'),
    get_string('subfield_provider_customer', 'local_subscriptions') => s($subscription->provider_customer_id ?? '-'),
    get_string('subfield_last_invoice', 'local_subscriptions') => s($subscription->last_invoice_id ?? '-'),

    get_string('admin_section_payment_failures', 'local_subscriptions') => '',
    get_string('payment_failed', 'local_subscriptions') => !empty($subscription->payment_failed) ? get_string('yes') : get_string('no'),
    get_string('subfield_last_failed_at', 'local_subscriptions') => !empty($subscription->last_payment_failed_at)
        ? AdminFormatter::datetime((int)$subscription->last_payment_failed_at)
        : '-',
    get_string('subfield_fail_reason', 'local_subscriptions') => !empty($subscription->last_payment_failed_reason)
        ? s($subscription->last_payment_failed_reason)
        : '-',

    get_string('admin_section_dates', 'local_subscriptions') => '',
    get_string('creation_date', 'local_subscriptions') => AdminFormatter::datetime((int)($subscription->creation_date ?? 0)),
    get_string('last_update', 'local_subscriptions') => AdminFormatter::datetime((int)($subscription->last_update ?? 0)),
];

echo html_writer::start_div('crm-commerce-detail-grid');

echo AdminDetailRenderer::card(
    get_string('subscription_details', 'local_subscriptions') . ' #' . $subscription->id,
    $rows
);

if ($paymentrequest) {
    $providerdata = AdminDetailRenderer::json($paymentrequest->response_json ?? '');

    $finalprice = !empty($paymentrequest->locked_final_price)
        ? $paymentrequest->locked_final_price
        : $paymentrequest->price;

    $prrows = [
        get_string('admin_section_payment_request_identity', 'local_subscriptions') => '',
        get_string('subfield_pr_id', 'local_subscriptions') => (string)$paymentrequest->id,
        get_string('user') => !empty($paymentrequest->userid)
            ? AdminEntityLinks::user((int)$paymentrequest->userid, '#' . (int)$paymentrequest->userid)
            : '-',
        get_string('email') => s($paymentrequest->email ?? '-'),
        get_string('firstname') => s($paymentrequest->firstname ?? '-'),
        get_string('lastname') => s($paymentrequest->lastname ?? '-'),
        get_string('phone', 'local_subscriptions') => s($paymentrequest->phone ?? '-'),
        get_string('phone_country', 'local_subscriptions') => s($paymentrequest->phone_country ?? '-'),

        get_string('admin_section_payment_status', 'local_subscriptions') => '',
        get_string('status', 'local_subscriptions') => SubsPresenter::render_status_badge($paymentrequest->status ?? ''),
        get_string('operation', 'local_subscriptions') => s($paymentrequest->operation ?? '-'),
        get_string('reference_subscription_id', 'local_subscriptions') => !empty($paymentrequest->reference_subscription_id)
            ? AdminEntityLinks::subscription((int)$paymentrequest->reference_subscription_id, '#' . (int)$paymentrequest->reference_subscription_id)
            : '-',
        get_string('payment_provider', 'local_subscriptions') => !empty($paymentrequest->payment_provider)
            ? Provider::label_with_icon_env($paymentrequest->payment_provider)
            : '-',
        get_string('digital_session_id', 'local_subscriptions') => s($paymentrequest->sessionid ?? '-'),
        get_string('transactionid', 'local_subscriptions') => s($paymentrequest->transactionid ?? '-'),

        get_string('admin_section_amounts', 'local_subscriptions') => '',
        get_string('price', 'local_subscriptions') => AdminFormatter::price($paymentrequest->price ?? 0, $paymentrequest->currency ?? ''),
        get_string('amount_minor', 'local_subscriptions') => s((string)($paymentrequest->amount_minor ?? 0)),
        get_string('locked_list_price', 'local_subscriptions') => AdminFormatter::price($paymentrequest->locked_list_price ?? 0, $paymentrequest->currency ?? ''),
        get_string('locked_discount_percent', 'local_subscriptions') => s((string)($paymentrequest->locked_discount_percent ?? 0)) . '%',
        get_string('locked_discount_amount', 'local_subscriptions') => AdminFormatter::price($paymentrequest->locked_discount_amount ?? 0, $paymentrequest->currency ?? ''),
        get_string('locked_discount_reason', 'local_subscriptions') => s($paymentrequest->locked_discount_reason ?? '-'),
        get_string('locked_final_price', 'local_subscriptions') => AdminFormatter::price($finalprice ?? 0, $paymentrequest->currency ?? ''),
        get_string('locked_at', 'local_subscriptions') => !empty($paymentrequest->locked_at)
            ? AdminFormatter::datetime((int)$paymentrequest->locked_at)
            : '-',

        get_string('admin_section_links_tokens', 'local_subscriptions') => '',
        get_string('digital_payment_link', 'local_subscriptions') => AdminDetailRenderer::external_link($paymentrequest->payment_link ?? ''),
        get_string('retry_token', 'local_subscriptions') => s($paymentrequest->retry_token ?? '-'),
        get_string('retry_expires', 'local_subscriptions') => !empty($paymentrequest->retry_expires)
            ? AdminFormatter::datetime((int)$paymentrequest->retry_expires)
            : '-',
        get_string('login_token', 'local_subscriptions') => s($paymentrequest->login_token ?? '-'),
        get_string('login_token_expires', 'local_subscriptions') => !empty($paymentrequest->login_token_expires)
            ? AdminFormatter::datetime((int)$paymentrequest->login_token_expires)
            : '-',

        get_string('admin_section_reminders_attempts', 'local_subscriptions') => '',
        get_string('emailsent', 'local_subscriptions') => !empty($paymentrequest->emailsent) ? get_string('yes') : get_string('no'),
        get_string('digital_attempts', 'local_subscriptions') => s((string)($paymentrequest->attempts ?? 0)),
        get_string('digital_last_attempt', 'local_subscriptions') => !empty($paymentrequest->last_attempt)
            ? AdminFormatter::datetime((int)$paymentrequest->last_attempt)
            : '-',
        get_string('digital_last_error', 'local_subscriptions') => AdminDetailRenderer::pre($paymentrequest->last_error ?? ''),
        get_string('reminder_stage', 'local_subscriptions') => s((string)($paymentrequest->reminder_stage ?? 0)),
        get_string('reminder1_at', 'local_subscriptions') => !empty($paymentrequest->reminder1_at)
            ? AdminFormatter::datetime((int)$paymentrequest->reminder1_at)
            : '-',
        get_string('reminder2_at', 'local_subscriptions') => !empty($paymentrequest->reminder2_at)
            ? AdminFormatter::datetime((int)$paymentrequest->reminder2_at)
            : '-',

        get_string('admin_section_request_context', 'local_subscriptions') => '',
        get_string('created_ip', 'local_subscriptions') => s($paymentrequest->created_ip ?? '-'),
        get_string('accept_language', 'local_subscriptions') => s($paymentrequest->accept_language ?? '-'),
        get_string('http_referer', 'local_subscriptions') => AdminDetailRenderer::external_link($paymentrequest->http_referer ?? ''),
        get_string('created_useragent', 'local_subscriptions') => AdminDetailRenderer::pre($paymentrequest->created_useragent ?? ''),

        get_string('admin_section_dates', 'local_subscriptions') => '',
        get_string('creation_date', 'local_subscriptions') => AdminFormatter::datetime((int)($paymentrequest->creation_date ?? 0)),
        get_string('last_update', 'local_subscriptions') => AdminFormatter::datetime((int)($paymentrequest->last_update ?? 0)),
        get_string('digital_purchases_export_payment_date', 'local_subscriptions') => !empty($paymentrequest->payment_date)
            ? AdminFormatter::datetime((int)$paymentrequest->payment_date)
            : '-',
        get_string('expiration_date', 'local_subscriptions') => !empty($paymentrequest->expiration_date)
            ? AdminFormatter::datetime((int)$paymentrequest->expiration_date)
            : '-',

        get_string('digital_response_json', 'local_subscriptions') => $providerdata,
    ];

    echo AdminDetailRenderer::card(
        get_string('payment_request', 'local_subscriptions') . ' #' . $paymentrequest->id,
        $prrows
    );

} else {
    echo html_writer::div(
        get_string('crm_no_payment_request_for_subscription', 'local_subscriptions'),
        'alert alert-info'
    );
}

echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();