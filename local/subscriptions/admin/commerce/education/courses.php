<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\access\CommercePedagogicalAvailabilitySynchronizer;
use local_subscriptions\commerce\education\admin\CommerceEducationNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $OUTPUT, $PAGE, $USER;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/education/courses.php'
);
$title = get_string(
    'commerce_education_courses_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-courses-page'
);

$repository =
    CommerceCourseAccessConfigurationRepository::create($DB);

if (
    data_submitted()
    && optional_param('save', 0, PARAM_BOOL)
) {
    require_sesskey();

    $submittedmodes = optional_param_array(
        'accessmode',
        [],
        PARAM_ALPHANUMEXT
    );

    foreach ($submittedmodes as $courseid => $mode) {
        $courseid = (int)$courseid;
        if ($courseid <= 0) {
            continue;
        }

        $repository->set_mode(
            $courseid,
            (string)$mode,
            (int)$USER->id
        );

        CommercePedagogicalAvailabilitySynchronizer::create($DB)
            ->sync_course($courseid);
    }

    redirect(
        $pageurl,
        get_string(
            'commerce_education_course_mode_saved',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$courses = $DB->get_records_select(
    'course',
    'id <> :siteid',
    ['siteid' => SITEID],
    'fullname ASC, id ASC',
    'id,fullname,shortname'
);

$modeoptions = [
    CommerceCourseAccessMode::CLASSIC_IMMEDIATE =>
        get_string(
            'commerce_education_access_mode_classic',
            'local_subscriptions'
        ),
    CommerceCourseAccessMode::PROMOTION =>
        get_string(
            'commerce_education_access_mode_promotion',
            'local_subscriptions'
        ),
];

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

echo CrmPageHeader::render(
    $title,
    get_string('commerce_education_courses_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::EDUCATION,
    $context
);

echo CommerceEducationNavigationRenderer::render(CommerceEducationNavigationRenderer::COURSES);

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'action' => $pageurl->out(false),
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'save',
        'value' => '1',
    ]
);

echo html_writer::start_div(
    'card commerce-education-course-modes'
);
echo html_writer::start_div('card-body');

if ($courses === []) {
    echo html_writer::div(
        get_string(
            'commerce_education_no_courses',
            'local_subscriptions'
        ),
        'alert alert-info mb-0'
    );
} else {
    echo html_writer::start_div('table-responsive');
    echo html_writer::start_tag(
        'table',
        ['class' => 'table align-middle mb-0']
    );

    echo html_writer::tag(
        'thead',
        html_writer::tag(
            'tr',
            html_writer::tag(
                'th',
                get_string('course'),
                ['scope' => 'col']
            )
            . html_writer::tag(
                'th',
                get_string(
                    'commerce_education_access_mode',
                    'local_subscriptions'
                ),
                ['scope' => 'col']
            )
            . html_writer::tag(
                'th',
                '',
                [
                    'scope' => 'col',
                    'class' => 'text-end',
                ]
            )
        )
    );

    echo html_writer::start_tag('tbody');

    foreach ($courses as $course) {
        $currentmode =
            $repository->mode_for_course(
                (int)$course->id
            );

        $selector = html_writer::select(
            $modeoptions,
            'accessmode[' . (int)$course->id . ']',
            $currentmode,
            false,
            [
                'class' => 'form-select form-select-sm',
                'aria-label' => get_string(
                    'commerce_education_access_mode',
                    'local_subscriptions'
                ),
            ]
        );

        echo html_writer::tag(
            'tr',
            html_writer::tag(
                'td',
                html_writer::div(
                    format_string(
                        (string)$course->fullname,
                        true,
                        [
                            'context' =>
                                context_course::instance(
                                    (int)$course->id
                                ),
                        ]
                    ),
                    'fw-semibold'
                )
                . html_writer::div(
                    s((string)$course->shortname),
                    'small text-muted'
                )
            )
            . html_writer::tag(
                'td',
                html_writer::span(
                    $currentmode ===
                        CommerceCourseAccessMode::PROMOTION
                        ? get_string(
                            'commerce_education_access_mode_promotion',
                            'local_subscriptions'
                        )
                        : get_string(
                            'commerce_education_access_mode_classic',
                            'local_subscriptions'
                        ),
                    'badge rounded-pill '
                    . (
                        $currentmode ===
                        CommerceCourseAccessMode::PROMOTION
                            ? 'text-bg-warning'
                            : 'text-bg-success'
                    )
                )
            )
            . html_writer::tag(
                'td',
                $selector,
                ['class' => 'text-end']
            )
        );
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}

echo html_writer::end_div();

if ($courses !== []) {
    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string('savechanges'),
            [
                'type' => 'submit',
                'class' => 'btn btn-primary',
            ]
        ),
        'card-footer d-flex justify-content-end'
    );
}

echo html_writer::end_div();
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
