<?php
/** D2.17.3.1 — Pair staging and strict preflight only (no import). */
declare(strict_types=1);
if (!defined('MOODLE_INTERNAL') || !has_capability('moodle/site:config', \context_system::instance())) {
    throw new \moodle_exception('nopermissions', 'error');
}
use local_subscriptions\crm\library\ReaderUnifiedBundlePlan as Pair;
use local_subscriptions\crm\library\ReaderUnifiedQAImportJob as Job;
global $CFG, $USER, $SESSION, $DB;
if (realpath((string)$CFG->dirroot) !== '/var/www/moodle-dev') {
    throw new \RuntimeException(get_string('cfr_i18n_qa_plan_01', 'local_subscriptions'));
}
$root = make_temp_directory('cfr-unified-qa-stage');
if (!is_dir($root) || is_link($root)) { throw new \RuntimeException(get_string('cfr_i18n_qa_plan_02', 'local_subscriptions')); }
// Staged archives expire after 30 minutes; prune abandoned files after two hours.
foreach (glob($root . '/cfruni-*.zip') ?: [] as $candidate) {
    if (!is_link($candidate) && is_file($candidate) &&
        preg_match('/\Acfruni-[0-9]+-[a-f0-9]{32}-(?:calendar|anthology)\.zip\z/D', basename($candidate)) &&
        filemtime($candidate) < time() - 7200) { @unlink($candidate); }
}


function cfr_d21731_clear(): void {
    global $SESSION;
    $v = $SESSION->cfr_d21731_stage ?? null;
    if (is_array($v)) {
        $base = realpath(make_temp_directory('cfr-unified-qa-stage'));
        foreach (['calendar','anthology'] as $kind) {
            $path = $v['files'][$kind] ?? null;
            if (is_string($path) && is_string($base) && realpath(dirname($path)) === $base &&
                preg_match('/\Acfruni-[0-9]+-[a-f0-9]{32}-(?:calendar|anthology)\.zip\z/D', basename($path)) &&
                !is_link($path) && is_file($path)) { @unlink($path); }
        }
    }
    unset($SESSION->cfr_d21731_stage);
}
function cfr_d21731_stage(): array {
    global $SESSION, $USER;
    $v = $SESSION->cfr_d21731_stage ?? null;
    if (!is_array($v) || ($v['userid'] ?? null) !== (int)$USER->id ||
        !is_int($v['at'] ?? null) || $v['at'] < time() - 1800 || $v['at'] > time() + 30 ||
        !is_array($v['files'] ?? null) || !is_array($v['sha'] ?? null)) {
        throw new \DomainException(get_string('cfr_i18n_qa_plan_03', 'local_subscriptions'));
    }
    $root = realpath(make_temp_directory('cfr-unified-qa-stage'));
    foreach (['calendar','anthology'] as $kind) {
        $path = $v['files'][$kind] ?? null; $sha = $v['sha'][$kind] ?? null;
        if (!is_string($root) || !is_string($path) || !is_string($sha) ||
            !preg_match('/\A[a-f0-9]{64}\z/D', $sha) ||
            !preg_match('/\Acfruni-[0-9]+-[a-f0-9]{32}-' . $kind . '\.zip\z/D', basename($path)) ||
            realpath(dirname($path)) !== $root || !is_file($path) || is_link($path) ||
            filesize($path) < 100 || filesize($path) > 2147483648 ||
            !hash_equals($sha, (string)hash_file('sha256', $path))) {
            throw new \DomainException(get_string('cfr_i18n_qa_plan_04', 'local_subscriptions'));
        }
    }
    return $v;
}
function cfr_d21731_upload(string $kind, string $root, string $token): string {
    global $USER;
    $file = $_FILES['zip_' . $kind] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK ||
        !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name']) ||
        (int)($file['size'] ?? 0) < 100 || (int)($file['size'] ?? 0) > 2147483648) {
        throw new \DomainException(get_string('cfr_i18n_qa_plan_05', 'local_subscriptions') . $kind . get_string('cfr_i18n_qa_plan_06', 'local_subscriptions'));
    }
    $path = $root . '/cfruni-' . (int)$USER->id . '-' . $token . '-' . $kind . '.zip';
    if (file_exists($path) || is_link($path) || !move_uploaded_file($file['tmp_name'], $path)) {
        throw new \RuntimeException(get_string('cfr_i18n_qa_plan_07', 'local_subscriptions'));
    }
    if (!chmod($path, 0600)) { @unlink($path); throw new \RuntimeException(get_string('cfr_i18n_qa_plan_08', 'local_subscriptions')); }
    return $path;
}

$error = ''; $success = ''; $pair = null; $plan = null;
$calendarSlug = ''; $anthologySlug = ''; $courseid = 0;
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        require_sesskey();
        $action = optional_param('operation', '', PARAM_ALPHA);
        if ($action === 'clear') {
            cfr_d21731_clear(); $success = get_string('cfr_i18n_qa_plan_09', 'local_subscriptions');
        } else if ($action === 'stage') {
            cfr_d21731_clear();
            $token = bin2hex(random_bytes(16));
            $paths = [];
            try {
                $paths['calendar'] = cfr_d21731_upload('calendar', $root, $token);
                $paths['anthology'] = cfr_d21731_upload('anthology', $root, $token);
                $pair = Pair::inspect_pair($paths['calendar'], $paths['anthology']);
                $SESSION->cfr_d21731_stage = [
                    'userid' => (int)$USER->id, 'at' => time(), 'files' => $paths,
                    'sha' => ['calendar' => $pair['calendar_sha256'],
                        'anthology' => $pair['anthology_sha256']], 'plan' => null,
                ];
                $success = get_string('cfr_i18n_qa_plan_10', 'local_subscriptions');
            } catch (\Throwable $e) {
                foreach ($paths as $path) { if (is_file($path) && !is_link($path)) { @unlink($path); } }
                throw $e;
            }
        } else if ($action === 'plan' || $action === 'execute') {
            $v = cfr_d21731_stage();
            $pair = Pair::inspect_pair($v['files']['calendar'], $v['files']['anthology']);
            $calendarSlug = required_param('calendarslug', PARAM_RAW_TRIMMED);
            $anthologySlug = required_param('anthologyslug', PARAM_RAW_TRIMMED);
            $courseid = optional_param('courseid', 0, PARAM_INT);
            $plan = Pair::destination_plan($pair, $calendarSlug, $anthologySlug, $courseid);
            $identity = hash('sha256', implode('|', [
                $v['sha']['calendar'], $v['sha']['anthology'], $calendarSlug, $anthologySlug, (string)$courseid,
            ]));
            if ($action === 'plan') {
                $SESSION->cfr_d21731_stage['plan'] = $plan['ready'] ? $identity : null;
            } else {
                if (!$plan['ready'] || !is_string($v['plan'] ?? null) ||
                    !hash_equals($v['plan'], $identity) ||
                    optional_param('confirmqa', 0, PARAM_INT) !== 1 ||
                    optional_param('confirmnoexport', 0, PARAM_INT) !== 1) {
                    throw new \DomainException(get_string('cfr_i18n_qa_plan_11', 'local_subscriptions'));
                }
                if (!is_siteadmin((int)$USER->id)) {
                    throw new \DomainException(get_string('cfr_i18n_qa_plan_12', 'local_subscriptions'));
                }
                $jobid = Job::enqueue($v, $plan, (int)$USER->id);
                $SESSION->cfr_d21732_last_job = $jobid;
                cfr_d21731_clear();
                $pair = null; $plan = null;
                $success = get_string('cfr_i18n_qa_plan_13', 'local_subscriptions');
            }
        } else if ($action === 'resume') {
            $id = (string)($SESSION->cfr_d21732_last_job ?? '');
            if (optional_param('confirmresume', 0, PARAM_INT) !== 1) {
                throw new \DomainException(get_string('cfr_i18n_qa_plan_14', 'local_subscriptions'));
            }
            Job::queue_resume($id, (int)$USER->id);
            $success = get_string('cfr_i18n_qa_plan_15', 'local_subscriptions');
        } else { throw new \DomainException(get_string('cfr_i18n_qa_plan_16', 'local_subscriptions')); }
    }
    if ($pair === null && !empty($SESSION->cfr_d21731_stage)) {
        $v = cfr_d21731_stage();
        $pair = Pair::inspect_pair($v['files']['calendar'], $v['files']['anthology']);
    }
} catch (\Throwable $e) {
    $error = $e instanceof \DomainException || $e instanceof \InvalidArgumentException ?
        $e->getMessage() : get_string('cfr_i18n_qa_plan_17', 'local_subscriptions');
    if (!empty($SESSION->cfr_d21731_stage)) {
        try { cfr_d21731_stage(); } catch (\Throwable $ignored) { cfr_d21731_clear(); }
    }
}
$jobstatus = null;
if (!empty($SESSION->cfr_d21732_last_job)) {
    try { $jobstatus = Job::status_for_user((string)$SESSION->cfr_d21732_last_job, (int)$USER->id); }
    catch (\Throwable $ignored) { unset($SESSION->cfr_d21732_last_job); }
}
if ($pair !== null) {
    $calbase = (string)$pair['calendar_manifest']['object']['slug'];
    if ($calendarSlug === '') {
        $calendarSlug = substr('qa-u-' . substr($pair['calendar_sha256'], 0, 8) . '-' . $calbase, 0, 64);
        $calendarSlug = rtrim($calendarSlug, '-');
    }
    if ($anthologySlug === '') {
        $anthologySlug = substr('qa-u-' . substr($pair['anthology_sha256'], 0, 8) . '-recueil', 0, 64);
    }
}
$title = get_string('cfr_i18n_qa_plan_18', 'local_subscriptions');
cfr_d21_start($title, get_string('cfr_i18n_qa_plan_19', 'local_subscriptions'),
    [['label' => get_string('cfr_i18n_qa_plan_20', 'local_subscriptions'), 'url' => cfr_d21_url('bundles.php')], ['label' => $title, 'url' => null]]);
echo '<div class="alert alert-warning"><strong>' . s(get_string('cfr_i18n_qa_plan_21', 'local_subscriptions')) . '</strong> ' . s(get_string('cfr_i18n_qa_plan_22', 'local_subscriptions')) . ''
    . s(get_string('cfr_i18n_qa_plan_23', 'local_subscriptions')) . '</div>';
if ($error) { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
if ($success) { echo '<div class="alert alert-success" role="status">' . s($success) . '</div>'; }
if ($jobstatus !== null) {
    $status = (string)$jobstatus['status'];
    $isbad = in_array($status, ['failed','partial'], true);
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_plan_24', 'local_subscriptions')) . '</h3>';
    echo '<div class="alert alert-' . ($isbad ? 'warning' : ($status === 'complete' ? 'success' : 'info')) . '">';
    echo '<strong>' . s(strtoupper($status)) . '</strong> — ' . s((string)($jobstatus['message'] ?? '')) . '</div>';
    echo '<ul><li>' . s(get_string('cfr_i18n_qa_plan_25', 'local_subscriptions')) . '' . s((string)($jobstatus['phase'] ?? '')) . '</li>';
    echo '<li>' . s(get_string('cfr_i18n_qa_plan_26', 'local_subscriptions')) . '' . (int)$jobstatus['count'] . '</li>';
    echo '<li>' . s(get_string('cfr_i18n_qa_plan_27', 'local_subscriptions')) . '' . (int)$jobstatus['collection'] . '</li>';
    echo '<li>' . s(get_string('cfr_i18n_qa_plan_28', 'local_subscriptions')) . '' . (int)$jobstatus['anthology'] . '</li>';
    echo '<li>' . s(get_string('cfr_i18n_qa_plan_29', 'local_subscriptions')) . '' . (int)$jobstatus['revision'] . '</li></ul>';
    echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('bundles.php', ['view'=>'unifiedplan'])->out(false))
        . '">' . s(get_string('cfr_i18n_qa_plan_30', 'local_subscriptions')) . '</a></p>';
    if ($status === 'complete') {
        echo '<p><a class="btn btn-primary" href="' . s(cfr_d21_url('bundles.php', [
            'view'=>'unified','collectionid'=>(int)$jobstatus['collection'],
            'anthologyid'=>(int)$jobstatus['anthology'],
        ])->out(false)) . '">' . s(get_string('cfr_i18n_qa_plan_31', 'local_subscriptions')) . '</a></p>';
    } elseif ($status === 'partial' && (int)($jobstatus['attempts'] ?? 0) < 2) {
        echo '<p>' . s(get_string('cfr_i18n_qa_plan_32', 'local_subscriptions')) . ''
            . s(get_string('cfr_i18n_qa_plan_33', 'local_subscriptions')) . '</p>';
        echo '<form method="post" action="' . s(cfr_d21_url('bundles.php', ['view'=>'unifiedplan'])->out(false)) . '">';
        echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
        echo '<input type="hidden" name="operation" value="resume">';
        echo '<label><input type="checkbox" name="confirmresume" value="1" required> '
            . s(get_string('cfr_i18n_qa_plan_34', 'local_subscriptions')) . '</label>';
        echo '<p><button class="btn btn-outline-primary" type="submit">' . s(get_string('cfr_i18n_qa_plan_35', 'local_subscriptions')) . '</button></p></form>';
    }
    echo '</section>';
}
if ($pair === null) {
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_plan_36', 'local_subscriptions')) . '</h3>';
    echo '<p>' . s(get_string('cfr_i18n_qa_plan_37', 'local_subscriptions')) . '</p>';
    echo '<form method="post" enctype="multipart/form-data" action="' . s(cfr_d21_url('bundles.php', ['view'=>'unifiedplan'])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="operation" value="stage">';
    foreach (['calendar' => get_string('cfr_i18n_qa_plan_38', 'local_subscriptions'), 'anthology' => get_string('cfr_i18n_qa_plan_39', 'local_subscriptions')] as $kind => $label) {
        echo '<p><label for="d21731-' . $kind . '">' . s($label) . '</label>';
        echo '<input id="d21731-' . $kind . '" name="zip_' . $kind . '" type="file" accept=".zip,application/zip" required class="form-control"></p>';
    }
    echo '<button class="btn btn-primary" type="submit">' . s(get_string('cfr_i18n_qa_plan_40', 'local_subscriptions')) . '</button></form></section>';
} else {
    echo '<div class="alert alert-success" role="status"><strong>PAIR_VALIDATED</strong> — '
        . (int)$pair['stories'] . s(get_string('cfr_i18n_qa_plan_41', 'local_subscriptions')) . '</div>';
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_qa_plan_42', 'local_subscriptions')) . '</h3><ul>';
    foreach ([get_string('cfr_i18n_qa_plan_43', 'local_subscriptions') => $pair['stories'], get_string('cfr_i18n_qa_plan_44', 'local_subscriptions') => $pair['slots'],
        get_string('cfr_i18n_qa_plan_45', 'local_subscriptions') => $pair['chapters'], get_string('cfr_i18n_qa_plan_46', 'local_subscriptions') => $pair['media_frozen'],
        get_string('cfr_i18n_qa_plan_47', 'local_subscriptions') => substr($pair['calendar_sha256'], 0, 16) . '…',
        get_string('cfr_i18n_qa_plan_48', 'local_subscriptions') => substr($pair['anthology_sha256'], 0, 16) . '…'] as $label => $value) {
        echo '<li>' . s($label) . ' : <strong>' . s((string)$value) . '</strong></li>';
    }
    echo '</ul><form method="post" action="' . s(cfr_d21_url('bundles.php', ['view'=>'unifiedplan'])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    // Operation is carried by the clicked submit button (plan or execute).
    echo '<label for="d21731calslug">' . s(get_string('cfr_i18n_qa_plan_49', 'local_subscriptions')) . '</label>';
    echo '<input class="form-control" type="text" name="calendarslug" id="d21731calslug" value="' . s($calendarSlug) . '" maxlength="64" required pattern="[a-z0-9]+(-[a-z0-9]+)*">';
    echo '<label for="d21731anthslug">' . s(get_string('cfr_i18n_qa_plan_50', 'local_subscriptions')) . '</label>';
    echo '<input class="form-control" type="text" name="anthologyslug" id="d21731anthslug" value="' . s($anthologySlug) . '" maxlength="64" required pattern="[a-z0-9]+(-[a-z0-9]+)*">';
    echo '<label for="d21731course">' . s(get_string('cfr_i18n_qa_plan_51', 'local_subscriptions')) . '</label>';
    echo '<select class="form-control" name="courseid" id="d21731course"><option value="0">' . s(get_string('cfr_i18n_qa_plan_52', 'local_subscriptions')) . '</option>';
    $all = $DB->get_records_select('course', 'id > :one', ['one'=>1], 'id ASC', 'id,shortname,fullname,visible', 0, 1000);
    foreach ($all as $c) {
        if ((int)$c->visible !== 0 || !preg_match('/qa|test/i', $c->shortname . ' ' . $c->fullname) ||
            $DB->record_exists('local_cr_collection', ['courseid'=>(int)$c->id]) ||
            $DB->record_exists_sql('SELECT 1 FROM {user_enrolments} ue JOIN {enrol} e ON e.id=ue.enrolid WHERE e.courseid=:cid', ['cid'=>(int)$c->id])) { continue; }
        echo '<option value="' . (int)$c->id . '"' . ($courseid === (int)$c->id ? ' selected' : '') . '>'
            . s($c->fullname . ' (#' . $c->id . ')') . '</option>';
    }
    echo '</select><p><button class="btn btn-outline-primary" type="submit" name="operation" value="plan">' . s(get_string('cfr_i18n_qa_plan_53', 'local_subscriptions')) . '</button></p>';
    if ($plan !== null) {
        echo '<div class="alert alert-' . ($plan['ready'] ? 'success' : 'warning') . '"><strong>'
            . ($plan['ready'] ? 'READY_FOR_UNIFIED_QA_IMPORT_PLAN' : 'BLOCKED') . '</strong>';
        foreach ($plan['problems'] as $problem) { echo '<p>' . s($problem) . '</p>'; }
        echo '</div>';
    }
    if ($plan !== null && $plan['ready']) {
        echo '<p><label><input type="checkbox" name="confirmqa" value="1"> '
            . s(get_string('cfr_i18n_qa_plan_54', 'local_subscriptions')) . '</label></p>';
        echo '<p><label><input type="checkbox" name="confirmnoexport" value="1"> '
            . s(get_string('cfr_i18n_qa_plan_55', 'local_subscriptions')) . '</label></p>';
        echo '<p><button class="btn btn-primary" type="submit" name="operation" value="execute">'
            . s(get_string('cfr_i18n_qa_plan_56', 'local_subscriptions')) . '</button></p>';
    }
    echo '</form>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_qa_plan_57', 'local_subscriptions')) . ''
        . get_string('cfr_i18n_qa_plan_58', 'local_subscriptions')
        . s(get_string('cfr_i18n_qa_plan_59', 'local_subscriptions')) . '</p>';
    echo '</section><form method="post" action="' . s(cfr_d21_url('bundles.php', ['view'=>'unifiedplan'])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">'
        . '<button class="btn btn-outline-secondary" type="submit" name="operation" value="clear">' . s(get_string('cfr_i18n_qa_plan_60', 'local_subscriptions')) . '</button></form>';
}
echo '<p><a class="btn btn-outline-primary" href="' . s(cfr_d21_url('bundles.php', ['view'=>'unified'])->out(false)) . '">' . s(get_string('cfr_i18n_qa_plan_61', 'local_subscriptions')) . '</a> '
    . '<a class="btn btn-outline-secondary" href="' . s(cfr_d21_url('bundles.php')->out(false)) . '">' . s(get_string('cfr_i18n_qa_plan_62', 'local_subscriptions')) . '</a></p>';
cfr_d21_end();
