<?php
require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/campus/lib.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/campus/courses.php'));

require_login(); // force la connexion

// Bloquer aussi l'utilisateur invité
if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cataloguetitle', 'local_campus'));
$PAGE->set_heading(get_string('catalogueheading', 'local_campus'));
$PAGE->navbar->add(get_string('courses'), $PAGE->url);

// Popup essai (mêmes comportements que le block)
ob_start();
local_campus_inject_trial_ui($PAGE); // cette fonction émet du HTML
$campus_trial_modal = ob_get_clean();

// Segment
$isguest   = (!isloggedin() || isguestuser());
$istrial   = function_exists('local_campus_is_trial_user') && local_campus_is_trial_user();
$restricted = ($isguest || $istrial);
$isadmin    = is_siteadmin();

// Option admin pour inclure aussi les cours masqués (ajoute ?hidden=1 dans l’URL si tu veux)
$showhidden = optional_param('hidden', 0, PARAM_BOOL);

if ($isadmin) {
    // Admin → tous les cours (sauf frontpage id=1). Avec ?hidden=1 on inclut les masqués.
    $sql = "SELECT id FROM {course} WHERE id > 1 " . ($showhidden ? "" : "AND visible = 1 ") . "ORDER BY sortorder";
    $courses = $DB->get_records_sql($sql);
} else {
    // Public → selon le segment (essai vs abonnés)
    $ids = $restricted ? local_campus_trial_course_ids() : local_campus_subscriber_course_ids();
    if (empty($ids)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('nocoursesconfigured', 'local_campus'), 'warning');
        echo $OUTPUT->footer(); exit;
    }
    list($in, $p) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
    $courses = $DB->get_records_sql("SELECT id FROM {course} WHERE id $in AND visible=1 ORDER BY sortorder", $p);
}

// Options de rendu prises dans la config Campus
$style  = (int)(get_config('local_campus','catalogue_style') ?? 1);
$opts = [
    'style' => $style,
    'class' => (string)(get_config('local_campus','catalogue_class') ?? 'courses-area ptb-100').' campus-fullbleed',
    'title' => (string)(get_config('local_campus','catalogue_title') ?? get_string('catalogueheading', 'local_campus')),
    'top_title' => (string)(get_config('local_campus','catalogue_top_title') ?? get_string('cataloguesub', 'local_campus')),
    'body' => (string)(get_config('local_campus','catalogue_body') ?? ''),
    'label_field' => (string)(get_config('local_campus','catalogue_label_field') ?? 'cardlabel'),
    'trial_field' => (string)(get_config('local_campus','catalogue_trial_field') ?? 'trialcourseid'),
    'real_field'  => (string)(get_config('local_campus','catalogue_real_field') ?? 'realcourseid'),
    'force_direct_loggedin' => (int)(get_config('local_campus','catalogue_force_direct_loggedin') ?? 0),
    // Fiche : on pointe par défaut vers ta page course.php
    'desc_baseurl' => (string)(get_config('local_campus','catalogue_desc_baseurl') ?? '/local/campus/course.php?id={id}&checktrial=1'),
    'desc_label' => (string)(get_config('local_campus','catalogue_desc_label') ?? get_string('moreinfo','local_campus')),
    'cta_guest' => (string)(get_config('local_campus','catalogue_cta_guest') ?? get_string('trial_access','theme_edly')),
    'cta_connected' => (string)(get_config('local_campus','catalogue_cta_connected') ?? get_string('cta_connected','local_campus')),
    'restricted' => $restricted,
];

// ——— Cours à griser = visibles dans l’offre mais NON inscrits (pour abonnés seulement)
$disabledids = [];
if (!$isadmin && !$restricted) { // abonné
    $enrolled    = enrol_get_my_courses('id');                 // [id => ...]
    $enrolledids = array_map('intval', array_keys($enrolled)); // ids des cours où je SUIS inscrit
    $displayids  = array_map('intval', array_keys($courses));  // ids des cours affichés dans le catalogue
    $disabledids = array_values(array_diff($displayids, $enrolledids));
}
$opts['disabled_ids'] = $disabledids;


/** @var \block_edly_course_filter\output\renderer $renderer */  
$renderer = $PAGE->get_renderer('block_edly_course_filter');

$PAGE->requires->css('/blocks/edly_course_filter/styles.css'); // OK car avant $OUTPUT->header()

// N’afficher les onglets qu’en dehors de l’accueil
if ($PAGE->pagetype !== 'site-index') {
    $isMy = $PAGE->url->compare(new moodle_url('/local/campus/mycourses.php'), URL_MATCH_BASE);
    $opts['tabs_html'] = local_campus_tabs_inline_html($isMy);
}


echo $OUTPUT->header();

if ($isadmin) {
    $native = new moodle_url('/course/index.php', ['campus' => 'pass']);
    $toggle = new moodle_url('/local/campus/courses.php', ['hidden' => $showhidden ? 0 : 1]);

    $native_label = get_string('admin_native_page', 'local_campus'); // ⤴︎ Page native Moodle
    $toggle_label = $showhidden
        ? get_string('admin_hide_hidden', 'local_campus')       // Masquer les cours cachés
        : get_string('admin_show_hidden', 'local_campus');      // Afficher aussi les cours cachés

    echo html_writer::div(
        html_writer::link($native, '⤴︎ '.$native_label, ['class'=>'btn btn-outline-secondary me-2']) .
        html_writer::link($toggle, $toggle_label, ['class'=>'btn btn-outline-secondary']),
        'mb-3'
    );
}


// Affiche la modale APRES le header
echo $campus_trial_modal;

echo $renderer->catalogue(array_values($courses), $opts);
echo $OUTPUT->footer();
?>
