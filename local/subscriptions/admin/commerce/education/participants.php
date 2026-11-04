<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\education\xp\CommercePedagogicalPointsPresenter;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantMoveException;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalCrmRanking;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpAdjustmentService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpBonusHistoryRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $OUTPUT, $PAGE, $USER;

$context = AdminSecurity::require(Capabilities::VIEW_PROMOTIONS);
$canmanage = has_capability(Capabilities::MANAGE_PROMOTIONS, $context);
$promotionid = required_param('promotionid', PARAM_INT);
$promotion = CommercePedagogicalPromotionRepository::create($DB)->get_by_id($promotionid);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord', 'error');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/education/participants.php', [
    'promotionid' => $promotionid,
]);
$title = get_string('commerce_education_participants_title', 'local_subscriptions', $promotion->get_name());

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-education-participants-page'
);

if (data_submitted()) {
    require_capability(Capabilities::MANAGE_PROMOTIONS, $context);
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    $userid = required_param('userid', PARAM_INT);
    $service = CommercePedagogicalParticipantService::create($DB);

    if ($action === 'xpcancel') {
        require_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new \coding_exception('Manual XP cancellation requires POST.');
        }
        CommercePedagogicalXpAdjustmentService::create($DB)->cancel(
            $promotionid, CommercePedagogicalXpAdjustmentService::USER, $userid,
            required_param('adjustmentid', PARAM_INT), (int)$USER->id
        );
        redirect($pageurl, get_string('commerce_education_xp_manual_cancel_success', 'local_subscriptions'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'xpbonus') {
        require_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new \coding_exception('Manual XP grants require POST.');
        }
        CommercePedagogicalXpAdjustmentService::create($DB)->award(
            $promotionid, CommercePedagogicalXpAdjustmentService::USER, $userid,
            required_param('points', PARAM_INT),
            required_param('reason', PARAM_TEXT), (int)$USER->id,
            required_param('requestkey', PARAM_ALPHANUMEXT)
        );
        redirect($pageurl, get_string('commerce_education_xp_manual_added', 'local_subscriptions'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'profile') {
        $service->set_profile(
            $promotionid,
            $userid,
            required_param('profile', PARAM_ALPHANUMEXT),
            (int)$USER->id,
            time()
        );
    } else if ($action === 'group') {
        try {
            $service->move_group(
                $promotionid,
                $userid,
                required_param('groupid', PARAM_INT),
                (int)$USER->id,
                time()
            );
        } catch (CommercePedagogicalParticipantMoveException $exception) {
            if ($exception->get_code_key() === CommercePedagogicalParticipantMoveException::GROUP_FULL) {
                redirect(
                    $pageurl,
                    get_string('commerce_education_participant_group_full', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
            throw $exception;
        }
    }

    redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$participants = CommercePedagogicalParticipantRepository::create($DB)->for_promotion($promotionid);
$grouprepository = CommercePedagogicalGroupRepository::create($DB);
$groupconfiguration = $grouprepository->get_configuration($promotionid);
$groups = $grouprepository->groups_for_promotion($promotionid, true);
$promotionoffers = CommercePedagogicalPromotionOfferRepository::create($DB)->links_for_promotion($promotionid);
$pointsbyuser = CommercePedagogicalXpRepository::create($DB)->points_by_user($promotionid);
$canmanagexp = $canmanage && has_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
$xpbonusservice = CommercePedagogicalXpAdjustmentService::create($DB);
$xpbonusallowed = ($promotion->get_xp_starts_at() === null || time() >= $promotion->get_xp_starts_at())
    && ($promotion->get_ends_at() === null || time() <= $promotion->get_ends_at())
    && in_array($promotion->get_status(), ['scheduled', 'open', 'full', 'started'], true);

$offernames = [];
foreach ($promotionoffers as $offer) {
    $offernames[(int)$offer->productid] = (string)$offer->productname . ' [' . (string)$offer->sku . ']';
}

$activejoins = $DB->get_records(
    'local_subs_commerce_ped_join',
    ['promotionid' => $promotionid, 'state' => 'active'],
    'id ASC',
    'id,userid,productid,timecreated'
);
$joinbyuser = [];
foreach ($activejoins as $join) {
    $joinbyuser[(int)$join->userid] = $join;
}

$groupoccupancy = [];
foreach ($groups as $group) {
    $groupoccupancy[(int)$group->get_id()] = $grouprepository->member_count((int)$group->get_id());
}

$progressivecount = 0;
$fullcount = 0;
$unassignedcount = 0;
foreach ($participants as $participant) {
    if ((string)$participant->profile === CommerceStudentAccessProfile::LIFETIME_FULL) {
        $fullcount++;
    } else {
        $progressivecount++;
    }
    if ($participant->pedagogicalgroupid === null) {
        $unassignedcount++;
    }
}

$query = trim(optional_param('q', '', PARAM_TEXT));
$sort = optional_param('sort', 'xp', PARAM_ALPHA);
if (!in_array($sort, ['xp', 'joined', 'group'], true)) {
    $sort = 'xp';
}
// Compute ranks on the entire promotion before filtering/pagination so medals remain stable.
$userranks = CommercePedagogicalCrmRanking::user_ranks($participants, $pointsbyuser);
$visibleparticipants = $participants;
if ($query !== '') {
    $needle = core_text::strtolower($query);
    $visibleparticipants = array_values(array_filter(
        $participants,
        static function($participant) use ($needle, $joinbyuser, $offernames): bool {
            $join = $joinbyuser[(int)$participant->userid] ?? null;
            $offer = $join !== null ? ($offernames[(int)$join->productid] ?? '') : '';
            $haystack = implode(' ', [
                fullname($participant),
                (string)$participant->email,
                (string)($participant->groupname ?? ''),
                $offer,
            ]);
            return core_text::strpos(core_text::strtolower($haystack), $needle) !== false;
        }
    ));
}

$visibleparticipants = CommercePedagogicalCrmRanking::participants(
    $visibleparticipants, $pointsbyuser, $joinbyuser, $sort
);
$participantperpage = 25;
$participantpage = max(0, optional_param('page', 0, PARAM_INT));
$filteredparticipantcount = count($visibleparticipants);
$participantmaxpage = $filteredparticipantcount > 0
    ? (int)floor(($filteredparticipantcount - 1) / $participantperpage)
    : 0;
$participantpage = min($participantpage, $participantmaxpage);
$participantoffset = $participantpage * $participantperpage;
$pagedparticipants = array_slice($visibleparticipants, $participantoffset, $participantperpage);
$participantpagingurl = new moodle_url($pageurl, [
    'promotionid' => $promotionid,
    'q' => $query,
    'sort' => $sort,
]);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('commerce_education_promotions_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php'),
    ],
    [
        'label' => $promotion->get_name(),
        'url' => $canmanage
            ? new moodle_url('/local/subscriptions/admin/commerce/education/promotion_edit.php', ['id' => $promotionid])
            : new moodle_url('/local/subscriptions/admin/commerce/education/followup.php', ['id' => $promotionid]),
    ],
    ['label' => get_string('commerce_education_participants', 'local_subscriptions'), 'url' => null],
]);

echo CrmPageHeader::render(
    $title,
    get_string('commerce_education_m74_participants_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);

if ($canmanage) {
    echo CommercePedagogicalPromotionAdminRenderer::render(
        $promotion,
        CommercePedagogicalPromotionAdminRenderer::PARTICIPANTS,
        $DB
    );
} else {
    echo html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/education/followup.php', ['id' => $promotionid]),
        get_string('commerce_education_followup_title', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary mb-3']
    );
}

echo html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/education/xp_history.php',
        ['promotionid' => $promotionid]),
    get_string('commerce_education_xp_history_crm_title', 'local_subscriptions'),
    ['class' => 'btn btn-sm btn-outline-primary mb-3']
);

// Participant metrics.
echo html_writer::start_div('commerce-ped-m74-metrics');
foreach ([
    ['fa-users', get_string('commerce_education_m74_metric_participants', 'local_subscriptions'), count($participants), ''],
    ['fa-calendar', get_string('commerce_education_m74_metric_progressive', 'local_subscriptions'), $progressivecount, ''],
    ['fa-unlock-alt', get_string('commerce_education_m74_metric_full_access', 'local_subscriptions'), $fullcount, ''],
    ['fa-user-times', get_string('commerce_education_m74_metric_unassigned', 'local_subscriptions'), $unassignedcount, $unassignedcount > 0 ? 'is-warning' : 'is-good'],
] as [$icon, $label, $value, $state]) {
    echo html_writer::start_div('commerce-ped-m74-metric ' . $state);
    echo html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']);
    echo html_writer::start_div();
    echo html_writer::div((string)$value, 'commerce-ped-m74-metric-value');
    echo html_writer::div($label, 'commerce-ped-m74-metric-label');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

if (!$groupconfiguration->is_enabled() && $groups !== []) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-info-circle', 'aria-hidden' => 'true'])
        . html_writer::span(get_string('commerce_education_m74_participants_groups_disabled', 'local_subscriptions')),
        'commerce-ped-m74-callout is-info'
    );
}

// Search / count bar.
echo html_writer::start_div('commerce-ped-m74-participant-toolbar');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false), 'class' => 'commerce-ped-m74-search']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'promotionid', 'value' => $promotionid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $sort]);
echo html_writer::start_div('commerce-ped-m74-search-box');
echo html_writer::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']);
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'name' => 'q',
    'value' => $query,
    'class' => 'form-control',
    'placeholder' => get_string('commerce_education_m74_search_placeholder', 'local_subscriptions'),
    'aria-label' => get_string('commerce_education_m74_search_placeholder', 'local_subscriptions'),
]);
echo html_writer::end_div();
echo html_writer::tag('button', get_string('search'), ['type' => 'submit', 'class' => 'btn btn-outline-secondary']);
if ($query !== '') {
    echo html_writer::link($pageurl, get_string('commerce_education_m74_clear_search', 'local_subscriptions'), ['class' => 'btn btn-link']);
}
echo html_writer::end_tag('form');
echo html_writer::start_tag('form', [
    'method' => 'get', 'action' => $pageurl->out(false), 'class' => 'commerce-ped-m74-sort-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'promotionid', 'value' => $promotionid]);
if ($query !== '') {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'q', 'value' => $query]);
}
echo html_writer::tag('label', get_string('commerce_education_sort_label', 'local_subscriptions'), [
    'for' => 'ped-participant-sort', 'class' => 'form-label',
]);
echo html_writer::select([
    'xp' => get_string('commerce_education_sort_xp', 'local_subscriptions'),
    'joined' => get_string('commerce_education_sort_joined', 'local_subscriptions'),
    'group' => get_string('commerce_education_sort_group', 'local_subscriptions'),
], 'sort', $sort, false, ['id' => 'ped-participant-sort', 'class' => 'form-select form-select-sm']);
echo html_writer::tag('button', get_string('commerce_education_sort_apply', 'local_subscriptions'), [
    'type' => 'submit', 'class' => 'btn btn-sm btn-outline-secondary',
]);
echo html_writer::end_tag('form');
echo html_writer::div(
    get_string('commerce_education_m74_participant_count', 'local_subscriptions', (object)[
        'shown' => $filteredparticipantcount,
        'total' => count($participants),
    ]),
    'commerce-ped-m74-result-count'
);
echo html_writer::end_div();

if ($participants === []) {
    echo html_writer::div(get_string('commerce_education_participants_empty', 'local_subscriptions'), 'commerce-ped-m74-empty is-large');
} else if ($visibleparticipants === []) {
    echo html_writer::div(get_string('commerce_education_m74_no_search_results', 'local_subscriptions'), 'commerce-ped-m74-empty is-large');
} else {
    echo html_writer::start_div('commerce-ped-m74-participant-list');

    foreach ($pagedparticipants as $participant) {
        $userid = (int)$participant->userid;
        $join = $joinbyuser[$userid] ?? null;
        $productid = $join !== null ? (int)$join->productid : null;
        $offername = $productid !== null && isset($offernames[$productid]) ? $offernames[$productid] : '—';
        $joinedat = $join !== null ? (int)$join->timecreated : (int)$participant->timecreated;
        $userpoints = $pointsbyuser[$userid] ?? 0;
        $profilelabel = (string)$participant->profile === CommerceStudentAccessProfile::LIFETIME_FULL
            ? get_string('commerce_education_profile_lifetime_full', 'local_subscriptions')
            : get_string('commerce_education_profile_promotion_progressive', 'local_subscriptions');

        $userplace = $userranks[$userid] ?? 0;
        $userpodium = [1 => 'gold', 2 => 'silver', 3 => 'bronze'][$userplace] ?? null;
        $usermedal = [1 => '🥇', 2 => '🥈', 3 => '🥉'][$userplace] ?? '';
        echo html_writer::start_div('commerce-ped-m74-participant-card'
            . ($userpodium !== null ? ' is-podium-' . $userpodium : ''));
        echo html_writer::start_div('commerce-ped-m74-participant-main');
        echo html_writer::start_div('commerce-ped-m74-participant-identity');
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-user-circle', 'aria-hidden' => 'true']),
            'commerce-ped-m74-avatar'
        );
        echo html_writer::start_div('commerce-ped-m74-participant-copy');
        echo html_writer::tag('h4', html_writer::link(
            new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $userid]),
            s(fullname($participant)),
            ['class' => 'text-decoration-none', 'title' => get_string('commerce_education_xp_user360_link', 'local_subscriptions')]
        ), ['class' => 'commerce-ped-m74-participant-name']);
        if ($usermedal !== '') {
            echo html_writer::span($usermedal . ' ' . get_string(
                'commerce_education_medal_' . $userpodium, 'local_subscriptions'),
                'commerce-ped-m74-medal is-' . $userpodium);
        }
        echo html_writer::div(s((string)$participant->email), 'commerce-ped-m74-participant-email');
        echo html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/education/xp_history.php',
                ['promotionid' => $promotionid, 'userid' => $userid]),
            get_string('commerce_education_xp_history_crm_short', 'local_subscriptions'),
            ['class' => 'small d-inline-block mt-1']
        );
        echo html_writer::start_div('commerce-ped-m74-participant-badges');
        echo html_writer::span($profilelabel, 'commerce-ped-m74-participant-badge is-profile');
        echo html_writer::span(
            $participant->groupname !== null
                ? s((string)$participant->groupname)
                : get_string('commerce_education_m74_no_group', 'local_subscriptions'),
            'commerce-ped-m74-participant-badge ' . ($participant->groupname !== null ? 'is-group' : 'is-muted')
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('commerce-ped-m74-participant-meta');
        foreach ([
            ['fa-shopping-bag', get_string('commerce_education_group_offer', 'local_subscriptions'), $offername],
            ['fa-clock-o', get_string('commerce_education_participant_since', 'local_subscriptions'), userdate($joinedat)],
            ['fa-star', get_string('commerce_education_m74_participant_xp', 'local_subscriptions'),
                CommercePedagogicalPointsPresenter::promotion((int)$userpoints)],
        ] as [$icon, $label, $value]) {
            echo html_writer::start_div('commerce-ped-m74-participant-meta-item');
            echo html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']);
            echo html_writer::start_div();
            echo html_writer::div($label, 'commerce-ped-m74-participant-meta-label');
            echo html_writer::div($icon === 'fa-star' ? $value : s((string)$value),
                'commerce-ped-m74-participant-meta-value');
            echo html_writer::end_div();
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
        echo html_writer::end_div();

        if ($canmanage) {
        echo html_writer::start_div('commerce-ped-m74-participant-controls');

        // Explicit access profile adjustment.
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'commerce-ped-m74-control-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'profile']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
        echo html_writer::tag('label', get_string('commerce_education_participant_profile', 'local_subscriptions'), [
            'class' => 'form-label', 'for' => 'participant-profile-' . $userid,
        ]);
        echo html_writer::start_div('commerce-ped-m74-control-row');
        echo html_writer::select([
            CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE => get_string('commerce_education_profile_promotion_progressive', 'local_subscriptions'),
            CommerceStudentAccessProfile::LIFETIME_FULL => get_string('commerce_education_profile_lifetime_full', 'local_subscriptions'),
        ], 'profile', (string)$participant->profile, false, [
            'id' => 'participant-profile-' . $userid,
            'class' => 'form-select form-select-sm',
        ]);
        echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']);
        echo html_writer::end_div();
        echo html_writer::end_tag('form');

        // Group movement only to groups belonging to the purchased offer.
        $groupoptions = [];
        foreach ($groups as $group) {
            if ($productid !== null && $group->get_product_id() !== $productid) {
                continue;
            }
            $groupid = (int)$group->get_id();
            $members = $groupoccupancy[$groupid] ?? 0;
            $label = $group->get_display_name() . ' · ' . $members . '/' . $groupconfiguration->get_group_size();
            if ($members >= $groupconfiguration->get_group_size() && $participant->pedagogicalgroupid !== $groupid) {
                $label .= ' · ' . get_string('commerce_education_m74_group_full_short', 'local_subscriptions');
            }
            $groupoptions[$groupid] = $label;
        }

        if ($groupoptions !== []) {
            echo html_writer::start_tag('form', [
                'method' => 'post',
                'action' => $pageurl->out(false),
                'class' => 'commerce-ped-m74-control-form',
            ]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'group']);
            echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $userid]);
            echo html_writer::tag('label', get_string('commerce_education_groups', 'local_subscriptions'), [
                'class' => 'form-label', 'for' => 'participant-group-' . $userid,
            ]);
            echo html_writer::start_div('commerce-ped-m74-control-row');
            echo html_writer::select(
                $groupoptions,
                'groupid',
                $participant->pedagogicalgroupid !== null ? (int)$participant->pedagogicalgroupid : '',
                ['' => get_string('choosedots')],
                ['id' => 'participant-group-' . $userid, 'class' => 'form-select form-select-sm', 'required' => 'required']
            );
            echo html_writer::tag('button', get_string('commerce_education_m74_move_button', 'local_subscriptions'), [
                'type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary',
            ]);
            echo html_writer::end_div();
            echo html_writer::end_tag('form');
        } else {
            echo html_writer::div(get_string('commerce_education_m74_no_compatible_group', 'local_subscriptions'), 'commerce-ped-m74-no-group-action');
        }

        echo html_writer::end_div();
        } // End manager-only participant controls.

        // Manually granted XP are confined to this promotion; no personal XP change.
        if ($canmanagexp) {
        echo html_writer::start_tag('details', ['class' => 'commerce-ped-m74-control-form commerce-ped-xp-bonus']);
        echo html_writer::tag('summary', get_string('commerce_education_xp_manual_heading', 'local_subscriptions'));
        echo html_writer::div(get_string('commerce_education_xp_manual_user_help', 'local_subscriptions'), 'small text-muted mb-2');
        if ($xpbonusallowed) {
            echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false)]);
            foreach ([
                'sesskey' => sesskey(), 'action' => 'xpbonus', 'userid' => $userid,
                'requestkey' => bin2hex(random_bytes(16)),
            ] as $name => $value) {
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
            }
            echo html_writer::tag('label', get_string('commerce_education_xp_manual_points', 'local_subscriptions'),
                ['for' => 'user-xp-points-' . $userid, 'class' => 'form-label']);
            echo html_writer::empty_tag('input', [
                'type' => 'number', 'name' => 'points', 'id' => 'user-xp-points-' . $userid,
                'min' => 1, 'max' => CommercePedagogicalXpAdjustmentService::MAX_USER_POINTS,
                'required' => 'required', 'class' => 'form-control form-control-sm mb-2',
            ]);
            echo html_writer::tag('label', get_string('commerce_education_xp_manual_reason', 'local_subscriptions'),
                ['for' => 'user-xp-reason-' . $userid, 'class' => 'form-label']);
            echo html_writer::empty_tag('input', [
                'type' => 'text', 'name' => 'reason', 'id' => 'user-xp-reason-' . $userid,
                'minlength' => 8, 'maxlength' => 250, 'required' => 'required',
                'class' => 'form-control form-control-sm mb-2',
            ]);
            echo html_writer::tag('button', get_string('commerce_education_xp_manual_submit', 'local_subscriptions'),
                ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']);
            echo html_writer::end_tag('form');
        } else {
            echo html_writer::div(get_string('commerce_education_xp_manual_notstarted', 'local_subscriptions'),
                'small text-muted');
        }
        $manualhistory = $xpbonusservice->recent_for_target(
            $promotionid, CommercePedagogicalXpAdjustmentService::USER, $userid);
        if ($manualhistory) {
            echo html_writer::tag('h5', get_string('commerce_education_xp_manual_history', 'local_subscriptions'),
                ['class' => 'commerce-ped-xp-history-title']);
            foreach ($manualhistory as $adjustment) {
                echo CommercePedagogicalXpBonusHistoryRenderer::render(
                    $adjustment, $pageurl, CommercePedagogicalXpAdjustmentService::USER, $userid);
            }
        }
        echo html_writer::end_tag('details');
        }
        echo html_writer::end_div();
    }

    echo html_writer::end_div();
    if ($filteredparticipantcount > $participantperpage) {
        echo html_writer::div(
            $OUTPUT->paging_bar(
                $filteredparticipantcount,
                $participantpage,
                $participantperpage,
                $participantpagingurl,
                'page'
            ),
            'commerce-ped-m75-pagination'
        );
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
