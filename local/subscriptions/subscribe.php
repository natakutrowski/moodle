<?php
require('../../config.php');
require_once(__DIR__ . '/lib/user_subs_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');

use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Operation;
use local_subscriptions\domain\SubscriptionAdvisor;

\local_subscriptions\subscription_config::guard_public_access();

// Devise globale (GET) – uniquement EUR ou RUB pour l’instant
$currency = optional_param('currency', '', PARAM_ALPHANUMEXT);
$currency = strtoupper($currency);
if (!in_array($currency, ['EUR','RUB'], true)) {
    // défaut : RU/BY -> RUB ; sinon EUR
    $cc = \local_subscriptions\support\Region::detect_country();
    $currency = in_array($cc, ['RU','BY'], true) ? 'RUB' : 'EUR';
}

$embedded = optional_param('embedded', 0, PARAM_BOOL);
// Pas besoin de require_login(); → page publique

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(UrlFactory::subscribe()->out(false), [
    'currency' => $currency,
    'embedded' => $embedded ? 1 : 0,
]));
$PAGE->set_title(get_string('subscribe', 'local_subscriptions'));
$PAGE->set_heading(get_string('subscribe', 'local_subscriptions'));
$PAGE->requires->css(
    new moodle_url(
        \local_subscriptions\subscription_config::plugin_stylesheet_page()
    )
);

$PAGE->set_pagelayout('standard');

if ($embedded) {
    $PAGE->add_body_class('subs-embedded');
}

// Rendu via renderer
echo $OUTPUT->header();

echo html_writer::tag('style', <<<CSS
html.subs-embedded body#page-local-subscriptions-subscribe .navbar-area,
html.subs-embedded body#page-local-subscriptions-subscribe .main-navbar,
html.subs-embedded body#page-local-subscriptions-subscribe .page-banner-area,
html.subs-embedded body#page-local-subscriptions-subscribe .footer-area,
html.subs-embedded body#page-local-subscriptions-subscribe .drawer,
html.subs-embedded body#page-local-subscriptions-subscribe .drawer-toggles,
html.subs-embedded body#page-local-subscriptions-subscribe #theme_boost-drawers-primary,
html.subs-embedded body#page-local-subscriptions-subscribe .edly-fullwidth-top,
html.subs-embedded body#page-local-subscriptions-subscribe .go-top {
    display: none !important;
}

html.subs-embedded body#page-local-subscriptions-subscribe .edly-page-wrapper,
html.subs-embedded body#page-local-subscriptions-subscribe #page {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    margin-top: 0 !important;
}

html.subs-embedded body#page-local-subscriptions-subscribe .ls-plans-page.container {
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
        } catch (e) {
        }
    }
})();
JS
);



// Segmented pills devise
// Utiliser l'URL de la page actuelle, qui contient embedded
$current = clone $PAGE->url;

$eurUrl = new \moodle_url($current, ['currency' => 'EUR']);
$rubUrl = new \moodle_url($current, ['currency' => 'RUB']);


echo \html_writer::start_div('container my-3');
echo \html_writer::div(get_string('currency_selector_label','local_subscriptions'), 'text-muted small mb-1');

// wrapper stylé type "segmented"
echo \html_writer::start_div('ls-seg');
echo \html_writer::link($eurUrl, get_string('currency_eur','local_subscriptions'),
    ['class'=>'seg'.($currency==='EUR'?' active':'')]);
echo \html_writer::link($rubUrl, get_string('currency_rub','local_subscriptions'),
    ['class'=>'seg'.($currency==='RUB'?' active':'')]);
echo \html_writer::end_div();

echo \html_writer::end_div();

// disable banners
/* local_campus_render_trial_discount_banner(false);
local_campus_render_subscription_expiry_banner(); */

global $DB;

$plans = $DB->get_records('subscription_plan', ['is_active' => 1], 'name ASC');

if (isloggedin() && !isguestuser()) {
    $plans = SubscriptionAdvisor::filter_plans_for_subscribe(
        (int)$USER->id,
        $plans
    );

    foreach ($plans as $plan) {
        $options = SubscriptionAdvisor::advise_options(
            (int)$USER->id,
            (int)$plan->id,
            $currency
        );

        if (empty($options)) {
            continue;
        }

        $bestoption = reset($options);

        $plan->display_amount = (float)$bestoption['amount'];
        $plan->display_currency = $bestoption['currency'] ?? $currency;

        $plan->display_base_amount =
            $bestoption['extra']['upgrade_base_amount']
            ?? $bestoption['extra']['base_amount']
            ?? null;

        $plan->display_discount_percent =
            !empty($bestoption['extra']['discount_percent'])
                ? (float)$bestoption['extra']['discount_percent']
                : 0;

        $plan->display_is_upgrade =
            (($bestoption['key'] ?? '') === Operation::UPGRADE_NOW_REPLACE_CHAIN);

        $plan->display_upgrade_summary = $bestoption['summary'] ?? '';
        $plan->display_badge = $bestoption['badge'] ?? '';

        $plan->display_cta = $plan->display_is_upgrade
            ? get_string('upgrade_cta', 'local_subscriptions')
            : get_string('subscribe', 'local_subscriptions');
    }
}

$plans = sort_plans_by_duration($plans, true);

if (empty($plans)) {
    echo html_writer::start_div('ls-plans-page container my-4');

    echo html_writer::div(
        html_writer::tag('h3', get_string('all_courses_owned_title', 'local_subscriptions')) .
        html_writer::tag('p', get_string('all_courses_owned_text', 'local_subscriptions')),
        'alert alert-success'
    );

    echo html_writer::end_div();
    echo $OUTPUT->footer();
    exit;
}

/** @var \local_subscriptions\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_subscriptions');

echo html_writer::start_div('ls-plans-page container my-4');
echo $renderer->render_available_plans($plans, $currency); // ← on passe la devise choisie
echo html_writer::end_div();



echo $OUTPUT->footer();
