<?php
// /local/campus/course.php
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');
require_once($CFG->dirroot.'/local/campus/lib.php');

$passedid = optional_param('id',0, PARAM_INT);        // ID reçu dans l'URL (souvent le cours RÉEL si tu suis le bloc)
$layout   = optional_param('layout', 2, PARAM_INT); // 1, 2, 3
$style = optional_param('style', '', PARAM_ALPHANUMEXT);
$dark  = ($style === 'dark');
$trialparam = optional_param('trial', 0, PARAM_INT);       // id du cours d'essai
$checktrial = optional_param('checktrial', 0, PARAM_BOOL); // 1 => auto-check trial

$isguest = (!isloggedin() || isguestuser());

$istrial = function_exists('local_campus_is_trial_user') && local_campus_is_trial_user();
$isrestricted = $isguest || $istrial;   // ← invité OU compte d’essai


// URL de la page d’abonnement (ajuste le chemin si besoin)
$subscribeurl = (new moodle_url('/local/subscriptions/subscribe.php'))->out(false);
// Si ta page est à la racine: new moodle_url('/subscribe.php')


// Helper : lit un champ personnalisé de cours par shortname.
function local_campus_cf(string $shortname, int $courseid): ?string {
    global $DB;
    $sql = "SELECT d.value
              FROM {customfield_data} d
              JOIN {customfield_field} f ON f.id = d.fieldid
              JOIN {customfield_category} c ON c.id = f.categoryid
             WHERE d.instanceid = :cid
               AND f.shortname = :sn
               AND c.component = 'core_course'
               AND c.area = 'course'";
    return $DB->get_field_sql($sql, ['cid' => $courseid, 'sn' => $shortname]) ?: null;
}

/**
 * Rend le bloc de CTA selon le statut invité/connecté.
 */
function local_campus_ctas_html(bool $guestlike, int $trialid, string $realurl, string $subscribeurl): string {
    $out = '<div class="course-ctas">';
    if ($guestlike) {
        if ($trialid > 0) {
            $out .= '<a class="btn-outline-main cta" href="#" data-campus-trial-redirect="'.(int)$trialid.'">'.
                        get_string('view_trial', 'local_campus').
                    '</a>';
        }
        $out .= '<a class="default-btn cta" href="'. s($subscribeurl) .'">'.
                    get_string('trial_btn_subscribe', 'local_campus').
                '</a>';
    } else {
        $out .= '<a class="default-btn cta" href="'. s($realurl) .'">'.
                    get_string('view_real', 'local_campus').
                '</a>';
    }
    $out .= '</div>';
    return $out;
}


// 1) Choisir un "candidat" de départ pour afficher la vitrine : id (réel ou essai) ou trial
$candidate = $passedid > 0 ? $passedid : ($trialparam > 0 ? $trialparam : 0);
if ($candidate === 0) {
    throw new moodle_exception('missingparam', 'error', '', 'id'); // sécurité
}

// 2) Afficher la vitrine du COURS RÉEL si dispo ; sinon, on affiche le candidat
$realid    = (int)(local_campus_cf('realcourseid', $candidate) ?? 0);
$displayid = $candidate;    // cours dont on affiche (titre/summary/image)
$trialid   = 0;

if ($realid > 0 && $realid !== $candidate) {
    // On nous a donné un essai → vitrine = réel, trial = candidat
    $displayid = $realid;
    $trialid   = $candidate;
} else {
    // On nous a donné le réel → chercher son essai (ou garder trialparam si fourni)
    $trialid = $trialparam > 0
        ? $trialparam
        : (int)(local_campus_cf('trialcourseid', $displayid) ?? 0);
}


$course = $DB->get_record('course', ['id' => $displayid], '*', MUST_EXIST);
$context = context_course::instance($displayid);

// 2) Page Moodle
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/campus/course.php', [
    'id'         => $passedid ?: null,
    'trial'      => $trialparam ?: null,
    'layout'     => $layout,
    'style'      => $style ?: null,
    'checktrial' => $checktrial ? 1 : null,
]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/local/campus/styles.css')); // ta CSS locale

//$PAGE->navbar->ignore(true);
$PAGE->navbar->add(get_string('courses'), new moodle_url('/local/campus/courses.php'));
$PAGE->navbar->add(format_string($course->fullname), $PAGE->url);



// 3) Image via le handler Edly
$edlyCourseHandler = new edlyCourseHandler();
$edlyCourse = $edlyCourseHandler->edlyGetCourseDetails($displayid);
// $edlyCourse->edlyRender->coverImage contient du HTML <img ...>
$coverhtml = (string)($edlyCourse->edlyRender->coverImage ?? '');

// 4) Summary (description) du cours
$summary = format_text($course->summary, $course->summaryformat, [
    'context' => $context,
    'overflowdiv' => true
]);

// 5) URLs boutons
$trialurl = $trialid > 0 ? (new moodle_url('/course/view.php', ['id' => $trialid]))->out(false) : null;
$realurl  = (new moodle_url('/course/view.php', ['id' => $displayid]))->out(false);

// 6) Layout
$layout = in_array($layout, [1,2,3], true) ? $layout : 1;

ob_start();
local_campus_inject_trial_ui($PAGE);
$campus_trial_modal = ob_get_clean();

echo $OUTPUT->header();
echo $campus_trial_modal;

?>
<div class="campus-course layout-<?= (int)$layout ?><?= $dark ? ' dark' : '' ?>">
  <div class="container">

    <?php if ($layout === 1): ?>
      <!-- LAYOUT 1 : Hero plein écran + carte -->
      <div class="course-hero">
        <figure class="hero-figure">
          <?= $coverhtml ?>
          <figcaption class="hero-title">
            <h1><?= format_string($course->fullname) ?></h1>
          </figcaption>
        </figure>
      </div>

      <div class="course-body card-like">
        <div class="course-summary">
          <?= $summary ?>
        </div>
        <?= local_campus_ctas_html($isrestricted, $trialid, $realurl, $subscribeurl) ?>
      </div>

    <?php elseif ($layout === 2): ?>
      <!-- LAYOUT 2 : Split image / texte -->
      <div class="row g-4 align-items-start course-split">
        <div class="col-lg-6">
          <figure class="split-figure">
            <?= $coverhtml ?>
          </figure>
        </div>
        <div class="col-lg-6">
          <h1 class="split-title"><?= format_string($course->fullname) ?></h1>
          <div class="split-summary">
            <?= $summary ?>
          </div>
          <?= local_campus_ctas_html($isrestricted, $trialid, $realurl, $subscribeurl) ?>
        </div>
      </div>

    <?php else: ?>
      <!-- LAYOUT 3 : Carte élégante -->
      <div class="course-card">
        <figure class="card-figure">
          <?= $coverhtml ?>
        </figure>
        <div class="card-content">
          <h1 class="card-title"><?= format_string($course->fullname) ?></h1>
          <div class="card-summary">
            <?= $summary ?>
          </div>
          <?= local_campus_ctas_html($isrestricted, $trialid, $realurl, $subscribeurl) ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
  <?php
    $backurl = new moodle_url('/local/campus/courses.php');
    echo html_writer::div(
      html_writer::link($backurl, get_string('back_to_all_courses', 'local_campus'), ['class' => 'btn btn-link p-0 mb-3']),
      'campus-course-back'
    );
  ?>


</div>
<?php
echo $OUTPUT->footer();
?>