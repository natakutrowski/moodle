<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\education\xp\CommercePedagogicalPointsPresenter;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpAdjustmentService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpBonusHistoryRenderer;
use local_subscriptions\commerce\education\xp\CommercePedagogicalGroupLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;
use local_subscriptions\commerce\education\admin\CommerceEducationNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $OUTPUT, $PAGE, $USER;

$context = AdminSecurity::require(Capabilities::VIEW_PROMOTIONS);
$promotionid = required_param('id', PARAM_INT);
$promotion = CommercePedagogicalPromotionRepository::create($DB)->get_by_id($promotionid);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord', 'error');
}
$canxp = has_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
$canmanage = has_capability(Capabilities::MANAGE_PROMOTIONS, $context);
$url = new moodle_url('/local/subscriptions/admin/commerce/education/followup.php', ['id' => $promotionid]);
$title = get_string('commerce_education_followup_title', 'local_subscriptions') . ' · ' . $promotion->get_name();
CrmPageConfigurator::configure($PAGE, $context, $url, $title,
    'local-subscriptions-commerce-education-followup-page');

// Every mutation is separately authorised. Read-only access never implies XP rights.
if (data_submitted()) {
    require_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    $userid = optional_param('userid', 0, PARAM_INT);
    $groupid = optional_param('groupid', 0, PARAM_INT);
    if (($userid > 0) === ($groupid > 0)) {
        throw new moodle_exception('invalidparameter', 'error');
    }
    $type = $userid > 0 ? CommercePedagogicalXpAdjustmentService::USER
        : CommercePedagogicalXpAdjustmentService::GROUP;
    $targetid = $userid > 0 ? $userid : $groupid;
    $service = CommercePedagogicalXpAdjustmentService::create($DB);
    if ($action === 'xpbonus') {
        $service->award($promotionid, $type, $targetid,
            required_param('points', PARAM_INT), required_param('reason', PARAM_TEXT),
            (int)$USER->id, required_param('requestkey', PARAM_ALPHANUMEXT));
        $message = 'commerce_education_xp_manual_added';
    } else if ($action === 'xpcancel') {
        $service->cancel($promotionid, $type, $targetid,
            required_param('adjustmentid', PARAM_INT), (int)$USER->id);
        $message = 'commerce_education_xp_manual_cancel_success';
    } else {
        throw new moodle_exception('invalidparameter', 'error');
    }
    redirect($url, get_string($message, 'local_subscriptions'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

$pointsbyuser = CommercePedagogicalXpRepository::create($DB)->points_by_user($promotionid);
$ranking = CommercePedagogicalGroupLeaderboardService::create($DB)->get_ranking($promotionid);
$members = $DB->get_records_sql(
    "SELECT gm.id, gm.groupid, u.id AS userid, u.firstname, u.lastname,
            u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
       FROM {local_subs_commerce_ped_gmem} gm
       JOIN {local_subs_commerce_ped_group} g ON g.id = gm.groupid
       JOIN {user} u ON u.id = gm.userid AND u.deleted = 0
      WHERE g.promotionid = :pid AND gm.active = 1
        AND EXISTS (SELECT 1 FROM {local_subs_commerce_ped_join} j
             WHERE j.promotionid = :pidjoin AND j.userid = u.id AND j.state = 'active')
   ORDER BY u.lastname, u.firstname, gm.id",
    ['pid' => $promotionid, 'pidjoin' => $promotionid]
);
$membersbygroup = [];
$memberids = [];
foreach ($members as $member) {
    $membersbygroup[(int)$member->groupid][] = $member;
    $memberids[(int)$member->userid] = true;
}
$totaljoined = $DB->count_records('local_subs_commerce_ped_join',
    ['promotionid' => $promotionid, 'state' => 'active']);
$adjustments = CommercePedagogicalXpAdjustmentService::create($DB);
$xpstarted = ($promotion->get_xp_starts_at() === null || time() >= $promotion->get_xp_starts_at())
    && ($promotion->get_ends_at() === null || time() <= $promotion->get_ends_at())
    && in_array($promotion->get_status(), ['scheduled', 'open', 'full', 'started'], true);

// Shared, strictly optional XP form. This cannot change the personal Level Up ledger.
$bonusform = static function(string $type, int $id) use ($canxp, $xpstarted, $url): string {
    if (!$canxp) {
        return '';
    }
    $target = $type === 'user' ? 'userid' : 'groupid';
    $limit = $type === 'user' ? CommercePedagogicalXpAdjustmentService::MAX_USER_POINTS
        : CommercePedagogicalXpAdjustmentService::MAX_GROUP_POINTS;
    $out = html_writer::start_tag('details', ['class' => 'commerce-ped-xp-bonus mt-3']);
    $out .= html_writer::tag('summary', get_string('commerce_education_xp_manual_heading', 'local_subscriptions'));
    if (!$xpstarted) {
        return $out . html_writer::div(get_string('commerce_education_xp_manual_notstarted', 'local_subscriptions'),
            'small text-muted') . html_writer::end_tag('details');
    }
    $out .= html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    foreach (['sesskey' => sesskey(), 'action' => 'xpbonus', $target => $id,
        'requestkey' => bin2hex(random_bytes(16))] as $key => $value) {
        $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
    }
    $out .= html_writer::tag('label', get_string('commerce_education_xp_manual_points', 'local_subscriptions'),
        ['class' => 'form-label']);
    $out .= html_writer::empty_tag('input', ['type' => 'number', 'name' => 'points', 'min' => 1,
        'max' => $limit, 'required' => 'required', 'class' => 'form-control form-control-sm mb-2']);
    $out .= html_writer::tag('label', get_string('commerce_education_xp_manual_reason', 'local_subscriptions'),
        ['class' => 'form-label']);
    $out .= html_writer::empty_tag('input', ['type' => 'text', 'name' => 'reason',
        'minlength' => 8, 'maxlength' => 250, 'required' => 'required',
        'class' => 'form-control form-control-sm mb-2']);
    $out .= html_writer::tag('button', get_string('commerce_education_xp_manual_submit', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-primary']);
    return $out . html_writer::end_tag('form') . html_writer::end_tag('details');
};

$history = static function(string $type, int $targetid) use ($adjustments, $url, $canxp, $promotionid): string {
    $rows = $adjustments->recent_for_target($promotionid, $type, $targetid);
    if (!$rows) {
        return '';
    }
    $out = html_writer::tag('h5', get_string('commerce_education_xp_manual_history', 'local_subscriptions'),
        ['class' => 'commerce-ped-xp-history-title']);
    foreach ($rows as $record) {
        $out .= CommercePedagogicalXpBonusHistoryRenderer::render($record, $url, $type, $targetid, $canxp);
    }
    return $out;
};

$profilelink = static function(\stdClass $user): string {
    return html_writer::link(new moodle_url('/local/subscriptions/admin/users/view.php',
        ['id' => (int)$user->userid]), s(fullname($user)));
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('commerce_education_promotions_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    get_string('commerce_education_followup_readonly', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);
echo CommerceEducationNavigationRenderer::render(CommerceEducationNavigationRenderer::PROMOTIONS);
if ($canmanage) {
    echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/education/promotion_edit.php',
        ['id' => $promotionid]), get_string('commerce_education_admin_manage', 'local_subscriptions'),
        ['class' => 'btn btn-sm btn-outline-secondary mb-3']);
}
// The support follow-up page should not expose raw Moodle calendar section IDs.
// Reuse the translated status and badge palette from the promotion list.
$status = $promotion->get_status();
$statusbadge = html_writer::span(
    get_string('commerce_education_promotion_status_' . $status, 'local_subscriptions'),
    'badge rounded-pill ' . match ($status) {
        'open' => 'text-bg-success',
        'started' => 'text-bg-primary',
        'scheduled' => 'text-bg-info',
        'full' => 'text-bg-warning',
        'finished' => 'text-bg-dark',
        default => 'text-bg-secondary',
    }
);
echo html_writer::div(
    html_writer::span(s($promotion->get_name()), 'fw-semibold')
        . ' · ' . $statusbadge . ' · ' . (int)$totaljoined . ' '
        . get_string('commerce_education_participants', 'local_subscriptions'),
    'alert alert-light border mb-3'
);
$supportnav = [
    ['groups.php', get_string('commerce_education_groups', 'local_subscriptions')],
    ['participants.php', get_string('commerce_education_participants', 'local_subscriptions')],
    ['calendar.php', get_string('commerce_education_calendar', 'local_subscriptions')],
    ['xp_history.php', get_string('commerce_education_xp_history_crm_title', 'local_subscriptions')],
];
$navlinks = [];
foreach ($supportnav as [$path, $label]) {
    $navlinks[] = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/education/' . $path,
            ['promotionid' => $promotionid]),
        s($label), ['class' => 'btn btn-sm btn-outline-secondary']
    );
}
echo html_writer::div(implode(' ', $navlinks), 'commerce-ped-followup-links mb-3');
$medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
foreach ($ranking as $entry) {
    $groupid = $entry->get_group_id();
    $rank = $entry->get_points() > 0 ? $entry->get_rank() : 0;
    $medal = $medals[$rank] ?? '';
    $roster = $membersbygroup[$groupid] ?? [];
    usort($roster, static fn($a, $b): int =>
        ((int)($pointsbyuser[(int)$b->userid] ?? 0) <=> (int)($pointsbyuser[(int)$a->userid] ?? 0))
        ?: strnatcasecmp(fullname($a), fullname($b)));
    $memberranks = [];
    $lastscore = null;
    $lastrank = 0;
    foreach ($roster as $index => $member) {
        $score = (int)($pointsbyuser[(int)$member->userid] ?? 0);
        if ($score <= 0) {
            $memberranks[(int)$member->userid] = 0;
            continue;
        }
        if ($lastscore === null || $lastscore !== $score) {
            $lastrank = $index + 1;
            $lastscore = $score;
        }
        $memberranks[(int)$member->userid] = $lastrank;
    }
    echo html_writer::start_div('card mb-3 commerce-ped-m74-group-card'
        . ($rank === 1 ? ' is-podium-gold' : ($rank === 2 ? ' is-podium-silver'
            : ($rank === 3 ? ' is-podium-bronze' : ''))));
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', ($medal ? $medal . ' ' : '') . s($entry->get_display_name()),
        ['class' => 'h5']);
    echo html_writer::div(CommercePedagogicalPointsPresenter::promotion((int)$entry->get_points()) . ' · ' . count($roster)
        . ' ' . get_string('commerce_education_participants', 'local_subscriptions'), 'small text-muted mb-2');
    echo html_writer::start_tag('details', ['class' => 'commerce-ped-m74-group-roster']);
    echo html_writer::tag('summary', get_string('commerce_education_group_roster_title', 'local_subscriptions')
        . ' (' . count($roster) . ')');
    if ($roster) {
        echo html_writer::start_tag('ul', ['class' => 'list-unstyled mt-2']);
        foreach ($roster as $member) {
            $userid = (int)$member->userid;
            echo html_writer::start_tag('li', ['class' => 'mb-2']);
            echo ([1 => '🥇 ', 2 => '🥈 ', 3 => '🥉 '][$memberranks[$userid] ?? 0] ?? '')
                . $profilelink($member) . ' · ' . CommercePedagogicalPointsPresenter::promotion((int)($pointsbyuser[$userid] ?? 0))
                . ' · ' . html_writer::link(new moodle_url(
                    '/local/subscriptions/admin/commerce/education/xp_history.php',
                    ['promotionid' => $promotionid, 'userid' => $userid]),
                    get_string('commerce_education_xp_history_crm_short', 'local_subscriptions'));
            echo $bonusform('user', $userid);
            echo $history('user', $userid);
            echo html_writer::end_tag('li');
        }
        echo html_writer::end_tag('ul');
    }
    echo html_writer::end_tag('details');
    echo $bonusform('group', (int)$groupid);
    echo $history('group', (int)$groupid);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
// Users who have bought the promotion but are not currently assigned to a group.
$unassigned = $DB->get_records_sql(
    "SELECT u.id, u.id AS userid, u.firstname, u.lastname,
            u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
       FROM {user} u
       JOIN {local_subs_commerce_ped_join} j ON j.userid = u.id
      WHERE j.promotionid = :pid AND j.state = 'active' AND u.deleted = 0
        AND NOT EXISTS (
            SELECT 1 FROM {local_subs_commerce_ped_gmem} gm
            JOIN {local_subs_commerce_ped_group} g ON g.id = gm.groupid
            WHERE gm.userid = u.id AND gm.active = 1 AND g.promotionid = :pid2
        )
   ORDER BY u.lastname, u.firstname",
    ['pid' => $promotionid, 'pid2' => $promotionid]
);
if ($unassigned) {
    echo html_writer::tag('h3', get_string('commerce_education_m74_metric_unassigned', 'local_subscriptions'),
        ['class' => 'h5 mt-4']);
    foreach ($unassigned as $member) {
        $userid = (int)$member->userid;
        echo html_writer::start_div('card card-body mb-2');
        echo $profilelink($member) . ' · ' . CommercePedagogicalPointsPresenter::promotion((int)($pointsbyuser[$userid] ?? 0))
            . ' · ' . html_writer::link(new moodle_url(
                '/local/subscriptions/admin/commerce/education/xp_history.php',
                ['promotionid' => $promotionid, 'userid' => $userid]),
                get_string('commerce_education_xp_history_crm_short', 'local_subscriptions'));
        echo $bonusform('user', $userid);
        echo $history('user', $userid);
        echo html_writer::end_div();
    }
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
