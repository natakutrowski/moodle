<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\admin\CommerceEducationNavigationRenderer;
use local_subscriptions\commerce\education\admin\CommercePedagogicalPromotionAdminRenderer;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpHistoryService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $PAGE, $OUTPUT;

// This endpoint is strictly GET/read-only. It has no award, cancellation or replay actions.
$context = AdminSecurity::require(Capabilities::VIEW_PROMOTIONS);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    throw new \coding_exception('Promotion XP history is read-only.');
}
$promotionid = required_param('promotionid', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
if ($userid < 0) {
    throw new moodle_exception('invalidrecord', 'error');
}
$promotion = CommercePedagogicalPromotionRepository::create($DB)->get_by_id($promotionid);
if (!$promotion) {
    throw new moodle_exception('invalidrecord', 'error');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/education/xp_history.php', [
    'promotionid' => $promotionid,
]);
$pagingurl = new moodle_url($pageurl, $userid > 0 ? ['userid' => $userid] : []);
$title = get_string('commerce_education_xp_history_crm_title', 'local_subscriptions');
CrmPageConfigurator::configure(
    $PAGE, $context, $pagingurl, $title, 'local-subscriptions-commerce-education-xp-history-page'
);

$service = CommercePedagogicalXpHistoryService::create($DB);
$data = $userid > 0
    ? $service->get_for_crm_user($promotionid, $userid, $page)
    : $service->get_for_crm_promotion($promotionid, $page);
if ($data === null) {
    throw new moodle_exception('invalidrecord', 'error');
}

// Both current and former participants remain selectable for historical CRM audits.
$participants = $DB->get_records_sql(
    "SELECT u.id, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
            u.middlename, u.alternatename
       FROM {user} u
      WHERE EXISTS (
            SELECT 1 FROM {local_subs_commerce_ped_join} j
             WHERE j.userid = u.id AND j.promotionid = :pidjoin
      ) OR EXISTS (
            SELECT 1 FROM {local_subs_commerce_ped_xp} x
             WHERE x.userid = u.id AND x.promotionid = :pidxp
      )
   ORDER BY u.lastname, u.firstname, u.id",
    ['pidjoin' => $promotionid, 'pidxp' => $promotionid]
);
if ($userid > 0 && !isset($participants[$userid])) {
    throw new moodle_exception('invalidrecord', 'error');
}

$groupsurl = new moodle_url('/local/subscriptions/admin/commerce/education/groups.php',
    ['promotionid' => $promotionid]);
$participantsurl = new moodle_url('/local/subscriptions/admin/commerce/education/participants.php',
    ['promotionid' => $promotionid]);
$canmanage = has_capability(Capabilities::MANAGE_PROMOTIONS, $context);
$overviewurl = $canmanage
    ? new moodle_url('/local/subscriptions/admin/commerce/education/promotion_edit.php', ['id' => $promotionid])
    : new moodle_url('/local/subscriptions/admin/commerce/education/followup.php', ['id' => $promotionid]);

$selectedname = $userid > 0 ? fullname($participants[$userid]) : '';

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('commerce_education_promotions_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php'),
    ],
    ['label' => $promotion->get_name(), 'url' => $overviewurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    get_string('commerce_education_xp_history_crm_intro', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);
echo CommerceEducationNavigationRenderer::render(CommerceEducationNavigationRenderer::PROMOTIONS);
if ($canmanage) {
    echo CommercePedagogicalPromotionAdminRenderer::render(
        $promotion, CommercePedagogicalPromotionAdminRenderer::XP_HISTORY, $DB
    );
} else {
    echo html_writer::link($overviewurl,
        get_string('commerce_education_followup_title', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary mb-3']);
}

echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-body');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false),
    'class' => 'd-flex flex-wrap align-items-end gap-2']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'promotionid',
    'value' => $promotionid]);
echo html_writer::start_div();
echo html_writer::tag('label',
    get_string('commerce_education_xp_history_crm_participant', 'local_subscriptions'),
    ['for' => 'promotion-xp-user', 'class' => 'form-label']);
$options = [0 => get_string('commerce_education_xp_history_crm_all', 'local_subscriptions')];
foreach ($participants as $participant) {
    $options[(int)$participant->id] = fullname($participant) . ' (#' . (int)$participant->id . ')';
}
echo html_writer::select($options, 'userid', $userid, false,
    ['id' => 'promotion-xp-user', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::tag('button', get_string('filter'),
    ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('row g-3 mb-3');
foreach ([
    [get_string($userid === 0 ? 'commerce_education_xp_history_crm_total_all'
        : 'commerce_education_xp_history_crm_total_user', 'local_subscriptions'),
        (int)$data['totalpoints'] . ' XP'],
    [get_string('commerce_promo_xp_history_count', 'local_subscriptions'),
        (string)$data['rewardcount']],
] as [$label, $value]) {
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card h-100');
    echo html_writer::start_div('card-body');
    echo html_writer::div(s($label), 'text-muted small');
    echo html_writer::div(s($value), 'h4 mb-0');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

if ($userid > 0) {
    echo html_writer::tag('h3',
        get_string('commerce_education_xp_history_crm_individual', 'local_subscriptions')
            . ' — ' . s($selectedname), ['class' => 'h5']);
    echo html_writer::link($pageurl,
        get_string('commerce_education_xp_history_crm_allentries', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary mb-3']);
} else {
    echo html_writer::tag('h3',
        get_string('commerce_education_xp_history_crm_allentries', 'local_subscriptions'),
        ['class' => 'h5']);
}

if (!$data['rows']) {
    echo html_writer::div(get_string('commerce_education_xp_history_crm_empty', 'local_subscriptions'),
        'alert alert-light border');
} else {
    echo html_writer::start_div('table-responsive');
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover align-middle']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    foreach (array_filter([
        get_string('commerce_promo_xp_history_date', 'local_subscriptions'),
        $userid === 0 ? get_string('commerce_education_xp_history_crm_participant', 'local_subscriptions') : null,
        get_string('commerce_promo_xp_history_source', 'local_subscriptions'),
        get_string('commerce_promo_xp_history_points', 'local_subscriptions'),
    ], static fn($v) => $v !== null) as $heading) {
        echo html_writer::tag('th', s($heading), ['scope' => 'col']);
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($data['rows'] as $row) {
        echo html_writer::start_tag('tr', $row['iscancelled'] ? ['class' => 'text-muted'] : []);
        echo html_writer::tag('td', s($row['date']));
        if ($userid === 0) {
            $entryurl = new moodle_url($pageurl, ['userid' => (int)$row['userid']]);
            echo html_writer::tag('td', html_writer::link($entryurl, s($row['username'])) . ' '
                . html_writer::link(
                    new moodle_url('/local/subscriptions/admin/users/view.php',
                        ['id' => (int)$row['userid']]), 'User 360', ['class' => 'small']));
        }
        $source = s((string)$row['source']);
        if (!empty($row['hasactivityurl'])) {
            $source = html_writer::link(new moodle_url((string)$row['activityurl']), $source);
        }
        if (!empty($row['crmreason'])) {
            $source .= html_writer::div(s(get_string('commerce_education_xp_history_crm_reason',
                'local_subscriptions', $row['crmreason'])), 'small text-muted');
        }
        if ($row['iscancelled']) {
            $source .= html_writer::div(s($row['cancelledlabel']), 'small text-danger');
        }
        echo html_writer::tag('td', $source);
        $points = $row['iscancelled']
            ? html_writer::tag('s', '+' . (int)$row['points']) . ' → 0'
            : '+' . (int)$row['points'];
        echo html_writer::tag('td', $points, ['class' => 'text-nowrap']);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
}
if ((int)$data['rewardcount'] > CommercePedagogicalXpHistoryService::PAGE_SIZE) {
    echo $OUTPUT->paging_bar((int)$data['rewardcount'], (int)$data['page'],
        CommercePedagogicalXpHistoryService::PAGE_SIZE, $pagingurl, 'page');
}
if ($userid === 0) {
    echo html_writer::div(
        get_string('commerce_education_xp_history_crm_activity_note', 'local_subscriptions'),
        'small text-muted mb-3'
    );
}
echo html_writer::div(html_writer::link($groupsurl,
    get_string('commerce_education_groups', 'local_subscriptions')) . ' · '
    . html_writer::link($participantsurl,
        get_string('commerce_education_participants', 'local_subscriptions')),
    'mt-3 mb-3');

// Group bonuses are not individual XP ledger entries: show them separately,
// without mixing their amounts into the summary or assigning them to pupils.
if ($userid === 0) {
    $groupbonuses = $DB->get_records_sql(
        "SELECT a.*, g.displayname AS groupname
           FROM {local_subs_commerce_ped_xpadj} a
      LEFT JOIN {local_subs_commerce_ped_group} g
             ON g.id = a.groupid AND g.promotionid = a.promotionid
          WHERE a.promotionid = :promotionid AND a.targettype = :targettype
       ORDER BY a.timecreated DESC, a.id DESC",
        ['promotionid' => $promotionid, 'targettype' => 'group'], 0, 25
    );
    if ($groupbonuses) {
        echo html_writer::start_tag('details', ['class' => 'card mb-3']);
        echo html_writer::tag('summary',
            get_string('commerce_education_xp_history_crm_groupbonus', 'local_subscriptions'),
            ['class' => 'card-header fw-semibold']);
        echo html_writer::start_div('card-body');
        echo html_writer::div(
            get_string('commerce_education_xp_history_crm_groupbonus_note', 'local_subscriptions'),
            'small text-muted mb-2'
        );
        echo html_writer::start_tag('ul', ['class' => 'list-unstyled mb-0']);
        foreach ($groupbonuses as $bonus) {
            $label = (string)($bonus->groupname ?: ('#' . (int)$bonus->groupid));
            $line = s(userdate((int)$bonus->timecreated)) . ' · ' . s($label) . ' · ';
            $line .= $bonus->cancelledat !== null
                ? html_writer::tag('s', '+' . (int)$bonus->points . ' XP') . ' → 0 XP'
                : '+' . (int)$bonus->points . ' XP';
            $line .= html_writer::div(s((string)$bonus->reason), 'small text-muted');
            if ($bonus->cancelledat !== null) {
                $line .= html_writer::div(s(get_string('commerce_promo_xp_history_cancelled',
                    'local_subscriptions', userdate((int)$bonus->cancelledat))), 'small text-danger');
            }
            echo html_writer::tag('li', $line, ['class' => 'mb-2']);
        }
        echo html_writer::end_tag('ul');
        echo html_writer::end_div();
        echo html_writer::end_tag('details');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
