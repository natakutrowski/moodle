<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');
use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(
    Capabilities::MANAGE_SUBSCRIPTIONS
);

define('LOCAL_SUBSCRIPTIONS_SCOPE_ALL_LEVELS', 13);
define('LOCAL_SUBSCRIPTIONS_SCOPE_TRIAL', 14);
define('LOCAL_SUBSCRIPTIONS_SCOPE_A1', 15);

$download = optional_param('download', 0, PARAM_BOOL);

$pageurl = new moodle_url(
    subscription_config::
        subscriptions_export_page()
);

$pagetitle = get_string(
    'crm_subscriptions_export_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-subscriptions-export-page'
);

if (!$download) {
    $downloadurl = new moodle_url(
        subscription_config::
            subscriptions_export_page(),
        [
            'download' => 1,
        ]
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
            'crm_subscriptions_export_description',
            'local_subscriptions'
        ),
        HelpContext::SUBSCRIPTIONS
    );

    echo html_writer::div(
        html_writer::tag(
            'p',
            get_string(
                'crm_subscriptions_export_help',
                'local_subscriptions'
            ),
            [
                'class' => 'text-muted mb-4',
            ]
        ) .
        html_writer::link(
            $downloadurl,
            get_string(
                'crm_subscriptions_export_download',
                'local_subscriptions'
            ),
            [
                'class' => 'btn btn-primary',
            ]
        ),
        'card card-body'
    );

    echo CrmWorkspaceRenderer::end();

    echo $OUTPUT->footer();
    exit;
}

$lang = current_language();

$sql = "
    SELECT
        us.id,
        u.firstname,
        u.lastname,
        u.email,
        u.phone1,
        u.phone2,
        COALESCE(NULLIF(pt.name, ''), p.name) AS planname,
        p.accessscopeid,
        COALESCE(NULLIF(ast.name, ''), s.name) AS scopename,
        p.duration_key,
        p.is_trial,
        us.pricepaid,
        us.currency,
        us.creation_date,
        us.start_date,
        us.status
    FROM {user_subscription} us
    JOIN {user} u ON u.id = us.userid
    LEFT JOIN {subscription_plan} p ON p.id = us.planid
    LEFT JOIN {subscription_plan_translation} pt
        ON pt.planid = p.id
        AND pt.lang = :planlang
    LEFT JOIN {subscription_access_scope} s ON s.id = p.accessscopeid
    LEFT JOIN {subscription_access_scope_translation} ast
        ON ast.accessscopeid = s.id
        AND ast.lang = :scopelang
    WHERE u.deleted = 0
    ORDER BY us.creation_date DESC, us.id DESC
";

$records = $DB->get_records_sql($sql, [
    'planlang' => $lang,
    'scopelang' => $lang,
]);

$sheets = [
    'long' => [
        'title' => get_string(
            'crm_subscriptions_export_sheet_long',
            'local_subscriptions'
        ),
        'records' => [],
    ],
    'a1' => [
        'title' => get_string(
            'crm_subscriptions_export_sheet_a1',
            'local_subscriptions'
        ),
        'records' => [],
    ],
    'trial' => [
        'title' => get_string(
            'crm_subscriptions_export_sheet_trial',
            'local_subscriptions'
        ),
        'records' => [],
    ],
];

foreach ($records as $record) {
    $accessscopeid = (int)($record->accessscopeid ?? 0);

    if ($accessscopeid === LOCAL_SUBSCRIPTIONS_SCOPE_TRIAL || (int)$record->is_trial === 1) {
        $sheets['trial']['records'][] = $record;
        continue;
    }

    if ($accessscopeid === LOCAL_SUBSCRIPTIONS_SCOPE_A1) {
        $sheets['a1']['records'][] = $record;
        continue;
    }

    if ($accessscopeid === LOCAL_SUBSCRIPTIONS_SCOPE_ALL_LEVELS) {
        $sheets['long']['records'][] = $record;
        continue;
    }

    if (in_array($record->duration_key, ['1year', '3years', 'lifetime'], true)) {
        $sheets['long']['records'][] = $record;
        continue;
    }
}

$filename =
    'subscriptions_' .
    date('Y-m-d_H-i') .
    '.xlsx';

$workbook = new MoodleExcelWorkbook('-');
$workbook->send($filename);

$headerformat = $workbook->add_format([
    'bold' => 1,
    'bg_color' => '#D9EAF7',
]);

$moneyformat = $workbook->add_format([
    'num_format' => '#,##0.00',
]);

$headers = [
    get_string(
        'lastname'
    ),
    get_string(
        'firstname'
    ),
    get_string(
        'email'
    ),
    get_string(
        'phone'
    ),
    get_string(
        'plan',
        'local_subscriptions'
    ),
    get_string(
        'scope',
        'local_subscriptions'
    ),
    get_string(
        'pricepaid',
        'local_subscriptions'
    ),
    get_string(
        'currency',
        'local_subscriptions'
    ),
    get_string(
        'registration_date',
        'local_subscriptions'
    ),
    get_string(
        'status'
    ),
];

foreach ($sheets as $sheetdata) {
    $worksheet = $workbook->add_worksheet($sheetdata['title']);

    foreach ($headers as $col => $header) {
        $worksheet->write_string(0, $col, $header, $headerformat);
    }

    $row = 1;

    foreach ($sheetdata['records'] as $record) {
        $phone = $record->phone1 ?: $record->phone2;
        $date = $record->creation_date ?: $record->start_date;

        $worksheet->write_string($row, 0, $record->lastname ?? '');
        $worksheet->write_string($row, 1, $record->firstname ?? '');
        $worksheet->write_string($row, 2, $record->email ?? '');
        $worksheet->write_string($row, 3, $phone ?? '');
        $worksheet->write_string($row, 4, $record->planname ?? '');
        $worksheet->write_string($row, 5, $record->scopename ?? '');

        if ($record->pricepaid !== null) {
            $worksheet->write_number($row, 6, (float)$record->pricepaid, $moneyformat);
        } else {
            $worksheet->write_string($row, 6, '');
        }

        $worksheet->write_string($row, 7, $record->currency ?? '');

        if (!empty($date)) {
            $worksheet->write_string($row, 8, userdate($date, '%d/%m/%Y %H:%M'));
        } else {
            $worksheet->write_string($row, 8, '');
        }

        $worksheet->write_string($row, 9, $record->status ?? '');

        $row++;
    }

    $worksheet->set_column(0, 0, 22);
    $worksheet->set_column(1, 1, 22);
    $worksheet->set_column(2, 2, 35);
    $worksheet->set_column(3, 3, 20);
    $worksheet->set_column(4, 4, 35);
    $worksheet->set_column(5, 5, 22);
    $worksheet->set_column(6, 6, 14);
    $worksheet->set_column(7, 7, 10);
    $worksheet->set_column(8, 8, 22);
    $worksheet->set_column(9, 9, 14);
}

$workbook->close();
exit;