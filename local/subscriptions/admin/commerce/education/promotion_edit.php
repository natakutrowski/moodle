<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
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

$id = optional_param('id', 0, PARAM_INT);
$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/education/promotion_edit.php',
    $id > 0 ? ['id' => $id] : []
);

$repository =
    CommercePedagogicalPromotionRepository::create($DB);

$courseaccessrepository =
    CommerceCourseAccessConfigurationRepository::create($DB);

$existing =
    $id > 0
        ? $repository->get_by_id($id)
        : null;

if ($id > 0 && $existing === null) {
    throw new moodle_exception(
        'invalidrecord',
        'error'
    );
}

$title = $existing
    ? get_string(
        'commerce_education_promotion_edit',
        'local_subscriptions'
    )
    : get_string(
        'commerce_education_promotion_create',
        'local_subscriptions'
    );

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-promotion-edit-page'
);

$parseoptionaldate = static function(
    string $name
): ?int {
    $raw = trim(
        optional_param(
            $name,
            '',
            PARAM_RAW_TRIMMED
        )
    );
    if ($raw === '') {
        return null;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        throw new \coding_exception(
            'Invalid date supplied for ' . $name . '.'
        );
    }

    return $timestamp;
};

if (
    data_submitted()
    && optional_param('save', 0, PARAM_BOOL)
) {
    require_sesskey();

    $now = time();
    $capacityraw = optional_param(
        'capacitytotal',
        '',
        PARAM_RAW_TRIMMED
    );
    $capacity = trim($capacityraw) === ''
        ? null
        : (int)$capacityraw;

    $courseid = required_param('courseid', PARAM_INT);
    if (
        $courseaccessrepository->mode_for_course($courseid)
        !== CommerceCourseAccessMode::PROMOTION
    ) {
        throw new \coding_exception(
            'A pedagogical promotion can only target a course configured in promotion mode.'
        );
    }

    $promotion = new CommercePedagogicalPromotion(
        $existing?->get_id(),
        strtolower(
            trim(
                required_param(
                    'promotionkey',
                    PARAM_ALPHANUMEXT
                )
            )
        ),
        trim(
            required_param(
                'name',
                PARAM_TEXT
            )
        ),
        $courseid,
        required_param(
            'status',
            PARAM_ALPHANUMEXT
        ),
        (bool)optional_param(
            'published',
            0,
            PARAM_BOOL
        ),
        $parseoptionaldate('salesopensat'),
        $parseoptionaldate('salesclosesat'),
        $parseoptionaldate('startsat'),
        $parseoptionaldate('endsat'),
        $capacity,
        $existing?->get_created_by()
            ?? (int)$USER->id,
        (int)$USER->id,
        $existing?->get_time_created()
            ?? $now,
        $now
    );

    $saved = $repository->save($promotion);

    redirect(
        new moodle_url(
            '/local/subscriptions/admin/commerce/education/promotions.php'
        ),
        get_string(
            'commerce_education_promotion_saved',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$promotioncourseids =
    $courseaccessrepository->promotion_course_ids();

if (
    $existing !== null
    && !in_array(
        $existing->get_course_id(),
        $promotioncourseids,
        true
    )
) {
    $promotioncourseids[] = $existing->get_course_id();
}

$courses = $promotioncourseids === []
    ? []
    : $DB->get_records_list(
        'course',
        'id',
        $promotioncourseids,
        'fullname ASC, id ASC',
        'id,fullname,shortname'
    );

$courseoptions = [];
foreach ($courses as $course) {
    $coursecontext = context_course::instance(
        (int)$course->id
    );
    $courseoptions[(int)$course->id] =
        format_string(
            (string)$course->fullname,
            true,
            ['context' => $coursecontext]
        );
}

$statusoptions = [];
foreach (
    CommercePedagogicalPromotionStatus::all()
    as $status
) {
    $statusoptions[$status] = get_string(
        'commerce_education_promotion_status_'
        . $status,
        'local_subscriptions'
    );
}

$datevalue = static function(
    ?int $timestamp
): string {
    return $timestamp !== null
        ? date('Y-m-d\TH:i', $timestamp)
        : '';
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'commerce_education_promotions_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/education/promotions.php'
        ),
    ],
    [
        'label' => $title,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_education_promotion_form_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::EDUCATION,
    $context
);

if ($existing !== null) {
    echo CommercePedagogicalPromotionAdminRenderer::render(
        $existing,
        CommercePedagogicalPromotionAdminRenderer::OVERVIEW,
        $DB
    );
}

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'commerce-ped-promotion-editor',
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

$rendersectionstart = static function(
    string $icon,
    string $title,
    string $description,
    string $extra = ''
): void {
    echo html_writer::start_div('commerce-ped-promotion-editor-section' . $extra);
    echo html_writer::start_div('commerce-ped-promotion-editor-section-header');
    echo html_writer::div(
        html_writer::tag(
            'i',
            '',
            ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']
        ),
        'commerce-ped-promotion-editor-section-icon'
    );
    echo html_writer::start_div('commerce-ped-promotion-editor-section-copy');
    echo html_writer::tag(
        'h3',
        $title,
        ['class' => 'commerce-ped-promotion-editor-section-title']
    );
    echo html_writer::div(
        $description,
        'commerce-ped-promotion-editor-section-description'
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::start_div('commerce-ped-promotion-editor-section-body');
};

$rendersectionend = static function(): void {
    echo html_writer::end_div();
    echo html_writer::end_div();
};

$renderfield = static function(
    string $id,
    string $label,
    string $html,
    ?string $help = null,
    string $class = ''
): void {
    echo html_writer::start_div('commerce-ped-promotion-field' . ($class !== '' ? ' ' . $class : ''));
    echo html_writer::tag(
        'label',
        $label,
        [
            'class' => 'form-label',
            'for' => $id,
        ]
    );
    echo $html;
    if ($help !== null && $help !== '') {
        echo html_writer::div($help, 'form-text');
    }
    echo html_writer::end_div();
};

echo html_writer::start_div('commerce-ped-promotion-editor-grid');

$rendersectionstart(
    'fa-id-card-o',
    get_string('commerce_education_m72_identity_title', 'local_subscriptions'),
    get_string('commerce_education_m72_identity_description', 'local_subscriptions')
);

echo html_writer::start_div('commerce-ped-promotion-fields-grid');
$renderfield(
    'ped-promo-name',
    get_string('commerce_education_promotion_name', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'text',
            'id' => 'ped-promo-name',
            'name' => 'name',
            'class' => 'form-control',
            'required' => 'required',
            'value' => $existing?->get_name() ?? '',
        ]
    ),
    get_string('commerce_education_m72_name_help', 'local_subscriptions'),
    'is-wide'
);
$renderfield(
    'ped-promo-key',
    get_string('commerce_education_promotion_key', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'text',
            'id' => 'ped-promo-key',
            'name' => 'promotionkey',
            'class' => 'form-control',
            'required' => 'required',
            'value' => $existing?->get_promotion_key() ?? '',
            'autocomplete' => 'off',
            'spellcheck' => 'false',
        ]
    ),
    get_string('commerce_education_m72_key_help', 'local_subscriptions')
);
$renderfield(
    'ped-promo-course',
    get_string('course'),
    html_writer::select(
        $courseoptions,
        'courseid',
        $existing?->get_course_id() ?? 0,
        ['' => get_string('choosedots')],
        [
            'class' => 'form-select',
            'id' => 'ped-promo-course',
            'required' => 'required',
        ]
    ),
    get_string('commerce_education_m72_course_help', 'local_subscriptions')
);
echo html_writer::end_div();
$rendersectionend();

$rendersectionstart(
    'fa-calendar',
    get_string('commerce_education_m72_cycle_title', 'local_subscriptions'),
    get_string('commerce_education_m72_cycle_description', 'local_subscriptions')
);

echo html_writer::start_div('commerce-ped-promotion-fields-grid');
$renderfield(
    'ped-promo-status',
    get_string('status'),
    html_writer::select(
        $statusoptions,
        'status',
        $existing?->get_status() ?? CommercePedagogicalPromotionStatus::DRAFT,
        false,
        [
            'class' => 'form-select',
            'id' => 'ped-promo-status',
        ]
    ),
    get_string('commerce_education_m72_status_help', 'local_subscriptions'),
    'is-wide'
);
$renderfield(
    'ped-promo-starts',
    get_string('commerce_education_starts_at', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'datetime-local',
            'id' => 'ped-promo-starts',
            'name' => 'startsat',
            'class' => 'form-control',
            'value' => $datevalue($existing?->get_starts_at()),
        ]
    ),
    get_string('commerce_education_m72_datetime_optional_help', 'local_subscriptions')
);
$renderfield(
    'ped-promo-ends',
    get_string('commerce_education_ends_at', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'datetime-local',
            'id' => 'ped-promo-ends',
            'name' => 'endsat',
            'class' => 'form-control',
            'value' => $datevalue($existing?->get_ends_at()),
        ]
    ),
    get_string('commerce_education_m72_datetime_optional_help', 'local_subscriptions')
);
echo html_writer::end_div();
$rendersectionend();

$rendersectionstart(
    'fa-shopping-cart',
    get_string('commerce_education_m72_sales_title', 'local_subscriptions'),
    get_string('commerce_education_m72_sales_description', 'local_subscriptions')
);

$publishedchecked = $existing?->is_published() ?? false;
echo html_writer::start_div('commerce-ped-promotion-publish-card');
echo html_writer::start_div('form-check form-switch');
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'checkbox',
        'name' => 'published',
        'value' => '1',
        'class' => 'form-check-input',
        'id' => 'ped-promo-published',
        'role' => 'switch',
        'checked' => $publishedchecked ? 'checked' : null,
    ]
);
echo html_writer::tag(
    'label',
    get_string('commerce_education_promotion_published', 'local_subscriptions'),
    [
        'class' => 'form-check-label commerce-ped-promotion-publish-label',
        'for' => 'ped-promo-published',
    ]
);
echo html_writer::end_div();
echo html_writer::div(
    get_string('commerce_education_m72_published_help', 'local_subscriptions'),
    'commerce-ped-promotion-publish-help'
);
echo html_writer::end_div();

if ($existing !== null) {
    $salesopen = $existing->sales_are_open(time());
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa ' . ($salesopen ? 'fa-check-circle' : 'fa-info-circle'),
            'aria-hidden' => 'true',
        ])
        . html_writer::span(
            $salesopen
                ? get_string('commerce_education_m72_sales_effective_open', 'local_subscriptions')
                : get_string('commerce_education_m72_sales_effective_closed', 'local_subscriptions')
        ),
        'commerce-ped-promotion-sales-state ' . ($salesopen ? 'is-open' : 'is-closed')
    );
}

echo html_writer::start_div('commerce-ped-promotion-fields-grid');
$renderfield(
    'ped-promo-sales-open',
    get_string('commerce_education_sales_opens_at', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'datetime-local',
            'id' => 'ped-promo-sales-open',
            'name' => 'salesopensat',
            'class' => 'form-control',
            'value' => $datevalue($existing?->get_sales_opens_at()),
        ]
    ),
    get_string('commerce_education_m72_sales_open_help', 'local_subscriptions')
);
$renderfield(
    'ped-promo-sales-close',
    get_string('commerce_education_sales_closes_at', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'datetime-local',
            'id' => 'ped-promo-sales-close',
            'name' => 'salesclosesat',
            'class' => 'form-control',
            'value' => $datevalue($existing?->get_sales_closes_at()),
        ]
    ),
    get_string('commerce_education_m72_sales_close_help', 'local_subscriptions')
);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-lightbulb-o', 'aria-hidden' => 'true'])
    . html_writer::span(
        get_string('commerce_education_m72_sales_rule_help', 'local_subscriptions')
    ),
    'commerce-ped-promotion-editor-note'
);
$rendersectionend();

$rendersectionstart(
    'fa-users',
    get_string('commerce_education_m72_capacity_title', 'local_subscriptions'),
    get_string('commerce_education_m72_capacity_description', 'local_subscriptions'),
    ' is-compact'
);
$renderfield(
    'ped-promo-capacity',
    get_string('commerce_education_capacity', 'local_subscriptions'),
    html_writer::empty_tag(
        'input',
        [
            'type' => 'number',
            'id' => 'ped-promo-capacity',
            'name' => 'capacitytotal',
            'min' => '1',
            'class' => 'form-control',
            'value' => $existing?->get_capacity_total() ?? '',
        ]
    ),
    get_string('commerce_education_capacity_help', 'local_subscriptions')
);
$rendersectionend();

echo html_writer::end_div();

echo html_writer::start_div('commerce-ped-promotion-editor-actions');
echo html_writer::div(
    html_writer::tag(
        'i',
        '',
        ['class' => 'fa fa-info-circle', 'aria-hidden' => 'true']
    )
    . html_writer::span(
        get_string('commerce_education_m72_save_hint', 'local_subscriptions')
    ),
    'commerce-ped-promotion-editor-actions-hint'
);
echo html_writer::start_div('commerce-ped-promotion-editor-actions-buttons');
echo html_writer::tag(
    'button',
    get_string('savechanges'),
    [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]
);
echo html_writer::link(
    new moodle_url(
        '/local/subscriptions/admin/commerce/education/promotions.php'
    ),
    get_string('cancel'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
