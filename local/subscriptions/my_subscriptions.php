<?php
require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;

require_login();

redirect(UrlFactory::my_purchases());


// // local/subscriptions/my_subscriptions.php
// require_once(__DIR__.'/../../config.php');
// require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');

// use local_subscriptions\constants\Operation;
// use local_subscriptions\constants\Status;
// use local_subscriptions\payment\Provider;
// use local_subscriptions\url\UrlFactory;
// use local_subscriptions\support\SubsPresenter;

// $PAGE->set_context(context_system::instance());
// $PAGE->set_url(UrlFactory::my_subscriptions());

// require_login(); // force la connexion

// // Bloquer aussi l'utilisateur invité
// if (isguestuser()) {
//     redirect(new moodle_url('/login/index.php'));
// }

// $PAGE->set_pagelayout('standard');
// $PAGE->set_title(get_string('mysubs_title', 'local_subscriptions'));
// $PAGE->set_heading(get_string('mysubs_title', 'local_subscriptions'));


// global $DB, $OUTPUT;

// // 1) Charger toutes les souscriptions de l'utilisateur
// $subs = $DB->get_records('user_subscription', ['userid' => $USER->id], 'end_date DESC');

// // 2) Précharger les plans reliés (pour is_recurring + nom)
// $planids = array_unique(array_map(static fn($s) => (int)$s->planid, $subs));
// $plans = $planids ? $DB->get_records_list('subscription_plan', 'id', $planids, '',
//     'id,name,is_recurring') : [];

// // Plans d'essai à forcer par config (ex: "12,34") sinon chaîne vide.
// $trialids = array_filter(array_map('intval',
//     explode(',', (string)get_config('local_subscriptions','trial_planids'))
// ));

// // Détection "essai 7 jours" (config > heuristique)
// $detect_trial = static function($plan, $sub) use ($trialids): bool {
//     if (!empty($trialids) && in_array((int)$plan->id, $trialids, true)) {
//         return true;
//     }
//     // Heuristique robuste si pas de config :
//     //  - nom du plan contient essai/trial/проб
//     //  - ET/OU durée ≈ 7 jours + prix payé = 0
//     $name = mb_strtolower((string)format_string($plan->name), 'UTF-8');
//     if (preg_match('/\b(essai|trial|проб)/u', $name)) {
//         return true;
//     }
//     $durationsec = (int)$sub->end_date - (int)$sub->start_date;
//     $days = (int)round($durationsec / DAYSECS);
//     $paid0 = (float)($sub->pricepaid ?? 0) == 0.0;
//     if ($paid0 && $days >= 6 && $days <= 8) { // ~7 jours
//         return true;
//     }
//     return false;
// };

// // Petits helpers
// $fmtmoney = fn($amt, $cur) => format_float((float)$amt, 2).' '.strtoupper((string)$cur);

// // 3) Render
// echo $OUTPUT->header();

// /** @var \local_subscriptions\output\renderer $lsrenderer */
// $lsrenderer = $PAGE->get_renderer('local_subscriptions');


// echo html_writer::tag('h3', get_string('mysubs_title', 'local_subscriptions'), ['class'=>'mb-3']);

// // Pas de souscriptions ?
// if (!$subs) {
//     echo $OUTPUT->notification(get_string('mysubs_empty', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
//     echo html_writer::div(
//         html_writer::link(UrlFactory::subscribe(), get_string('subscribe', 'local_subscriptions'), ['class'=>'btn btn-primary']),
//         'mt-3'
//     );
//     echo $OUTPUT->footer(); exit;
// }

// // Cards
// foreach ($subs as $sub) {
//     $plan = $plans[$sub->planid] ?? (object)['name'=>get_string('unknown_plan','local_subscriptions'), 'is_recurring'=>0];
    
//     $istrial = $detect_trial($plan, $sub);

//     $displayname = local_subscriptions_plan_display_name($plan);

//     $isactive = ($sub->status === Status::ACTIVE);
//     $cardclasses = 'card shadow-sm mb-3'.($isactive ? '' : ' border-0 bg-light'); // ← fond gris pour ≠ active

//     $head = html_writer::start_div('d-flex align-items-center justify-content-between');
//     $title = html_writer::tag('span', format_string($displayname), ['class'=>'h5 m-0']);
//     $badges = SubsPresenter::render_status_badge($sub->status);
//     $head .= $title . $badges;
//     $head .= html_writer::end_div();

//     // Corps : période, prix, provider
//     $list = html_writer::start_tag('ul', ['class'=>'list-unstyled mb-2 small']);

//     if (empty($sub->end_date ?? null)) {
//         $enddate = get_string('subfield_unlimited', 'local_subscriptions'); // 'Sans fin'
//     } else {
//         $enddate = userdate($sub->end_date);
//     }
    

//     $list .= html_writer::tag('li', html_writer::tag('span', get_string('period','local_subscriptions').': ', ['class'=>'text-muted']). userdate($sub->start_date).' &rarr; '.$enddate);
//     // Prix payé : on le masque pour les trials
//     if (!$istrial) {
//         $list .= html_writer::tag('li',
//             html_writer::tag('span', get_string('pricepaid','local_subscriptions').': ', ['class'=>'text-muted']) .
//             $fmtmoney($sub->pricepaid ?? 0, $sub->currency ?? '')
//         );
//     }
//     if (!empty($sub->payment_failed)) {
//         $list .= html_writer::tag('li',
//             html_writer::span(get_string('payment_failed','local_subscriptions'), 'badge bg-warning text-dark')
//             . (!empty($sub->last_payment_failed_at) ? html_writer::span(' — '.userdate($sub->last_payment_failed_at), 'text-muted ms-1') : ''),
//             ['class'=>'mt-1']
//         );
//     }
//     $list .= html_writer::end_tag('ul');

//     // Boutons : Customer Portal si Stripe + récurrent, et Détails (modal)
//     $btns = [];

//     // Récurrent ? => boutons Portal déjà faits ; sinon on propose Renouveler/Prolonger
//     if (empty($plan->is_recurring)) {

//         // Même devise et même montant que la sub
//         $cur = strtoupper($sub->currency ?? 'EUR');
//         $amt = format_float((float)($sub->pricepaid ?? 0), 2); // on réutilise exactement ce qui a été payé

//         if (!$istrial) {
//             $subscribelink = UrlFactory::subscribe();

// /*             if ($sub->status === Status::EXPIRED) {
//                 // RENOUVELER → simple CTA vers la page des plans
//                 $btns[] = html_writer::link(
//                     $subscribelink,
//                     get_string('renew_now','local_subscriptions'),
//                     ['class'=>'btn btn-primary btn-sm']
//                 );
//             }

//             if ($sub->status === Status::ACTIVE) {
//                 // PROLONGER → même chose, mais texte spécifique
//                 $btns[] = html_writer::link(
//                     $subscribelink,
//                     get_string('btn_extend','local_subscriptions'),
//                     ['class'=>'btn btn-outline-primary btn-sm']
//                 );
//             } */
//         }


//     }

//     if (!empty($plan->is_recurring) && $sub->payment_provider === Provider::STRIPE && !empty($sub->provider_customer_id)) { // Provider::ALFA not yet supported
//         $btns[] = html_writer::link(
//             UrlFactory::portal(['subid'=>$sub->id]),
//             get_string('manage_billing','local_subscriptions'),
//             ['class'=>'btn btn-outline-primary btn-sm']
//         );
//         // petit badge auto-renouvellement
//         $list .= html_writer::div(
//             html_writer::span(get_string('badge_recurring','local_subscriptions'), 'badge bg-info'),
//             'mb-2'
//         );
//     }

//     $modalid = 'subModal'.$sub->id;
//     $btns[] = html_writer::tag('button', get_string('details','local_subscriptions'),
//         ['class'=>'btn btn-outline-secondary btn-sm', 'data-bs-toggle'=>'modal', 'data-bs-target'=>'#'.$modalid]);
//     $btnshtml = implode(' ', $btns);

//     // Card HTML
//     echo html_writer::start_div($cardclasses);
//       echo html_writer::div($head, 'card-header bg-white');
//       echo html_writer::start_div('card-body');
//         echo $list;
//         echo html_writer::div($btnshtml, 'mt-2');
//       echo html_writer::end_div();
//     echo html_writer::end_div();

//     // Modal Détails
//     $rows = SubsPresenter::rows(
//         $sub,
//         $plan,
//         function (float $amount, string $cur) use ($fmtmoney): string { return $fmtmoney($amount, $cur); },
//         'user' // <- important
//     );
    
//     $table = html_writer::start_tag('table', ['class'=>'table table-sm mb-0']);
//     foreach ($rows as [$k,$v]) {
//         $table .= '<tr><th class="text-muted" style="width:28%;white-space:nowrap;">'.s($k).'</th><td class="fw-semibold">'.(is_string($v)?$v:s($v)).'</td></tr>';
//     }
//     $table .= html_writer::end_tag('table');

//     echo html_writer::start_div('modal fade', ['id'=>$modalid, 'tabindex'=>'-1', 'aria-hidden'=>'true']);
//       echo html_writer::start_div('modal-dialog modal-lg modal-dialog-scrollable');
//         echo html_writer::start_div('modal-content');
//           echo html_writer::div(
//               html_writer::tag('h5', get_string('subscription_details','local_subscriptions'), ['class'=>'modal-title'])
//             . html_writer::tag('button','', ['type'=>'button','class'=>'btn-close','data-bs-dismiss'=>'modal','aria-label'=>'Close']),
//             'modal-header d-flex align-items-center justify-content-between'
//           );
//           echo html_writer::div($table, 'modal-body bg-light');

//             if (!empty($sub->payment_failed)) {
//                 $portal = UrlFactory::portal(['subid' => $sub->id]);
//                 echo html_writer::div(
//                     html_writer::link($portal, get_string('manage_billing','local_subscriptions'), ['class'=>'btn btn-warning btn-sm']),
//                     'mt-2'
//                 );
//             }
//           echo html_writer::div(
//               html_writer::tag('button', get_string('close','local_subscriptions'), ['class'=>'btn btn-secondary','data-bs-dismiss'=>'modal']),
//             'modal-footer'
//           );
//         echo html_writer::end_div();
//       echo html_writer::end_div();
//     echo html_writer::end_div();

// }

// echo $OUTPUT->footer();
