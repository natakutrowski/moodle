<?php
// CampusFR C2.2c.4 — manual Native grant revocation; admins only.
declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\grant\CommerceManualCourseGrantRevocationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
// The service itself enforces siteadmin; hide sensitive grant details from every other role.
if (!is_siteadmin((int)$USER->id)) {
    throw new moodle_exception('nopermissions', 'error', '', 'site administrator');
}
// CampusFR C2.2c.5.3: explicit loading for legacy CamelCase Commerce classes.
// Moodle's automatic class lookup does not resolve these filenames in HTTP.
// Load only after the site-admin gate; keep all preview/revoke guards unchanged.
require_once($CFG->dirroot . '/local/subscriptions/classes/commerce/persistence/CommercePersistenceSchema.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/commerce/grant/CommerceManualProductGrantService.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/commerce/grant/CommerceManualCourseGrantRevocationService.php');
$id = required_param('id', PARAM_INT);
$grant = $DB->get_record('local_subs_commerce_grant', ['id' => $id], '*', MUST_EXIST);
$service = new CommerceManualCourseGrantRevocationService($DB);
$url = new moodle_url('/local/subscriptions/admin/commerce/grants/revoke_manual.php', ['id' => $id]);
$returnurl = new moodle_url('/local/subscriptions/admin/commerce/grants/view.php', ['id' => $id]);
$title = get_string('commerce_manual_revoke_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-manual-native-revoke');

// Only the precisely scoped manual source can be shown or submitted.
$preview = null;
$problem = null;
try {
    $preview = $service->preview((string)$grant->grantreference);
    if ((string)$preview['grantreference'] !== (string)$grant->grantreference
            || (int)$preview['userid'] !== (int)$grant->beneficiaryuserid) {
        throw new coding_exception('Manual grant preview mismatch.');
    }
} catch (Throwable $e) {
    // Unsupported grant is never revocable from this screen, including on POST.
    $problem = get_string('commerce_manual_revoke_unsupported', 'local_subscriptions');
}
$metadata = json_decode((string)$grant->metadatajson, true);
$metadata = is_array($metadata) ? $metadata : [];
$revocation = $metadata['rights_revocation'] ?? null;
$isrevoked = (string)$grant->status === 'revoked';
$canrevoke = $preview !== null && !$isrevoked
    && in_array((string)$grant->status, ['active', 'granted', 'completed'], true);

if (data_submitted()) {
    require_sesskey();
    if (!$canrevoke) {
        throw new moodle_exception('commerce_manual_revoke_unsupported', 'local_subscriptions');
    }
    $confirmed = optional_param('confirmrevoke', 0, PARAM_BOOL);
    $typed = trim(optional_param('confirmtext', '', PARAM_RAW_TRIMMED));
    $reason = trim(optional_param('reason', '', PARAM_RAW_TRIMMED));
    $postedreference = optional_param('grantreference', '', PARAM_RAW_TRIMMED);
    $expected = 'REVOQUER ' . $id;
    if (!$confirmed || $typed !== $expected
            || !hash_equals((string)$grant->grantreference, $postedreference)) {
        redirect($url, get_string('commerce_manual_revoke_confirm_error', 'local_subscriptions'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    if (strlen($reason) < 5 || strlen($reason) > 500) {
        redirect($url, get_string('commerce_manual_revoke_reason_error', 'local_subscriptions'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    // Locks, transaction, provenance and independent access checks are in the certified service.
    try {
        $result = $service->revoke((string)$grant->grantreference, (int)$USER->id, $reason);
    } catch (Throwable $e) {
        debugging('Manual Native revoke failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        redirect($url, get_string('commerce_manual_revoke_failed', 'local_subscriptions'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
    redirect($returnurl,
        get_string('commerce_manual_revoke_success', 'local_subscriptions', (int)$result['revoked']),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

$user = $DB->get_record('user', ['id' => (int)$grant->beneficiaryuserid, 'deleted' => 0],
    'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename,email',
    IGNORE_MISSING);
$clientlabel = $user ? fullname($user) : (string)$grant->beneficiaryemail;

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_grants_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/index.php')],
    ['label' => '#' . $id, 'url' => $returnurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title,
    get_string('commerce_manual_revoke_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::GRANTS);

$details = [
    ['label' => get_string('commerce_manual_revoke_beneficiary', 'local_subscriptions'),
        'value' => $clientlabel],
    ['label' => get_string('email'), 'value' => (string)$grant->beneficiaryemail],
    ['label' => get_string('commerce_manual_revoke_course', 'local_subscriptions'),
        'value' => $preview === null ? '—' : (string)$preview['courseid']],
    ['label' => get_string('commerce_manual_revoke_status', 'local_subscriptions'),
        'value' => (string)$grant->status],
    ['label' => get_string('commerce_manual_revoke_enrolment', 'local_subscriptions'),
        'value' => $preview === null ? '—' : (string)$preview['enrolmentstatus']],
    ['label' => get_string('commerce_manual_revoke_reference', 'local_subscriptions'),
        'value' => (string)$grant->grantreference],
];
$items = '';
foreach ($details as $detail) {
    $items .= html_writer::tag('dt', s($detail['label']), ['class' => 'col-sm-4 fw-semibold']);
    $items .= html_writer::tag('dd', s($detail['value']), ['class' => 'col-sm-8']);
}
echo html_writer::div(html_writer::tag('dl', $items, ['class' => 'row mb-0']),
    'card card-body mb-3');

if ($isrevoked) {
    echo $OUTPUT->notification(get_string('commerce_manual_revoke_already', 'local_subscriptions'),
        \core\output\notification::NOTIFY_INFO);
    if (is_array($revocation) && ($revocation['source'] ?? '') === 'admin_explicit_manual_revocation') {
        $auditrows = [
            ['label' => get_string('commerce_manual_revoke_audit_date', 'local_subscriptions'),
                'value' => userdate((int)($revocation['revokedat'] ?? 0))],
            ['label' => get_string('commerce_manual_revoke_audit_actor', 'local_subscriptions'),
                'value' => (string)($revocation['revokedbyuserid'] ?? '')],
            ['label' => get_string('commerce_manual_revoke_audit_reason', 'local_subscriptions'),
                'value' => (string)($revocation['reason'] ?? '')],
        ];
        $audithtml = '';
        foreach ($auditrows as $row) {
            $audithtml .= html_writer::tag('dt', s($row['label']), ['class' => 'col-sm-4']);
            $audithtml .= html_writer::tag('dd', s($row['value']), ['class' => 'col-sm-8']);
        }
        echo html_writer::div(html_writer::tag('dl', $audithtml, ['class' => 'row mb-0']),
            'card card-body mb-3');
    }
} else if (!$canrevoke) {
    echo $OUTPUT->notification($problem ?: get_string('commerce_manual_revoke_unsupported',
        'local_subscriptions'), \core\output\notification::NOTIFY_WARNING);
} else {
    echo $OUTPUT->notification(get_string('commerce_manual_revoke_warning', 'local_subscriptions'),
        \core\output\notification::NOTIFY_WARNING);
    $expected = 'REVOQUER ' . $id;
    $form = html_writer::start_tag('form', [
        'method' => 'post', 'action' => $url->out(false), 'class' => 'mt-3',
    ]);
    foreach (['id' => (string)$id, 'sesskey' => sesskey(),
        'grantreference' => (string)$grant->grantreference] as $name => $value) {
        $form .= html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => $name, 'value' => $value,
        ]);
    }
    $form .= html_writer::tag('label', get_string('commerce_rights_revoke_reason', 'local_subscriptions'),
        ['for' => 'cfr-manual-reason', 'class' => 'form-label fw-semibold']);
    $form .= html_writer::tag('textarea', '', [
        'id' => 'cfr-manual-reason', 'name' => 'reason', 'rows' => 3, 'minlength' => 5,
        'maxlength' => 500, 'required' => 'required', 'class' => 'form-control mb-3',
    ]);
    $form .= html_writer::div(html_writer::checkbox('confirmrevoke', '1', false,
        get_string('commerce_manual_revoke_confirmation', 'local_subscriptions'),
        ['id' => 'cfr-manual-confirm', 'required' => 'required']), 'mb-3');
    $form .= html_writer::tag('label',
        get_string('commerce_manual_revoke_type', 'local_subscriptions', $expected),
        ['for' => 'cfr-manual-type', 'class' => 'form-label fw-semibold']);
    $form .= html_writer::empty_tag('input', [
        'id' => 'cfr-manual-type', 'name' => 'confirmtext', 'type' => 'text',
        'required' => 'required', 'autocomplete' => 'off', 'class' => 'form-control mb-3',
        'placeholder' => $expected,
    ]);
    $form .= html_writer::tag('button',
        get_string('commerce_manual_revoke_submit', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-danger']);
    $form .= ' ' . html_writer::link($returnurl, get_string('cancel'),
        ['class' => 'btn btn-outline-secondary']);
    $form .= html_writer::end_tag('form');
    echo html_writer::div($form, 'card card-body mb-3');
}
echo html_writer::link($returnurl, get_string('back'), ['class' => 'btn btn-outline-secondary']);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
