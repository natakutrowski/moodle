<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/user_subs_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/user_subs_renderer.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\subscription_manager;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;
use local_subscriptions\commerce\grant\CommerceManualProductGrantService;
use local_subscriptions\commerce\mail\service\CommerceGrantAccessMailService;

function local_subscriptions_generate_unique_username_from_email(string $email): string {
    global $DB;

    $base = core_text::strtolower(trim($email));
    $base = clean_param($base, PARAM_USERNAME);

    if ($base === '') {
        $base = 'user';
    }

    $username = $base;
    $i = 1;

    while ($DB->record_exists('user', ['username' => $username])) {
        $username = $base . '.' . $i;
        $i++;
    }

    return $username;
}

global $DB, $PAGE, $OUTPUT, $CFG;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$preselecteduserid = optional_param('userid', 0, PARAM_INT);

$pageurl = new moodle_url(
    subscription_config::add_manual_subscription_page()
);

if ($preselecteduserid > 0) {
    $pageurl->param('userid', $preselecteduserid);
}

$pagetitle = get_string(
    'add_subscription',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-subscription-add-page'
);

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

$PAGE->requires->css(new moodle_url('/local/subscriptions/thirdparty/flatpickr/flatpickr.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/thirdparty/flatpickr/flatpickr.min.js'), true);
$PAGE->requires->js(new moodle_url('/local/subscriptions/thirdparty/flatpickr/l10n/fr.js'), true);

$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

$preselecteduser = null;

if ($preselecteduserid > 0) {
    $preselecteduser = $DB->get_record('user', [
        'id' => $preselecteduserid,
        'deleted' => 0,
    ], '*', MUST_EXIST);
}

$plans = [];
foreach ($DB->get_records('subscription_plan', null, 'name ASC') as $plan) {
    $translation = subscription_manager::get_translated_plan_name($plan->id, current_language());
    $label = $translation ?: format_string($plan->name);

    if (empty($plan->is_active)) {
        $label .= ' ' . get_string('label_inactive', 'local_subscriptions');
    }

    $plans[$plan->id] = $label;
}


$nativeproducts = [];
$productnameresolver = CommerceProductDisplayNameResolver::create($DB);
foreach ($DB->get_records(
    'local_subs_commerce_product',
    ['status' => 'active'],
    'name ASC, id ASC',
    'id,sku,type,name,status'
) as $product) {
    $displayname = $productnameresolver->resolve(
        [(string)$product->sku],
        current_language(),
        (string)$product->name
    );
    $nativeproducts[(int)$product->id] = $displayname
        . ' · ' . strtoupper((string)$product->type)
        . ' · ' . (string)$product->sku;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $grantmode = optional_param('grant_mode', 'legacy', PARAM_ALPHA);
    if (!in_array($grantmode, ['legacy', 'native'], true)) {
        throw new moodle_exception('commerce_manual_grant_invalid_mode', 'local_subscriptions');
    }

    $planid = 0;
    $pricecurrency = '';
    $startraw = '';

    if ($grantmode === 'legacy') {
        $planid = required_param('plan', PARAM_INT);
        $price = required_param('price', PARAM_FLOAT);
        $currency = strtoupper(required_param('currency', PARAM_ALPHA));
        $pricecurrency = number_format($price, 2, '.', '') . '|' . $currency;

        $startraw = optional_param('start_date', '', PARAM_RAW_TRIMMED);
        if ($startraw !== '' && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $startraw, $m)) {
            $startraw = $m[3] . '-' . $m[2] . '-' . $m[1];
        }
    }

    $userid = optional_param('userid', 0, PARAM_INT);
    $usermode = optional_param('user_mode', 'existing', PARAM_ALPHA);

    if ($userid <= 0 && $usermode === 'new') {
        $firstname = required_param('firstname', PARAM_TEXT);
        $lastname = required_param('lastname', PARAM_TEXT);
        $email = required_param('email', PARAM_EMAIL);
        $country = optional_param('country', '', PARAM_ALPHA);
        $country = strtoupper($country);

        $existing = $DB->get_record('user', ['email' => $email, 'deleted' => 0], '*', IGNORE_MISSING);

        if ($existing) {
            $userid = (int)$existing->id;
        } else {
            $password = generate_password(12);

            $newuser = (object)[
                'auth' => 'manual',
                'confirmed' => 1,
                'mnethostid' => $CFG->mnet_localhost_id,
                'username' => local_subscriptions_generate_unique_username_from_email($email),
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'country' => $country,
                'password' => hash_internal_user_password($password),
                'timecreated' => time(),
                'timemodified' => time(),
            ];

            $userid = user_create_user($newuser, false, false);
        }
    }

    if ($userid <= 0) {
        throw new moodle_exception('missing_user_for_manual_subscription', 'local_subscriptions');
    }

    if ($grantmode === 'native') {
        $productid = required_param('native_product_id', PARAM_INT);
        $reason = optional_param('grant_reason', '', PARAM_TEXT);

        $result = (new CommerceManualProductGrantService($DB))->grant(
            $userid,
            $productid,
            (int)$USER->id,
            $reason
        );

        if (optional_param('send_access_email', 0, PARAM_BOOL)) {
            CommerceGrantAccessMailService::create()->queue(
                $userid,
                $productid,
                $result['plan'],
                true
            );
        }

        $user = core_user::get_user($userid, '*', MUST_EXIST);
        $a = (object)[
            'user' => fullname($user) . ' (' . $user->email . ')',
            'product' => $nativeproducts[$productid] ?? ('#' . $productid),
            'count' => $result['plan']->count(),
        ];
        \core\notification::success(
            get_string('commerce_manual_grant_success', 'local_subscriptions', $a)
        );

        redirect(new moodle_url(
            subscription_config::admin_user_view_page(),
            ['id' => $userid]
        ));
    }

    $initialoblevel = ob_get_level();
    ob_start();

    try {
        $status = local_subscriptions_enrol_user_manual(
            $userid,
            $planid,
            $pricecurrency,
            $startraw,
            true
        );

    } finally {
        $unexpectedoutput = '';

        while (ob_get_level() > $initialoblevel) {
            $unexpectedoutput = (string)ob_get_clean() . $unexpectedoutput;
        }

        $unexpectedoutput = trim($unexpectedoutput);

        if ($unexpectedoutput !== '') {
            error_log(
                '[local_subscriptions] Unexpected output during manual subscription creation: '
                . strip_tags($unexpectedoutput)
            );
        }
    }

    if ($status === 'created') {
        $subscription = $DB->get_record_sql("
            SELECT us.*, sp.name AS planname
            FROM {user_subscription} us
        LEFT JOIN {subscription_plan} sp ON sp.id = us.planid
            WHERE us.userid = :userid
            AND us.planid = :planid
        ORDER BY us.id DESC
        ", [
            'userid' => $userid,
            'planid' => $planid,
        ], IGNORE_MISSING);

        if ($subscription) {
            AdminLog::subscriptionCreatedManual($subscription);
        }
    }

    $user = core_user::get_user($userid);
    $planlabel = $plans[$planid] ?? $planid;

    $a = (object)[
        'user' => fullname($user) . ' (' . $user->email . ')',
        'plan' => $planlabel,
    ];

    if ($status === 'created') {
        \core\notification::success(get_string('sub_created', 'local_subscriptions', $a));
    } else {
        \core\notification::info(get_string('sub_exists', 'local_subscriptions', $a));
    }

    if ($preselecteduserid > 0) {
        redirect(
            new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                [
                    'id' =>
                        $preselecteduserid,
                ]
            )
        );
    }

    if (
        $status === 'created' &&
        !empty($subscription)
    ) {
        redirect(
            new moodle_url(
                subscription_config::
                    user_subscription_view_page(),
                [
                    'id' =>
                        $subscription->id,
                ]
            )
        );
    }

    redirect(
        new moodle_url(
            subscription_config::
                user_subscriptions_page(),
            [
                'planid' => $planid,
            ]
        )
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
        'crm_subscription_add_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SUBSCRIPTIONS
);

echo $renderer->
    render_manual_subscription_form_v2(
        $plans,
        $preselecteduser,
        $nativeproducts
    );

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();