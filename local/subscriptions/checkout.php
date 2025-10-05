<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib.php');

use local_subscriptions\url\UrlFactory;
use local_subscriptions\domain\SubscriptionAdvisor;
use local_subscriptions\constants\Operation;
use local_subscriptions\constants\Status;
use local_subscriptions\support\Region;

\local_subscriptions\subscription_config::guard_public_access();

$planid   = required_param('planid', PARAM_INT);
$currency = optional_param('currency', '', PARAM_ALPHANUMEXT); // ex: 'eur'

$userid = (isloggedin() && !isguestuser()) ? $USER->id : 0;
$options = $userid ? SubscriptionAdvisor::advise_options($userid, $planid, $currency) : [];

global $DB, $USER, $SITE;

// Récup plan actif.
$plan = $DB->get_record('subscription_plan', ['id' => $planid, 'is_active' => 1], '*', MUST_EXIST);

// Sub active la plus récente dans le MÊME scope que le plan cible (pour la popover d’upgrade).
$currsub  = null;
$currplan = null;
if (!empty($userid)) {
    $currsub = $DB->get_record_sql("
        SELECT s.*
          FROM {user_subscription} s
          JOIN {subscription_plan} p ON p.id = s.planid
          WHERE s.userid = :u AND s.status = '".Status::ACTIVE."' AND p.accessscopeid = :scope
          ORDER BY s.end_date DESC, s.id DESC
          LIMIT 1
    ", ['u' => $userid, 'scope' => (int)$plan->accessscopeid]);
    if ($currsub) {
        $currplan = $DB->get_record('subscription_plan', ['id' => $currsub->planid], '*', MUST_EXIST);
    }
}

// Devise choisie (ou première dispo).
if ($currency === '') {
    $priceobj = $DB->get_record('subscription_plan_price', ['planid' => $planid], '*', MUST_EXIST);
    $currency = $priceobj->currency;
} else {
    $priceobj = $DB->get_record('subscription_plan_price', ['planid' => $planid, 'currency' => core_text::strtolower($currency)], '*', MUST_EXIST);
}
$price = (float)$priceobj->price;

// Si pas connecté (achat invité), ne propose pas upgrade -> achat standard :
if (!$userid || empty($options)) {
    $options = [[
        'key'       => Operation::PURCHASE_NEW,
        'label'     => get_string('option_purchase_new', 'local_subscriptions'),
        'amount'    => $price, // ton prix déjà calculé pour le plan
        'currency'  => $currency,
        'ref_subid' => null
    ]];
}

// Texte d’aide au-dessus des options (selon le contexte)
$hasupgrade = array_reduce($options, fn($c,$o)=>$c || ($o['key']===Operation::UPGRADE_NOW_REPLACE_CHAIN), false);
if ($userid) {
    $helptext = $hasupgrade
        ? get_string('advisor_help_upgrade', 'local_subscriptions')
        : get_string('advisor_help_standard', 'local_subscriptions');
} else {
    $helptext = get_string('advisor_help_guest', 'local_subscriptions');
}

// Texte durée (à partir de duration_key) — i18n + centralisé.
$durationkey = trim(mb_strtolower($plan->duration_key ?? '1year'));

// Récupère le mapping clé → libellé depuis la config (plan_… dans lang)
$labels = \local_subscriptions\subscription_config::get_plans();

// libellé connu ou fallback sur 1 an
$durationtext = $labels[$durationkey] ?? ($labels['1year'] ?? get_string('plan_1year','local_subscriptions'));


// Page bootstrap.
$PAGE->set_url(UrlFactory::checkout($planid, $currency));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('checkout_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->requires->css('/local/subscriptions/styles.css');

$PAGE->requires->strings_for_js(
    ['summary_price_wait', 'existing_account_hint_html'],
    'local_subscriptions'
);
$PAGE->requires->js_call_amd('local_subscriptions/guest_email_hint', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/checkout', 'init');

// Rendu.
echo $OUTPUT->header();

// Fil d’ariane simple.
echo html_writer::div(
    html_writer::link(UrlFactory::subscribe(), get_string('back_to_plans', 'local_subscriptions')),
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
        // Après (évite le doublon):
        $label = $course->fullname;
        $hasicon = preg_match('/^\x{1F4D8}/u', $label); // 📘 en UTF-8
        if (!$hasicon) {
            $label = '&#x1F4D8; ' . $label; // entité => pas de mojibake
        }
        $courselist .= \html_writer::tag('a', $label, [
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
$body .= \html_writer::tag('p', get_string('courselist','local_subscriptions').' : ', ['class' => 'mb-1']);
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

// Formulaire (visiteur vs connecté).
echo html_writer::start_div('card p-3 bg-light-subtle');

$formattrs = [
    'action' => (UrlFactory::create_session())->out(false),
    'method' => 'post',
    'class'  => 'ls-checkout-form',
];

echo html_writer::start_tag('form', $formattrs);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'planid',   'value' => $planid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'currency', 'value' => core_text::strtolower($currency)]);

$isguest = (!isloggedin() || isguestuser());

// Pré-remplissage si connecté.
$prefillemail = $isguest ? '' : ($USER->email ?? '');
$prefillfn    = $isguest ? '' : ($USER->firstname ?? '');
$prefillln    = $isguest ? '' : ($USER->lastname ?? '');

// Champs (si invité) — sinon on les masque mais on les envoie quand même.
$fieldsstyle = $isguest ? '' : 'display:none';

if ($isguest){
    // En-tête et petite aide au-dessus du formulaire des coordonnées
    echo html_writer::tag('h5', get_string('personal_info_title', 'local_subscriptions'), ['class'=>'mt-0 mb-1']);
    echo html_writer::div(get_string('personal_info_help', 'local_subscriptions'), 'text-muted mb-2');
}

// (Invité) lien connexion plus visible
if (!$userid) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/login/index.php', ['returnurl' => qualified_me()]),
            get_string('have_account_login_to_see_options', 'local_subscriptions'),
            ['class'=>'link-primary fw-semibold']
        ),
        'mb-3'
    );
}

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
echo html_writer::div('', 'text-warning small', ['id'=>'ls_email_hint']);


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
    'class' => 'form-control mb-5', 'placeholder' => get_string('lastname'),
    'required' => $isguest ? true : null, 'value' => s($prefillln)
]);

echo html_writer::end_div(); // .ls-fields

// Radios d’options (au-dessus du bouton payer)
echo html_writer::tag('h5', get_string('choose_option', 'local_subscriptions'), ['class'=>'mt-0 mb-1']); // mt-0 : enlève la ligne d’espace
echo html_writer::div($helptext, 'text-muted mb-3');

// Aucune option pré-cochée
foreach ($options as $i => $opt) {
    $id   = 'opt_'.$i;
    $base = (float)$price;           // prix public du plan cible
    $amt  = (float)$opt['amount'];   // montant spécifique de l’option
    // Extra JSON (optionnel) — si l’option contient un champ "extra"
    $dataExtra = !empty($opt['extra'])
        ? json_encode($opt['extra'], JSON_UNESCAPED_UNICODE)
        : '';


    // prix dans l’étiquette (barré + vert si upgrade moins cher)
    $isupgrade = (strpos($opt['key'], Operation::UPGRADE_NOW_REPLACE_CHAIN) === 0);

    if ($isupgrade && $amt < $base) {
        $pricehtml =
            html_writer::span(format_float($base, 2).' '.strtoupper($opt['currency']), 'text-muted text-decoration-line-through me-2')
          . html_writer::span(format_float($amt, 2).' '.strtoupper($opt['currency']), 'fw-semibold text-success');
    } else {
        $pricehtml = html_writer::span(format_float($amt, 2).' '.strtoupper($opt['currency']), 'fw-semibold');
    }

    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', [
        'type'  => 'radio',
        'class' => 'form-check-input',
        'name'  => 'operation',
        'id'    => $id,
        'value' => $opt['key'],
        // PAS de 'checked'
        // Data pour le résumé de prix dynamique :
        'data-amount'   => $amt,
        'data-currency' => strtoupper($opt['currency']),
        'data-isupgrade'=> $isupgrade ? '1' : '0',
        'data-base'     => $base,
        'data-extra' => $dataExtra,
    ]);

    // Label texte (sans popover à l'intérieur)
    $labelHtml = $opt['label'] . ' — ' . $pricehtml;
    echo html_writer::label($labelHtml, $id, false, ['class' => 'form-check-label']);

    if ($isupgrade && $currsub && $currplan) {
        $body = local_subs_upgrade_calc_body($opt, $currplan, $plan, $currsub, $currency); // HTML BRUT

        echo html_writer::tag(
            'details',
            html_writer::tag('summary', get_string('upgrade_details_summary','local_subscriptions'), [
                'class' => 'small text-muted cursor-pointer'
            ]) .
            html_writer::div($body, 'mt-2 p-2 border rounded bg-light small'),
            ['class' => 'mt-2 upg-details']
        );
    }


    if (!empty($opt['ref_subid'])) {
        echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'ref_subid','value'=>$opt['ref_subid']]);
    }
    echo html_writer::end_div();
}

echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'extra_json','id'=>'ls_extra_json']);


// Résumé prix SOUS les options (et donc sous le formulaire invité, car ce bloc est après)
echo html_writer::start_div('mt-3 pt-3 border-top');
echo html_writer::tag('div', get_string('summary_price_title', 'local_subscriptions'), ['class'=>'text-muted small mb-1']);
echo html_writer::tag('div', html_writer::span(get_string('summary_price_wait', 'local_subscriptions'), 'text-muted', ['id'=>'ls_price_summary']), ['class'=>'fs-5']);
echo html_writer::end_div();

// Bloc Terms + bouton, bien décollé et lié visuellement
echo html_writer::start_div('mt-4 pt-3 border-top');

// URLs policy/terms (RU/BY vs reste du monde)
$urls = Region::policyUrls();

// Privacy
echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', [
    'type'     => 'checkbox',
    'class'    => 'form-check-input',
    'id'       => 'accept_privacy',
    'name'     => 'accept_privacy',
    'value'    => '1',
    'required' => 'required',
]);
echo html_writer::label(
    get_string('i_accept_policy', 'local_subscriptions',
        html_writer::link($urls['policy'], get_string('privacy_policy', 'local_subscriptions'), [
            'target' => '_blank', 'rel' => 'noopener'
        ])
    ),
    'accept_privacy',
    false,
    ['class' => 'form-check-label small']
);
echo html_writer::end_div();

// Terms / CGU-CGV
echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', [
    'type'     => 'checkbox',
    'class'    => 'form-check-input',
    'id'       => 'agree_terms',     // garde cet id pour compat avec ton JS actuel
    'name'     => 'accept_terms',    // côté serveur: required_param('accept_terms', PARAM_BOOL)
    'value'    => '1',
    'required' => 'required',
]);
echo html_writer::label(
    get_string('i_accept_terms', 'local_subscriptions',
        html_writer::link($urls['terms'], get_string('terms_cgu', 'local_subscriptions'), [
            'target' => '_blank', 'rel' => 'noopener'
        ])
    ),
    'agree_terms',
    false,
    ['class' => 'form-check-label small']
);
echo html_writer::end_div();

echo html_writer::end_div(); // fin du bloc border-top


// Bouton principal (désactivé tant qu’aucune option & terms non cochés)
$btntext = $isguest ? get_string('subscribe', 'local_subscriptions') : get_string('checkout_go_to_payment', 'local_subscriptions');
echo html_writer::tag('button', $btntext, [
    'type' => 'submit',
    'class' => 'btn btn-outline-primary subscribe-button w-100 fs-5',
    'id'    => 'ls_submit_btn',
    'disabled' => 'disabled'
]);

echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div(); // .card p-3

echo $OUTPUT->footer();
