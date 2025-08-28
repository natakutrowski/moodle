<?php
// local/subscriptions/checkout.php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib.php');

$planid   = required_param('planid', PARAM_INT);
$currency = optional_param('currency', '', PARAM_ALPHANUMEXT); // ex: 'eur'

global $DB, $USER, $SITE;

// Récup plan actif.
$plan = $DB->get_record('subscription_plan', ['id' => $planid, 'is_active' => 1], '*', MUST_EXIST);

// Devise choisie (ou première dispo).
if ($currency === '') {
    $priceobj = $DB->get_record('subscription_plan_price', ['planid' => $planid], '*', MUST_EXIST);
    $currency = $priceobj->currency;
} else {
    $priceobj = $DB->get_record('subscription_plan_price', ['planid' => $planid, 'currency' => core_text::strtolower($currency)], '*', MUST_EXIST);
}
$price = (float)$priceobj->price;

// Texte durée (à partir de duration_key).
$durationkey = $plan->duration_key ?? '1year';
$durationmap = [
    '1month' => '1 month',
    '3months'=> '3 months',
    '6months'=> '6 months',
    '1year'  => '1 year',
    '2years' => '2 years',
    '3years' => '3 years',
];
$durationtext = $durationmap[$durationkey] ?? '1 year';

// Page bootstrap.
$PAGE->set_url(new moodle_url('/local/subscriptions/checkout.php', ['planid' => $planid, 'currency' => $currency]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('checkout_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->requires->css('/local/subscriptions/styles.css');
$PAGE->requires->js_call_amd('local_subscriptions/checkout', 'init');

// Rendu.
echo $OUTPUT->header();

// Fil d’ariane simple.
echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/subscribe.php'), get_string('back_to_plans', 'local_subscriptions')),
    'mb-3'
);

// Carte récap plan.
echo html_writer::start_div('card shadow-sm mb-4');
echo html_writer::div(
    html_writer::tag('h2', format_string($plan->name), ['class' => 'h4 m-0']),
    'card-header bg-light'
);

$body = '';
// Description (si disponible).
if (!empty($plan->description)) {
    $body .= html_writer::div(format_text($plan->description), 'text-muted mb-2');
}

// Durée.
$body .= html_writer::div(get_string('checkout_duration', 'local_subscriptions') . ' ' .
          html_writer::span($durationtext, 'fw-semibold'), 'mb-2');

// (Optionnel) Liste de cours — si tu as déjà une fonction qui les récupère.
$plan->courses = local_subscriptions_get_courses_by_plan($plan->id);

// --- Courses included (accordion/toggle), reuse your function output ---
$courselist = '';
if (!empty($plan->courses)) {
    $courselist .= \html_writer::start_tag('ul', ['class' => 'list-unstyled courselist']);
    foreach ($plan->courses as $course) {
        $descid = 'desc-' . $plan->id . '-' . $course->id;
        $courselist .= \html_writer::start_tag('li', ['class' => 'course-item mb-1']);
        $courselist .= \html_writer::tag('a', $course->fullname, [
            'href' => '#',
            'class' => 'coursename',
            'data-toggle' => 'desc-toggle',
            'data-target' => '#' . $descid
        ]);
        $courselist .= \html_writer::tag('div', $course->summary, [
            'id' => $descid,
            'class' => 'course-desc mt-1 d-none',
        ]);
        $courselist .= \html_writer::end_tag('li');
    }
    $courselist .= \html_writer::end_tag('ul');
}
// Injecte le rendu dans le corps de carte :
$body .= html_writer::div($courselist, 'plan-courselist mb-3');

// Ajout du JS pour toggler la description
$body .= \html_writer::script("
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-toggle=\"desc-toggle\"]').forEach(function(trigger) {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                var target = document.querySelector(trigger.getAttribute('data-target'));
                if (target) {
                    target.classList.toggle('d-none');
                }
            });
        });
    });
");

echo html_writer::div($body, 'card-body');

// Prix (avec petit label).
$footer = html_writer::div(get_string('price_label', 'local_subscriptions'), 'text-muted small mb-1');
$footer .= html_writer::div(
    format_float($price, 2) . ' ' . strtoupper($currency),
    'fw-semibold fs-5'
);
echo html_writer::div($footer, 'card-footer bg-white');

// Formulaire (visiteur vs connecté).
echo html_writer::start_div('card p-3');

$formattrs = [
    'action' => (new moodle_url('/local/subscriptions/stripe/create_session.php'))->out(false),
    'method' => 'post',
    'class'  => 'ls-checkout-form',
];

echo html_writer::start_tag('form', $formattrs);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'planid',   'value' => $planid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'currency', 'value' => $currency]);

$isguest = (!isloggedin() || isguestuser());

// Pré-remplissage si connecté.
$prefillemail = $isguest ? '' : ($USER->email ?? '');
$prefillfn    = $isguest ? '' : ($USER->firstname ?? '');
$prefillln    = $isguest ? '' : ($USER->lastname ?? '');

// Champs (si invité) — sinon on les masque mais on les envoie quand même.
$fieldsstyle = $isguest ? '' : 'display:none';

echo html_writer::start_div('ls-fields', ['style' => $fieldsstyle]);

echo html_writer::tag('label',
    get_string('email') . ' ' . html_writer::span('*', 'text-danger'),
    ['for' => 'email', 'class' => 'form-label small mt-2']
);
echo html_writer::empty_tag('input', [
    'type' => 'email', 'name' => 'email', 'id' => 'email',
    'class' => 'form-control mb-2', 'placeholder' => get_string('email'),
    'required' => $isguest ? true : null, 'value' => s($prefillemail)
]);

echo html_writer::tag('label',
    get_string('firstname') . ' ' . html_writer::span('*', 'text-danger'),
    ['for' => 'firstname', 'class' => 'form-label small mt-2']
);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'firstname', 'id' => 'firstname',
    'class' => 'form-control mb-2', 'placeholder' => get_string('firstname'),
    'required' => $isguest ? true : null, 'value' => s($prefillfn)
]);

echo html_writer::tag('label',
    get_string('lastname') . ' ' . html_writer::span('*', 'text-danger'),
    ['for' => 'lastname', 'class' => 'form-label small mt-2']
);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'lastname', 'id' => 'lastname',
    'class' => 'form-control mb-3', 'placeholder' => get_string('lastname'),
    'required' => $isguest ? true : null, 'value' => s($prefillln)
]);

echo html_writer::end_div(); // .ls-fields

// Consentement RGPD (toujours visible).
echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox', 'class' => 'form-check-input', 'id' => 'consent',
    'required' => true, 'name' => 'consent'
]);
echo html_writer::tag('label',
    get_string('checkout_consent_label', 'local_subscriptions'),
    ['class' => 'form-check-label small', 'for' => 'consent']
);
echo html_writer::end_div();

// Bouton principal (style demandé).
$btntext = $isguest ? get_string('checkout_subscribe', 'local_subscriptions') : get_string('checkout_go_to_payment', 'local_subscriptions');
echo html_writer::tag('button', $btntext, [
    'type' => 'submit',
    'class' => 'btn btn-outline-primary subscribe-button w-100 fs-5',
    'data-mode' => $isguest ? 'guest' : 'user'
]);

echo html_writer::end_tag('form');
echo html_writer::end_div(); // .card p-3

echo $OUTPUT->footer();
