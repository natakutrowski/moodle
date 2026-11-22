<?php
/** D2.17.3.0 — CRM inspection only, called by guarded bundles.php. */
declare(strict_types=1);
if (!defined('MOODLE_INTERNAL') || !has_capability('moodle/site:config', \context_system::instance())) {
    throw new \moodle_exception('nopermissions', 'error');
}
global $CFG;
if (realpath((string)$CFG->dirroot) !== '/var/www/moodle-dev') {
    throw new \RuntimeException(get_string('cfr_fix_unified_qa_audit_01', 'local_subscriptions'));
}
use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anthology;
use local_subscriptions\crm\library\ReaderUnifiedQAReadiness as Readiness;

$qaCollections = [];
foreach (Editorial::collections() as $c) {
    if ((int)$c->published === 0 && preg_match('/(?:^|-)(?:qa|test)(?:-|$)/i', (string)$c->slug)) {
        $qaCollections[(int)$c->id] = $c;
    }
}
$qaAnthologies = [];
foreach (Anthology::list_all() as $a) {
    if ((string)$a->status === 'draft' && preg_match('/(?:^|-)(?:qa|test)(?:-|$)/i', (string)$a->slug)) {
        $qaAnthologies[(int)$a->id] = $a;
    }
}
$requestedCollection = optional_param('collectionid', 9, PARAM_INT);
$requestedAnthology = optional_param('anthologyid', 6, PARAM_INT);
$collectionid = isset($qaCollections[$requestedCollection]) ? $requestedCollection : $requestedCollection;
$anthologyid = isset($qaAnthologies[$requestedAnthology]) ? $requestedAnthology : $requestedAnthology;
$report = null; $message = '';
if (isset($qaCollections[$collectionid], $qaAnthologies[$anthologyid])) {
    try { $report = Readiness::inspect($collectionid, $anthologyid); }
    catch (\DomainException | \InvalidArgumentException $e) { $message = $e->getMessage(); }
    catch (\Throwable $e) { $message = get_string('cfr_i18n_qa_audit_22', 'local_subscriptions'); }
} else { $message = get_string('cfr_i18n_qa_audit_23', 'local_subscriptions'); }
$title = get_string('cfr_i18n_qa_audit_24', 'local_subscriptions');
cfr_d21_start($title, get_string('cfr_i18n_qa_audit_25', 'local_subscriptions'),
    [['label' => get_string('cfr_i18n_qa_audit_26', 'local_subscriptions'), 'url' => cfr_d21_url('bundles.php')],
     ['label' => $title, 'url' => null]]);
echo '<p class="alert alert-warning"><strong>' . s(get_string('cfr_i18n_qa_audit_27', 'local_subscriptions')) . '</strong> ' . s(get_string('cfr_i18n_qa_audit_01', 'local_subscriptions')) . ''
    . s(get_string('cfr_i18n_qa_audit_02', 'local_subscriptions')) . '</p>';
echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_audit_03', 'local_subscriptions')) . '</h3>';
echo '<form method="get" action="' . s(cfr_d21_url('bundles.php')->out(false)) . '">';
echo '<input type="hidden" name="view" value="unified">';
echo '<label for="d2173collection">' . s(get_string('cfr_i18n_qa_audit_04', 'local_subscriptions')) . '</label>';
echo '<select class="form-control" id="d2173collection" name="collectionid" required>';
foreach ($qaCollections as $c) {
    echo '<option value="' . (int)$c->id . '"' . ((int)$c->id === $collectionid ? ' selected' : '') . '>'
        . s($c->title . ' — ' . $c->slug . ' (#' . $c->id . ')') . '</option>';
}
echo '</select><label for="d2173anthology">' . s(get_string('cfr_i18n_qa_audit_05', 'local_subscriptions')) . '</label>';
echo '<select class="form-control" id="d2173anthology" name="anthologyid" required>';
foreach ($qaAnthologies as $a) {
    echo '<option value="' . (int)$a->id . '"' . ((int)$a->id === $anthologyid ? ' selected' : '') . '>'
        . s($a->title . ' — ' . $a->slug . ' (#' . $a->id . ')') . '</option>';
}
echo '</select><p><button class="btn btn-primary" type="submit">' . s(get_string('cfr_i18n_qa_audit_06', 'local_subscriptions')) . '</button></p></form></section>';
if ($report !== null) {
    echo '<div class="alert alert-success" role="status"><strong>READY_FOR_QA_EDITORIAL_REVIEW</strong>'
        . s(get_string('cfr_i18n_qa_audit_28', 'local_subscriptions')) . '</div>';
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_audit_07', 'local_subscriptions')) . '</h3><ul>';
    foreach ([
        get_string('cfr_i18n_qa_audit_08', 'local_subscriptions') => '#' . $report['courseid'],
        get_string('cfr_i18n_qa_audit_09', 'local_subscriptions') => '#' . $report['collectionid'] . ' / #' . $report['anthologyid'],
        get_string('cfr_i18n_qa_audit_10', 'local_subscriptions') => $report['books'],
        get_string('cfr_i18n_qa_audit_11', 'local_subscriptions') => $report['slots'] . ' / ' . $report['chapters'],
        get_string('cfr_i18n_qa_audit_12', 'local_subscriptions') => $report['pages'],
        get_string('cfr_i18n_qa_audit_13', 'local_subscriptions') => $report['storymedia'],
        get_string('cfr_i18n_qa_audit_14', 'local_subscriptions') => $report['frozenmedia'],
        get_string('cfr_i18n_qa_audit_15', 'local_subscriptions') => '#' . $report['revisionid'],
    ] as $label => $value) {
        echo '<li>' . s((string)$label) . ' : <strong>' . s((string)$value) . '</strong></li>';
    }
    echo '</ul><p>' . s(get_string('cfr_i18n_qa_audit_16', 'local_subscriptions')) . ''
        . s(get_string('cfr_i18n_qa_audit_17', 'local_subscriptions')) . '</p>';
    echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('collection.php', ['id' => $collectionid])->out(false))
        . '">' . s(get_string('cfr_i18n_qa_audit_18', 'local_subscriptions')) . '</a> '
        . '<a class="btn btn-outline-primary" href="' . s(cfr_d21_url('anthology.php', ['id' => $anthologyid])->out(false))
        . '">' . s(get_string('cfr_i18n_qa_audit_19', 'local_subscriptions')) . '</a></p></section>';
} else {
    echo '<div class="alert alert-danger" role="alert"><strong>BLOCKED</strong> — ' . s($message) . '</div>';
}
echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('bundles.php', ['view' => 'unifiedplan'])->out(false))
    . '">' . s(get_string('cfr_i18n_qa_audit_20', 'local_subscriptions')) . '</a></p>';
echo '<p><a class="btn btn-outline-secondary" href="' . s(cfr_d21_url('bundles.php')->out(false))
    . '">' . s(get_string('cfr_i18n_qa_audit_21', 'local_subscriptions')) . '</a></p>';
cfr_d21_end();
