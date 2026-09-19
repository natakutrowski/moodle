<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarItem;
use local_subscriptions\commerce\education\calendar\CommercePedagogicalCalendarRepository;
use local_subscriptions\commerce\education\access\CommercePedagogicalAvailabilitySynchronizer;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $OUTPUT, $PAGE, $USER;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$promotionid = required_param('promotionid', PARAM_INT);

$promotionrepository = CommercePedagogicalPromotionRepository::create($DB);
$promotion = $promotionrepository->get_by_id($promotionid);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord', 'error');
}

$course = get_course($promotion->get_course_id());
$coursecontext = context_course::instance((int)$course->id);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/education/calendar.php',
    ['promotionid' => $promotionid]
);
$title = get_string('commerce_education_calendar_title', 'local_subscriptions', $promotion->get_name());

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-calendar-page'
);

$repository = CommercePedagogicalCalendarRepository::create($DB);

CommercePedagogicalAvailabilitySynchronizer::create($DB)
    ->sync_course((int)$course->id);

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

$update = optional_param('update', 0, PARAM_INT);
if ($update > 0) {
    require_sesskey();

    $existingitem = null;
    foreach ($repository->for_promotion($promotionid) as $candidate) {
        if ($candidate->get_id() === $update) {
            $existingitem = $candidate;
            break;
        }
    }

    if ($existingitem === null) {
        throw new \coding_exception('Unknown pedagogical calendar item.');
    }

    $unlockraw = trim(required_param('unlocksat', PARAM_RAW_TRIMMED));
    $unlocksat = strtotime($unlockraw);
    if ($unlocksat === false) {
        throw new \coding_exception('Invalid pedagogical calendar unlock date.');
    }

    $now = time();
    $repository->save(
        new CommercePedagogicalCalendarItem(
            $existingitem->get_id(),
            $existingitem->get_promotion_id(),
            $existingitem->get_item_type(),
            $existingitem->get_item_id(),
            $existingitem->get_position(),
            $unlocksat,
            $existingitem->get_created_by(),
            (int)$USER->id,
            $existingitem->get_time_created(),
            $now
        )
    );

    redirect(
        $pageurl,
        get_string('commerce_education_calendar_updated', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if (data_submitted() && optional_param('save', 0, PARAM_BOOL)) {
    require_sesskey();

    $sectionid = required_param('sectionid', PARAM_INT);
    $unlockraw = trim(required_param('unlocksat', PARAM_RAW_TRIMMED));
    $unlocksat = strtotime($unlockraw);
    if ($unlocksat === false) {
        throw new \coding_exception('Invalid pedagogical calendar unlock date.');
    }

    $now = time();
    $existingitems = $repository->for_promotion($promotionid);
    $position = $existingitems === []
        ? 0
        : max(array_map(
            static fn(CommercePedagogicalCalendarItem $item): int => $item->get_position(),
            $existingitems
        )) + 1;

    $repository->save(
        new CommercePedagogicalCalendarItem(
            null,
            $promotionid,
            CommercePedagogicalCalendarItem::TYPE_COURSE_SECTION,
            $sectionid,
            $position,
            $unlocksat,
            (int)$USER->id,
            (int)$USER->id,
            $now,
            $now
        )
    );

    redirect(
        $pageurl,
        get_string('commerce_education_calendar_item_saved', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$sections = $DB->get_records(
    'course_sections',
    ['course' => (int)$course->id],
    'section ASC',
    'id,course,section,name,visible'
);
$scheduled = $repository->for_promotion($promotionid);
$calendarperpage = 10;
$calendarpage = max(0, optional_param('calpage', 0, PARAM_INT));
$calendarcount = count($scheduled);
$calendarmaxpage = $calendarcount > 0 ? (int)floor(($calendarcount - 1) / $calendarperpage) : 0;
$calendarpage = min($calendarpage, $calendarmaxpage);
$calendaroffset = $calendarpage * $calendarperpage;
$pagedcalendar = array_slice($scheduled, $calendaroffset, $calendarperpage);
$calendarpagingurl = new moodle_url($pageurl, ['promotionid' => $promotionid]);
$scheduledbysection = [];
foreach ($scheduled as $item) {
    $scheduledbysection[$item->get_item_id()] = $item;
}

$sectionnames = [];
$sectionoptions = [];
$totalcoursesections = 0;
foreach ($sections as $section) {
    if ((int)$section->section === 0) {
        continue;
    }
    $totalcoursesections++;

    $name = trim((string)$section->name);
    if ($name === '') {
        $name = get_string(
            'commerce_education_calendar_section_fallback',
            'local_subscriptions',
            (int)$section->section
        );
    } else {
        $name = format_string($name, true, ['context' => $coursecontext]);
    }

    $sectionnames[(int)$section->id] = $name;
    if (!isset($scheduledbysection[(int)$section->id])) {
        $sectionoptions[(int)$section->id] = $name;
    }
}

$now = time();
$unlockedcount = 0;
$futurecount = 0;
$nextitem = null;
foreach ($scheduled as $item) {
    if ($item->is_unlocked_at($now)) {
        $unlockedcount++;
    } else {
        $futurecount++;
        if ($nextitem === null || $item->get_unlocks_at() < $nextitem->get_unlocks_at()) {
            $nextitem = $item;
        }
    }
}
$unscheduledcount = max(0, $totalcoursesections - count($scheduled));

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('commerce_education_promotions_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php'),
    ],
    [
        'label' => $promotion->get_name(),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotion_edit.php', ['id' => $promotionid]),
    ],
    ['label' => get_string('commerce_education_calendar', 'local_subscriptions'), 'url' => null],
]);

echo CrmPageHeader::render(
    $title,
    get_string('commerce_education_m73_calendar_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::EDUCATION,
    $context
);

echo CommercePedagogicalPromotionAdminRenderer::render(
    $promotion,
    CommercePedagogicalPromotionAdminRenderer::CALENDAR,
    $DB
);

// Calendar metrics.
echo html_writer::start_div('commerce-ped-m73-metrics');
$nextvalue = $nextitem !== null
    ? userdate($nextitem->get_unlocks_at(), get_string('strftimedatetimeshort', 'langconfig'))
    : get_string('commerce_education_m73_calendar_no_next', 'local_subscriptions');
foreach ([
    ['fa-list-ol', get_string('commerce_education_m73_calendar_metric_scheduled', 'local_subscriptions'), count($scheduled)],
    ['fa-unlock', get_string('commerce_education_m73_calendar_metric_open', 'local_subscriptions'), $unlockedcount],
    ['fa-lock', get_string('commerce_education_m73_calendar_metric_future', 'local_subscriptions'), $futurecount],
    ['fa-step-forward', get_string('commerce_education_m73_calendar_metric_next', 'local_subscriptions'), $nextvalue],
] as [$icon, $label, $value]) {
    echo html_writer::start_div('commerce-ped-m73-metric');
    echo html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']);
    echo html_writer::start_div();
    echo html_writer::div((string)$value, 'commerce-ped-m73-metric-value');
    echo html_writer::div((string)$label, 'commerce-ped-m73-metric-label');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('commerce-ped-m73-layout');

// Add milestone side panel.
echo html_writer::start_div('commerce-ped-m73-side');
echo html_writer::start_div('commerce-ped-m73-panel');
echo html_writer::start_div('commerce-ped-m73-panel-header');
echo html_writer::tag('i', '', ['class' => 'fa fa-calendar-plus-o commerce-ped-m73-panel-icon', 'aria-hidden' => 'true']);
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_calendar_add_item', 'local_subscriptions'), ['class' => 'commerce-ped-m73-panel-title']);
echo html_writer::div(get_string('commerce_education_m73_calendar_add_help', 'local_subscriptions'), 'commerce-ped-m73-panel-description');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('commerce-ped-m73-panel-body');

if ($sectionoptions === []) {
    echo html_writer::div(
        get_string('commerce_education_m73_calendar_all_scheduled', 'local_subscriptions'),
        'commerce-ped-m73-empty'
    );
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'commerce-ped-m73-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'promotionid', 'value' => $promotionid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'save', 'value' => '1']);

    echo html_writer::start_div('commerce-ped-m73-field');
    echo html_writer::tag('label', get_string('commerce_education_calendar_item', 'local_subscriptions'), [
        'class' => 'form-label',
        'for' => 'ped-calendar-section',
    ]);
    echo html_writer::select(
        $sectionoptions,
        'sectionid',
        '',
        ['' => get_string('choosedots')],
        ['id' => 'ped-calendar-section', 'class' => 'form-select', 'required' => 'required']
    );
    echo html_writer::end_div();

    echo html_writer::start_div('commerce-ped-m73-field');
    echo html_writer::tag('label', get_string('commerce_education_calendar_unlocks_at', 'local_subscriptions'), [
        'class' => 'form-label',
        'for' => 'ped-calendar-unlock',
    ]);
    echo html_writer::empty_tag('input', [
        'id' => 'ped-calendar-unlock',
        'type' => 'datetime-local',
        'name' => 'unlocksat',
        'class' => 'form-control',
        'required' => 'required',
    ]);
    echo html_writer::end_div();

    echo html_writer::tag('button', get_string('commerce_education_m73_calendar_add_button', 'local_subscriptions'), [
        'type' => 'submit',
        'class' => 'btn btn-primary w-100',
    ]);
    echo html_writer::end_tag('form');
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('commerce-ped-m73-calendar-coverage');
echo html_writer::start_div('commerce-ped-m73-calendar-coverage-head');
echo html_writer::span(get_string('commerce_education_m73_calendar_coverage', 'local_subscriptions'), 'fw-semibold');
echo html_writer::span(
    get_string('commerce_education_m73_calendar_coverage_value', 'local_subscriptions', (object)[
        'scheduled' => count($scheduled),
        'total' => $totalcoursesections,
    ]),
    'text-muted small'
);
echo html_writer::end_div();
$coverage = $totalcoursesections > 0
    ? min(100, (int)round((count($scheduled) / $totalcoursesections) * 100))
    : 0;
echo html_writer::div(
    html_writer::div('', 'commerce-ped-m73-calendar-coverage-bar', ['style' => 'width: ' . $coverage . '%']),
    'commerce-ped-m73-calendar-coverage-track'
);
echo html_writer::div(
    $unscheduledcount > 0
        ? get_string('commerce_education_m73_calendar_unscheduled', 'local_subscriptions', $unscheduledcount)
        : get_string('commerce_education_m73_calendar_complete', 'local_subscriptions'),
    'commerce-ped-m73-calendar-coverage-note'
);
echo html_writer::end_div();

echo html_writer::end_div();

// Timeline.
echo html_writer::start_div('commerce-ped-m73-main');
echo html_writer::start_div('commerce-ped-m73-section-heading');
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_calendar_current', 'local_subscriptions'), ['class' => 'commerce-ped-m73-section-title']);
echo html_writer::div(get_string('commerce_education_m73_calendar_current_help', 'local_subscriptions'), 'commerce-ped-m73-section-description');
echo html_writer::end_div();
echo html_writer::span((string)count($scheduled), 'commerce-ped-m73-count');
echo html_writer::end_div();

if ($scheduled === []) {
    echo html_writer::div(get_string('commerce_education_calendar_empty', 'local_subscriptions'), 'commerce-ped-m73-empty is-large');
} else {
    echo html_writer::start_div('commerce-ped-calendar-timeline');

    foreach ($pagedcalendar as $index => $item) {
        $sectionname = $sectionnames[$item->get_item_id()] ?? '#' . $item->get_item_id();
        $isopen = $item->is_unlocked_at($now);
        $isnext = $nextitem !== null && $item->get_id() === $nextitem->get_id();
        $deleteurl = new moodle_url($pageurl, [
            'delete' => $item->get_id(),
            'sesskey' => sesskey(),
        ]);

        $itemclasses = 'commerce-ped-calendar-item ' . ($isopen ? 'is-open' : 'is-future');
        if ($isnext) {
            $itemclasses .= ' is-next';
        }

        echo html_writer::start_div($itemclasses);
        echo html_writer::div((string)($calendaroffset + $index + 1), 'commerce-ped-calendar-step');
        echo html_writer::start_div('commerce-ped-calendar-card');

        echo html_writer::start_div('commerce-ped-calendar-card-header');
        echo html_writer::start_div('commerce-ped-calendar-card-title-wrap');
        echo html_writer::tag('h4', $sectionname, ['class' => 'commerce-ped-calendar-card-title']);
        $badges = html_writer::span(
            $isopen
                ? get_string('commerce_education_m73_calendar_open', 'local_subscriptions')
                : get_string('commerce_education_m73_calendar_future', 'local_subscriptions'),
            'commerce-ped-calendar-state ' . ($isopen ? 'is-open' : 'is-future')
        );
        if ($isnext) {
            $badges .= html_writer::span(
                get_string('commerce_education_m73_calendar_next', 'local_subscriptions'),
                'commerce-ped-calendar-state is-next'
            );
        }
        echo html_writer::div($badges, 'commerce-ped-calendar-card-badges');
        echo html_writer::end_div();
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-clock-o', 'aria-hidden' => 'true'])
                . html_writer::span(userdate($item->get_unlocks_at())),
            'commerce-ped-calendar-date'
        );
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-calendar-card-actions');
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'commerce-ped-calendar-edit-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'update', 'value' => (int)$item->get_id()]);
        echo html_writer::tag('label', get_string('commerce_education_m73_calendar_change_date', 'local_subscriptions'), [
            'class' => 'visually-hidden',
            'for' => 'ped-calendar-date-' . $item->get_id(),
        ]);
        echo html_writer::empty_tag('input', [
            'id' => 'ped-calendar-date-' . $item->get_id(),
            'type' => 'datetime-local',
            'name' => 'unlocksat',
            'class' => 'form-control form-control-sm',
            'value' => userdate($item->get_unlocks_at(), '%Y-%m-%dT%H:%M'),
            'required' => 'required',
        ]);
        echo html_writer::tag('button', get_string('savechanges'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-outline-primary',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::link(
            $deleteurl,
            html_writer::tag('i', '', ['class' => 'fa fa-trash', 'aria-hidden' => 'true'])
                . ' ' . get_string('delete'),
            ['class' => 'btn btn-sm btn-outline-danger']
        );
        echo html_writer::end_div();

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
    if ($calendarcount > $calendarperpage) {
        echo html_writer::div(
            $OUTPUT->paging_bar(
                $calendarcount,
                $calendarpage,
                $calendarperpage,
                $calendarpagingurl,
                'calpage'
            ),
            'commerce-ped-m75-pagination'
        );
    }
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
