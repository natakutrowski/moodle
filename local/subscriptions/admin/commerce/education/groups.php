<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOfferService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalGroupLeaderboardService;
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

$promotion = CommercePedagogicalPromotionRepository::create($DB)->get_by_id($promotionid);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord', 'error');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/education/groups.php', [
    'promotionid' => $promotionid,
]);
$title = get_string('commerce_education_groups_title', 'local_subscriptions', $promotion->get_name());

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-groups-page'
);

$repository = CommercePedagogicalGroupRepository::create($DB);
$orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
$participations = CommercePedagogicalParticipationRepository::create($DB);

if (data_submitted()) {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);

    if ($action === 'config') {
        $now = time();
        $current = $repository->get_configuration($promotionid);
        $repository->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                $promotionid,
                optional_param('enabled', 0, PARAM_BOOL) === 1,
                max(1, required_param('groupsize', PARAM_INT)),
                $current->get_created_by() ?? (int)$USER->id,
                (int)$USER->id,
                $current->get_time_created() ?: $now,
                $now
            )
        );
    } else if ($action === 'create') {
        $existinggroups = $repository->groups_for_promotion($promotionid);
        $position = $existinggroups === []
            ? 0
            : max(array_map(
                static fn($group): int => $group->get_position(),
                $existinggroups
            )) + 1;

        $supportlang = optional_param('supportlang', '', PARAM_ALPHANUMEXT);
        if ($supportlang !== '' && !in_array($supportlang, ['fr', 'ru', 'en'], true)) {
            throw new \coding_exception('Unsupported pedagogical group support language.');
        }

        $orchestrator->create_group(
            $promotionid,
            required_param('displayname', PARAM_TEXT),
            $position,
            null,
            $supportlang !== '' ? $supportlang : null,
            optional_param('telegramref', '', PARAM_URL) ?: null,
            null,
            (int)$USER->id,
            time(),
            optional_param('tutorname', '', PARAM_TEXT) ?: null,
            required_param('productid', PARAM_INT)
        );
    } else if ($action === 'edit') {
        $supportlang = optional_param('supportlang', '', PARAM_ALPHANUMEXT);
        if ($supportlang !== '' && !in_array($supportlang, ['fr', 'ru', 'en'], true)) {
            throw new \coding_exception('Unsupported pedagogical group support language.');
        }

        $orchestrator->update_group(
            $promotionid,
            required_param('groupid', PARAM_INT),
            required_param('displayname', PARAM_TEXT),
            optional_param('tutorname', '', PARAM_TEXT) ?: null,
            $supportlang !== '' ? $supportlang : null,
            optional_param('telegramref', '', PARAM_URL) ?: null,
            (int)$USER->id,
            time()
        );
    } else if ($action === 'bind') {
        CommercePedagogicalGroupOfferService::create($DB)->bind(
            $promotionid,
            required_param('groupid', PARAM_INT),
            required_param('productid', PARAM_INT),
            (int)$USER->id,
            time()
        );
    } else if ($action === 'backfill') {
        $configuration = $repository->get_configuration($promotionid);
        if (!$configuration->is_enabled()) {
            redirect(
                $pageurl,
                get_string('commerce_education_m74_backfill_requires_enabled', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }

        $assigned = 0;
        $pending = 0;
        $seen = [];
        $joins = $DB->get_records(
            'local_subs_commerce_ped_join',
            ['promotionid' => $promotionid, 'state' => 'active'],
            'id ASC',
            'id,userid,productid'
        );
        foreach ($joins as $join) {
            $userid = (int)$join->userid;
            if (isset($seen[$userid])) {
                continue;
            }
            $seen[$userid] = true;
            if ($repository->group_for_user($promotionid, $userid) !== null) {
                continue;
            }

            $productid = $participations->active_product_id_for_user(
                $promotionid,
                $userid
            );
            if ($productid === null) {
                $pending++;
                continue;
            }

            $group = $orchestrator->assign_first_available_for_product(
                $promotionid,
                $productid,
                $userid,
                (int)$USER->id,
                time()
            );
            if ($group !== null) {
                $assigned++;
            } else {
                $pending++;
            }
        }

        redirect(
            $pageurl,
            get_string('commerce_education_m74_backfill_result', 'local_subscriptions', (object)[
                'assigned' => $assigned,
                'pending' => $pending,
            ]),
            null,
            $pending > 0
                ? \core\output\notification::NOTIFY_WARNING
                : \core\output\notification::NOTIFY_SUCCESS
        );
    }

    redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$configuration = $repository->get_configuration($promotionid);
$groups = $repository->groups_for_promotion($promotionid);
$groupperpage = 12;
$grouppage = max(0, optional_param('gpage', 0, PARAM_INT));
$groupcount = count($groups);
$groupmaxpage = $groupcount > 0 ? (int)floor(($groupcount - 1) / $groupperpage) : 0;
$grouppage = min($grouppage, $groupmaxpage);
$groupoffset = $grouppage * $groupperpage;
$pagedgroups = array_slice($groups, $groupoffset, $groupperpage);
$grouppagingurl = new moodle_url($pageurl, ['promotionid' => $promotionid]);
$activegroups = array_values(array_filter(
    $groups,
    static fn($group): bool => $group->is_active()
));
$offerlinks = CommercePedagogicalPromotionOfferRepository::create($DB)
    ->links_for_promotion($promotionid);

$offeroptions = [];
$offernames = [];
foreach ($offerlinks as $offerlink) {
    $label = (string)$offerlink->productname . ' [' . (string)$offerlink->sku . ']';
    $offeroptions[(int)$offerlink->productid] = $label;
    $offernames[(int)$offerlink->productid] = $label;
}

$leaderboardbygroup = [];
foreach (CommercePedagogicalGroupLeaderboardService::create($DB)->get_ranking($promotionid) as $entry) {
    $leaderboardbygroup[$entry->get_group_id()] = $entry;
}

$activejoinrecords = $DB->get_records(
    'local_subs_commerce_ped_join',
    ['promotionid' => $promotionid, 'state' => 'active'],
    '',
    'id,userid'
);
$activeuserids = [];
foreach ($activejoinrecords as $join) {
    $activeuserids[(int)$join->userid] = true;
}
$activeparticipants = count($activeuserids);

$assigneduserids = [];
$occupied = 0;
foreach ($activegroups as $group) {
    $groupid = (int)$group->get_id();
    $occupied += $repository->member_count($groupid);
    foreach ($repository->member_user_ids($groupid) as $userid) {
        if (isset($activeuserids[$userid])) {
            $assigneduserids[$userid] = true;
        }
    }
}
$unassigned = max(0, $activeparticipants - count($assigneduserids));
$totalseats = count($activegroups) * $configuration->get_group_size();

$languageLabel = static function(?string $lang): string {
    return match ($lang) {
        'fr' => get_string('commerce_education_group_support_language_fr', 'local_subscriptions'),
        'ru' => get_string('commerce_education_group_support_language_ru', 'local_subscriptions'),
        'en' => get_string('commerce_education_group_support_language_en', 'local_subscriptions'),
        default => get_string('commerce_education_group_support_language_none', 'local_subscriptions'),
    };
};

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
    ['label' => get_string('commerce_education_groups', 'local_subscriptions'), 'url' => null],
]);

echo CrmPageHeader::render(
    $title,
    get_string('commerce_education_m74_groups_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);

echo CommercePedagogicalPromotionAdminRenderer::render(
    $promotion,
    CommercePedagogicalPromotionAdminRenderer::GROUPS,
    $DB
);

// Operational summary.
echo html_writer::start_div('commerce-ped-m74-metrics');
$metrics = [
    [
        'fa-random',
        get_string('commerce_education_m74_metric_assignment', 'local_subscriptions'),
        $configuration->is_enabled()
            ? get_string('commerce_education_m74_enabled', 'local_subscriptions')
            : get_string('commerce_education_m74_disabled', 'local_subscriptions'),
        $configuration->is_enabled() ? 'is-good' : 'is-muted',
    ],
    [
        'fa-users',
        get_string('commerce_education_m74_metric_groups', 'local_subscriptions'),
        (string)count($activegroups),
        '',
    ],
    [
        'fa-th-large',
        get_string('commerce_education_m74_metric_seats', 'local_subscriptions'),
        $totalseats > 0 ? $occupied . ' / ' . $totalseats : '—',
        '',
    ],
    [
        'fa-user-times',
        get_string('commerce_education_m74_metric_unassigned', 'local_subscriptions'),
        (string)$unassigned,
        $unassigned > 0 ? 'is-warning' : 'is-good',
    ],
];
foreach ($metrics as [$icon, $label, $value, $state]) {
    echo html_writer::start_div('commerce-ped-m74-metric ' . $state);
    echo html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']);
    echo html_writer::start_div();
    echo html_writer::div($value, 'commerce-ped-m74-metric-value');
    echo html_writer::div($label, 'commerce-ped-m74-metric-label');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

if (!$configuration->is_enabled() && $activegroups !== []) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle', 'aria-hidden' => 'true'])
        . html_writer::span(get_string('commerce_education_m74_groups_disabled_warning', 'local_subscriptions')),
        'commerce-ped-m74-callout is-warning'
    );
} else if ($configuration->is_enabled() && $unassigned > 0) {
    $backfill = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'commerce-ped-m74-backfill-form',
    ])
        . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()])
        . html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'backfill'])
        . html_writer::tag(
            'button',
            get_string('commerce_education_m74_backfill_button', 'local_subscriptions'),
            ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']
        )
        . html_writer::end_tag('form');
    echo html_writer::div(
        html_writer::start_div('commerce-ped-m74-callout-copy')
        . html_writer::tag('i', '', ['class' => 'fa fa-user-plus', 'aria-hidden' => 'true'])
        . html_writer::span(get_string('commerce_education_m74_unassigned_warning', 'local_subscriptions', $unassigned))
        . html_writer::end_div()
        . $backfill,
        'commerce-ped-m74-callout is-info'
    );
}

echo html_writer::start_div('commerce-ped-m74-layout');

// Configuration + creation rail.
echo html_writer::start_div('commerce-ped-m74-side');

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'commerce-ped-m74-panel',
]);
echo html_writer::start_div('commerce-ped-m74-panel-header');
echo html_writer::tag('i', '', ['class' => 'fa fa-sliders commerce-ped-m74-panel-icon', 'aria-hidden' => 'true']);
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_groups_settings', 'local_subscriptions'), ['class' => 'commerce-ped-m74-panel-title']);
echo html_writer::div(get_string('commerce_education_m74_settings_help', 'local_subscriptions'), 'commerce-ped-m74-panel-description');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('commerce-ped-m74-panel-body');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'config']);
echo html_writer::div(
    html_writer::checkbox(
        'enabled',
        '1',
        $configuration->is_enabled(),
        html_writer::span(get_string('commerce_education_groups_enabled', 'local_subscriptions'), 'ms-2')
    ),
    'commerce-ped-m74-toggle'
);
echo html_writer::start_div('commerce-ped-m74-field');
echo html_writer::tag('label', get_string('commerce_education_group_size', 'local_subscriptions'), [
    'class' => 'form-label',
    'for' => 'ped-group-size',
]);
echo html_writer::empty_tag('input', [
    'id' => 'ped-group-size',
    'type' => 'number',
    'name' => 'groupsize',
    'min' => '1',
    'value' => $configuration->get_group_size(),
    'class' => 'form-control',
]);
echo html_writer::div(get_string('commerce_education_m74_group_size_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();
echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary w-100']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::start_div('commerce-ped-m74-panel');
echo html_writer::start_div('commerce-ped-m74-panel-header');
echo html_writer::tag('i', '', ['class' => 'fa fa-plus-circle commerce-ped-m74-panel-icon', 'aria-hidden' => 'true']);
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_group_create', 'local_subscriptions'), ['class' => 'commerce-ped-m74-panel-title']);
echo html_writer::div(get_string('commerce_education_m74_create_help', 'local_subscriptions'), 'commerce-ped-m74-panel-description');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('commerce-ped-m74-panel-body');

if ($offeroptions === []) {
    echo html_writer::div(get_string('commerce_education_m74_no_offers_for_groups', 'local_subscriptions'), 'commerce-ped-m74-empty');
} else {
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'commerce-ped-m74-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'create']);

    echo html_writer::start_div('commerce-ped-m74-field');
    echo html_writer::tag('label', get_string('commerce_education_group_offer', 'local_subscriptions'), ['class' => 'form-label', 'for' => 'ped-group-product']);
    echo html_writer::select($offeroptions, 'productid', '', ['' => get_string('choosedots')], [
        'id' => 'ped-group-product', 'class' => 'form-select', 'required' => 'required',
    ]);
    echo html_writer::div(get_string('commerce_education_group_offer_help', 'local_subscriptions'), 'form-text');
    echo html_writer::end_div();

    echo html_writer::start_div('commerce-ped-m74-field');
    echo html_writer::tag('label', get_string('commerce_education_group_visible_name', 'local_subscriptions'), ['class' => 'form-label', 'for' => 'ped-group-name']);
    echo html_writer::empty_tag('input', ['id' => 'ped-group-name', 'type' => 'text', 'name' => 'displayname', 'class' => 'form-control', 'required' => 'required']);
    echo html_writer::end_div();

    echo html_writer::start_div('commerce-ped-m74-field');
    echo html_writer::tag('label', get_string('commerce_education_group_tutor_name', 'local_subscriptions'), ['class' => 'form-label', 'for' => 'ped-group-tutor']);
    echo html_writer::empty_tag('input', ['id' => 'ped-group-tutor', 'type' => 'text', 'name' => 'tutorname', 'class' => 'form-control']);
    echo html_writer::div(get_string('commerce_education_group_tutor_name_help', 'local_subscriptions'), 'form-text');
    echo html_writer::end_div();

    echo html_writer::start_div('commerce-ped-m74-field');
    echo html_writer::tag('label', get_string('commerce_education_group_support_language', 'local_subscriptions'), ['class' => 'form-label', 'for' => 'ped-group-language']);
    echo html_writer::select([
        '' => get_string('commerce_education_group_support_language_none', 'local_subscriptions'),
        'fr' => get_string('commerce_education_group_support_language_fr', 'local_subscriptions'),
        'ru' => get_string('commerce_education_group_support_language_ru', 'local_subscriptions'),
        'en' => get_string('commerce_education_group_support_language_en', 'local_subscriptions'),
    ], 'supportlang', '', false, ['id' => 'ped-group-language', 'class' => 'form-select']);
    echo html_writer::end_div();

    echo html_writer::start_div('commerce-ped-m74-field');
    echo html_writer::tag('label', get_string('commerce_education_group_telegram_link', 'local_subscriptions'), ['class' => 'form-label', 'for' => 'ped-group-telegram']);
    echo html_writer::empty_tag('input', [
        'id' => 'ped-group-telegram', 'type' => 'url', 'name' => 'telegramref', 'class' => 'form-control', 'placeholder' => 'https://t.me/…',
    ]);
    echo html_writer::end_div();

    echo html_writer::tag('button', get_string('commerce_education_m74_create_group_button', 'local_subscriptions'), [
        'type' => 'submit', 'class' => 'btn btn-primary w-100',
    ]);
    echo html_writer::end_tag('form');
}

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Existing groups.
echo html_writer::start_div('commerce-ped-m74-main');
echo html_writer::start_div('commerce-ped-m74-section-heading');
echo html_writer::start_div();
echo html_writer::tag('h3', get_string('commerce_education_groups_current', 'local_subscriptions'), ['class' => 'commerce-ped-m74-section-title']);
echo html_writer::div(get_string('commerce_education_m74_groups_current_help', 'local_subscriptions'), 'commerce-ped-m74-section-description');
echo html_writer::end_div();
echo html_writer::span((string)count($groups), 'commerce-ped-m74-count');
echo html_writer::end_div();

if ($groups === []) {
    echo html_writer::div(get_string('commerce_education_groups_empty', 'local_subscriptions'), 'commerce-ped-m74-empty is-large');
} else {
    echo html_writer::start_div('commerce-ped-m74-group-grid');
    foreach ($pagedgroups as $group) {
        $groupid = (int)$group->get_id();
        $membercount = $repository->member_count($groupid);
        $groupsize = $configuration->get_group_size();
        $occupancy = $groupsize > 0 ? min(100, (int)round(($membercount / $groupsize) * 100)) : 0;
        $isfull = $membercount >= $groupsize;
        $entry = $leaderboardbygroup[$groupid] ?? null;
        $productid = $group->get_product_id();
        $offername = $productid !== null && isset($offernames[$productid])
            ? $offernames[$productid]
            : get_string('commerce_education_m74_offer_unbound', 'local_subscriptions');

        echo html_writer::start_div('commerce-ped-m74-group-card' . ($group->is_active() ? '' : ' is-inactive'));
        echo html_writer::start_div('commerce-ped-m74-group-card-head');
        echo html_writer::start_div('commerce-ped-m74-group-title-wrap');
        echo html_writer::tag('h4', s($group->get_display_name()), ['class' => 'commerce-ped-m74-group-title']);
        echo html_writer::div(s($offername), 'commerce-ped-m74-group-offer');
        echo html_writer::end_div();
        echo html_writer::span(
            $group->is_active()
                ? get_string('commerce_education_m74_group_active', 'local_subscriptions')
                : get_string('commerce_education_m74_group_inactive', 'local_subscriptions'),
            'commerce-ped-m74-state ' . ($group->is_active() ? 'is-active' : 'is-inactive')
        );
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-m74-occupancy');
        echo html_writer::start_div('commerce-ped-m74-occupancy-head');
        echo html_writer::span(get_string('commerce_education_group_members', 'local_subscriptions'), 'fw-semibold');
        echo html_writer::span($membercount . ' / ' . $groupsize, 'commerce-ped-m74-occupancy-value' . ($isfull ? ' is-full' : ''));
        echo html_writer::end_div();
        echo html_writer::div(
            html_writer::div('', 'commerce-ped-m74-occupancy-bar' . ($isfull ? ' is-full' : ''), ['style' => 'width: ' . $occupancy . '%']),
            'commerce-ped-m74-occupancy-track'
        );
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-m74-group-meta');
        $groupmeta = [
            ['fa-user', get_string('commerce_education_group_tutor_name', 'local_subscriptions'), $group->get_tutor_name() ?? '—'],
            ['fa-language', get_string('commerce_education_group_support_language', 'local_subscriptions'), $languageLabel($group->get_support_language())],
            ['fa-trophy', get_string('commerce_education_m74_group_score', 'local_subscriptions'), $entry !== null ? (string)$entry->get_points() : '0'],
            ['fa-list-ol', get_string('commerce_education_m74_group_rank', 'local_subscriptions'), $entry !== null ? '#' . $entry->get_rank() : '—'],
        ];
        foreach ($groupmeta as [$icon, $label, $value]) {
            echo html_writer::start_div('commerce-ped-m74-group-meta-item');
            echo html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']);
            echo html_writer::start_div();
            echo html_writer::div($label, 'commerce-ped-m74-group-meta-label');
            echo html_writer::div(s((string)$value), 'commerce-ped-m74-group-meta-value');
            echo html_writer::end_div();
            echo html_writer::end_div();
        }

        echo html_writer::start_div('commerce-ped-m74-group-meta-item is-wide');
        echo html_writer::tag('i', '', ['class' => 'fa fa-paper-plane', 'aria-hidden' => 'true']);
        echo html_writer::start_div('commerce-ped-m74-group-meta-copy');
        echo html_writer::div(
            get_string('commerce_education_group_telegram_link', 'local_subscriptions'),
            'commerce-ped-m74-group-meta-label'
        );
        if ($group->get_telegram_reference() !== null) {
            $telegramref = $group->get_telegram_reference();
            echo html_writer::link(
                new moodle_url($telegramref),
                s($telegramref),
                [
                    'class' => 'commerce-ped-m74-group-meta-value commerce-ped-m74-telegram-url',
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer',
                    'title' => $telegramref,
                ]
            );
        } else {
            echo html_writer::div('—', 'commerce-ped-m74-group-meta-value');
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-m74-group-actions');

        echo html_writer::start_tag('details', ['class' => 'commerce-ped-m741-group-edit']);
        echo html_writer::tag(
            'summary',
            html_writer::tag('i', '', ['class' => 'fa fa-pencil', 'aria-hidden' => 'true'])
                . html_writer::span(get_string('edit')),
            ['class' => 'commerce-ped-m741-group-edit-summary']
        );
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'commerce-ped-m741-group-edit-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'edit']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'groupid', 'value' => $groupid]);

        echo html_writer::start_div('commerce-ped-m741-group-edit-grid');
        $nameid = 'group-name-' . $groupid;
        echo html_writer::start_div('commerce-ped-m74-field');
        echo html_writer::tag('label', get_string('commerce_education_group_visible_name', 'local_subscriptions'), [
            'class' => 'form-label', 'for' => $nameid,
        ]);
        echo html_writer::empty_tag('input', [
            'id' => $nameid,
            'type' => 'text',
            'name' => 'displayname',
            'class' => 'form-control form-control-sm',
            'value' => $group->get_display_name(),
            'required' => 'required',
        ]);
        echo html_writer::end_div();

        $tutorid = 'group-tutor-' . $groupid;
        echo html_writer::start_div('commerce-ped-m74-field');
        echo html_writer::tag('label', get_string('commerce_education_group_tutor_name', 'local_subscriptions'), [
            'class' => 'form-label', 'for' => $tutorid,
        ]);
        echo html_writer::empty_tag('input', [
            'id' => $tutorid,
            'type' => 'text',
            'name' => 'tutorname',
            'class' => 'form-control form-control-sm',
            'value' => $group->get_tutor_name() ?? '',
        ]);
        echo html_writer::end_div();

        $langid = 'group-lang-' . $groupid;
        echo html_writer::start_div('commerce-ped-m74-field');
        echo html_writer::tag('label', get_string('commerce_education_group_support_language', 'local_subscriptions'), [
            'class' => 'form-label', 'for' => $langid,
        ]);
        echo html_writer::select([
            '' => get_string('commerce_education_group_support_language_none', 'local_subscriptions'),
            'fr' => get_string('commerce_education_group_support_language_fr', 'local_subscriptions'),
            'ru' => get_string('commerce_education_group_support_language_ru', 'local_subscriptions'),
            'en' => get_string('commerce_education_group_support_language_en', 'local_subscriptions'),
        ], 'supportlang', $group->get_support_language() ?? '', false, [
            'id' => $langid,
            'class' => 'form-select form-select-sm',
        ]);
        echo html_writer::end_div();

        $telegramid = 'group-telegram-' . $groupid;
        echo html_writer::start_div('commerce-ped-m74-field');
        echo html_writer::tag('label', get_string('commerce_education_group_telegram_link', 'local_subscriptions'), [
            'class' => 'form-label', 'for' => $telegramid,
        ]);
        echo html_writer::empty_tag('input', [
            'id' => $telegramid,
            'type' => 'url',
            'name' => 'telegramref',
            'class' => 'form-control form-control-sm',
            'value' => $group->get_telegram_reference() ?? '',
            'placeholder' => 'https://t.me/…',
        ]);
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::tag('button', get_string('savechanges'), [
            'type' => 'submit',
            'class' => 'btn btn-sm btn-primary',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('details');

        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'commerce-ped-m74-bind-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'bind']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'groupid', 'value' => $groupid]);
        echo html_writer::tag('label', get_string('commerce_education_group_offer', 'local_subscriptions'), [
            'class' => 'form-label', 'for' => 'group-offer-' . $groupid,
        ]);
        echo html_writer::start_div('commerce-ped-m74-bind-row');
        echo html_writer::select($offeroptions, 'productid', $group->get_product_id() ?? '', ['' => get_string('choosedots')], [
            'id' => 'group-offer-' . $groupid,
            'class' => 'form-select form-select-sm',
            'required' => 'required',
        ]);
        echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']);
        echo html_writer::end_div();
        echo html_writer::end_tag('form');

        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
    if ($groupcount > $groupperpage) {
        echo html_writer::div(
            $OUTPUT->paging_bar(
                $groupcount,
                $grouppage,
                $groupperpage,
                $grouppagingurl,
                'gpage'
            ),
            'commerce-ped-m75-pagination'
        );
    }
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
