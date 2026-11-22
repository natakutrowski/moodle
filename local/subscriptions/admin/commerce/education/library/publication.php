<?php
// CampusFR D2.4.3 — controlled publication workflow, draft by default.
declare(strict_types=1);
require_once(__DIR__ . '/_bootstrap.php');
use local_subscriptions\crm\library\ReaderCommercePublicationService as Publications;
use local_subscriptions\crm\library\ReaderAnthologyRevisionService as Revisions;
use local_campusreader\local\calendar_presentation as CalendarDisplay;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
if (!Publications::ready()) { throw new moodle_exception('invalidparameter'); }
$id = optional_param('id', 0, PARAM_INT);
$pub = $id ? Publications::get($id) : null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);
    try {
        if ($action === 'create' && !$pub) {
            $id = Publications::create(required_param('slug', PARAM_RAW_TRIMMED),
                required_param('kind', PARAM_ALPHA), required_param('anthologyid', PARAM_INT),
                required_param('revisionid', PARAM_INT), optional_param('collectionid', 0, PARAM_INT));
        } else if ($action === 'revision' && $pub) {
            Publications::change_revision($id, required_param('revisionid', PARAM_INT),
                optional_param('collectionid', 0, PARAM_INT));
        } else if ($action === 'product' && $pub) {
            Publications::bind_product($id, required_param('productsku', PARAM_RAW_TRIMMED));
        } else if ($action === 'publish' && $pub) {
            if (required_param('confirm', PARAM_INT) !== 1) {
                throw new invalid_parameter_exception(get_string('cfr_i18n_pub_01', 'local_subscriptions'));
            }
            Publications::publish($id);
        } else if ($action === 'swap' && $pub) {
            if (required_param('confirm', PARAM_INT) !== 1 ||
                    ((string)$pub->kind === 'calendar' && required_param('confirmdates', PARAM_INT) !== 1)) {
                throw new invalid_parameter_exception(get_string('cfr_i18n_pub_02', 'local_subscriptions'));
            }
            Publications::swap_published_revision($id,
                required_param('expectedrevision', PARAM_INT), required_param('revisionid', PARAM_INT));
        } else if ($action === 'calendardisplay' && $pub) {
            $labels = [];
            foreach (CalendarDisplay::LANGUAGES as $language) {
                foreach (CalendarDisplay::FIELDS as $field => $limit) {
                    $labels[$language][$field] = required_param($language . '_' . $field, PARAM_RAW_TRIMMED);
                }
            }
            Publications::update_calendar_display($id,
                required_param('expected_calendar_hash', PARAM_ALPHANUM), $labels);
        } else if ($action === 'pause' && $pub) {
            Publications::pause($id);
        } else {
            throw new invalid_parameter_exception(get_string('cfr_i18n_pub_03', 'local_subscriptions'));
        }
        redirect(cfr_d21_url('publication.php', ['id' => $id]), get_string('changessaved'));
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}
$pub = $id ? Publications::get($id) : null;
$title = get_string('cfr_d243_publications', 'local_subscriptions') . ($pub ? ' · ' . $pub->slug : ' · ' . get_string('cfr_d243_new', 'local_subscriptions'));
cfr_d21_start($title, get_string('cfr_d243_intro', 'local_subscriptions'), [
    ['label' => get_string('cfr_d243_publications', 'local_subscriptions'), 'url' => cfr_d21_url('publications.php')],
    ['label' => $pub ? $pub->slug : get_string('cfr_d243_new', 'local_subscriptions'), 'url' => null],
]);
if ($error !== '') { echo '<div class="alert alert-danger" role="alert">' . s($error) . '</div>'; }
echo '<p class="cfr-d24-warning">' . s(get_string('cfr_d243_warning', 'local_subscriptions')) . '</p>';
$revoptions = [];
foreach (Publications::approved_revisions() as $r) {
    $revoptions[$r->id] = get_string('cfr_i18n_pub_04', 'local_subscriptions') . (int)$r->anthologyid . get_string('cfr_i18n_pub_05', 'local_subscriptions') . (int)$r->revno . ' · ' . substr($r->digest, 0, 12);
}
$colloptions = [0 => get_string('cfr_i18n_pub_06', 'local_subscriptions')];
foreach (Publications::collections() as $c) { $colloptions[$c->id] = $c->title . ' (' . $c->slug . ')'; }
$productoptions = ['' => get_string('cfr_i18n_pub_07', 'local_subscriptions')];
foreach (Publications::candidate_products() as $p) {
    $productoptions[$p->sku] = $p->name . ' [' . $p->sku . '] · ' . $p->status;
}
$startform = static function(string $action) use ($id): string {
    return '<form method="post" class="cfr-reader-editor__panel"><input type="hidden" name="sesskey" value="' .
        s(sesskey()) . '"><input type="hidden" name="action" value="' . s($action) . '">';
};
if (!$pub) {
    echo $startform('create');
    echo '<h3>' . s(get_string('cfr_d243_new', 'local_subscriptions')) . '</h3>';
    cfr_d21_field('slug', get_string('cfr_i18n_pub_08', 'local_subscriptions'), '', false, 'text', get_string('cfr_i18n_pub_09', 'local_subscriptions'));
    echo '<label>' . s(get_string('cfr_i18n_pub_format', 'local_subscriptions')) . '</label>' . html_writer::select([
        'anthology' => get_string('cfr_d243_kind_anthology', 'local_subscriptions'),
        'calendar' => get_string('cfr_d243_kind_calendar', 'local_subscriptions'),
    ], 'kind', 'anthology', false, ['class' => 'form-select']);
    echo '<label>' . s(get_string('cfr_i18n_pub_11', 'local_subscriptions')) . '</label>' . html_writer::select($revoptions, 'revisionid', '',
        false, ['class' => 'form-select', 'required' => true]);
    // Require anthology id explicitly to avoid cross-anthology mismatches.
    echo '<label>' . s(get_string('cfr_i18n_pub_12', 'local_subscriptions')) . '</label><input class="form-control" name="anthologyid" type="number" min="1" required>';
    echo '<label>' . s(get_string('cfr_i18n_pub_13', 'local_subscriptions')) . '</label>' .
        html_writer::select($colloptions, 'collectionid', 0, false, ['class' => 'form-select']);
    echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_pub_14', 'local_subscriptions')) . '</p>';
    echo '<button class="btn btn-primary" type="submit">' . s(get_string('cfr_i18n_pub_15', 'local_subscriptions')) . '</button></form>';
} else {
    echo '<section class="cfr-reader-editor__panel"><h3>' . s($pub->slug) . '</h3>';
    echo '<p><strong>' . s(get_string('cfr_i18n_pub_16', 'local_subscriptions')) . '</strong> ' . s($pub->status) .
        ' · <strong>' . s(get_string('cfr_i18n_pub_17', 'local_subscriptions')) . '</strong> ' . s($pub->kind) .
        ' · <strong>' . s(get_string('cfr_i18n_pub_18', 'local_subscriptions')) . '</strong> ' . (int)$pub->revisionid . '</p>';
    echo '<p><strong>' . s(get_string('cfr_i18n_pub_19', 'local_subscriptions')) . '</strong> ' . s((string)$pub->productsku ?: get_string('cfr_i18n_pub_20', 'local_subscriptions')) . '</p>';
    echo '<p>' . html_writer::link(cfr_d21_url('qa_access.php', ['id' => $id]),
        get_string('cfr_i18n_pub_21', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p>';
    if ($pub->productsku) {
        $prod = (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))
            ->find_by_sku((string)$pub->productsku);
        if ($prod) {
            $backoffice = new moodle_url('/local/subscriptions/admin/commerce/products/view.php',
                ['catalogkey' => 'native:' . (int)$prod->get_id()]);
            $publicproduct = CommerceProductDiscoveryUrlResolver::resolve(
                $prod->get_sku(), $prod->get_type(), $prod->get_metadata());
            echo '<p>' . html_writer::link($backoffice, get_string('cfr_i18n_pub_22', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . ' ' .
                html_writer::link($publicproduct, get_string('cfr_i18n_pub_23', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p>';
        }
    }
    echo '<p>' . html_writer::link(new moodle_url('/local/campusreader/publication.php',
        ['slug' => $pub->slug]), get_string('cfr_i18n_pub_24', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']) . '</p></section>';
    // Only the published calendar's presentation copy is editable. Frozen slots,
    // schedule and customer entitlement stay untouched on this screen.
    if ($pub->kind === 'calendar') {
        $calendarview = json_decode((string)$pub->calendarjson, true);
        if (is_array($calendarview) && is_array($calendarview['slots'] ?? null)) {
            $snapview = Revisions::snapshot((int)$pub->anthologyid, (int)$pub->revisionid);
            echo $startform('calendardisplay');
            echo '<h3>' . s(get_string('cfr_c_crm_cal_heading', 'local_campusreader')) . '</h3>';
            echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_c_crm_cal_hint', 'local_campusreader')) . '</p>';
            echo '<input type="hidden" name="expected_calendar_hash" value="' .
                s(hash('sha256', (string)$pub->calendarjson)) . '">';
            foreach (CalendarDisplay::LANGUAGES as $language) {
                $labels = CalendarDisplay::resolve($snapview, $calendarview, $language);
                echo '<fieldset style="margin:1rem 0;padding:1rem;border:1px solid var(--bs-border-color,#ddd);border-radius:12px">' .
                    '<legend style="float:none;width:auto;font-size:1rem;padding:0 .5rem">' .
                    s(strtoupper($language)) . '</legend>';
                foreach (CalendarDisplay::FIELDS as $field => $limit) {
                    $captions = [
                        'title' => get_string('cfr_c_crm_cal_title', 'local_campusreader'),
                        'subtitle' => get_string('cfr_c_crm_cal_subtitle', 'local_campusreader'),
                        'skytitle' => get_string('cfr_c_crm_cal_skytitle', 'local_campusreader'),
                        'skycaption' => get_string('cfr_c_crm_cal_skycaption', 'local_campusreader'),
                    ];
                    $name = $language . '_' . $field;
                    echo '<label for="cfr-cal-' . s($name) . '" class="form-label">' .
                        s($captions[$field]) . '</label>' .
                        '<input class="form-control" type="text" id="cfr-cal-' . s($name) .
                        '" name="' . s($name) . '" maxlength="' . (int)$limit . '" value="' .
                        s($labels[$field]) . '" ' . ($field === 'subtitle' ? '' : 'required') . '>';
                }
                echo '</fieldset>';
            }
            echo '<button type="submit" class="btn btn-outline-primary">' . s(get_string('cfr_c_crm_cal_save', 'local_campusreader')) . '</button></form>';
        }
    }
    if ($pub->status === 'draft') {
        echo $startform('revision');
        echo '<h3>' . s(get_string('cfr_i18n_pub_25', 'local_subscriptions')) . '</h3>' . html_writer::select($revoptions, 'revisionid',
            (int)$pub->revisionid, false, ['class' => 'form-select']);
        echo '<label>' . s(get_string('cfr_i18n_pub_26', 'local_subscriptions')) . '</label>' . html_writer::select($colloptions, 'collectionid',
            (int)$pub->collectionid, false, ['class' => 'form-select']);
        echo '<button class="btn btn-outline-primary" type="submit">' . s(get_string('cfr_i18n_pub_27', 'local_subscriptions')) . '</button></form>';
        echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_pub_28', 'local_subscriptions')) . '</h3>';
        echo '<p>' . s(get_string('cfr_i18n_pub_29', 'local_subscriptions')) . '<strong>Service</strong>, ' . s(get_string('cfr_i18n_pub_state', 'local_subscriptions')) .
            ' <strong>' . s(get_string('cfr_i18n_pub_inactive', 'local_subscriptions')) . '</strong> ' .
            s(get_string('cfr_i18n_pub_or', 'local_subscriptions')) . ' <strong>' .
            s(get_string('cfr_i18n_pub_draft', 'local_subscriptions')) . '</strong>' . s(get_string('cfr_i18n_pub_34', 'local_subscriptions')) . '' .
            s(get_string('cfr_i18n_pub_35', 'local_subscriptions')) . '</p>';
        echo '<p>' . html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/products/edit.php'),
            get_string('cfr_i18n_pub_36', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary']
        ) . '</p>';
        if ($pub->productsku) {
            echo '<p><strong>' . s(get_string('cfr_i18n_pub_37', 'local_subscriptions')) . '</strong> ' . s((string)$pub->productsku) .
                s(get_string('cfr_i18n_pub_38', 'local_subscriptions')) . '</p>';
        } else if (count($productoptions) < 2) {
            echo '<p>' . s(get_string('cfr_i18n_pub_39', 'local_subscriptions')) . '</p>';
        } else {
            echo $startform('product');
            echo html_writer::select($productoptions, 'productsku', '', false,
                ['class' => 'form-select', 'required' => true]);
            echo '<p class="cfr-reader-editor__hint">' . s(get_string('cfr_i18n_pub_40', 'local_subscriptions')) . '' .
                s(get_string('cfr_i18n_pub_41', 'local_subscriptions')) . '</p>';
            echo '<button class="btn btn-outline-primary" type="submit">' . s(get_string('cfr_i18n_pub_42', 'local_subscriptions')) . '</button></form>';
        }
        echo '</section>';
        echo $startform('publish');
        echo '<h3>' . s(get_string('cfr_i18n_pub_43', 'local_subscriptions')) . '</h3><p>' . s(get_string('cfr_i18n_pub_44', 'local_subscriptions')) . '</p>';
        echo '<label><input type="checkbox" name="confirm" value="1" required>' . s(get_string('cfr_i18n_pub_45', 'local_subscriptions')) . '</label>';
        echo '<button class="btn btn-primary" type="submit">' . s(get_string('cfr_i18n_pub_46', 'local_subscriptions')) . '</button></form>';
    } else if ($pub->status === 'published') {
        // A published anthology can be replaced without ever suspending customer access.
        $swapoptions = [];
        foreach (Publications::approved_revisions() as $candidate) {
            if ((int)$candidate->anthologyid === (int)$pub->anthologyid &&
                    (int)$candidate->id !== (int)$pub->revisionid) {
                $swapoptions[$candidate->id] = 'Version ' . (int)$candidate->revno .
                    get_string('cfr_i18n_pub_48', 'local_subscriptions') . (int)$candidate->id . ' · ' . substr((string)$candidate->digest, 0, 12);
            }
        }
        echo '<section class="cfr-reader-editor__panel"><h3>' . s(get_string('cfr_i18n_pub_49', 'local_subscriptions')) . '</h3>';
        echo '<p>' . s(get_string('cfr_i18n_pub_50', 'local_subscriptions')) . '' .
            s(get_string('cfr_i18n_pub_51', 'local_subscriptions')) . '</p>';
        if ((string)$pub->kind === 'calendar') {
            echo '<p><strong>' . s(get_string('cfr_i18n_pub_52', 'local_subscriptions')) . '</strong>' . s(get_string('cfr_i18n_pub_53', 'local_subscriptions')) . '' .
                get_string('cfr_i18n_pub_54', 'local_subscriptions') .
                s(get_string('cfr_i18n_pub_55', 'local_subscriptions')) . '</p>';
        }
        if ($swapoptions) {
            echo $startform('swap');
            echo '<input type="hidden" name="expectedrevision" value="' . (int)$pub->revisionid . '">';
            echo '<label for="cfr-swap-revision">' . s(get_string('cfr_i18n_pub_56', 'local_subscriptions')) . '</label>' .
                html_writer::select($swapoptions, 'revisionid', '', ['' => get_string('cfr_i18n_pub_57', 'local_subscriptions')],
                    ['class' => 'form-select', 'id' => 'cfr-swap-revision', 'required' => true]);
            echo '<p><label><input type="checkbox" name="confirm" value="1" required> ' .
                s(get_string('cfr_i18n_pub_58', 'local_subscriptions')) . '</label></p>';
            if ((string)$pub->kind === 'calendar') {
                echo '<p><label><input type="checkbox" name="confirmdates" value="1" required> ' .
                    s(get_string('cfr_i18n_pub_59', 'local_subscriptions')) . '</label></p>';
            }
            echo '<button class="btn btn-primary" type="submit">' . s(get_string('cfr_i18n_pub_60', 'local_subscriptions')) . '</button></form>';
        } else {
            echo '<p>' . s(get_string('cfr_i18n_pub_61', 'local_subscriptions')) . '</p>';
        }
        echo '</section>';
        echo $startform('pause');
        echo '<p>' . s(get_string('cfr_i18n_pub_62', 'local_subscriptions')) . '</p>';
        echo '<button class="btn btn-outline-danger" type="submit">' . s(get_string('cfr_i18n_pub_63', 'local_subscriptions')) . '</button></form>';
    }
}
cfr_d21_end();
