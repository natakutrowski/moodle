<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\xp\CommerceXpAssistedRepairService;
use local_subscriptions\commerce\education\xp\CommerceXpAutomaticRepairService;
use local_subscriptions\commerce\education\admin\CommerceEducationNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $DB, $PAGE, $OUTPUT;

// No POST is allowed unless two management capabilities, sesskey, and opt-in toggle are present.
$context = AdminSecurity::require(Capabilities::VIEW_PROMOTIONS);
$canrepair = has_capability(Capabilities::MANAGE_PROMOTIONS, $context) &&
    has_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
$assistedenabled = (int)get_config('local_subscriptions', 'xp_assisted_enabled') === 1;
$service = CommerceXpAssistedRepairService::create($DB);
$auditid = max(0, optional_param('auditid', 0, PARAM_INT));
$tab = optional_param('tab', 'anomalies', PARAM_ALPHA);
if (!in_array($tab, ['anomalies', 'history'], true)) {
    $tab = 'anomalies';
}
$status = optional_param('status', 'review', PARAM_ALPHA);
if (!in_array($status, ['review', 'resolved', 'all'], true)) {
    $status = 'review';
}
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 30;
$baseurl = new moodle_url('/local/subscriptions/admin/commerce/education/xp_reconciliation.php');
$pageurl = new moodle_url($baseurl, ['tab' => $tab, 'status' => $status, 'page' => $page]);
if ($auditid) { $pageurl->param('auditid', $auditid); }
$title = get_string('commerce_xp_audit_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title,
    'local-subscriptions-commerce-education-xp-audit-page');
// Only a deliberate, CSRF-protected POST can award XP. The audit cron never calls repair().
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_capability(Capabilities::MANAGE_PROMOTIONS, $context);
    require_capability(Capabilities::MANAGE_PROMOTION_XP, $context);
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    if ($action === 'autocron' || $action === 'automode') {
        // Changing automated monetary balances requires system administrator rights.
        require_capability('moodle/site:config', \context_system::instance());
        if ($action === 'autocron') {
            $enable = required_param('enable', PARAM_INT);
            if (!in_array($enable, [0, 1], true)) {
                throw new moodle_exception('invalidparameter');
            }
            set_config('xp_auto_cron_enabled', $enable, 'local_subscriptions');
        } else {
            $mode = required_param('automode', PARAM_ALPHA);
            if (!in_array($mode, ['simulation', 'live'], true) ||
                    ($mode === 'live' && required_param('confirm', PARAM_INT) !== 1)) {
                throw new moodle_exception('invalidparameter');
            }
            set_config('xp_auto_mode', $mode, 'local_subscriptions');
        }
        redirect($baseurl, get_string('commerce_xp_auto_updated', 'local_subscriptions'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action !== 'repair' || required_param('confirm', PARAM_INT) !== 1 ||
            !$assistedenabled) {
        throw new moodle_exception('invalidparameter');
    }
    $id = required_param('auditid', PARAM_INT);
    $service->repair($id, required_param('proof', PARAM_ALPHANUMEXT),
        (int)$USER->id, required_param('reason', PARAM_TEXT));
    redirect(new moodle_url($baseurl, ['auditid' => $id]),
        get_string('commerce_xp_assisted_success', 'local_subscriptions'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    throw new coding_exception('Only GET and POST are supported.');
}


$review = $DB->count_records('local_subs_xp_audit', ['status' => 'review']);
$resolved = $DB->count_records('local_subs_xp_audit', ['status' => 'resolved']);
$fixcount = $DB->count_records('local_subs_xp_audit_fix');
$lastruns = $DB->get_records('local_subs_xp_audit_run', null, 'id DESC', '*', 0, 1);
$lastrun = $lastruns ? reset($lastruns) : null;

$tabs = [
    'anomalies' => get_string('commerce_xp_audit_tab_anomalies', 'local_subscriptions'),
    'history' => get_string('commerce_xp_audit_tab_history', 'local_subscriptions'),
];
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('crm_commerce_nav_education', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/education/promotions.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    get_string('commerce_xp_audit_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::EDUCATION, $context);
echo CommerceEducationNavigationRenderer::render(CommerceEducationNavigationRenderer::AUDIT);

echo html_writer::div(get_string('commerce_xp_assisted_mode_explain', 'local_subscriptions'),
    'alert alert-info');

// Automatic worker has its own physical schedule and two software gates.
$canconfigureauto = has_capability('moodle/site:config', \context_system::instance()) && $canrepair;
$autoenabled = CommerceXpAutomaticRepairService::is_cron_enabled();
$automode = CommerceXpAutomaticRepairService::mode();
$lastautorun = (int)get_config('local_subscriptions', 'xp_auto_last_run');
$lastautojson = (string)get_config('local_subscriptions', 'xp_auto_last_summary');
$lastauto = json_decode($lastautojson, true);
if (!is_array($lastauto)) { $lastauto = []; }
$autocard = html_writer::tag('h3', get_string('commerce_xp_auto_title', 'local_subscriptions'),
    ['class' => 'h5']);
$autocard .= html_writer::div(get_string('commerce_xp_auto_description', 'local_subscriptions'),
    'text-muted mb-2');
$autocard .= html_writer::div(
    get_string('commerce_xp_auto_cron_label', 'local_subscriptions') . ': ' .
    ($autoenabled ? get_string('commerce_xp_auto_on', 'local_subscriptions') :
        get_string('commerce_xp_auto_off', 'local_subscriptions')) . ' | ' .
    get_string('commerce_xp_auto_mode_label', 'local_subscriptions') . ': ' .
    get_string('commerce_xp_auto_mode_' . $automode, 'local_subscriptions'),
    'mb-2 fw-semibold');
if ($canconfigureauto) {
    $autocard .= html_writer::start_tag('form', ['method' => 'post',
        'action' => $baseurl->out(false), 'class' => 'd-inline-block me-3 mb-2']);
    foreach (['action' => 'autocron', 'enable' => $autoenabled ? 0 : 1,
            'sesskey' => sesskey()] as $name => $value) {
        $autocard .= html_writer::empty_tag('input', ['type' => 'hidden',
            'name' => $name, 'value' => $value]);
    }
    $autocard .= html_writer::empty_tag('input', [
        'type' => 'submit', 'class' => 'btn btn-outline-primary',
        'value' => get_string($autoenabled ? 'commerce_xp_auto_disable' :
            'commerce_xp_auto_enable', 'local_subscriptions')]);
    $autocard .= html_writer::end_tag('form');
    $autocard .= html_writer::start_tag('form', ['method' => 'post',
        'action' => $baseurl->out(false), 'class' => 'd-inline-block mb-2']);
    foreach (['action' => 'automode', 'automode' => $automode === 'simulation' ? 'live' : 'simulation',
            'sesskey' => sesskey()] as $name => $value) {
        $autocard .= html_writer::empty_tag('input', ['type' => 'hidden',
            'name' => $name, 'value' => $value]);
    }
    if ($automode === 'simulation') {
        $autocard .= html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'confirm',
            'value' => 1, 'required' => 'required', 'id' => 'xp-auto-confirm']);
        $autocard .= ' ' . html_writer::tag('label',
            get_string('commerce_xp_auto_confirm_live', 'local_subscriptions'),
            ['for' => 'xp-auto-confirm', 'class' => 'me-2']);
    }
    $autocard .= html_writer::empty_tag('input', ['type' => 'submit',
        'class' => $automode === 'simulation' ? 'btn btn-danger' : 'btn btn-outline-secondary',
        'value' => get_string($automode === 'simulation' ? 'commerce_xp_auto_to_live' :
            'commerce_xp_auto_to_simulation', 'local_subscriptions')]);
    $autocard .= html_writer::end_tag('form');
}
$autocard .= html_writer::div(get_string('commerce_xp_auto_last_run', 'local_subscriptions') . ': ' .
    ($lastautorun ? userdate($lastautorun) : '—'), 'small text-muted');
if ($lastauto) {
    $autocard .= html_writer::div(get_string('commerce_xp_auto_summary', 'local_subscriptions') .
        ': ' . (int)($lastauto['scanned'] ?? 0) . ' ' .
        get_string('commerce_xp_audit_examined', 'local_subscriptions') . '; ' .
        (int)($lastauto['eligible'] ?? 0) . ' ' .
        get_string('commerce_xp_auto_candidates', 'local_subscriptions') . '; ' .
        (int)($lastauto['credited'] ?? 0) . ' ' .
        get_string('commerce_xp_auto_credited', 'local_subscriptions') . '; ' .
        (int)($lastauto['errors'] ?? 0) . ' ' .
        get_string('commerce_xp_auto_errors', 'local_subscriptions'), 'small text-muted');
}
echo html_writer::div($autocard, 'card card-body mb-3');

if (!$assistedenabled) {
    echo html_writer::div(get_string('commerce_xp_assisted_disabled', 'local_subscriptions'),
        'alert alert-warning');
}
echo html_writer::start_div('row g-3 mb-3');
foreach ([
    [$review, 'commerce_xp_audit_metric_review'],
    [$resolved, 'commerce_xp_audit_metric_resolved'],
    [$fixcount, 'commerce_xp_audit_metric_corrections'],
] as [$value, $labelkey]) {
    echo html_writer::start_div('col-md-4');
    echo html_writer::div(html_writer::div((string)$value, 'fs-3 fw-semibold') .
        html_writer::div(get_string($labelkey, 'local_subscriptions'), 'text-muted'),
        'card card-body');
    echo html_writer::end_div();
}
echo html_writer::end_div();
if ($lastrun) {
    $state = (string)$lastrun->state;
    $line = get_string('commerce_xp_audit_last_run', 'local_subscriptions') . ': ' .
        userdate((int)$lastrun->startedat) . ' — ' . s($state) .
        ' (' . (int)$lastrun->examined . ' ' .
        get_string('commerce_xp_audit_examined', 'local_subscriptions') . ')';
    $css = $state === 'ok' ? 'alert alert-light' : 'alert alert-warning';
    if ($state !== 'ok' && $lastrun->details) {
        $line .= ' — ' . s((string)$lastrun->details);
    }
    echo html_writer::div($line, $css);
} else {
    echo html_writer::div(get_string('commerce_xp_audit_never_run', 'local_subscriptions'),
        'alert alert-warning');
}
$tabshtml = '';
foreach ($tabs as $key => $label) {
    $url = new moodle_url($baseurl, ['tab' => $key]);
    $tabshtml .= html_writer::link($url, $label, [
        'class' => 'btn ' . ($key === $tab ? 'btn-primary' : 'btn-outline-secondary'),
        'aria-current' => $key === $tab ? 'page' : null,
    ]);
}
echo html_writer::div($tabshtml, 'd-flex gap-2 mb-3');
if ($auditid) {
    $report = $service->analyse($auditid);
    $ob = $report['audit'];
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_xp_assisted_details', 'local_subscriptions') .
        ' #' . (int)$ob->id, ['class' => 'h5']);
    $user = $report['user'];
    $person = $user ? fullname($user) . ' (#' . (int)$ob->userid . ')' : '#' . (int)$ob->userid;
    $fields = [
        get_string('user') => $person,
        get_string('course') => (string)($report['course']->fullname ?? '#' . $ob->courseid),
        get_string('activity') => (string)($report['activityname'] ?? '#' . $ob->cmid) .
            ' (#' . (int)$ob->cmid . ')',
        get_string('commerce_xp_audit_currency', 'local_subscriptions') =>
            $ob->kind === 'personal' ? '🥐' : '🥮',
        get_string('commerce_xp_audit_points', 'local_subscriptions') => (int)$ob->points,
        get_string('commerce_xp_audit_evidence', 'local_subscriptions') => userdate((int)$ob->evidenceat),
        get_string('commerce_xp_assisted_first_proof', 'local_subscriptions') =>
            $report['proofat'] ? userdate((int)$report['proofat']) .
                ' / H5P #' . (int)$report['proofid'] : '—',
        get_string('commerce_xp_assisted_rule', 'local_subscriptions') => '#' . (int)$ob->ruleid,
        get_string('commerce_xp_assisted_badge', 'local_subscriptions') => (string)$report['badge'],
        get_string('commerce_xp_assisted_existing', 'local_subscriptions') => (int)$report['existing'],
        get_string('status') => (string)$ob->status,
    ];
    if ($ob->kind === 'personal') {
        $fields[get_string('commerce_xp_assisted_balance', 'local_subscriptions')] = $report['xp'];
    }
    $info = new html_table();
    $info->attributes['class'] = 'table table-sm';
    foreach ($fields as $key => $value) {
        $info->data[] = [s((string)$key), s((string)$value)];
    }
    echo html_writer::table($info);
    if ($report['issues']) {
        echo html_writer::div(get_string('commerce_xp_assisted_blocked', 'local_subscriptions') .
            html_writer::alist(array_map('s', $report['issues'])), 'alert alert-warning');
    } else {
        echo html_writer::div(get_string('commerce_xp_assisted_eligible', 'local_subscriptions'),
            'alert alert-success');
    }
    echo html_writer::div(get_string('commerce_xp_assisted_history_warning', 'local_subscriptions'),
        'small text-muted mb-2');
    if ($report['eligible'] && $canrepair && $assistedenabled) {
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl->out(false)]);
        foreach (['action' => 'repair', 'auditid' => $ob->id,
                'proof' => CommerceXpAssistedRepairService::preview_token($report),
                'sesskey' => sesskey()] as $name => $value) {
            echo html_writer::empty_tag('input', ['type' => 'hidden',
                'name' => $name, 'value' => $value]);
        }
        echo html_writer::tag('label', get_string('commerce_xp_assisted_reason', 'local_subscriptions'),
            ['for' => 'xp-assist-reason', 'class' => 'form-label']);
        echo html_writer::tag('textarea', '', ['id' => 'xp-assist-reason', 'name' => 'reason',
            'class' => 'form-control mb-2', 'rows' => 3, 'required' => 'required',
            'minlength' => 12, 'maxlength' => 2000]);
        echo html_writer::start_div('form-check mb-3');
        echo html_writer::empty_tag('input', ['type' => 'checkbox', 'id' => 'xp-assist-confirm',
            'class' => 'form-check-input', 'name' => 'confirm', 'value' => 1, 'required' => 'required']);
        echo html_writer::tag('label', get_string('commerce_xp_assisted_confirm', 'local_subscriptions'),
            ['for' => 'xp-assist-confirm', 'class' => 'form-check-label']);
        echo html_writer::end_div();
        echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-danger',
            'value' => get_string('commerce_xp_assisted_repair', 'local_subscriptions')]);
        echo html_writer::end_tag('form');
    }
    echo html_writer::end_div();
}


if ($tab === 'anomalies') {
    $filters = '';
    foreach (['review', 'resolved', 'all'] as $s) {
        $filters .= html_writer::link(new moodle_url($baseurl,
            ['tab' => 'anomalies', 'status' => $s]),
            get_string('commerce_xp_audit_filter_' . $s, 'local_subscriptions'),
            ['class' => 'btn btn-sm ' . ($status === $s ? 'btn-secondary' : 'btn-outline-secondary')]);
    }
    echo html_writer::div($filters, 'd-flex gap-2 mb-3');
    $conditions = $status === 'all' ? [] : ['status' => $status];
    $total = $DB->count_records('local_subs_xp_audit', $conditions);
    $where = $status === 'all' ? '' : 'WHERE a.status = :status';
    $params = $status === 'all' ? [] : ['status' => $status];
    $rows = $DB->get_records_sql(
        "SELECT a.*, u.firstname, u.lastname, c.fullname AS coursename,
                cm.id AS existscm
           FROM {local_subs_xp_audit} a
           JOIN {user} u ON u.id = a.userid
           LEFT JOIN {course} c ON c.id = a.courseid
           LEFT JOIN {course_modules} cm ON cm.id = a.cmid
           {$where}
       ORDER BY a.lastseen DESC, a.id DESC",
        $params, $page * $perpage, $perpage
    );
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped align-middle';
    $table->head = [get_string('user'), get_string('course'),
        get_string('activity'), get_string('commerce_xp_audit_currency', 'local_subscriptions'),
        get_string('commerce_xp_audit_points', 'local_subscriptions'),
        get_string('commerce_xp_audit_evidence', 'local_subscriptions'),
        get_string('status'), get_string('commerce_xp_audit_reason', 'local_subscriptions'),
        get_string('commerce_xp_assisted_action', 'local_subscriptions')];
    foreach ($rows as $item) {
        $userlink = html_writer::link(new moodle_url('/user/profile.php',
            ['id' => (int)$item->userid]), s(fullname($item)));
        $activity = $item->existscm ? html_writer::link(
            new moodle_url('/mod/h5pactivity/view.php', ['id' => (int)$item->cmid]),
            '#' . (int)$item->cmid) : '#' . (int)$item->cmid;
        $table->data[] = [
            $userlink, s((string)($item->coursename ?? $item->courseid)), $activity,
            $item->kind === 'personal' ? '🥐' : '🥮', (int)$item->points,
            userdate((int)$item->evidenceat),
            s((string)$item->status), s((string)$item->reason),
            html_writer::link(new moodle_url($baseurl, ['auditid' => (int)$item->id]),
                get_string('commerce_xp_assisted_analyse', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-primary']),
        ];
    }
    echo $rows ? html_writer::table($table) : html_writer::div(
        get_string('commerce_xp_audit_empty', 'local_subscriptions'), 'alert alert-light');
    echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url($baseurl,
        ['tab' => 'anomalies', 'status' => $status]));
} else {
    $total = $DB->count_records('local_subs_xp_audit_fix');
    $rows = $DB->get_records_sql(
        "SELECT f.*, u.firstname, u.lastname
           FROM {local_subs_xp_audit_fix} f JOIN {user} u ON u.id = f.userid
       ORDER BY f.timecreated DESC, f.id DESC",
        [], $page * $perpage, $perpage
    );
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped align-middle';
    $table->head = [get_string('date'), get_string('user'), get_string('activity'),
        get_string('commerce_xp_audit_currency', 'local_subscriptions'),
        get_string('commerce_xp_audit_points', 'local_subscriptions'),
        get_string('commerce_xp_audit_mode', 'local_subscriptions'),
        get_string('commerce_xp_audit_reason', 'local_subscriptions')];
    foreach ($rows as $f) {
        $table->data[] = [userdate((int)$f->timecreated), s(fullname($f)),
            '#' . (int)$f->cmid, $f->kind === 'personal' ? '🥐' : '🥮',
            (int)$f->points, s((string)$f->mode), s((string)$f->reason)];
    }
    echo $rows ? html_writer::table($table) : html_writer::div(
        get_string('commerce_xp_audit_history_empty', 'local_subscriptions'), 'alert alert-light');
    echo $OUTPUT->paging_bar($total, $page, $perpage,
        new moodle_url($baseurl, ['tab' => 'history']));
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
