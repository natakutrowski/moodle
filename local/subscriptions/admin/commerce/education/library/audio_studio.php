<?php
// CampusFR D2.9a: private human-narration phrase alignment studio, DEV-only.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');

use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderNarrationAlignmentService as Alignment;

$id = required_param('id', PARAM_INT);
$book = Editorial::book($id);
$collection = Editorial::collection((int)$book->collectionid);
$locked = (int)$book->published !== 0 || (int)$collection->published !== 0;
$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if ($locked) {
        throw new moodle_exception('invalidparameter', 'error', '', get_string('cfr_i18n_audio_01', 'local_subscriptions'));
    }
    try {
        global $USER;
        $action = required_param('action', PARAM_ALPHA);
        if ($action === 'save') {
            $rev = Alignment::save($id, required_param('base', PARAM_INT),
                required_param('textdigest', PARAM_ALPHANUMEXT),
                required_param('audiohash', PARAM_ALPHANUMEXT),
                required_param('alignmentjson', PARAM_RAW), (int)$USER->id);
            redirect(cfr_d21_url('audio_studio.php', ['id' => $id, 'saved' => $rev]));
        } else if ($action === 'approve') {
            if (required_param('confirm', PARAM_INT) !== 1) {
                throw new moodle_exception('invalidparameter');
            }
            Alignment::approve($id, required_param('revision', PARAM_INT),
                required_param('digest', PARAM_ALPHANUMEXT), (int)$USER->id);
            redirect(cfr_d21_url('audio_studio.php', ['id' => $id, 'approved' => 1]));
        } else {
            throw new moodle_exception('invalidparameter');
        }
    } catch (moodle_exception $ex) {
        $error = $ex->getMessage();
    }
}
$source = Alignment::source($id);
$revisions = Alignment::revisions($id);
$latest = $revisions ? (int)array_key_last($revisions) : 0;
$selected = $latest ? $revisions[$latest] : null;
$matches = $selected && hash_equals($source['textdigest'], (string)($selected['payload']['textdigest'] ?? ''))
    && hash_equals($source['audiohash'], (string)($selected['payload']['audiohash'] ?? ''));
$storedcues = $matches ? ($selected['payload']['cues'] ?? []) : [];
$byid = [];
foreach ($storedcues as $cue) {
    if (is_array($cue) && isset($cue['id'])) { $byid[$cue['id']] = $cue; }
}
$saved = optional_param('saved', 0, PARAM_INT);
$approved = optional_param('approved', 0, PARAM_INT);
$trail = [
    ['label' => $collection->title, 'url' => cfr_d21_url('collection.php', ['id' => $collection->id])],
    ['label' => $book->title, 'url' => cfr_d21_url('book.php', ['id' => $id])],
    ['label' => 'Studio audio', 'url' => null],
];
cfr_d21_start('Studio audio · ' . $book->title,
    get_string('cfr_i18n_audio_02', 'local_subscriptions'), $trail);
if ($error !== '') {
    echo '<div role="alert" class="alert alert-danger">' . s($error) . '</div>';
}
if ($saved) {
    echo '<div role="status" class="alert alert-success">' . s(get_string('cfr_fix_audio_private_version', 'local_subscriptions')) . $saved .
        '' . s(get_string('cfr_i18n_audio_03', 'local_subscriptions')) . '</div>';
}
if ($approved) {
    echo '<div role="status" class="alert alert-success">' . s(get_string('cfr_i18n_audio_04', 'local_subscriptions')) . '</div>';
}
if ($locked) {
    echo '<div class="cfr-reader-editor__info">' . s(get_string('cfr_i18n_audio_05', 'local_subscriptions')) . '</div>';
}
if ($selected && !$matches) {
    echo '<div class="alert alert-warning" role="status">' . s(get_string('cfr_i18n_audio_06', 'local_subscriptions')) . '' .
        '' . s(get_string('cfr_i18n_audio_07', 'local_subscriptions')) . '</div>';
}
echo '<div class="cfr-reader-editor__toolbar cfr-d29-toolbar">';
echo '<strong>' . s(get_string('cfr_i18n_audio_08', 'local_subscriptions')) . '</strong>';
echo '<div class="cfr-reader-editor__actions">';
echo html_writer::link(cfr_d21_url('book.php', ['id' => $id]), get_string('cfr_i18n_audio_09', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(cfr_d21_url('media.php', ['id' => $id]), '' . s(get_string('cfr_i18n_audio_10', 'local_subscriptions')) . '',
    ['class' => 'btn btn-outline-secondary']);
echo '</div></div>';
echo '<section class="cfr-reader-editor__panel cfr-d29-intro">';
echo '<p><strong>' . s(get_string('cfr_i18n_audio_11', 'local_subscriptions')) . '</strong>' . s(get_string('cfr_i18n_audio_12', 'local_subscriptions')) . '' .
    get_string('cfr_i18n_audio_13', 'local_subscriptions') .
    '' . s(get_string('cfr_i18n_audio_14', 'local_subscriptions')) . '</p>';
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_audio_15', 'local_subscriptions')) . '' .
    '' . s(get_string('cfr_i18n_audio_16', 'local_subscriptions')) . '</p>';
echo '<div class="cfr-d29-status"><span>' . s(get_string('cfr_i18n_audio_17', 'local_subscriptions')) . '<strong>#' . $latest . '</strong></span>' .
    '<span>' . s(get_string('cfr_i18n_audio_18', 'local_subscriptions')) . '<strong>' . ($selected && $selected['approved'] && $matches ? get_string('cfr_i18n_audio_19', 'local_subscriptions') : get_string('cfr_i18n_audio_20', 'local_subscriptions')) .
    '</strong></span><span>' . count($source['segments']) . '' . s(get_string('cfr_i18n_audio_21', 'local_subscriptions')) . '</span></div>';
echo '</section>';
if (!$source['audio']) {
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_audio_22', 'local_subscriptions')) . '</h3>' .
        '<p>' . s(get_string('cfr_fix_audio_missing_narration', 'local_subscriptions')) . '</p>' .
        html_writer::link(cfr_d21_url('media.php', ['id' => $id]), get_string('cfr_i18n_audio_24', 'local_subscriptions'),
            ['class' => 'btn btn-primary']) . '</section>';
} else if (!$source['segments']) {
    echo '<section class="cfr-reader-editor__panel">' . s(get_string('cfr_i18n_audio_25', 'local_subscriptions')) . '</section>';
} else {
    $audioUrl = cfr_d21_url('preview.php', ['id' => $id, 'area' => 'narration']);
    echo '<section class="cfr-reader-editor__panel cfr-d29-player" id="cfr-d29-player">';
    echo '<div class="cfr-d29-player-head"><div><h3>' . s(get_string('cfr_i18n_audio_26', 'local_subscriptions')) . '</h3>' .
        '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_audio_27', 'local_subscriptions')) . '</p></div>' .
        '<output id="cfr-d29-current" aria-live="off">0:00.00</output></div>';
    echo '<audio id="cfr-d29-audio" controls preload="metadata" src="' . s($audioUrl->out(false)) . '">' .
        '' . s(get_string('cfr_i18n_audio_28', 'local_subscriptions')) . '</audio>';
    echo '<div class="cfr-d29-wave-head"><strong>' . s(get_string('cfr_i18n_audio_29', 'local_subscriptions')) . '</strong>' .
        '<label>' . s(get_string('cfr_i18n_audio_30', 'local_subscriptions')) . '<select id="cfr-d29-speed" aria-label="' . s(get_string('cfr_i18n_audio_31', 'local_subscriptions')) . '">' .
        '<option value="0.75">0,75×</option><option selected value="1">1×</option>' .
        '<option value="1.25">1,25×</option></select></label></div>';
    echo '<div class="cfr-d29-wave-box"><canvas id="cfr-d29-wave" height="152"' .
        ' role="img" aria-label="' . s(get_string('cfr_i18n_audio_32', 'local_subscriptions')) . '"></canvas>' .
        '<span class="cfr-d29-wave-cursor" aria-hidden="true"></span></div>';
    echo '<div class="cfr-d29-wave-scale"><span>0:00</span><span id="cfr-d29-endlabel">' . s(get_string('cfr_i18n_audio_33', 'local_subscriptions')) . '</span></div>';
    echo '<p class="cfr-reader-editor__hint" id="cfr-d29-help">' . s(get_string('cfr_i18n_audio_34', 'local_subscriptions')) . '' .
        '' . s(get_string('cfr_i18n_audio_35', 'local_subscriptions')) . '</p>';
    echo '<div class="cfr-d29-tools"><button class="btn btn-outline-secondary" id="cfr-d29-audition" type="button">' .
        '' . s(get_string('cfr_i18n_audio_36', 'local_subscriptions')) . '</button>' .
        '<button class="btn btn-outline-secondary" id="cfr-d29-suggest" type="button"' . ($locked ? ' disabled' : '') . '>' .
        '' . s(get_string('cfr_i18n_audio_37', 'local_subscriptions')) . '</button>' .
        '<span class="cfr-reader-editor__hint" id="cfr-d29-tools-status" role="status" aria-live="polite"></span></div>';
    echo '</section>';
    echo '<section class="cfr-reader-editor__panel cfr-d29-editor"><h3>' . s(get_string('cfr_fix_audio_cue_heading', 'local_subscriptions')) . '</h3>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_audio_39', 'local_subscriptions')) . '' .
        s(get_string('cfr_fix_audio_estimate_note', 'local_subscriptions')) .
        '<strong>' . s(get_string('cfr_i18n_audio_41', 'local_subscriptions')) . '</strong>' . s(get_string('cfr_i18n_audio_42', 'local_subscriptions')) . '</p>';
    echo '<form id="cfr-d29-form" method="post" action="' .
        s(cfr_d21_url('audio_studio.php', ['id' => $id])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="action" value="save">';
    echo '<input type="hidden" name="base" value="' . $latest . '">';
    echo '<input type="hidden" name="textdigest" value="' . s($source['textdigest']) . '">';
    echo '<input type="hidden" name="audiohash" value="' . s($source['audiohash']) . '">';
    echo '<input type="hidden" name="alignmentjson" id="cfr-d29-payload" value="">';
    $previouspage = -1;
    foreach ($source['segments'] as $i => $seg) {
        if ($previouspage !== $seg['page']) {
            echo '<h4 class="cfr-d29-page-heading">' . s(get_string('cfr_i18n_audio_43', 'local_subscriptions')) . '' . ($seg['page'] + 1) . ' · ' . s($seg['title']) . '</h4>';
            $previouspage = $seg['page'];
        }
        $cue = $byid[$seg['id']] ?? [];
        $skip = !empty($cue['skip']);
        $start = isset($cue['startms']) && is_int($cue['startms']) ? number_format($cue['startms'] / 1000, 2, '.', '') : '';
        $end = isset($cue['endms']) && is_int($cue['endms']) ? number_format($cue['endms'] / 1000, 2, '.', '') : '';
        echo '<div class="cfr-d29-cue" tabindex="0" data-cue-id="' . s($seg['id']) . '">';
        echo '<div class="cfr-d29-cue-text"><button type="button" class="cfr-d29-cue-play" aria-label="' . s(get_string('cfr_i18n_audio_44', 'local_subscriptions')) . '" title="' . s(get_string('cfr_i18n_audio_45', 'local_subscriptions')) . '">▶</button>' .
            '<span class="cfr-d29-cue-n">' . ($i + 1) . '</span><span>' . s($seg['text']) . '</span></div>';
        echo '<div class="cfr-d29-cue-controls">';
        foreach (['start' => [get_string('cfr_i18n_audio_46', 'local_subscriptions'), $start], 'end' => [get_string('cfr_i18n_audio_47', 'local_subscriptions'), $end]] as $key => $details) {
            echo '<label class="cfr-d29-time"><span>' . $details[0] . ' (s)</span>' .
                '<input type="number" step="0.01" min="0" max="7200" class="form-control" data-time="' . $key .
                '" value="' . s($details[1]) . '"' . ($locked ? ' disabled' : '') . '></label>';
            if (!$locked) {
                echo '<button class="btn btn-outline-secondary cfr-d29-mark" type="button" data-mark="' . $key . '">' .
                    $details[0] . '' . s(get_string('cfr_i18n_audio_48', 'local_subscriptions')) . '</button>';
            }
        }
        echo '<label class="cfr-d29-skip"><input type="checkbox" data-skip="1"' .
            ($skip ? ' checked' : '') . ($locked ? ' disabled' : '') . '>' . s(get_string('cfr_i18n_audio_49', 'local_subscriptions')) . '</label>';
        echo '</div></div>';
    }
    if (!$locked) {
        echo '<div class="cfr-reader-editor__actions cfr-d29-submit"><button class="btn btn-primary" type="submit">' .
            '' . s(get_string('cfr_i18n_audio_50', 'local_subscriptions')) . '</button><span class="cfr-reader-editor__hint" id="cfr-d29-summary" role="status"></span></div>';
    }
    echo '</form>';
    if ($selected && $matches && !$locked) {
        echo '<div class="cfr-d29-review"><h3>' . s(get_string('cfr_i18n_audio_51', 'local_subscriptions')) . '</h3>';
        if ($selected['approved']) {
            echo '<p class="cfr-reader-editor__hint">Version #' . $latest . '' . s(get_string('cfr_i18n_audio_52', 'local_subscriptions')) . '</p>';
        } else {
            echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_audio_53', 'local_subscriptions')) . '' .
                '' . s(get_string('cfr_i18n_audio_54', 'local_subscriptions')) . '</p>';
            echo '<form method="post" action="' . s(cfr_d21_url('audio_studio.php', ['id' => $id])->out(false)) . '">';
            echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
            echo '<input type="hidden" name="action" value="approve">';
            echo '<input type="hidden" name="revision" value="' . $latest . '">';
            echo '<input type="hidden" name="digest" value="' . s($selected['digest']) . '">';
            echo '<label class="cfr-d29-confirm"><input type="checkbox" name="confirm" value="1" required>' .
                '' . s(get_string('cfr_i18n_audio_55', 'local_subscriptions')) . '</label>';
            echo '<button class="btn btn-outline-secondary" type="submit">' . s(get_string('cfr_i18n_audio_56', 'local_subscriptions')) . '' . $latest . '</button></form>';
        }
        echo '</div>';
    }
    echo '</section>';
}
echo '<section class="cfr-reader-editor__panel cfr-d29-history"><h3>' . s(get_string('cfr_i18n_audio_57', 'local_subscriptions')) . '</h3>';
if (!$revisions) {
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_audio_58', 'local_subscriptions')) . '</p>';
} else {
    echo '<ul>';
    foreach (array_reverse($revisions, true) as $no => $rev) {
        echo '<li><strong>#' . (int)$no . '</strong> · ' .
            ($rev['approved'] ? get_string('cfr_i18n_audio_19', 'local_subscriptions') : get_string('cfr_i18n_audio_20', 'local_subscriptions')) . ' · ' .
            userdate((int)($rev['payload']['createdat'] ?? 0)) . '</li>';
    }
    echo '</ul>';
}
echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_audio_59', 'local_subscriptions')) . '' .
    '' . s(get_string('cfr_i18n_audio_60', 'local_subscriptions')) . '</p>';
echo '</section>';
if ($source['audio'] && $source['segments']) {
    $jsmessages = [];
    foreach (['cfr_i18n_audio_js_01', 'cfr_i18n_audio_js_02', 'cfr_i18n_audio_js_03', 'cfr_i18n_audio_js_04', 'cfr_i18n_audio_js_05', 'cfr_i18n_audio_js_06', 'cfr_i18n_audio_js_07', 'cfr_i18n_audio_js_08', 'cfr_i18n_audio_js_09', 'cfr_i18n_audio_js_10', 'cfr_i18n_audio_js_11', 'cfr_i18n_audio_js_12', 'cfr_i18n_audio_js_13', 'cfr_i18n_audio_js_14', 'cfr_i18n_audio_js_15', 'cfr_i18n_audio_js_16'] as $key) {
        $jsmessages[$key] = get_string($key, 'local_subscriptions');
    }
    echo '<script id="cfr-d29-i18n" type="application/json">' . json_encode($jsmessages,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_THROW_ON_ERROR) . '</script>';
    echo '<script src="' . s(cfr_d21_url('audio_studio.js')->out(false)) . '" defer></script>';
}
cfr_d21_end();
