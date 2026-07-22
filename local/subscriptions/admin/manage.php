<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

$PAGE->requires->css(new moodle_url('/local/subscriptions/select2.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/select2.min.js'), true);

$currenttab = optional_param(
    'tab',
    'scopes',
    PARAM_ALPHANUMEXT
);

$delete = optional_param(
    'delete',
    0,
    PARAM_INT
);

$allowedtabs = [
    'scopes',
    'plans',
];

if (
    !in_array(
        $currenttab,
        $allowedtabs,
        true
    )
) {
    $currenttab = 'scopes';

}

$pageurl = new moodle_url(
    subscription_config::
        manage_page(),
    [
        'tab' => $currenttab,
    ]
);

$pagetitle = get_string(
    'crm_subscription_configuration_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-subscription-configuration-page'
);

if ($currenttab === 'scopes') {

    if ($delete) {
        require_sesskey();

        require_once(
            $CFG->dirroot .
            '/local/subscriptions/lib/scopes_lib.php'
        );

        local_subscriptions_delete_scope(
            $delete
        );
    }
    
    require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_form.php');
    // Instancie le form avec son action sur l’onglet scopes.
    $formaction = new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']);
    $mform = new access_scope_form($formaction);

    if ($mform->is_cancelled()) {
        // Retour propre à la liste des plans.
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']));
    } else if ($data = $mform->get_data()) {
        // Sauvegarde du scope.
        global $DB;

        $rec = new stdClass();
        $rec->id             = (int)($data->id ?? 0);
        $rec->name           = $data->name;
        $rec->course_ids     = is_array($data->course_ids) ? implode(',', $data->course_ids) : '';
        $rec->last_update    = time();

        if ($rec->id) {
            $DB->update_record('subscription_access_scope', $rec);
        } else {
            $rec->creation_date = time();
            $rec->id = $DB->insert_record('subscription_access_scope', $rec);

            if ($rec->id) {
                redirect(
                    new moodle_url(subscription_config::scopes_translations_page(), [
                        'accessscopeid' => $rec->id,
                        'add' => $rec->id,
                        'sesskey' => sesskey()
                    ]),
                    get_string('scopecreated', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
                    get_string('scopecreateerror', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        }

        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
            get_string('changessaved'), 2, \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // IMPORTANT : on laisse $mform disponible pour l’affichage plus bas.
}


elseif ($currenttab === 'plans') {

    if ($delete) {
        require_sesskey();

        require_once(
            $CFG->dirroot .
            '/local/subscriptions/lib/plans_lib.php'
        );

        local_subscriptions_delete_plan(
            $delete
        );
    }

    require_once($CFG->dirroot . '/local/subscriptions/forms/plan_form.php');
    // Instancie le form avec son action sur l’onglet plans.
    $formaction = new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']);
    $mform = new plan_form($formaction);

    if ($mform->is_cancelled()) {
        // Retour propre à la liste des plans.
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']));
    } else if ($data = $mform->get_data()) {
        // Sauvegarde du plan + highlight_type.
        global $DB;

        $rec = new stdClass();
        $rec->id             = (int)($data->id ?? 0);
        $rec->name           = $data->name;
        $rec->accessscopeid  = (int)$data->accessscopeid;
        $rec->duration_key   = $data->duration_key;
        $rec->highlight_type = in_array($data->highlight_type ?? '', ['popular','premium'], true) ? $data->highlight_type : null;
        $rec->last_update    = time();
        $rec->is_active      = 0;
        $rec->is_recurring   = (int)$data->is_recurring;

        if ($rec->id) {
            $DB->update_record('subscription_plan', $rec);
        } else {
            $rec->creation_date = time();
            $rec->id = $DB->insert_record('subscription_plan', $rec);

            if ($rec->id) {
                redirect(
                    new moodle_url(subscription_config::plans_translations_page(), [
                        'planid' => $rec->id,
                        'add' => $rec->id,
                        'sesskey' => sesskey()
                    ]),
                    get_string('plancreated', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
                    get_string('plancreateerror', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        }

        // Option : un seul "popular"
        if ($rec->highlight_type === 'popular') {
            $DB->execute("UPDATE {subscription_plan} SET highlight_type = NULL WHERE id <> :id AND highlight_type = 'popular'", ['id' => $rec->id]);
        }

        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
            get_string('changessaved'), 2, \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // IMPORTANT : on laisse $mform disponible pour l’affichage plus bas.
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
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            admin_commerce_page()
    ),
    get_string(
        'crm_commerce_title',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_subscription_configuration_description',
        'local_subscriptions'
    ),
    HelpContext::SUBSCRIPTIONS
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::CONFIGURATION
);


$tabs = [
    new tabobject(
        'scopes',
        new moodle_url(
            subscription_config::
                manage_page(),
            [
                'tab' => 'scopes',
            ]
        ),
        get_string(
            'scopes',
            'local_subscriptions'
        )
    ),

    new tabobject(
        'plans',
        new moodle_url(
            subscription_config::
                manage_page(),
            [
                'tab' => 'plans',
            ]
        ),
        get_string(
            'plans',
            'local_subscriptions'
        )
    ),
];

print_tabs(
    [
        $tabs,
    ],
    $currenttab
);

// Include selected tab
switch ($currenttab) {
    case 'plans':
        include_once(__DIR__ . '/../tabs/plans.php');
        break;
    case 'scopes':
    default:
        include_once(__DIR__ . '/../tabs/scopes.php');
        break;
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
