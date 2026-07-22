<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(
    $CFG->dirroot .
    '/local/subscriptions/renderer/user_subs_renderer.php'
);

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;

global $DB, $OUTPUT, $PAGE;

$context = AdminSecurity::require(
    Capabilities::MANAGE_SUBSCRIPTIONS
);

$planid = optional_param(
    'planid',
    0,
    PARAM_INT
);

$page = optional_param(
    'page',
    0,
    PARAM_INT
);

$perpage = optional_param(
    'perpage',
    50,
    PARAM_INT
);

$perpage = max(
    10,
    min(
        200,
        $perpage
    )
);

$urlparams = [
    'planid' => $planid,
    'perpage' => $perpage,
];

$pageurl = new moodle_url(
    subscription_config::
        user_subscriptions_page(),
    $urlparams
);

$pagetitle = get_string(
    'crm_subscriptions_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-subscriptions-page'
);

$where = [];
$params = [];

if ($planid > 0) {
    $where[] =
        'us.planid = :planid';

    $params['planid'] =
        $planid;
}

$wheresql = $where
    ? 'WHERE ' .
        implode(
            ' AND ',
            $where
        )
    : '';

$countsql = "
    SELECT COUNT(1)
      FROM {user_subscription} us
      JOIN {user} u
        ON u.id = us.userid
 LEFT JOIN {subscription_plan} sp
        ON sp.id = us.planid
    $wheresql
";

$totalcount = $DB->count_records_sql(
    $countsql,
    $params
);

$sql = "
    SELECT
        us.*,
        u.firstname,
        u.lastname,
        u.email,
        u.firstnamephonetic,
        u.lastnamephonetic,
        u.middlename,
        u.alternatename,
        sp.name AS planname,
        sp.duration_key,
        sp.accessscopeid,
        sp.is_recurring
      FROM {user_subscription} us
      JOIN {user} u
        ON u.id = us.userid
 LEFT JOIN {subscription_plan} sp
        ON sp.id = us.planid
    $wheresql
  ORDER BY
        us.start_date DESC,
        us.id DESC
";

$subscriptions = $DB->get_records_sql(
    $sql,
    $params,
    $page * $perpage,
    $perpage
);

$plans = [];

$planrecords = $DB->get_records(
    'subscription_plan',
    null,
    'name ASC',
    'id, name'
);

foreach ($planrecords as $plan) {
    $translation =
        subscription_manager::
            get_translated_plan_name(
                $plan->id,
                current_language()
            );

    $plans[$plan->id] =
        $translation
            ?: format_string(
                $plan->name
            );
}

$renderer =
    new local_subscriptions_user_subs_renderer(
        $PAGE,
        $OUTPUT
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
                'crm_subscriptions_breadcrumb',
                'local_subscriptions'
            ),
            'url' => null,
        ],
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscriptions_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SUBSCRIPTIONS
);


echo html_writer::start_div('crm-commerce-list crm-commerce-subscriptions-list');

echo $renderer->
    render_user_subscriptions_admin_page(
        $subscriptions,
        $plans,
        $planid,
        $page,
        $perpage,
        $totalcount
    );

echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();