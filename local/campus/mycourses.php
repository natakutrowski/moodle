<?php
// /local/campus/mycourses.php
require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/campus/lib.php');
require_once($CFG->libdir.'/completionlib.php'); // fallback si core_completion n'est pas dispo

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/campus/mycourses.php'));
$PAGE->set_pagelayout('standard'); // même header que le catalogue
$PAGE->set_title(get_string('mycourses_title', 'local_campus'));
$PAGE->set_heading(get_string('mycourses_title', 'local_campus'));
$PAGE->navbar->add(get_string('mycourses_title', 'local_campus'), $PAGE->url);

// Styles (block + campus)
$PAGE->requires->css(new moodle_url('/local/campus/styles.css'));

// Récupérer les cours de l'utilisateur (hors frontpage id=1)
$cours = enrol_get_my_courses('*', 'fullname ASC');
unset($cours[SITEID]);

require_once($CFG->libdir.'/completionlib.php'); // pour completion_info

$progressMap   = [];
$completed_ids = [];

foreach ($cours as $rec) { // ← très important : $cours
    $cid    = (int)$rec->id;
    $course = get_course($cid);
    $cinfo  = new completion_info($course);
    $modinfo = get_fast_modinfo($course);


    // Y a-t-il au moins une activité "traçable" pour cet utilisateur ?
    $hasTrackable = false;
    foreach ($modinfo->get_cms() as $cm) {
        if (!$cm->uservisible) continue;
        if ($cinfo->is_enabled($cm)) { $hasTrackable = true; break; }
    }

    $pct = null;
    if ($hasTrackable) {
        // Vrai % de complétion quand c'est possible
        if (class_exists('\core_completion\progress')) {
            $p = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
        } else if (method_exists($cinfo, 'get_progress_percentage')) {
            $p = $cinfo->get_progress_percentage($USER->id);
        } else {
            $p = null;
        }
        if ($p !== null) {
            $pct = max(0.0, min(100.0, $p));
        }
    } else {
        // Aucun suivi disponible → visite de la page du cours = 100 %
        if (function_exists('local_campus_user_has_visited_course')
            && local_campus_user_has_visited_course($USER->id, $cid)) {
            $pct = 100.0;
        }
    }

    if ($pct !== null) {
        $progressMap[$cid] = $pct;
        if ($pct >= 100.0) { $completed_ids[] = $cid; }
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
            new moodle_url('/subscribe.php'),
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

    echo $OUTPUT->footer();
    exit;
}



// Prépare la liste pour le renderer (il suffit d’id → le handler Edly retrouvera les visuels)
$records = array_map(function($o){ return (object)['id' => (int)$o->id]; }, $cours);

// Options pour le renderer partagé du block
/** @var \block_edly_course_filter\output\renderer $renderer */
$renderer = $PAGE->get_renderer('block_edly_course_filter');

$style = (int)(get_config('local_campus','catalogue_style') ?? 1);

// Onglets “Mes cours | Catalogue” (dans le bloc)
$isMy = $PAGE->url->compare(new moodle_url('/local/campus/mycourses.php'), URL_MATCH_BASE);
$tabsHtml = local_campus_tabs_inline_html($isMy);

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

    // libellés
    'cta_connected'          => get_string('cta_connected','local_campus'),
    'cta_connected_start'    => get_string('cta_connected_start','local_campus'),
    'cta_connected_resume'   => get_string('cta_connected_resume','local_campus'),
    'cta_connected_free'     => get_string('cta_connected','local_campus'),
];

echo $OUTPUT->header();

echo $renderer->catalogue($records, $opts);
echo $OUTPUT->footer();
