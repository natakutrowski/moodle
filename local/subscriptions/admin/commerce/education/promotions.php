<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\admin\CommerceEducationNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $OUTPUT, $PAGE;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/education/promotions.php'
);
$title = get_string(
    'commerce_education_promotions_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-promotions-page'
);

$repository =
    CommercePedagogicalPromotionRepository::create($DB);

$delete = optional_param('delete', 0, PARAM_INT);
if ($delete > 0) {
    require_sesskey();
    $repository->delete($delete);
    redirect(
        $pageurl,
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$promotions = $repository->all();

$now = time();
$openpromotions = 0;
$startedpromotions = 0;
foreach ($promotions as $promotion) {
    if ($promotion->sales_are_open($now)) {
        $openpromotions++;
    }
    if ($promotion->get_status() === CommercePedagogicalPromotionStatus::STARTED) {
        $startedpromotions++;
    }
}
$activeparticipants = $DB->count_records(
    'local_subs_commerce_ped_join',
    ['state' => 'active']
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => $title,
        'url' => null,
    ],
]);

$actions = html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/education/promotion_edit.php'),
    get_string('commerce_education_promotion_create', 'local_subscriptions'),
    ['class' => 'btn btn-primary']
);

echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_education_promotions_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE,
    $actions
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::EDUCATION,
    $context
);

echo CommerceEducationNavigationRenderer::render(CommerceEducationNavigationRenderer::PROMOTIONS);

echo html_writer::start_div('row g-3 commerce-ped-admin-metrics mb-4');
$metriccards = [
    ['value' => count($promotions), 'label' => get_string('commerce_education_admin_metric_promotions', 'local_subscriptions'), 'icon' => 'fa-flag-checkered'],
    ['value' => $openpromotions, 'label' => get_string('commerce_education_admin_metric_sales_open', 'local_subscriptions'), 'icon' => 'fa-shopping-cart'],
    ['value' => $startedpromotions, 'label' => get_string('commerce_education_admin_metric_started', 'local_subscriptions'), 'icon' => 'fa-play-circle'],
    ['value' => $activeparticipants, 'label' => get_string('commerce_education_admin_metric_participants', 'local_subscriptions'), 'icon' => 'fa-users'],
];
foreach ($metriccards as $metric) {
    echo html_writer::start_div('col-6 col-xl-3');
    echo html_writer::start_div('commerce-ped-admin-metric-card');
    echo html_writer::tag('i', '', ['class' => 'fa ' . $metric['icon'] . ' commerce-ped-admin-metric-icon', 'aria-hidden' => 'true']);
    echo html_writer::start_div();
    echo html_writer::div((string)$metric['value'], 'commerce-ped-admin-metric-value');
    echo html_writer::div($metric['label'], 'commerce-ped-admin-metric-label');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('card');
echo html_writer::start_div('card-body');

if ($promotions === []) {
    echo html_writer::div(
        get_string(
            'commerce_education_promotions_empty',
            'local_subscriptions'
        ),
        'alert alert-info mb-0'
    );
} else {
    $table = new html_table();
    $table->attributes['class'] = 'table align-middle commerce-ped-admin-promotions-table';
    $table->head = [
        get_string(
            'commerce_education_promotion',
            'local_subscriptions'
        ),
        get_string('course'),
        get_string('status'),
        get_string(
            'commerce_education_sales_window',
            'local_subscriptions'
        ),
        get_string(
            'commerce_education_capacity',
            'local_subscriptions'
        ),
        '',
    ];

    foreach ($promotions as $promotion) {
        $course = get_course(
            $promotion->get_course_id()
        );
        $coursecontext = context_course::instance(
            (int)$course->id
        );
        $coursename = format_string(
            (string)$course->fullname,
            true,
            ['context' => $coursecontext]
        );

        $sales = [];
        if ($promotion->get_sales_opens_at() !== null) {
            $sales[] =
                userdate(
                    $promotion->get_sales_opens_at()
                );
        }
        if ($promotion->get_sales_closes_at() !== null) {
            $sales[] =
                userdate(
                    $promotion->get_sales_closes_at()
                );
        }

        $statuslabel = get_string(
            'commerce_education_promotion_status_'
            . $promotion->get_status(),
            'local_subscriptions'
        );

        $editurl = new moodle_url(
            '/local/subscriptions/admin/commerce/education/promotion_edit.php',
            ['id' => $promotion->get_id()]
        );
        $deleteurl = new moodle_url(
            $pageurl,
            [
                'delete' => $promotion->get_id(),
                'sesskey' => sesskey(),
            ]
        );

        $table->data[] = [
            html_writer::div(
                s($promotion->get_name()),
                'fw-semibold'
            )
            . html_writer::div(
                s($promotion->get_promotion_key()),
                'small text-muted'
            ),
            $coursename,
            html_writer::span(
                $statuslabel,
                'badge rounded-pill ' . match ($promotion->get_status()) {
                    CommercePedagogicalPromotionStatus::OPEN => 'text-bg-success',
                    CommercePedagogicalPromotionStatus::STARTED => 'text-bg-primary',
                    CommercePedagogicalPromotionStatus::SCHEDULED => 'text-bg-info',
                    CommercePedagogicalPromotionStatus::FULL => 'text-bg-warning',
                    CommercePedagogicalPromotionStatus::FINISHED => 'text-bg-dark',
                    default => 'text-bg-secondary',
                }
            )
            . html_writer::div(
                $promotion->sales_are_open($now)
                    ? get_string('commerce_education_admin_sales_open', 'local_subscriptions')
                    : get_string('commerce_education_admin_sales_closed', 'local_subscriptions'),
                'small mt-1 ' . ($promotion->sales_are_open($now) ? 'text-success' : 'text-muted')
            ),
            $sales !== []
                ? implode(' → ', $sales)
                : '—',
            html_writer::div(
                $promotion->get_capacity_total() !== null
                    ? (string)$promotion->get_capacity_total()
                    : get_string('commerce_education_capacity_unlimited', 'local_subscriptions'),
                'fw-semibold'
            )
            . html_writer::div(
                get_string(
                    'commerce_education_admin_participant_count',
                    'local_subscriptions',
                    $DB->count_records(
                        'local_subs_commerce_ped_join',
                        ['promotionid' => $promotion->get_id(), 'state' => 'active']
                    )
                ),
                'small text-muted'
            ),
            html_writer::div(
                html_writer::link(
                    $editurl,
                    get_string('commerce_education_admin_manage', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-primary']
                )
                . html_writer::link(
                    $deleteurl,
                    get_string('delete'),
                    ['class' => 'btn btn-sm btn-outline-danger']
                ),
                'd-flex gap-2 flex-wrap justify-content-end'
            ),
        ];
    }

    echo html_writer::table($table);
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
