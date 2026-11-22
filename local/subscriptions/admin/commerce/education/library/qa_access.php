<?php
// CampusFR D2.4.3 — QA audit and explicit manual Reader grant revocation; DEV-only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderCommercePublicationService as Publications;
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revisions;
use local_campusreader\local\publication_access as Access;

if (!is_siteadmin((int)$USER->id)) {
    throw new moodle_exception('nopermissions', 'error', '', 'site administrator');
}
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    exit;
}
$id = required_param('id', PARAM_INT);
$revokeerror = '';
$revokesuccess = '';
$grantsuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $action = required_param('action', PARAM_ALPHA);
        if ($action === 'grantreader') {
            if ((int)required_param('confirm', PARAM_INT) !== 1 ||
                    (int)required_param('confirmqa', PARAM_INT) !== 1) {
                throw new \invalid_parameter_exception(get_string('cfr_i18n_qa_double', 'local_subscriptions'));
            }
            $userid = required_param('qauserid', PARAM_INT);
            $reason = required_param('reason', PARAM_RAW_TRIMMED);
            $issued = (new \local_subscriptions\commerce\grant\CommerceManualReaderQaGrantService($DB))
                ->grant($id, $userid, (int)$USER->id, $reason);
            $grantsuccess = get_string('cfr_i18n_qa_rights_02', 'local_subscriptions') . (int)$issued['userid'] .
                get_string('cfr_i18n_qa_rights_03', 'local_subscriptions');
        } else if ($action === 'revokereader') {
            if ((int)required_param('confirm', PARAM_INT) !== 1) {
                throw new \invalid_parameter_exception(get_string('cfr_i18n_qa_rights_01', 'local_subscriptions'));
            }
            $reference = required_param('grantreference', PARAM_ALPHANUMEXT);
            $reason = required_param('reason', PARAM_RAW_TRIMMED);
            $result = (new \local_subscriptions\commerce\grant\CommerceManualReaderGrantRevocationService($DB))
                ->revoke($reference, $id, (int)$USER->id, $reason);
            $revokesuccess = $result['revoked'] === 1
                ? get_string('cfr_i18n_qa_rights_04', 'local_subscriptions')
                : get_string('cfr_i18n_qa_rights_05', 'local_subscriptions');
        } else {
            throw new \invalid_parameter_exception(get_string('cfr_i18n_qa_unknown', 'local_subscriptions'));
        }
    } catch (\Throwable $e) {
        // This page is only available to site administrators; do not expose stack traces.
        $revokeerror = $e instanceof \invalid_parameter_exception || $e instanceof \coding_exception
            ? get_string('cfr_i18n_qa_rights_06', 'local_subscriptions')
            : get_string('cfr_i18n_qa_rights_07', 'local_subscriptions');
        error_log('[CampusFR Reader QA] action refused: ' . get_class($e) . ' ' . $e->getMessage());
    }
}

if ($id < 1 || !Publications::ready()) {
    throw new invalid_parameter_exception('QA edition does not exist');
}
$pub = Publications::get($id);
$sku = strtoupper(trim((string)($pub->productsku ?? '')));
$key = Access::key((string)$pub->slug);
$product = $sku !== '' ? $DB->get_record('local_subs_commerce_product', ['sku' => $sku], '*', IGNORE_MISSING) : false;
$entitlements = $product ? array_values($DB->get_records('local_subs_commerce_prod_ent',
    ['productid' => (int)$product->id])) : [];
$entitlementok = count($entitlements) === 1 &&
    (string)$entitlements[0]->type === 'reader_access' &&
    (string)$entitlements[0]->resourcekey === $key;
$revision = $DB->get_record('local_cr_anthrev',
    ['id' => (int)$pub->revisionid, 'anthologyid' => (int)$pub->anthologyid], '*', IGNORE_MISSING);
$revisionok = $revision && (string)$revision->state === 'approved';
$mediaok = false;
$mediaerror = '';
if ($revisionok) {
    try {
        $snapshot = Revisions::snapshot((int)$pub->anthologyid, (int)$pub->revisionid);
        Revisions::assert_media((int)$pub->revisionid, $snapshot);
        $mediaok = true;
    } catch (\Throwable $e) {
        // No internal path or sensitive exception details in the browser.
        $mediaerror = 'Integrity check failed';
    }
}
$counts = ['active' => 0, 'revoked' => 0, 'expired' => 0, 'other' => 0];
if ($sku !== '') {
    $rows = $DB->get_records_sql(
        'SELECT MIN(id) AS id, status, COUNT(1) AS total FROM {local_subs_commerce_grant}' .
        ' WHERE productsku = :sku AND type = :type AND resourcekey = :resourcekey GROUP BY status',
        ['sku' => $sku, 'type' => 'reader_access', 'resourcekey' => $key]);
    foreach ($rows as $r) {
        $status = strtolower((string)$r->status);
        $bucket = isset($counts[$status]) ? $status : 'other';
        $counts[$bucket] += (int)$r->total;
    }
}
$qaonly = (str_starts_with((string)$pub->slug, 'test-') ||
    str_starts_with((string)$pub->slug, 'qa-') || str_ends_with((string)$pub->slug, '-dev')) &&
    str_starts_with($sku, 'QA_');
$checks = [
    [get_string('cfr_i18n_qa_rights_08', 'local_subscriptions'), $product !== false, $sku !== '' ? $sku : get_string('cfr_i18n_qa_rights_09', 'local_subscriptions')],
    [get_string('cfr_i18n_qa_rights_10', 'local_subscriptions'), $product && (string)$product->type === 'service',
        $product ? (string)$product->type : '—'],
    [get_string('cfr_i18n_qa_rights_11', 'local_subscriptions'), $entitlementok, $key],
    [get_string('cfr_i18n_qa_rights_12', 'local_subscriptions'), (bool)$revisionok,
        $revision ? get_string('cfr_i18n_qa_rights_13', 'local_subscriptions') . (int)$pub->revisionid . ' · ' . (string)$revision->state : get_string('cfr_i18n_qa_rights_14', 'local_subscriptions')],
    [get_string('cfr_i18n_qa_rights_15', 'local_subscriptions'), $mediaok, $mediaok ? get_string('cfr_i18n_qa_rights_16', 'local_subscriptions') : ($mediaerror ?: get_string('cfr_i18n_qa_rights_17', 'local_subscriptions'))],
    [get_string('cfr_i18n_qa_rights_18', 'local_subscriptions'), $qaonly, get_string('cfr_i18n_qa_rights_19', 'local_subscriptions')],
];
$productstatus = $product ? (string)$product->status : get_string('cfr_i18n_qa_rights_20', 'local_subscriptions');
$pubstatus = (string)$pub->status;
$healthy = !in_array(false, array_column($checks, 1), true);
$title = get_string('cfr_i18n_qa_rights_21', 'local_subscriptions') . (string)$pub->slug;
cfr_d21_start($title, get_string('cfr_i18n_qa_rights_22', 'local_subscriptions'), [
    ['label' => get_string('cfr_i18n_qa_rights_23', 'local_subscriptions'), 'url' => cfr_d21_url('publications.php')],
    ['label' => (string)$pub->slug, 'url' => cfr_d21_url('publication.php', ['id' => $id])],
    ['label' => get_string('cfr_i18n_qa_rights_24', 'local_subscriptions'), 'url' => null],
]);
if ($grantsuccess !== '') {
    echo '<div class="alert alert-success" role="status">' . s($grantsuccess) . '</div>';
}
if ($revokeerror !== '') {
    echo '<div class="alert alert-danger" role="alert">' . s($revokeerror) . '</div>';
}
if ($revokesuccess !== '') {
    echo '<div class="alert alert-success" role="status">' . s($revokesuccess) . '</div>';
}
echo '<section class="cfr-reader-editor__panel">';
echo '<h3>' . s(get_string('cfr_i18n_qa_rights_25', 'local_subscriptions')) . '</h3>';
echo '<p>' . s(get_string('cfr_i18n_qa_rights_26', 'local_subscriptions')) . '<strong>' . s($pubstatus) . '</strong>' . s(get_string('cfr_i18n_qa_rights_27', 'local_subscriptions')) . '<strong>' .
    s($productstatus) . '</strong> · SKU : <code>' . s($sku) . '</code></p>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_qa_rights_28', 'local_subscriptions')) . '' .
    '' . s(get_string('cfr_i18n_qa_rights_29', 'local_subscriptions')) . '</p>';
echo '</section>';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_rights_30', 'local_subscriptions')) . '</h3>';
echo '<table class="generaltable"><thead><tr><th>' . s(get_string('cfr_i18n_qa_rights_31', 'local_subscriptions')) . '</th><th>' . s(get_string('cfr_i18n_qa_rights_32', 'local_subscriptions')) . '</th><th>' . s(get_string('cfr_i18n_qa_rights_33', 'local_subscriptions')) . '</th></tr></thead><tbody>';
foreach ($checks as [$label, $ok, $detail]) {
    echo '<tr><td>' . s($label) . '</td><td><strong>' . ($ok ? 'OK' : get_string('cfr_i18n_qa_rights_34', 'local_subscriptions')) .
        '</strong></td><td>' . s((string)$detail) . '</td></tr>';
}
echo '</tbody></table>';
echo '<p>' . s(get_string('cfr_i18n_qa_rights_35', 'local_subscriptions')) . '<strong>' . array_sum($counts) .
    '</strong>' . s(get_string('cfr_i18n_qa_rights_36', 'local_subscriptions')) . '' . $counts['active'] . get_string('cfr_i18n_qa_rights_37', 'local_subscriptions') . $counts['revoked'] .
    get_string('cfr_i18n_qa_rights_38', 'local_subscriptions') . $counts['expired'] . get_string('cfr_i18n_qa_rights_39', 'local_subscriptions') . $counts['other'] . ').</p>';
echo '</section>';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_rights_40', 'local_subscriptions')) . '</h3>';
if (!$healthy) {
    echo '<p>' . s(get_string('cfr_i18n_qa_rights_41', 'local_subscriptions')) . '' .
        '' . s(get_string('cfr_i18n_qa_rights_42', 'local_subscriptions')) . '</p>';
} else if ($pubstatus !== 'published') {
    echo '<p>' . s(get_string('cfr_i18n_qa_rights_43', 'local_subscriptions')) . '' .
        get_string('cfr_i18n_qa_rights_44', 'local_subscriptions') .
        '' . s(get_string('cfr_i18n_qa_rights_45', 'local_subscriptions')) . '</p>';
} else {
    echo '<p>' . s(get_string('cfr_i18n_qa_rights_46', 'local_subscriptions')) . '' .
        '' . s(get_string('cfr_i18n_qa_rights_47', 'local_subscriptions')) . '</p>';
}
echo '<p><strong>' . s(get_string('cfr_i18n_qa_rights_48', 'local_subscriptions')) . '</strong>' . s(get_string('cfr_i18n_qa_rights_49', 'local_subscriptions')) . '' .
    '' . s(get_string('cfr_i18n_qa_rights_50', 'local_subscriptions')) . '</p>';
if ($qaonly) {
    $manualrows = $DB->get_records('local_subs_commerce_grant',
        ['productsku' => $sku, 'type' => 'reader_access', 'resourcekey' => $key],
        'id ASC', 'id,grantreference,beneficiaryuserid,status,metadatajson', 0, 100);
    echo '<h4>' . s(get_string('cfr_i18n_qa_rights_51', 'local_subscriptions')) . '</h4>';
    if (!$manualrows) {
        echo '<p>' . s(get_string('cfr_i18n_qa_rights_52', 'local_subscriptions')) . '</p>';
    } else {
        echo '<table class="generaltable"><thead><tr><th>' . s(get_string('cfr_i18n_qa_rights_53', 'local_subscriptions')) . '</th><th>' . s(get_string('cfr_i18n_qa_rights_54', 'local_subscriptions')) . '</th>' .
            '<th>' . s(get_string('cfr_i18n_qa_rights_55', 'local_subscriptions')) . '</th><th>' . s(get_string('cfr_i18n_qa_rights_action', 'local_subscriptions')) . '</th></tr></thead><tbody>';
        $revoker = new \local_subscriptions\commerce\grant\CommerceManualReaderGrantRevocationService($DB);
        foreach ($manualrows as $g) {
            $eligible = false;
            try { $revoker->preview((string)$g->grantreference, $id); $eligible = true; }
            catch (\Throwable $ignore) { /* Never grant a revocation action for unknown provenance. */ }
            echo '<tr><td>#' . (int)$g->beneficiaryuserid . '</td><td><code>' .
                s((string)$g->grantreference) . '</code></td><td>' . s((string)$g->status) . '</td><td>';
            if ($eligible && (string)$g->status === 'active') {
                echo '<form method="post" class="d-flex flex-wrap align-items-center gap-2">' .
                    '<input type="hidden" name="id" value="' . (int)$id . '">' .
                    '<input type="hidden" name="action" value="revokereader">' .
                    '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">' .
                    '<input type="hidden" name="grantreference" value="' . s((string)$g->grantreference) . '">' .
                    '<input type="text" class="form-control" name="reason" required minlength="4" maxlength="500" ' .
                    'placeholder="' . s(get_string('cfr_i18n_qa_rights_57', 'local_subscriptions')) . '" aria-label="' . s(get_string('cfr_i18n_qa_rights_58', 'local_subscriptions')) . '">' .
                    '<label><input type="checkbox" name="confirm" value="1" required>' . s(get_string('cfr_i18n_qa_rights_59', 'local_subscriptions')) . '</label>' .
                    '<button class="btn btn-outline-danger" type="submit">' . s(get_string('cfr_i18n_qa_rights_60', 'local_subscriptions')) . '</button></form>';
            } else {
                echo get_string('cfr_i18n_qa_rights_61', 'local_subscriptions');
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
}

// The grant action is intentionally unavailable until explicit independent publication/product activation.
if ($qaonly) {
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_rights_62', 'local_subscriptions')) . '</h3>';
    echo '<p>' . s(get_string('cfr_i18n_qa_rights_63', 'local_subscriptions')) . '' .
        get_string('cfr_i18n_qa_rights_64', 'local_subscriptions') .
        '' . s(get_string('cfr_i18n_qa_rights_65', 'local_subscriptions')) . '</p>';
    $qaeligible = $healthy && $pubstatus === 'published' && $productstatus === 'active';
    if ($product && $qaeligible && $DB->record_exists('local_subs_commerce_prod_price',
            ['productid' => (int)$product->id, 'active' => 1])) { $qaeligible = false; }
    if (!$qaeligible) {
        echo '<p><strong>' . s(get_string('cfr_i18n_qa_rights_66', 'local_subscriptions')) . '</strong>' . s(get_string('cfr_i18n_qa_rights_67', 'local_subscriptions')) . '' .
            get_string('cfr_i18n_qa_rights_68', 'local_subscriptions') .
            '' . s(get_string('cfr_i18n_qa_rights_69', 'local_subscriptions')) . '</p>';
    } else {
        echo '<form method="post" class="cfr-reader-editor__panel">' .
            '<input type="hidden" name="id" value="' . (int)$id . '">' .
            '<input type="hidden" name="action" value="grantreader">' .
            '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">' .
            '<label for="qa-reader-user">' . s(get_string('cfr_i18n_qa_rights_70', 'local_subscriptions')) . '</label>' .
            '<input id="qa-reader-user" class="form-control" type="number" min="3" name="qauserid" required>' .
            '<label for="qa-reader-reason">' . s(get_string('cfr_i18n_qa_rights_71', 'local_subscriptions')) . '</label>' .
            '<input id="qa-reader-reason" class="form-control" type="text" minlength="4" maxlength="500" name="reason" required>' .
            '<label><input type="checkbox" name="confirm" value="1" required>' . s(get_string('cfr_i18n_qa_rights_72', 'local_subscriptions')) . '</label> ' .
            '<label><input type="checkbox" name="confirmqa" value="1" required>' . s(get_string('cfr_i18n_qa_rights_73', 'local_subscriptions')) . '</label> ' .
            '<button class="btn btn-outline-primary" type="submit">' . s(get_string('cfr_i18n_qa_rights_74', 'local_subscriptions')) . '</button></form>';
    }
    echo '</section>';
}
echo '<p>' . html_writer::link(cfr_d21_url('publication.php', ['id' => $id]),
    get_string('cfr_i18n_qa_rights_75', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . ' ' .
    html_writer::link(new moodle_url('/local/campusreader/publication.php',
        ['slug' => (string)$pub->slug]), get_string('cfr_i18n_qa_rights_76', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']) . '</p>';
echo '</section>';
cfr_d21_end();
