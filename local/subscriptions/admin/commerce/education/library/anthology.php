<?php
// D2.4.1 — private CRM anthology editor, no public publication, no course dependency.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderEditorialAnthologyService as Anth;
use local_subscriptions\crm\library\ReaderEditorialService as Editorial;
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revisions;

Anth::assert_ready();
$id = optional_param('id', 0, PARAM_INT);
$error = '';
// Keep a rejected submission visible so users can correct it without retyping.
$submitted = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    try {
        $action = optional_param('action', '', PARAM_ALPHA);
        if ($action === 'save') {
            $submitted = [
                'title' => optional_param('title', '', PARAM_RAW_TRIMMED),
                'slug' => optional_param('slug', '', PARAM_RAW_TRIMMED),
                'subtitle' => optional_param('subtitle', '', PARAM_RAW_TRIMMED),
            ];
            $saved = Anth::save($id, $submitted['slug'], $submitted['title'], $submitted['subtitle'],
                optional_param('expectedmodified', 0, PARAM_INT));
            redirect(cfr_d21_url('anthology.php', ['id' => $saved]), get_string('changessaved'));
        } else if ($id > 0 && $action === 'snapshot') {
            $readyids = optional_param_array('release_ready_books', [], PARAM_INT);
            $readyids = array_values(array_map('intval', $readyids));
            $newid = Revisions::create($id, required_param('expectedmodified', PARAM_INT), $readyids);
            redirect(cfr_d21_url('anthology_revision.php', ['id' => $id, 'rev' => $newid]),
                get_string('cfr_d242_created', 'local_subscriptions'));
        } else if ($id > 0 && $action === 'approve') {
            Revisions::approve($id, required_param('revisionid', PARAM_INT));
            redirect(cfr_d21_url('anthology.php', ['id' => $id]),
                get_string('cfr_d242_approved', 'local_subscriptions'));
        } else if ($id > 0 && in_array($action, ['add', 'up', 'down', 'remove'], true)) {
            Anth::mutate($id, $action, required_param('storyid', PARAM_INT),
                required_param('expectedmodified', PARAM_INT));
            redirect(cfr_d21_url('anthology.php', ['id' => $id]), get_string('changessaved'));
        } else {
            throw new moodle_exception('invalidparameter');
        }
    } catch (moodle_exception $e) {
        $error = $e->getMessage();
    }
}
$anth = $id > 0 ? Anth::anthology($id) : null;
$readonly = $anth && $anth->status !== 'draft';
$chapters = $anth ? Anth::chapters($id) : [];
$trail = [
    ['label' => get_string('cfr_d24_anthologies', 'local_subscriptions'),
        'url' => cfr_d21_url('anthologies.php')],
    ['label' => $anth ? $anth->title : get_string('cfr_d24_create', 'local_subscriptions'), 'url' => null],
];
$actions = $anth ? html_writer::link(cfr_d21_url('anthology_preview.php', ['id' => $id]),
    get_string('cfr_d24_preview', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']) .
    ' ' . html_writer::link(cfr_d21_url('anthology_flipbook.php', ['id' => $id]),
    get_string('cfr_d242b_flipdraft', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']) : null;
cfr_d21_start($anth ? $anth->title : get_string('cfr_d24_create', 'local_subscriptions'),
    get_string('cfr_d24_listhelp', 'local_subscriptions'), $trail, $actions);
if ($error !== '') { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
echo '<div class="cfr-d24-warning">' . s(get_string('cfr_d24_protect', 'local_subscriptions')) . '</div>';
echo '<section class="cfr-reader-editor__panel">';
echo '<div class="cfr-d24-hero"><h3>' . s(get_string('cfr_d24_title', 'local_subscriptions')) . '</h3>';
echo '<span class="cfr-reader-editor__pill">' . s(get_string('cfr_d24_draft', 'local_subscriptions')) . '</span></div>';
if (!$readonly) {
    echo '<form method="post" action="' . s(cfr_d21_url('anthology.php', $id ? ['id' => $id] : [])->out(false)) . '">';
    echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
    echo '<input type="hidden" name="action" value="save">';
    echo '<input type="hidden" name="expectedmodified" value="' . (int)($anth->timemodified ?? 0) . '">';
    echo '<div class="cfr-reader-editor__fields">';
    cfr_d21_field('title', get_string('cfr_d24_title', 'local_subscriptions'),
        (string)($submitted['title'] ?? $anth->title ?? ''));
    cfr_d21_field('slug', 'Slug', (string)($submitted['slug'] ?? $anth->slug ?? ''), (bool)$anth,
        'text', get_string($anth ? 'cfr_d24_slughelp' : 'cfr_d241b_slughelp', 'local_subscriptions'), false);
    cfr_d21_field('subtitle', get_string('cfr_d24_subtitle', 'local_subscriptions'),
        (string)($submitted['subtitle'] ?? $anth->subtitle ?? ''), false, 'text', '', false);
    echo '</div><button type="submit" class="btn btn-primary">' .
        s(get_string('cfr_d24_save', 'local_subscriptions')) . '</button></form>';
} else {
    echo '<p>' . s($anth->subtitle) . '</p>';
}
echo '</section>';
if ($anth) {
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d24_chapters', 'local_subscriptions')) .
        ' (' . count($chapters) . ')</h3>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d24_addhint', 'local_subscriptions')) . '</p>';
    if (!$chapters) { echo '<p>' . s(get_string('cfr_d24_empty', 'local_subscriptions')) . '</p>'; }
    echo '<ol class="cfr-d24-chapters">';
    foreach ($chapters as $index => $row) {
        echo '<li class="cfr-d24-chapter"><div class="cfr-d24-chapter-details"><strong>' .
            (int)$row->position . '. ' . s($row->title) . '</strong><small>' .
            s($row->collectiontitle) . ' · ' . (int)Editorial::page_count((int)$row->bookid) . ' ' .
            s(get_string('cfr_d24_pages', 'local_subscriptions')) . '</small></div>';
        echo '<div class="cfr-d24-chapter-controls">';
        echo html_writer::link(cfr_d21_url('book.php', ['id' => $row->bookid]),
            get_string('cfr_d24_story', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-secondary']);
        echo html_writer::link(cfr_d21_url('story_flipbook.php', ['id' => $row->bookid]),
            get_string('cfr_d242b_storypreview', 'local_subscriptions'),
            ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
        if (!$readonly) {
            foreach (['up', 'down', 'remove'] as $action) {
                if ($action === 'up' && $index === 0) { continue; }
                if ($action === 'down' && $index === count($chapters) - 1) { continue; }
                $label = get_string('cfr_d24_' . $action, 'local_subscriptions');
                echo '<form method="post" action="' . s(cfr_d21_url('anthology.php', ['id' => $id])->out(false)) . '"';
                if ($action === 'remove') {
                    echo ' onsubmit="return confirm(' . s(json_encode(
                        get_string('cfr_d24_removeconfirm', 'local_subscriptions'),
                        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    )) . ')"';
                }
                echo '><input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
                echo '<input type="hidden" name="action" value="' . s($action) . '">';
                echo '<input type="hidden" name="storyid" value="' . (int)$row->bookid . '">';
                echo '<input type="hidden" name="expectedmodified" value="' . (int)$anth->timemodified . '">';
                echo '<button type="submit" class="btn btn-sm btn-outline-secondary">' . s($label) . '</button></form>';
            }
        }
        echo '</div></li>';
    }
    echo '</ol>';
    if (!$readonly && count($chapters) < 100) {
        $selectedids = array_fill_keys(array_map(static fn($r) => (int)$r->bookid, $chapters), true);
        $available = array_filter(Anth::all_stories(), static fn($r) => !isset($selectedids[(int)$r->id]));
        echo '<h4>' . s(get_string('cfr_d24_available', 'local_subscriptions')) . '</h4>';
        if ($available) {
            echo '<form class="cfr-d27-addstory" method="post" action="' . s(cfr_d21_url('anthology.php', ['id' => $id])->out(false)) . '">';
            echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
            echo '<input type="hidden" name="action" value="add">';
            echo '<input type="hidden" name="expectedmodified" value="' . (int)$anth->timemodified . '">';
            echo '<div class="cfr-reader-editor__field"><label for="d24-story">' .
                s(get_string('cfr_d24_story', 'local_subscriptions')) . '</label>';
            echo '<select class="form-control" id="d24-story" name="storyid" required>';
            foreach ($available as $story) {
                echo '<option value="' . (int)$story->id . '">' .
                    s($story->collectiontitle . ' · ' . $story->title) . '</option>';
            }
            echo '</select></div><div class="cfr-d27-form-actions"><button type="submit" class="btn btn-primary">' .
                s(get_string('cfr_d24_add', 'local_subscriptions')) . '</button></div></form>';
        } else {
            echo '<p>' . s(get_string('cfr_d24_nobook', 'local_subscriptions')) . '</p>';
        }
    }
    echo '</section>';
    echo '<section class="cfr-reader-editor__panel"><div class="cfr-d24-hero"><h3>' .
        s(get_string('cfr_d242_revisions', 'local_subscriptions')) . '</h3><span class="cfr-reader-editor__pill">' .
        s(get_string('cfr_d242_private', 'local_subscriptions')) . '</span></div>';
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_d242_explain', 'local_subscriptions')) . '</p>';
    if (Revisions::ready()) {
        $savedversions = Revisions::list_for($id);
        if (!$readonly && $chapters) {
            echo '<form method="post" action="' . s(cfr_d21_url('anthology.php', ['id' => $id])->out(false)) . '">';
            echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
            echo '<input type="hidden" name="action" value="snapshot">';
            echo '<input type="hidden" name="expectedmodified" value="' . (int)$anth->timemodified . '">';
            // Preserve previous explicit approvals when the same canonical book stays at its chapter position.
            $precheckedready = [];
            foreach ($savedversions as $candidate) {
                if ($candidate->state !== 'approved') { continue; }
                $olddata = Revisions::snapshot($id, (int)$candidate->id);
                foreach ($olddata['chapters'] as $oldchapter) {
                    if (($oldchapter['release_ready'] ?? false) === true) {
                        $precheckedready[(int)$oldchapter['bookid']] = true;
                    }
                }
                break;
            }
            echo '<fieldset class="cfr-reader-editor__field"><legend>' .
                s(get_string('cfr_launch_editorialtitle', 'local_subscriptions')) . '</legend>';
            echo '<p class="cfr-reader-editor__hint">' .
                s(get_string('cfr_launch_editorialhint', 'local_subscriptions')) . '</p>';
            foreach ($chapters as $readychapter) {
                $bookid = (int)$readychapter->bookid;
                echo '<label style="display:block;margin:.35rem 0">' .
                    '<input type="checkbox" name="release_ready_books[]" value="' . $bookid . '"' .
                    (isset($precheckedready[$bookid]) ? ' checked' : '') . '> ' .
                    (int)$readychapter->position . '. ' . s($readychapter->title) . '</label>';
            }
            echo '</fieldset>';
            echo '<button class="btn btn-primary" type="submit">' .
                s(get_string('cfr_d242_capture', 'local_subscriptions')) . '</button></form>';
        }
        if (!$savedversions) {
            echo '<p>' . s(get_string('cfr_d242_none', 'local_subscriptions')) . '</p>';
        }
        echo '<div class="cfr-d242-revisions">';
        foreach ($savedversions as $vr) {
            echo '<article class="cfr-d242-revision"><div><strong>' .
                s(get_string('cfr_d242_rev', 'local_subscriptions')) . ' ' . (int)$vr->revno .
                '</strong> <span class="cfr-reader-editor__pill">' .
                s(get_string($vr->state === 'approved' ? 'cfr_d242_approvedlabel' :
                    'cfr_d242_reviewlabel', 'local_subscriptions')) . '</span><small>' .
                userdate((int)$vr->timecreated, get_string('strftimedatetime', 'langconfig')) .
                '</small></div><div class="cfr-d242-actions">';
            echo html_writer::link(cfr_d21_url('anthology_revision.php', ['id' => $id, 'rev' => $vr->id]),
                get_string('cfr_d242_view', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-secondary']);
            echo html_writer::link(cfr_d21_url('anthology_flipbook.php', ['id' => $id, 'rev' => $vr->id]),
                get_string('cfr_d242b_flipversion', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener noreferrer']);
            if ($vr->state === 'review') {
                echo '<form method="post" action="' .
                    s(cfr_d21_url('anthology.php', ['id' => $id])->out(false)) . '">';
                echo '<input type="hidden" name="sesskey" value="' . s(sesskey()) . '">';
                echo '<input type="hidden" name="action" value="approve">';
                echo '<input type="hidden" name="revisionid" value="' . (int)$vr->id . '">';
                echo '<button class="btn btn-sm btn-outline-secondary" type="submit">' .
                    s(get_string('cfr_d242_validate', 'local_subscriptions')) . '</button></form>';
            }
            echo '</div></article>';
        }
        echo '</div>';
    } else {
        echo '<p>' . s(get_string('cfr_d242_migrate', 'local_subscriptions')) . '</p>';
    }
    echo '</section>';
    echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_d24_status', 'local_subscriptions')) . '</h3>';
    echo '<p>' . s(get_string('cfr_d24_product', 'local_subscriptions')) . '</p>';
    echo '<p>' . s(get_string('cfr_d24_previewhint', 'local_subscriptions')) . '</p></section>';
}
echo '<p class="cfr-d243-link">' . html_writer::link(cfr_d21_url('publications.php'), get_string('cfr_fix_anthologies_commerce', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p>';
cfr_d21_end();
