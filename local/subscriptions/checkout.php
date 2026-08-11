<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
// Essayer de charger les helpers Campus (pour le sélecteur d’indicatif)
$campusdir = \core_component::get_component_directory('local_campus');
if ($campusdir && file_exists($campusdir.'/lib.php')) {
    require_once($campusdir.'/lib.php');
}



use local_subscriptions\url\UrlFactory;
use local_subscriptions\commerce\catalog\checkout\CommerceSubscriptionCheckoutPlanInactiveException;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\constants\Operation;
use local_subscriptions\support\Region;

\local_subscriptions\subscription_config::guard_public_access();

// --- Helpers monnaie (symbole ou code selon setting) ---
function ls_cur_symbol(string $cur): string {
    $c = strtoupper($cur);
    return $c === 'EUR' ? '€' : ($c === 'RUB' || $c === 'RUR' ? '₽' : $c);
}
function ls_format_money(float $amount, string $currency): string {
    $usesymbol = (bool) get_config('local_subscriptions','display_currency_symbols');
    $amt = number_format($amount, 2, '.', '');
    return $usesymbol ? ($amt.' '.ls_cur_symbol($currency)) : ($amt.' '.strtoupper($currency));
}


$forceguest = optional_param('forceguest', 0, PARAM_BOOL);
$istripossible = function_exists('local_campus_is_trial_user');
if (!$istripossible) {
    // tentative douce de charger la fonction depuis local_campus/lib.php ; sinon fallback (voir §3)
    $comp = \core_component::get_component_directory('local_campus');
    if ($comp && file_exists($comp.'/lib.php')) {
        require_once($comp.'/lib.php');
        $istripossible = function_exists('local_campus_is_trial_user');
    }
}
$istrial  = $istripossible ? (bool) local_campus_is_trial_user() : false;

$isguest    = (!isloggedin() || isguestuser());
$isrestricted = $isguest || $istrial || $forceguest;   // ← la “philo” commune
$isrealguest  = $isguest && !$istrial && !$forceguest;


$planid   = required_param('planid', PARAM_INT);
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHANUMEXT));
if (!in_array($currency, ['EUR','RUB'], true)) {
    $cc = Region::detect_country();
    $currency = in_array($cc, ['RU','BY'], true) ? 'RUB' : 'EUR';
}


$userid   = (isloggedin() && !isguestuser()) ? (int)$USER->id : 0;
//$effUserid = $isrestricted ? 0 : $userid; // ← on traite trial comme invité
$effUserid = (!$isguest && !$forceguest) ? $userid : 0;


try {
    $checkoutcontext = CommerceCatalogFactory::create()
        ->subscription_checkout_page()
        ->prepare($userid, $effUserid, $planid, $currency);
} catch (CommerceSubscriptionCheckoutPlanInactiveException $exception) {
    $url = new \moodle_url('/local/subscriptions/subscribe.php', [
        'scope' => $exception->get_scope_id(),
    ]);
    redirect(
        $url,
        get_string('plan_inactive_redirect', 'local_subscriptions'),
        0,
        \core\output\notification::NOTIFY_WARNING
    );
}

global $USER, $SITE;

$plan = $checkoutcontext->get_plan();
$currsub = $checkoutcontext->get_current_subscription();
$currplan = $checkoutcontext->get_current_plan();
$options = $checkoutcontext->get_options();
$usedCurrency = $checkoutcontext->get_used_currency();
$basePrice = $checkoutcontext->get_base_price();
$hasSelected = $checkoutcontext->is_requested_currency_available();
$discountOpen = $checkoutcontext->is_discount_open();
$discPct = $checkoutcontext->get_discount_percent();
$deadlineTs = $checkoutcontext->get_discount_deadline();
$finalPriceNew = $checkoutcontext->get_final_purchase_price();

if ($checkoutcontext->is_already_covered()) {
    redirect(
        new moodle_url('/local/subscriptions/subscribe.php', ['currency' => $currency]),
        get_string('plan_already_covered', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$multipleOptions = count($options) > 1;

// Texte d’aide au-dessus des options (selon le contexte)
$hasupgrade = array_reduce($options, fn($c,$o)=>$c || ($o['key']===Operation::UPGRADE_NOW_REPLACE_CHAIN), false);
if ($isrestricted) {
    $helptext = get_string('advisor_help_guest', 'local_subscriptions');
} else {
    $helptext = $hasupgrade
        ? get_string('advisor_help_upgrade', 'local_subscriptions')
        : get_string('advisor_help_standard', 'local_subscriptions');
}

// Texte durée (à partir de duration_key) — i18n + centralisé.
$durationkey = trim(mb_strtolower($plan->duration_key ?? '1year'));

// Récupère le mapping clé → libellé depuis la config (plan_… dans lang)
$labels = \local_subscriptions\subscription_config::get_plans();

// libellé connu ou fallback sur 1 an
$durationtext = $labels[$durationkey] ?? ($labels['1year'] ?? get_string('plan_1year','local_subscriptions'));


// Page bootstrap.
$embedded = optional_param('embedded', 0, PARAM_BOOL);

$PAGE->set_url(new moodle_url(UrlFactory::checkout($planid, $currency)->out(false), [
    'embedded' => $embedded ? 1 : 0,
]));
$PAGE->set_context(context_system::instance());

$PAGE->set_pagelayout('standard');
$PAGE->add_body_class('commerce-chromeless-page');
$PAGE->set_title(get_string('checkout_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->requires->css(
    new moodle_url(
        \local_subscriptions\subscription_config::plugin_stylesheet_page()
    )
);

if ($embedded) {
    $PAGE->add_body_class('subs-embedded');
}

$PAGE->requires->strings_for_js(
    ['summary_price_wait', 'existing_account_hint_html'],
    'local_subscriptions'
);
$PAGE->requires->js_call_amd('local_subscriptions/guest_email_hint', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/checkout', 'init');

// Langue UI courante (priorité à la session, sinon langue effective de la page)
$uilang = strtolower(substr((string)($SESSION->lang ?? current_language() ?? $USER->lang ?? $CFG->lang ?? (get_config('defaultuserlang','local_subscriptions') ?? 'ru')), 0, 2));
if (!in_array($uilang, ['fr','en','ru'], true)) { $uilang = 'ru'; }

// Rendu.
echo $OUTPUT->header();

echo html_writer::tag('style', <<<CSS
/* Mode embed pour checkout dans la popup (header/footer Edly masqués) */

html.subs-embedded body#page-local-subscriptions-checkout .navbar-area,
html.subs-embedded body#page-local-subscriptions-checkout .main-navbar,
html.subs-embedded body#page-local-subscriptions-checkout .page-banner-area,
html.subs-embedded body#page-local-subscriptions-checkout .footer-area,
html.subs-embedded body#page-local-subscriptions-checkout .drawer,
html.subs-embedded body#page-local-subscriptions-checkout .drawer-toggles,
html.subs-embedded body#page-local-subscriptions-checkout #theme_boost-drawers-primary,
html.subs-embedded body#page-local-subscriptions-checkout .edly-fullwidth-top,
html.subs-embedded body#page-local-subscriptions-checkout .go-top {
    display: none !important;
}

html.subs-embedded body#page-local-subscriptions-checkout .edly-page-wrapper,
html.subs-embedded body#page-local-subscriptions-checkout #page {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    margin-top: 0 !important;
}

html.subs-embedded body#page-local-subscriptions-checkout .container {
    max-width: 960px;
    margin-top: 8px !important;
    margin-bottom: 8px !important;
}
CSS);

echo html_writer::script(<<<'JS'
(function() {
    if (window.self === window.top) {
        return;
    }

    document.documentElement.classList.add('subs-embedded');

    if (window.parent && window.parent !== window) {
        try {
            window.parent.postMessage({type: 'subs_popup_ready'}, '*');
        } catch (e) {}
    }
})();
JS
);


// URLs devise
$eurUrl = new moodle_url(UrlFactory::checkout($planid, 'EUR')->out(false), [
    'embedded' => $embedded ? 1 : 0,
]);
$rubUrl = new moodle_url(UrlFactory::checkout($planid, 'RUB')->out(false), [
    'embedded' => $embedded ? 1 : 0,
]);

// URL retour aux plans
$backUrl = new moodle_url(UrlFactory::subscribe()->out(false), [
    'embedded' => $embedded ? 1 : 0,
    'currency' => $currency,
]);
$backLabel = '← ' . get_string('back_to_plans', 'local_subscriptions');

// Ligne combinée : retour à gauche, devise à droite
echo html_writer::start_div('container my-3');
echo html_writer::start_div('ls-toprow d-flex align-items-center justify-content-between');


// Colonne gauche : retour
echo html_writer::div(
    html_writer::link(
        $backUrl,
        $backLabel,
        ['class' => 'ls-back-to-plans-link']
    ),
    'ls-toprow-back mb-2 mb-sm-0'
);

// Colonne droite : devise
$currencyHtml  = html_writer::div(
    get_string('currency_selector_label','local_subscriptions'),
    'text-muted small mb-1'
);
$currencyHtml .= html_writer::start_div('ls-seg');
$currencyHtml .= html_writer::link($eurUrl, get_string('currency_eur','local_subscriptions'),
    ['class' => 'seg'.($currency==='EUR'?' active':'')]);
$currencyHtml .= html_writer::link($rubUrl, get_string('currency_rub','local_subscriptions'),
    ['class'=>'seg'.($currency==='RUB'?' active':'')]);
$currencyHtml .= html_writer::end_div();

echo html_writer::div($currencyHtml, 'ls-toprow-currency text-sm-end');

echo html_writer::end_div(); // .ls-toprow
echo html_writer::end_div(); // .container


// Bandeau compte d’essai (si triallimited)
if ($istrial ?? false) {
    echo html_writer::div(
        get_string('trial_checkout_banner','local_subscriptions'),
        'alert alert-warning mb-3'
    );
}


if (!$hasSelected) {
    $note = (object)['curr'=>$currency, 'fallback'=>$usedCurrency];
    echo html_writer::div(
        get_string('price_unavailable_in','local_subscriptions', $note),
        'text-muted small mb-2'
    );
}

$displayname    = local_subscriptions_plan_display_name($plan);
$planlabel      = get_string('plan_label', 'local_subscriptions');
$fullaccessline = get_string('checkout_full_access_line', 'local_subscriptions');

// Petit bloc résumé du plan, directement au-dessus du formulaire
echo html_writer::start_div('ls-plan-summary mb-3');
/* echo html_writer::tag('h2',
    s($planlabel).' '.format_string($displayname),
    ['class' => 'h4 m-0']
); */
echo html_writer::tag('h2',
    format_string($displayname),
    ['class' => 'h4 m-0']
);
echo html_writer::div(
    s($fullaccessline),
    'text-muted small mt-1'
);
echo html_writer::end_div();

// Formulaire (visiteur vs connecté).
$outerclasses = $embedded ? 'p-3 bg-light-subtle' : 'card p-3 bg-light-subtle';
echo html_writer::start_div($outerclasses);


$formattrs = [
    'action' => (UrlFactory::create_session())->out(false),
    'method' => 'post',
    'class'  => 'ls-checkout-form',
];

echo html_writer::start_tag('form', $formattrs);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'planid',   'value' => $planid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'currency', 'value' => core_text::strtolower($usedCurrency)]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'uilang',
    'value'=> s($uilang)
]);

echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'embedded',
    'value' => $embedded ? 1 : 0,
]);


// === CSRF: ajout du sesskey (à coller ici) ==============================
echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'sesskey',
    'value' => sesskey()
]);
// =======================================================================

// Pré-remplissage si connecté.
$prefillemail = $isrestricted ? '' : ($USER->email ?? '');
$prefillfn    = $isrestricted ? '' : ($USER->firstname ?? '');
$prefillln    = $isrestricted ? '' : ($USER->lastname ?? '');

// Champs (si invité) — sinon on les masque mais on les envoie quand même.
$fieldsstyle = $isrestricted ? '' : 'display:none';

if ($isrestricted){
    // En-tête et petite aide au-dessus du formulaire des coordonnées
    // echo html_writer::tag('h5', get_string('personal_info_title', 'local_subscriptions'), ['class'=>'mt-0 mb-1']);
    // echo html_writer::div(get_string('personal_info_help', 'local_subscriptions'), 'text-muted mb-2');
}

// (Invité) lien connexion plus visible
if (!$effUserid) {
    // URL de retour : checkout plein écran (embedded=0) avec plan + devise actuels
    $returnto = new moodle_url(
        UrlFactory::checkout($planid, $currency)->out(false),
        [
            'embedded' => 0,          // on force la version non-embedded
            // planid et currency sont déjà dans l’URL générée par UrlFactory::checkout,
            // donc pas besoin de les repasser ici, mais tu peux si tu veux être explicite.
        ]
    );

    $loginurl  = new moodle_url('/login/index.php', ['returnurl' => $returnto->out(false)]);
    $logintext = get_string('have_account_login_to_see_options', 'local_subscriptions');

    $linkhtml = html_writer::link(
        $loginurl,
        '<i class="ri-question-line ls-have-account-icon" aria-hidden="true"></i>'
        . html_writer::span($logintext, 'ls-have-account-text'),
        [
            'class'  => 'link-soft fw-normal ls-have-account-link',
            'target' => '_top', // on sort de la popup/iframe si besoin
        ]
    );

    echo html_writer::div($linkhtml, 'mb-2 ls-have-account-hint');
}



echo html_writer::start_div('ls-fields', ['style' => $fieldsstyle]);

if ($isrealguest) {
    // VRAI INVITÉ : on réutilise exactement le formulaire de la popup Trial
    $defaults = (object)[
        'firstname' => $prefillfn,
        'lastname'  => $prefillln,
        'email'     => $prefillemail,
    ];
    echo local_campus_render_signup_fields('checkout', $defaults);
} else {
    // Cas restreint mais pas "vrai invité" (trial, forceguest, etc.)
    // On garde le formulaire simple (email / prénom / nom) si tu en as besoin.
    echo html_writer::tag('label',
        get_string('email') . ' ' . html_writer::span('*', 'text-danger'),
        ['for' => 'email', 'class' => 'form-label small mt-2']
    );
    echo html_writer::empty_tag('input', [
        'type' => 'email', 'name' => 'email', 'id' => 'email',
        'class' => 'form-control mb-2', 'placeholder' => get_string('email'),
        'required' => $isrestricted ? true : null, 'value' => s($prefillemail)
    ]);
    echo html_writer::div('', 'text-warning small', ['id'=>'ls_email_hint']);

    echo html_writer::tag('label',
        get_string('firstname') . ' ' . html_writer::span('*', 'text-danger'),
        ['for' => 'firstname', 'class' => 'form-label small mt-2']
    );
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'firstname', 'id' => 'firstname',
        'class' => 'form-control mb-2', 'placeholder' => get_string('firstname'),
        'required' => $isrestricted ? true : null, 'value' => s($prefillfn)
    ]);

    echo html_writer::tag('label',
        get_string('lastname') . ' ' . html_writer::span('*', 'text-danger'),
        ['for' => 'lastname', 'class' => 'form-label small mt-2']
    );
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'lastname', 'id' => 'lastname',
        'class' => 'form-control mb-3', 'placeholder' => get_string('lastname'),
        'required' => $isrestricted ? true : null, 'value' => s($prefillln)
    ]);
}

echo html_writer::end_div(); // .ls-fields


// Radios d’options (au-dessus du bouton payer)
if ($multipleOptions) {
    echo html_writer::tag('h5', get_string('choose_option', 'local_subscriptions'), ['class'=>'mt-0 mb-1']);
    echo html_writer::div($helptext, 'text-muted mb-3');
}

$displayBase = $basePrice; // prix catalogue (dans $usedCurrency)

// ----- LIGNE D’INFO PROMO (sous le titre, avant les radios) -----
if ($discountOpen && $discPct > 0 && $deadlineTs > time()) {
    echo \html_writer::div(
        get_string('checkout_discount_note_prefix','local_subscriptions', $discPct) .
        ' <span class="ls-disc-eta fw-semibold"></span>',
        'alert alert-info py-2 px-3 mb-3 mt-0 small',
        ['id' => 'ls_discount_note', 'data-deadline' => $deadlineTs]
    );

    // Compte à rebours : X jours HH:mm:ss
    echo \html_writer::script("
      (function(){
        var el  = document.getElementById('ls_discount_note');
        if(!el) return;
        var tgt = el.querySelector('.ls-disc-eta'); if(!tgt) return;
        var dl  = parseInt(el.getAttribute('data-deadline'),10) * 1000;
        function two(n){ return (n<10?'0':'') + n; }
        function tick(){
          var now = Date.now();
          var sec = Math.max(0, Math.floor((dl - now)/1000));
          var d = Math.floor(sec/86400);
          var h = Math.floor((sec % 86400) / 3600);
          var m = Math.floor((sec % 3600) / 60);
          var s =        (sec % 60);
          var dlabel = d > 0 ? (d + ' " . addslashes(get_string('days_short','local_subscriptions')) . " ') : '';
          tgt.textContent = dlabel + two(h) + ':' + two(m) + ':' + two(s);
        }
        tick();
        setInterval(tick, 1000);
      })();
    ");
}
echo \html_writer::script(<<<'JS'
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.campus-password-toggle');
    if (!btn) { return; }

    var targetSel = btn.getAttribute('data-target');
    if (!targetSel) { return; }
    var input = document.querySelector(targetSel);
    if (!input) { return; }

    var icon  = btn.querySelector('.password-toggle-icon');
    var showLabel = btn.getAttribute('data-show-label') || '';
    var hideLabel = btn.getAttribute('data-hide-label') || '';

    if (input.type === 'password') {
        input.type = 'text';
        btn.setAttribute('aria-label', hideLabel);
        btn.setAttribute('title', hideLabel);
        if (icon) { icon.textContent = '🙈'; }
    } else {
        input.type = 'password';
        btn.setAttribute('aria-label', showLabel);
        btn.setAttribute('title', showLabel);
        if (icon) { icon.textContent = '👁️'; }
    }
});
JS
);

// Cas 1 OPTION UNIQUE : on ne montre pas les radios, mais on garde une valeur cachée.
if (!$multipleOptions) {
    $only = $options[0];

    echo html_writer::empty_tag('input', [
        'type'  => 'hidden',
        'name'  => 'operation',
        'value' => $only['key'],
    ]);

    if (!empty($only['ref_subid'])) {
        echo html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => 'ref_subid',
            'value' => (int)$only['ref_subid'],
        ]);
    }

    if (!empty($only['extra'])) {
        echo html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => 'extra_json',
            'value' => json_encode($only['extra'], JSON_UNESCAPED_UNICODE),
        ]);
    }
} else {
    // ----- RADIOS DES OPTIONS -----
    foreach ($options as $i => $opt) {
        $id   = 'opt_'.$i;
        $isupgrade = (strpos($opt['key'], Operation::UPGRADE_NOW_REPLACE_CHAIN) === 0);
        $cur  = strtoupper($opt['currency'] ?? $usedCurrency);

        // Montant final préparé par la frontière Native checkout.
        $finalForThis = (float)$opt['amount'];

        if ($opt['key'] === Operation::PURCHASE_NEW && $discountOpen && $discPct > 0) {
            $finalForThis = $finalPriceNew;
        }

        // Prix de référence à barrer.
        $catalog = (float)$displayBase;

        if ($opt['key'] === Operation::UPGRADE_NOW_REPLACE_CHAIN) {
            $catalog = !empty($opt['extra']['upgrade_base_amount'])
                ? (float)$opt['extra']['upgrade_base_amount']
                : $finalForThis;
        } else if ($opt['key'] === Operation::PURCHASE_NEW) {
            $catalog = (float)$displayBase;
        }

        echo html_writer::start_div('form-check mb-2');

        // Libellés formatés (respectent l’option symbole/devise)
        $catalogDisp = ls_format_money($catalog, $cur);
        $finalDisp   = ls_format_money($finalForThis, $cur);

        // Prix de base vs final : on affiche les deux uniquement s'ils diffèrent vraiment
        $hasDiff = (abs($finalForThis - $catalog) > 0.01);

        if ($hasDiff) {
            $priceHtml =
                html_writer::span($catalogDisp, 'text-muted text-decoration-line-through me-2') .
                html_writer::span($finalDisp, 'fw-semibold text-success');
        } else {
            $priceHtml = html_writer::span($catalogDisp, 'fw-semibold');
        }

        // On garde aussi les data-* pour ton JS de résumé
        echo html_writer::empty_tag('input', [
            'type'  => 'radio',
            'class' => 'form-check-input',
            'name'  => 'operation',
            'id'    => $id,
            'value' => $opt['key'],

            // numériques (sécurité / create_session)
            'data-base'     => number_format($catalog, 2, '.', ''),
            'data-amount'   => number_format($finalForThis, 2, '.', ''),  // ce qu'on montre comme "à payer"
            'data-final'    => $hasDiff ? number_format($finalForThis, 2, '.', '') : '',

            // ces trois-là servent à ton JS (si tu veux continuer à les utiliser)
            'data-base-display'   => $catalogDisp,
            'data-amount-display' => $finalDisp,
            'data-final-display'  => $hasDiff ? $finalDisp : '',

            // métadonnées
            'data-currency'   => $cur,
            'data-isupgrade'  => $isupgrade ? '1' : '0',
            'data-disc-pct'   => (string)($opt['extra']['discount_percent'] ?? 0),
            'data-extra'      => !empty($opt['extra']) ? json_encode($opt['extra'], JSON_UNESCAPED_UNICODE) : '',
        ]);

        // Label texte + prix HTML (on ne l'échappe pas pour garder les <span>)
        $labelHtml = $opt['label'] . ' — ' . $priceHtml;
        echo html_writer::label(
            $labelHtml,
            $id,
            false,
            ['class' => 'form-check-label']
        );

        // Détails pour upgrade (inchangé)
        if ($isupgrade && $currsub && $currplan) {
            $body = local_subs_upgrade_calc_body($opt, $currplan, $plan, $currsub, $usedCurrency);
            echo \html_writer::tag(
                'details',
                \html_writer::tag('summary', get_string('upgrade_details_summary','local_subscriptions'), [
                    'class' => 'small text-muted cursor-pointer'
                ]) .
                \html_writer::div($body, 'mt-2 p-2 border rounded bg-light small'),
                ['class' => 'mt-2 upg-details']
            );
        }

        if (!empty($opt['ref_subid'])) {
            echo \html_writer::empty_tag('input', ['type'=>'hidden','name'=>'ref_subid','value'=>$opt['ref_subid']]);
        }

        echo \html_writer::end_div();
    }
}

if ($multipleOptions) {
    echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'extra_json','id'=>'ls_extra_json']);
}
echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'force_guest','value'=> $forceguest ]);
echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'from','value'=> $istrial ? 'trial' : '']);
echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'enforce_login_if_known',
    'value' => '1'
]);


// Résumé prix SOUS les options (et donc sous le formulaire invité, car ce bloc est après)
echo html_writer::start_div('mt-3 pt-3 border-top');

// Titre : "Подписка на {durée}" si 1 option, sinon titre standard
/* $summaryTitle = get_string('summary_price_title_single', 'local_subscriptions', $durationtext);
echo html_writer::tag('div', $summaryTitle, ['class'=>'text-muted small mb-1']); */

$summaryContent = html_writer::span(
    get_string('summary_price_wait', 'local_subscriptions'),
    'text-muted',
    ['id' => 'ls_price_summary']
);

if (!$multipleOptions) {
    $only    = $options[0];
    $cur     = strtoupper($only['currency'] ?? $usedCurrency);
    $catalog = (float)$displayBase;

    if ($only['key'] === Operation::UPGRADE_NOW_REPLACE_CHAIN) {
        echo html_writer::div(
            html_writer::span($only['badge'] ?? get_string('upgrade_badge', 'local_subscriptions'), 'badge bg-primary me-2')
            . html_writer::span($only['summary'] ?? '', 'fw-semibold'),
            'alert alert-info'
        );
    }

    if (!empty($only['extra']['discount_percent'])) {
        echo html_writer::div(
            get_string('upgrade_discount_applied', 'local_subscriptions', [
                'discount' => $only['extra']['discount_percent'],
            ]),
            'alert alert-success'
        );
    }   

    $finalForThis = (float)$only['amount'];

    // Prix de référence à barrer.
    $baseForThis = $catalog;

    if ($only['key'] === Operation::UPGRADE_NOW_REPLACE_CHAIN) {
        $baseForThis = !empty($only['extra']['upgrade_base_amount'])
            ? (float)$only['extra']['upgrade_base_amount']
            : $finalForThis;
    } else if ($only['key'] === Operation::PURCHASE_NEW) {
        $baseForThis = $catalog;
    }

    $baseDisp  = ls_format_money($baseForThis, $cur);
    $finalDisp = ls_format_money($finalForThis, $cur);

    if ($baseForThis > $finalForThis + 0.01) {
        $summaryContent =
            html_writer::span($baseDisp, 'text-muted text-decoration-line-through me-2') .
            html_writer::span($finalDisp, 'fw-semibold text-success');
    } else {
        $summaryContent = html_writer::span($finalDisp, 'fw-semibold');
    }

    $summaryContent = html_writer::span($summaryContent, null, ['id' => 'ls_price_summary']);
}


echo html_writer::tag('div', $summaryContent, ['class'=>'fs-5']);
echo html_writer::end_div();



// Bloc Terms + bouton, bien décollé et lié visuellement
echo html_writer::start_div('mt-2 pt-3 border-top');

// URLs policy/terms/offer (RU/BY vs reste du monde)
$urls = Region::policyUrls();

// Construire les 3 liens
$links = (object)[
    'policy' => html_writer::link(
        $urls['policy'],
        get_string('privacy_policy', 'local_subscriptions'),
        ['target' => '_blank', 'rel' => 'noopener']
    ),
    'terms' => html_writer::link(
        $urls['terms'],
        get_string('terms_cgu', 'local_subscriptions'),
        ['target' => '_blank', 'rel' => 'noopener']
    ),
    'offer' => html_writer::link(
        $urls['offer'],
        get_string('terms_cgv', 'local_subscriptions'),
        ['target' => '_blank', 'rel' => 'noopener']
    ),
];

echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', [
    'type'     => 'checkbox',
    'class'    => 'form-check-input',
    'id'       => 'agree_terms',
    'name'     => 'accept_terms',
    'value'    => '1',
    'required' => 'required',
]);
echo html_writer::label(
    get_string('i_accept_all_terms', 'local_subscriptions', $links),
    'agree_terms',
    false,
    ['class' => 'form-check-label small']
);
echo html_writer::end_div();


echo html_writer::end_div(); // fin du bloc border-top


// Bouton principal
if ($discountOpen && $discPct > 0) {
    $btntext = get_string('checkout_go_to_payment_discount', 'local_subscriptions');
} else {
    $btntext = get_string('checkout_go_to_payment', 'local_subscriptions');
}


// data-mode = 'user' si on a un effUserid (connecté non restreint), 'guest' sinon
$btnmode = $effUserid ? 'user' : 'guest';

echo html_writer::tag('button', $btntext, [
    'type'      => 'submit',
    'class'     => 'btn btn-outline-primary subscribe-button w-100 fs-5',
    'id'        => 'ls_submit_btn',
    'disabled'  => 'disabled',
    'data-mode' => $btnmode,
]);


echo html_writer::end_div();

echo html_writer::end_tag('form');

echo html_writer::end_div(); // .card p-3

echo $OUTPUT->footer();
