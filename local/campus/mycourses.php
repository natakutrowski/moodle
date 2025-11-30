<?php
// /local/campus/mycourses.php
require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/campus/lib.php');
require_once($CFG->dirroot.'/local/subscriptions/lib.php');
require_once($CFG->libdir.'/completionlib.php'); // fallback si core_completion n'est pas dispo

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/campus/mycourses.php'));

require_login(); // force la connexion

// Bloquer aussi l'utilisateur invité
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

$PAGE->set_pagelayout('standard'); // même header que le catalogue
$PAGE->set_title(get_string('mycourses_title', 'local_campus'));
$PAGE->set_heading(get_string('mycourses_title', 'local_campus'));
$PAGE->navbar->add(get_string('mycourses_title', 'local_campus'), $PAGE->url);

// --- Message de bienvenue une fois après inscription à l’essai ---
$welcome_pending = get_user_preferences('local_campus_trial_welcome_pending', 0);
if ($welcome_pending) {
    $msg = get_string('trial_welcome_banner_html', 'local_campus');
    \core\notification::add($msg, \core\output\notification::NOTIFY_SUCCESS);
    unset_user_preference('local_campus_trial_welcome_pending');
}

// Styles (block + campus)
$PAGE->requires->css(new moodle_url('/local/campus/styles.css'));

ob_start();
local_subscriptions_inject_subscribe_modal($PAGE);
$subspopup = ob_get_clean();

// Récupérer les cours de l'utilisateur (hors frontpage id=1)
$cours = enrol_get_my_courses('*', 'fullname ASC');
unset($cours[SITEID]);

require_once($CFG->libdir.'/completionlib.php'); // pour completion_info

$progressMap     = [];
$progressCounts  = []; // [courseid => ['done'=>X, 'total'=>Y]]
$completed_ids   = [];

foreach ($cours as $rec) { // ← très important : $cours
    $cid    = (int)$rec->id;
    $course = get_course($cid);
    $cinfo  = new completion_info($course);
    $modinfo = get_fast_modinfo($course);


    $pct   = null;
    $total = 0;
    $done  = 0;

    // Parcours des modules pour compter X / Y
    foreach ($modinfo->get_cms() as $cm) {
        if (!$cm->uservisible) {
            continue;
        }
        if (!$cinfo->is_enabled($cm)) {
            continue;
        }
        $total++;

        // Données de complétion pour ce module
        $data = $cinfo->get_data($cm, true, $USER->id);
        $state = isset($data->completionstate) ? (int)$data->completionstate : 0;
        // Tout état non nul = considéré comme complété (complete, pass, fail…)
        if ($state !== 0) {
            $done++;
        }
    }

    if ($total > 0) {
        // % basé sur X/Y
        $pct = 100.0 * ($done / $total);
        $pct = max(0.0, min(100.0, $pct));

        $progressMap[$cid] = $pct;
        $progressCounts[$cid] = ['done' => $done, 'total' => $total];

        if ($done > 0 && $done >= $total) {
            $completed_ids[] = $cid;
        }

    } else {
        // Aucun suivi disponible → visite de la page du cours = 100 %
        if (function_exists('local_campus_user_has_visited_course')
            && local_campus_user_has_visited_course($USER->id, $cid)) {

            $pct = 100.0;
            $progressMap[$cid] = $pct;
            $completed_ids[] = $cid;
            // pas de progressCounts ici (pas d'activités traçables)
        }
    }

}

// Aucun cours à lister (visiteur ou utilisateur sans inscriptions)
if (empty($cours)) {

    // Injecter la popup d’essai (comme sur la home)
    $trialModal = '';
    if (function_exists('local_campus_inject_trial_ui')) {
        ob_start();
        local_campus_inject_trial_ui($PAGE);
        $trialModal = ob_get_clean();
    }

    // 1er cours d’essai (pour data-campus-trial-redirect)
    $firsttrial = 0;
    if (function_exists('local_campus_trial_course_ids')) {
        $trialids = (array)local_campus_trial_course_ids();
        if (!empty($trialids)) { $firsttrial = (int)reset($trialids); }
    }

    echo $OUTPUT->header();

    // Banderole info
    echo html_writer::div(
        html_writer::div(
            html_writer::span(get_string('no_courses_banner_title','local_campus'), 'fw-semibold') . ' ' .
            get_string('no_courses_banner_text','local_campus'),
            'alert alert-info'
        ),
        'container mt-4'
    );

    // ----------------------------
    // BOUTONS LOCAUX (design pill)
    // ----------------------------
    $buttons = [];

    // Parcourir le catalogue
    $buttons[] = html_writer::link(
        new moodle_url('/local/campus/courses.php'),
        get_string('browse_catalog', 'local_campus'),
        ['class' => 'default-btn me-2']
    );

    // Accéder aux cours d'essai (ouvre popup si dispo)
    $trialattrs = ['class' => 'default-btn me-2'];
    if ($firsttrial > 0) { $trialattrs['data-campus-trial-redirect'] = $firsttrial; }
    $buttons[] = html_writer::link(
        $firsttrial ? '#' : new moodle_url('/local/campus/courses.php', ['segment'=>'trial']),
        get_string('access_trial_courses', 'local_campus'),
        $trialattrs
    );

    // Fallback mobile : on garde S’abonner + Connexion (le header est replié)
    $isguest = (!isloggedin() || isguestuser());
    if ($isguest) {
        $buttons[] = html_writer::link(
            new moodle_url('/local/subscriptions/subscribe.php'),
            get_string('subscribe_now', 'local_campus'),
            ['class' => 'default-btn me-2 d-lg-none'] // visible mobile uniquement
        );
        $buttons[] = html_writer::link(
            new moodle_url('/login/index.php'),
            get_string('login_now', 'local_campus'),
            ['class' => 'default-btn d-lg-none']
        );
    }

    echo html_writer::div(
        html_writer::tag('h2', get_string('mycourses_title', 'local_campus')) .
        html_writer::tag('p', get_string('mycourses_empty', 'local_campus')) .
        html_writer::div(implode('', $buttons), 'd-flex flex-wrap gap-2 mt-3'),
        'container py-5'
    );

    // ----------------------------
    // HINT (DESKTOP) → bulle + flèche qui pointent vers #campus-cta-anchor
    // ----------------------------
    echo html_writer::div(
        '<div class="hint-inner">
            <div class="hint-bubble"><span>'.s(get_string('hint_go_to_header_cta','local_campus')).'</span></div>
        </div>',
        'campus-auth-hint',
        ['id' => 'campusAuthHint']
    );

    // --- SVG overlay plein écran pour la flèche
    echo '<svg id="campusAuthArrow" class="campus-auth-arrow" width="0" height="0" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <defs>
            <marker id="campusArrowHead" markerWidth="10" markerHeight="10" refX="8" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill="#1EA69A"></polygon>
            </marker>
        </defs>
        <path id="campusAuthPath" d="" stroke="#1EA69A" stroke-width="4" fill="none" marker-end="url(#campusArrowHead)"></path>
    </svg>';

    // Positionnement dynamique sous les CTA du header (desktop)
    echo html_writer::script(<<<'JS'
    (function(){
    const hint   = document.getElementById('campusAuthHint');
    const bubble = hint ? hint.querySelector('.hint-bubble') : null;
    const anchor = document.getElementById('campus-cta-anchor');
    const svg    = document.getElementById('campusAuthArrow');
    const path   = document.getElementById('campusAuthPath');

    if (!hint || !bubble || !anchor || !svg || !path) return;

    function placeEverything(){
        const desktop = window.matchMedia('(min-width: 992px)').matches;
        if (!desktop) { hideAll(); return; }

        const a = anchor.getBoundingClientRect();
        if (a.width === 0 && a.height === 0) { hideAll(); return; }

        // --- Positionner la bulle sous la barre, vers la droite ---
        hint.style.display = 'block';

        // Bulle sous le bas de la barre à ~12px
        const top  = window.scrollY + a.bottom + 12;
        const bubbleW = Math.min(Math.max(260, bubble.getBoundingClientRect().width), 560);
        const viewportW = window.innerWidth;
        let left = window.scrollX + a.right - bubbleW; // aligné sur la droite du CTA

        // clamp pour rester à l'écran
        const margin = 16;
        if (left + bubbleW > window.scrollX + viewportW - margin) {
        left = window.scrollX + viewportW - bubbleW - margin;
        }
        if (left < window.scrollX + margin) {
        left = window.scrollX + margin;
        }
        hint.style.top  = `${top}px`;
        hint.style.left = `${left}px`;

        // Recalcule la rect de la bulle une fois posée
        const b = hint.getBoundingClientRect();

        // --- Dessiner une courbe du bord droit de la bulle vers le centre du CTA ---
        svg.style.display = 'block';

        const startX = b.right + window.scrollX - 10;                 // point près du bord droit de la bulle
        const startY = b.top   + window.scrollY + b.height * 0.45;    // légèrement au-dessus du centre de la bulle
        const endX   = a.left  + window.scrollX + a.width * 0.65;     // viser un point vers la gauche du bouton
        const endY   = a.top   + window.scrollY + a.height * 0.5;

        // On calcule une boîte englobante (pour n'afficher que ce qu'il faut)
        const minX = Math.min(startX, endX) - 20;
        const minY = Math.min(startY, endY) - 20;
        const maxX = Math.max(startX, endX) + 20;
        const maxY = Math.max(startY, endY) + 20;

        svg.setAttribute('width',  (maxX - minX));
        svg.setAttribute('height', (maxY - minY));
        svg.style.left = `${minX}px`;
        svg.style.top  = `${minY}px`;

        // Points dans le repère local du SVG
        const sx = startX - minX, sy = startY - minY;
        const ex = endX   - minX, ey = endY   - minY;

        // Contrôle de tangentes pour jolie courbe (cubic Bézier)
        const dx = ex - sx, dy = ey - sy;
        const c1x = sx + Math.min(120, Math.max(60, Math.abs(dx) * 0.35));
        const c1y = sy + Math.max(-60, Math.min(80, dy * 0.15));
        const c2x = ex - Math.min(100, Math.max(50, Math.abs(dx) * 0.35));
        const c2y = ey - Math.max(-60, Math.min(80, dy * 0.15));

        path.setAttribute('d', `M ${sx},${sy} C ${c1x},${c1y} ${c2x},${c2y} ${ex},${ey}`);
    }

    function hideAll(){
        hint.style.display = 'none';
        svg.style.display  = 'hidden';
        svg.setAttribute('width', 0);
        svg.setAttribute('height',0);
    }

    // Écouteurs
    ['load','resize','scroll'].forEach(ev => window.addEventListener(ev, placeEverything, {passive:true}));
    // première position
    placeEverything();
    })();
    JS);



    // Popup essai (si injectée)
    echo $trialModal;

    echo $subspopup;

    echo $OUTPUT->footer();
    exit;
}

// Prépare la liste pour le renderer, groupée par catégorie
$bycat = [];
foreach ($cours as $c) {
    $catid = (int)$c->category;
    if (!isset($bycat[$catid])) {
        $bycat[$catid] = [];
    }
    $bycat[$catid][] = $c;
}

// Récupère les noms de catégories
$catnames = [];
if (!empty($bycat)) {
    list($insql, $params) = $DB->get_in_or_equal(array_keys($bycat), SQL_PARAMS_NAMED);
    $cats = $DB->get_records_select('course_categories', "id $insql", $params, '', 'id, name');
    foreach ($cats as $cat) {
        $catnames[(int)$cat->id] = format_string($cat->name);
    }
}

// Options pour le renderer partagé du block
/** @var \block_edly_course_filter\output\renderer $renderer */
$renderer = $PAGE->get_renderer('block_edly_course_filter');

$style = (int)(get_config('local_campus','catalogue_style') ?? 1);

// Onglets “Mes cours | Catalogue” désactivés : mycourses devient la page centrale
$tabsHtml = '';


$opts = [
    'style'                  => $style,
    'class'                  => (string)(get_config('local_campus','catalogue_class') ?? 'courses-area ptb-100'),
    'title'                  => get_string('mycourses_title', 'local_campus'),
    'top_title'              => get_string('mycourses_sub', 'local_campus'),
    'body'                   => '',
    'label_field'            => (string)(get_config('local_campus','catalogue_label_field') ?? 'cardlabel'),
    'trial_field'            => (string)(get_config('local_campus','catalogue_trial_field') ?? 'trialcourseid'),
    'real_field'             => (string)(get_config('local_campus','catalogue_real_field') ?? 'realcourseid'),
    'force_direct_loggedin'  => 1, // CTA → page du cours
    'desc_baseurl'           => '/local/campus/course.php?id={id}&checktrial=1',
    'desc_label'             => get_string('moreinfo', 'local_campus'),
    'restricted'             => false,

    // Nouveautés :
    'tabs_html'              => $tabsHtml,
    'progress_map'           => $progressMap,
    'completed_ids'          => $completed_ids,
    'progress_below'         => 1,  // barre (ou placeholder) SOUS les boutons
    'progress_counts'        => $progressCounts,

    // libellés
    'cta_connected'          => get_string('cta_connected','local_campus'),
    'cta_connected_start'    => get_string('cta_connected_start','local_campus'),
    'cta_connected_resume'   => get_string('cta_connected_resume','local_campus'),
    'cta_connected_free'     => get_string('cta_connected','local_campus'),

    // *** Nouveaux flags pour le renderer ***
    'hide_header'            => 1, // pas de top_title/title sur mycourses
    'hide_desc'              => 1, // pas de bouton "En savoir plus"
];

echo $OUTPUT->header();
local_campus_render_subscription_expiry_banner();

// --- Bandeaux essai & flag "expired" ---
require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

$trialExpiredNoPaid = false;

// A-t-il un abonnement actif (hors essai) ?
$trialplanid = (int)(get_config('local_subscriptions','trial_plan_id') ?? 0);
$hasPaid = $DB->record_exists_select(
    'user_subscription',
    "userid = :u AND status = 'ACTIVE' AND end_date > :now" . ($trialplanid ? " AND planid <> :t" : ""),
    ['u' => (int)$USER->id, 'now' => time()] + ($trialplanid ? ['t' => $trialplanid] : [])
);

// État de l’essai
$activeTrial = \local_subscriptions\trial_manager::user_has_active_trial((int)$USER->id);
$winOpen     = \local_subscriptions\trial_manager::is_discount_window_open((int)$USER->id);

// 1) Rappel de fin (essai encore actif MAIS promo plus active)
if (!$hasPaid && $activeTrial && !$winOpen) {
    $expDate = userdate((int)$activeTrial->end_date, '%e %B %Y, %H:%M');
    $subUrl  = (new moodle_url('/local/subscriptions/subscribe.php'))->out(false);
    $msg = html_writer::tag('strong', get_string('trial_banner_reminder_title','local_campus')) . ' ' .
            get_string('trial_banner_reminder_body','local_campus', $expDate);

    // Bandeau avec CTA « S’abonner » à droite
    echo html_writer::div(
        html_writer::div(
            html_writer::div($msg, 'me-3') .
            html_writer::link($subUrl, get_string('subscribe_now','local_campus'), ['class'=>'btn btn-sm btn-primary ms-auto',  'data-subs-modal'=>'1']),
            'alert alert-info d-flex align-items-center justify-content-between'
        ),
        'container mt-3'
    );

}

// 2) Essai expiré (pas d’essai actif et pas d’abonnement)
if (!$hasPaid && !$activeTrial && $trialplanid) {
    $latest = $DB->get_record_sql("
        SELECT * FROM {user_subscription}
         WHERE userid = :u AND planid = :p
         ORDER BY end_date DESC
         LIMIT 1",
        ['u'=>(int)$USER->id,'p'=>$trialplanid]
    );
    if ($latest && (int)$latest->end_date > 0 && (int)$latest->end_date <= time()) {
        $expDate = userdate((int)$latest->end_date, '%e %B %Y, %H:%M');
        $subUrl  = (new moodle_url('/local/subscriptions/subscribe.php'))->out(false);
        $msg     = get_string('trial_banner_expired_html','local_campus', (object)['date'=>$expDate,'url'=>$subUrl]);

        // Bandeau avec CTA « S’abonner » à droite
        echo html_writer::div(
            html_writer::div(
                html_writer::div($msg, 'me-3') .
                html_writer::link($subUrl, get_string('subscribe_now','local_campus'), ['class'=>'btn btn-sm btn-primary ms-auto']),
                'alert alert-warning d-flex align-items-center justify-content-between'
            ),
            'container mt-3'
        );

        $trialExpiredNoPaid = true;
    }

}

if (!empty($trialExpiredNoPaid)) {
    echo html_writer::start_div('trial-expired');
}

// Affichage groupé par catégorie
foreach ($bycat as $catid => $courseobjs) {
    $catTitle = $catnames[$catid] ?? '';

    // Wrapper de catégorie
    echo html_writer::start_div('campus-category-block container mt-4');

    if ($catTitle !== '') {
        echo html_writer::tag('h2', $catTitle, ['class' => 'campus-category-title mb-3']);
    }

    // Liste de cours pour cette catégorie (le renderer n'a besoin que des IDs)
    $records = array_map(function($o){ return (object)['id' => (int)$o->id]; }, $courseobjs);

    echo $renderer->catalogue($records, $opts);

    echo html_writer::end_div();
}

if (!empty($trialExpiredNoPaid)) {
    echo html_writer::end_div();
}


if (!empty($trialExpiredNoPaid)) {
    echo html_writer::tag('style', <<<CSS
/* Griser toutes les cartes Edly quand essai expiré et pas d'abonnement */
.trial-expired .block_edly_course_filter .courses-card {
  position: relative;
  filter: grayscale(100%);
  opacity: .6;
}

/* Désactiver tous les liens d'accès (image, titre, CTA) */
.trial-expired .block_edly_course_filter .courses-card a {
  pointer-events: none !important;
  cursor: not-allowed !important;
}

/* (Optionnel) désactiver seulement les accès direct cours mais laisser "En savoir plus"
.trial-expired .block_edly_course_filter .courses-card .cf-cta,
.trial-expired .block_edly_course_filter .courses-card .courses-image a,
.trial-expired .block_edly_course_filter .courses-card .top-content h3 a {
  pointer-events: none !important;
  cursor: not-allowed !important;
}
*/

/* Petit ruban "Essai expiré" dans le coin (optionnel) */
.trial-expired .block_edly_course_filter .courses-card::after {
  content: 'Essai expiré';
  position: absolute;
  right: 12px;
  bottom: 12px;
  background: rgba(17,24,39,.9);
  color:#fff;
  font-weight:600;
  font-size:.85rem;
  padding:.25rem .5rem;
  border-radius:.5rem;
}
CSS
    );
}

echo $subspopup;
echo $OUTPUT->footer();
