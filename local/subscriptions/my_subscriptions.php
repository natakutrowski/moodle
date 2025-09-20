<?php
// local/subscriptions/my_subscriptions.php
require_once(__DIR__.'/../../config.php');

require_login(); // page utilisateur

$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/subscriptions/my_subscriptions.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mysubs_title', 'local_subscriptions'));
$PAGE->set_heading(fullname($USER));

global $DB, $OUTPUT;

// 1) Charger toutes les souscriptions de l'utilisateur
$subs = $DB->get_records('user_subscription', ['userid' => $USER->id], 'end_date DESC');

// 2) Précharger les plans reliés (pour is_recurring + nom)
$planids = array_unique(array_map(static fn($s) => (int)$s->planid, $subs));
$plans = $planids ? $DB->get_records_list('subscription_plan', 'id', $planids, '',
    'id,name,is_recurring') : [];

// Petits helpers
$statusbadge = function(string $status): string {
    switch ($status) {
        case 'active':   return html_writer::span(get_string('status_active', 'local_subscriptions'),   'badge bg-success');
        case 'queued':   return html_writer::span(get_string('status_queued', 'local_subscriptions'),   'badge bg-secondary');
        case 'replaced': return html_writer::span(get_string('status_replaced', 'local_subscriptions'), 'badge bg-warning text-dark');
        case 'canceled': return html_writer::span(get_string('status_canceled', 'local_subscriptions'), 'badge bg-danger');
        case 'expired':
        default:         return html_writer::span(get_string('status_expired', 'local_subscriptions'),  'badge bg-danger');
    }
};
$fmtmoney = fn($amt, $cur) => format_float((float)$amt, 2).' '.strtoupper((string)$cur);

// 3) Render
echo $OUTPUT->header();

echo html_writer::tag('h3', get_string('mysubs_title', 'local_subscriptions'), ['class'=>'mb-3']);

// Pas de souscriptions ?
if (!$subs) {
    echo $OUTPUT->notification(get_string('mysubs_empty', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);
    echo html_writer::div(
        html_writer::link(new moodle_url('/local/subscriptions/subscribe.php'), get_string('go_subscribe', 'local_subscriptions'), ['class'=>'btn btn-primary']),
        'mt-3'
    );
    echo $OUTPUT->footer(); exit;
}

// Cards
foreach ($subs as $sub) {
    $plan = $plans[$sub->planid] ?? (object)['name'=>get_string('unknown_plan','local_subscriptions'), 'is_recurring'=>0];
    
    $isactive = ($sub->status === 'active');
    $cardclasses = 'card shadow-sm mb-3'.($isactive ? '' : ' border-0 bg-light'); // ← fond gris pour ≠ active

    $head = html_writer::start_div('d-flex align-items-center justify-content-between');
    $title = html_writer::tag('span', format_string($plan->name), ['class'=>'h5 m-0']);
    $badges = $statusbadge($sub->status);
    $head .= $title . $badges;
    $head .= html_writer::end_div();

    // Corps : période, prix, provider
    $list = html_writer::start_tag('ul', ['class'=>'list-unstyled mb-2 small']);
    $list .= html_writer::tag('li', html_writer::tag('span', get_string('period','local_subscriptions').': ', ['class'=>'text-muted']). userdate($sub->start_date).' &rarr; '.userdate($sub->end_date));
    $list .= html_writer::tag('li', html_writer::tag('span', get_string('pricepaid','local_subscriptions').': ', ['class'=>'text-muted']). $fmtmoney($sub->pricepaid ?? 0, $sub->currency ?? ''));
    if (!empty($sub->payment_provider)) {
        $list .= html_writer::tag('li', html_writer::tag('span', 'Provider: ', ['class'=>'text-muted']). s($sub->payment_provider));
    }
    if (!empty($sub->payment_failed)) {
        $list .= html_writer::tag('li',
            html_writer::span(get_string('payment_failed_flag','local_subscriptions'), 'badge bg-warning text-dark')
            . (!empty($sub->last_payment_failed_at) ? html_writer::span(' — '.userdate($sub->last_payment_failed_at), 'text-muted ms-1') : ''),
            ['class'=>'mt-1']
        );
    }
    $list .= html_writer::end_tag('ul');

    // Boutons : Customer Portal si Stripe + récurrent, et Détails (modal)
    $btns = [];

    // Récurrent ? => boutons Portal déjà faits ; sinon on propose Renouveler/Prolonger
    if (empty($plan->is_recurring)) {

        // Même devise et même montant que la sub
        $cur = strtoupper($sub->currency ?? 'EUR');
        $amt = format_float((float)($sub->pricepaid ?? 0), 2); // on réutilise exactement ce qui a été payé

        if ($sub->status === 'expired') {
            // RENOUVELER (start = now)
            $renewurl = new moodle_url('/local/subscriptions/stripe/create_session.php', [
                'planid'           => $sub->planid,
                'currency'         => $cur,
                'operation'        => 'purchase_new',
                'override_amount'  => $amt,
                'override_currency'=> $cur,
            ]);
            $btns[] = html_writer::link($renewurl, get_string('btn_renew_now','local_subscriptions'),
                ['class'=>'btn btn-primary btn-sm']);
        }

        if ($sub->status === 'active') {
            // PROLONGER (start = end_date existante)
            $extendurl = new moodle_url('/local/subscriptions/checkout.php', [
                'planid'           => $sub->planid,
                'currency'         => $cur,
                'operation'        => 'queue_future',
                'ref_subid'        => $sub->id,       // pour démarrer à la fin de celle-ci
                'override_amount'  => $amt,           // on fige le prix au montant payé
                'override_currency'=> $cur,
            ]);
            $btns[] = html_writer::link($extendurl, get_string('btn_extend','local_subscriptions'),
                ['class'=>'btn btn-outline-primary btn-sm']);
        }
    }

    if (!empty($plan->is_recurring) && $sub->payment_provider === 'stripe' && !empty($sub->provider_customer_id)) {
        $btns[] = html_writer::link(
            new moodle_url('/local/subscriptions/portal.php', ['subid'=>$sub->id]),
            get_string('manage_payment','local_subscriptions'),
            ['class'=>'btn btn-outline-primary btn-sm']
        );
        // petit badge auto-renouvellement
        $list .= html_writer::div(
            html_writer::span(get_string('badge_recurring','local_subscriptions'), 'badge bg-info'),
            'mb-2'
        );
    }
    $modalid = 'subModal'.$sub->id;
    $btns[] = html_writer::tag('button', get_string('details','local_subscriptions'),
        ['class'=>'btn btn-outline-secondary btn-sm', 'data-bs-toggle'=>'modal', 'data-bs-target'=>'#'.$modalid]);
    $btnshtml = implode(' ', $btns);

    // Card HTML
    echo html_writer::start_div($cardclasses);
      echo html_writer::div($head, 'card-header bg-white');
      echo html_writer::start_div('card-body');
        echo $list;
        echo html_writer::div($btnshtml, 'mt-2');
      echo html_writer::end_div();
    echo html_writer::end_div();

    // Modal Détails
    $rows = [
        ['ID', $sub->id],
        ['Plan', format_string($plan->name)],
        ['Statut', s($sub->status)],
        ['Début', userdate($sub->start_date)],
        ['Fin', userdate($sub->end_date)],
        ['Montant', $fmtmoney($sub->pricepaid ?? 0, $sub->currency ?? '')],
        ['Devise', strtoupper($sub->currency ?? '')],
        ['Transaction', s($sub->transactionid ?? '')],
        ['Provider', s($sub->payment_provider ?? '')],
        ['Stripe sub', s($sub->provider_subscription_id ?? '')],
        ['Stripe customer', s($sub->provider_customer_id ?? '')],
        ['Dernière facture', s($sub->last_invoice_id ?? '')],
        ['Échec prélèvement', !empty($sub->payment_failed) ? get_string('yes') : get_string('no')],
        ['Dernier échec', !empty($sub->last_payment_failed_at) ? userdate($sub->last_payment_failed_at) : '-'],
        ['Raison échec', s($sub->last_payment_failed_reason ?? '')],
        ['Créée', userdate($sub->creation_date)],
        ['MàJ', userdate($sub->last_update)],
    ];
    $table = html_writer::start_tag('table', ['class'=>'table table-sm mb-0']);
    foreach ($rows as [$k,$v]) {
        $table .= '<tr><th class="text-muted" style="width:28%;white-space:nowrap;">'.s($k).'</th><td class="fw-semibold">'.(is_string($v)?$v:s($v)).'</td></tr>';
    }
    $table .= html_writer::end_tag('table');

    echo html_writer::start_div('modal fade', ['id'=>$modalid, 'tabindex'=>'-1', 'aria-hidden'=>'true']);
      echo html_writer::start_div('modal-dialog modal-lg modal-dialog-scrollable');
        echo html_writer::start_div('modal-content');
          echo html_writer::div(
              html_writer::tag('h5', get_string('subscription_details','local_subscriptions').' #'.$sub->id, ['class'=>'modal-title'])
            . html_writer::tag('button','', ['type'=>'button','class'=>'btn-close','data-bs-dismiss'=>'modal','aria-label'=>'Close']),
            'modal-header d-flex align-items-center justify-content-between'
          );
          echo html_writer::div($table, 'modal-body bg-light');
          echo html_writer::div(
              html_writer::tag('button', 'Close', ['class'=>'btn btn-secondary','data-bs-dismiss'=>'modal']),
            'modal-footer'
          );
        echo html_writer::end_div();
      echo html_writer::end_div();
    echo html_writer::end_div();
}

// Lien pour s’abonner (CTA)
echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/subscribe.php'), get_string('go_subscribe', 'local_subscriptions'), ['class'=>'btn btn-primary']),
    'mt-4'
);

echo $OUTPUT->footer();
