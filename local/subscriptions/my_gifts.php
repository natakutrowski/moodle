<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\gift\CommerceGiftCustomerPresentation;
use local_subscriptions\commerce\gift\CommerceGiftFeatureToggle;
use local_subscriptions\commerce\gift\CommerceGiftDevE2EGate;
use local_subscriptions\commerce\gift\CommerceGiftInvitationService;
use local_subscriptions\commerce\gift\CommerceGiftSchema;
use local_subscriptions\commerce\gift\CommerceGiftDeliveryDraftService;
use local_subscriptions\commerce\gift\CommerceGiftDeliveryDispatchService;
use local_subscriptions\commerce\gift\CommerceGiftCommercialDevGate;
use local_subscriptions\commerce\gift\CommerceGiftPurchaseSummary;
use local_subscriptions\currency\CurrencyFormatter;

require_login();
if (isguestuser() || empty($USER->confirmed) || !empty($USER->suspended)) {
    throw new moodle_exception('nopermissions');
}
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
$PAGE->set_context(context_user::instance((int)$USER->id));
$PAGE->set_url(new moodle_url('/local/subscriptions/my_gifts.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cfr_gift_g4_my_gifts', 'local_subscriptions'));
$PAGE->set_heading(get_string('cfr_gift_g4_my_gifts', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/gifts_g4.css', [
    'v' => (string)filemtime(__DIR__ . '/styles/gifts_g4.css'),
]));
$PAGE->navbar->add(get_string('commerce_customer_hub_title', 'local_subscriptions'),
    new moodle_url('/local/subscriptions/mon_campus.php'));
$PAGE->navbar->add(get_string('cfr_gift_g4_my_gifts', 'local_subscriptions'));
$view = new CommerceGiftCustomerPresentation($DB);
$giftsummary = new CommerceGiftPurchaseSummary($DB);
$offered = $view->offered((int)$USER->id);
$received = $view->received((int)$USER->id);
$flash = '';
$oncecode = null;
$g4bdraft = new CommerceGiftDeliveryDraftService($DB);
$g4bdispatch = new CommerceGiftDeliveryDispatchService($DB);
$g4bnotice = '';
$g4bnoticelevel = 'success';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_sesskey();
    $action = required_param('gift_action', PARAM_ALPHA);
    if (!in_array($action, ['queue', 'link', 'save'], true)) { throw new moodle_exception('invalidparameter'); }
    $giftid = required_param('giftid', PARAM_INT);
    if ($action === 'save') {
        if (!\local_subscriptions\commerce\gift\CommerceGiftCommercialRuntimeGate::can_operate()) {
            throw new moodle_exception('nopermissions');
        }
        try {
            $g4bdraft->save($giftid, (int)$USER->id,
                optional_param('gift_note', '', PARAM_RAW_TRIMMED),
                optional_param('gift_locale', 'fr', PARAM_ALPHA));
            $g4bnotice = 'cfr_gift_g4b_saved';
        } catch (\Throwable $e) {
            $g4bnotice = 'cfr_gift_g4b_unavailable';
            $g4bnoticelevel = 'warning';
        }
        $offered = $view->offered((int)$USER->id);
    } else {
        try {
            if ($action === 'queue') {
                $g4bdispatch->queue_email($giftid, (int)$USER->id);
                $g4bnotice = 'cfr_gift_g4b2_queue_success';
            } else {
                $oncecode = $g4bdispatch->issue_link($giftid, (int)$USER->id);
                $g4bnotice = 'cfr_gift_g4b2_link_success';
            }
        } catch (\Throwable $e) {
            // Never show recipient data, signed intent details or raw exception messages.
            $g4bnotice = 'cfr_gift_g4b2_unavailable';
            $g4bnoticelevel = 'warning';
        }
        $offered = $view->offered((int)$USER->id);
    }

}

$g4 = static fn(string $key): string => get_string($key, 'local_subscriptions');
echo $OUTPUT->header();
echo html_writer::start_div('container cfr-gift-g4 py-4');
echo html_writer::start_div('cfr-gift-g4__welcome');
echo html_writer::tag('span', '🎁', ['class' => 'cfr-gift-g4__welcome-icon', 'aria-hidden' => 'true']);
echo html_writer::start_div('cfr-gift-g4__welcome-copy');
echo html_writer::tag('span', s($g4('cfr_gift_g4_kicker')), ['class' => 'cfr-gift-g4__eyebrow']);
echo html_writer::tag('h1', s($g4('cfr_gift_g4_my_gifts')), ['class' => 'cfr-gift-g4__heading']);
echo html_writer::tag('p', s($g4('cfr_gift_g4_list_intro')));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::start_div('cfr-gift-g4__overview');
echo html_writer::tag('span', s($g4('cfr_gift_g4_offered')) . ' : ' . count($offered));
echo html_writer::tag('span', s($g4('cfr_gift_g4_received')) . ' : ' . count($received));
echo html_writer::end_div();
if ($oncecode !== null) {
    echo html_writer::start_div('cfr-gift-g4__token', ['role' => 'status']);
    echo html_writer::tag('h2', s($g4('cfr_gift_g4_invitation_ready')), ['class' => 'h5']);
    echo html_writer::tag('p', s($g4('cfr_gift_g4b2_link_success')));
    echo html_writer::tag('code', s($oncecode), ['class' => 'cfr-gift-g4__secret']);
    echo html_writer::tag('p', s($g4('cfr_gift_g4_invitation_share')));
    echo html_writer::link(new moodle_url('/local/subscriptions/gift_claim.php'),
        s($g4('cfr_gift_g4_claim_page_link')), ['class' => 'btn btn-outline-primary']);
    echo html_writer::end_div();
}
if ($flash !== '') { echo $OUTPUT->notification(s($flash), 'warning'); }
if ($g4bnotice !== '') { echo $OUTPUT->notification(s($g4($g4bnotice)), $g4bnoticelevel); }
echo html_writer::start_tag('section', ['class' => 'cfr-gift-g4__section', 'aria-labelledby' => 'cfr-gifts-given']);
echo html_writer::tag('h2', s($g4('cfr_gift_g4_offered')), ['id' => 'cfr-gifts-given', 'class' => 'cfr-gift-g4__section-title']);
if (!$offered) {
    echo html_writer::tag('p', s($g4('cfr_gift_g4_offered_empty')), ['class' => 'cfr-gift-g4__empty']);
} else {
    echo html_writer::start_div('cfr-gift-g4__cards');
    foreach ($offered as $gift) {
        $receipt = $view->receipt_for_offered($gift, (int)$USER->id);
        echo html_writer::start_tag('article', ['class' => 'cfr-gift-g4__card']);
        echo html_writer::start_div('cfr-gift-g4__card-top');
        echo html_writer::tag('span', '🎁', ['class' => 'cfr-gift-g4__card-icon', 'aria-hidden' => 'true']);
        echo html_writer::tag('span', s($g4(CommerceGiftCustomerPresentation::status_key((string)$gift->status))),
            ['class' => 'cfr-gift-g4__status']);
        echo html_writer::end_div();
        echo html_writer::tag('h3', s($g4('cfr_gift_g4_product')), ['class' => 'cfr-gift-g4__product']);
        if ((string)$gift->status === 'notified') {
            echo html_writer::tag('p', s($g4('cfr_gift_g4b2_sent')),
                ['class' => 'cfr-gift-g4__muted', 'role' => 'status']);
        }

        // Recipient is visible ONLY in the authenticated buyer-owned card.
        $giftbuyerrecipient = $giftsummary->for_purchase((int)$gift->purchaseid);
        if ($giftbuyerrecipient !== null && hash_equals(
            $giftbuyerrecipient['recipientemail'], strtolower(trim((string)$gift->intendedrecipientemail)))) {
            echo html_writer::tag('p', s($g4('cfr_gift_g6_recipient')) . ' : ' .
                s($giftbuyerrecipient['label']), ['class' => 'cfr-gift-g4__muted']);
        }
        if ($receipt !== null) {
            echo html_writer::start_div('cfr-gift-g4__price');
            echo html_writer::tag('span', s($g4('cfr_gift_g4_amount_paid')), ['class' => 'cfr-gift-g4__muted']);
            echo html_writer::tag('strong', s(CurrencyFormatter::format_minor_code(
                $receipt['amountminor'], $receipt['currency'])));
            echo html_writer::end_div();
            echo html_writer::start_div('cfr-gift-g4__meta');
            echo html_writer::tag('span', s($g4('cfr_gift_g4_order_date')) . ' : ' .
                s(userdate($receipt['created'], get_string('strftimedatefullshort', 'langconfig'))));
            echo html_writer::end_div();
            echo html_writer::link(new moodle_url('/local/subscriptions/order_details.php',
                ['reference' => $receipt['reference']]), s($g4('cfr_gift_g4_view_order')),
                ['class' => 'cfr-gift-g4__text-link']);
        } else {
            // Do not invent or disclose a price when purchase ownership cannot be proved.
            echo html_writer::tag('p', s($g4('cfr_gift_g4_purchase_unavailable')),
                ['class' => 'cfr-gift-g4__muted']);
        }

        // Editing is only useful before dispatch. Never offer a false editable
        // 'Personnaliser' panel once the invitation is queued or sent.
        $draft = $g4bdraft->for_buyer((int)$gift->id, (int)$USER->id);
        $draftstatus = (string)($draft->status ?? 'draft');
        if (CommerceGiftDevE2EGate::is_dev_environment()
            && in_array((string)$gift->deliverymode, ['email', 'link'], true)
            && in_array((string)$gift->status, ['issued', 'scheduled', 'delivery_failed'], true)
            && $draftstatus === 'draft'
            && $gift->claimedat === null && $gift->activatedat === null
            && $gift->rightsholderuserid === null) {
            $savedmessage = (string)($draft->message ?? '');
            $savedlocale = (string)($draft->locale ?? current_language());
            if (!in_array($savedlocale, CommerceGiftDeliveryDraftService::LOCALES, true)) {
                $savedlocale = 'fr';
            }
            echo html_writer::start_div('cfr-gift-g4b__panel');
            echo html_writer::tag('h4', s($g4('cfr_gift_g4b_title')), ['class' => 'cfr-gift-g4b__title']);
            echo html_writer::tag('p', s($g4('cfr_gift_g4b_description')), ['class' => 'cfr-gift-g4__muted']);
            if ($gift->scheduledat !== null) {
                echo html_writer::tag('p', s($g4('cfr_gift_g4b_scheduled')) . ' : ' .
                    s(userdate((int)$gift->scheduledat, get_string('strftimedatetime', 'langconfig'))));
            } else {
                echo html_writer::tag('p', s($g4('cfr_gift_g4b_immediate')), ['class' => 'cfr-gift-g4__muted']);
            }
            if ($draft === null || (string)$draft->status === 'draft') {
                echo html_writer::start_tag('form', [
                    'method' => 'post', 'action' => (new moodle_url('/local/subscriptions/my_gifts.php'))->out(false),
                ]);
                foreach (['sesskey' => sesskey(), 'gift_action' => 'save', 'giftid' => (int)$gift->id] as $name => $value) {
                    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
                }
                $noteid = 'cfr-gift-note-' . (int)$gift->id;
                echo html_writer::tag('label', s($g4('cfr_gift_g4b_note')),
                    ['for' => $noteid, 'class' => 'cfr-gift-g4b__label']);
                echo html_writer::tag('textarea', s($savedmessage), [
                    'id' => $noteid, 'name' => 'gift_note', 'maxlength' => CommerceGiftDeliveryDraftService::MAX_CHARS,
                    'rows' => 3, 'class' => 'form-control cfr-gift-g4b__textarea',
                ]);
                echo html_writer::tag('label', s($g4('cfr_gift_g4b_locale')),
                    ['for' => 'cfr-gift-locale-' . (int)$gift->id, 'class' => 'cfr-gift-g4b__label']);
                echo html_writer::select(['fr' => 'Français', 'en' => 'English', 'ru' => 'Русский'],
                    'gift_locale', $savedlocale, false, ['id' => 'cfr-gift-locale-' . (int)$gift->id,
                        'class' => 'form-select cfr-gift-g4b__select']);
                echo html_writer::tag('button', s($g4('cfr_gift_g4b_save')),
                    ['type' => 'submit', 'class' => 'btn btn-outline-primary']);
                echo html_writer::end_tag('form');
            }
            if ($draft !== null && $savedmessage !== '') {
                echo html_writer::tag('h5', s($g4('cfr_gift_g4b_preview')), ['class' => 'cfr-gift-g4b__title']);
                echo html_writer::tag('p', s($savedmessage), ['class' => 'cfr-gift-g4b__preview']);
            }
            echo html_writer::tag('p', s($g4('cfr_gift_g4b_notice')), ['class' => 'cfr-gift-g4__muted']);
            $deliverystatus = (string)($draft->status ?? 'draft');
            $g4bkeys = [
                'queued' => 'cfr_gift_g4b2_queued',
                'sent' => 'cfr_gift_g4b2_sent',
                'manual_review' => 'cfr_gift_g4b2_manual',
                'failed' => 'cfr_gift_g4b2_failed',
                'link_ready' => 'cfr_gift_g4b2_link_ready',
            ];
            if (isset($g4bkeys[$deliverystatus])) {
                echo html_writer::tag('p', s($g4($g4bkeys[$deliverystatus])),
                    ['class' => 'cfr-gift-g4b__status']);
            } else if (CommerceGiftDeliveryDispatchService::enabled()
                && ((CommerceGiftCommercialDevGate::is_armed()
                    && (int)get_config('local_subscriptions',
                        CommerceGiftCommercialDevGate::BUYER_SETTING) === (int)$USER->id)
                    || (CommerceGiftDevE2EGate::is_armed()
                        && (int)get_config('local_subscriptions',
                            CommerceGiftDevE2EGate::BUYER_SETTING) === (int)$USER->id))) {
                if ((string)$gift->deliverymode === 'link'
                    && $gift->scheduledat !== null && (int)$gift->scheduledat > time()) {
                    echo html_writer::tag('p', s($g4('cfr_gift_g4b2_link_wait')),
                        ['class' => 'cfr-gift-g4__muted']);
                } else {
                if ((string)$gift->deliverymode === 'email') {
                    $deliveraction = 'queue';
                    $deliverbutton = 'cfr_gift_g4b2_queue';
                } else {
                    $deliveraction = 'link';
                    $deliverbutton = 'cfr_gift_g4b2_link';
                }
                echo html_writer::start_tag('form', [
                    'method' => 'post', 'action' => (new moodle_url('/local/subscriptions/my_gifts.php'))->out(false),
                ]);
                foreach (['sesskey' => sesskey(), 'gift_action' => $deliveraction,
                          'giftid' => (int)$gift->id] as $name => $value) {
                    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
                }
                echo html_writer::tag('button', s($g4($deliverbutton)),
                    ['type' => 'submit', 'class' => 'btn btn-primary']);
                echo html_writer::end_tag('form');
                }
            } else {
                echo html_writer::tag('p', s($g4('cfr_gift_g4b2_disabled')),
                    ['class' => 'cfr-gift-g4b__warning']);
            }
            echo html_writer::end_div();
        }

        echo html_writer::end_tag('article');
    }
    echo html_writer::end_div();
}
echo html_writer::end_tag('section');
echo html_writer::start_tag('section', ['class' => 'cfr-gift-g4__section', 'aria-labelledby' => 'cfr-gifts-received']);
echo html_writer::tag('h2', s($g4('cfr_gift_g4_received')), ['id' => 'cfr-gifts-received', 'class' => 'cfr-gift-g4__section-title']);
if (!$received) {
    echo html_writer::tag('p', s($g4('cfr_gift_g4_received_empty')), ['class' => 'cfr-gift-g4__empty']);
} else {
    echo html_writer::start_div('cfr-gift-g4__cards');
    foreach ($received as $gift) {
        echo html_writer::start_tag('article', ['class' => 'cfr-gift-g4__card']);
        echo html_writer::start_div('cfr-gift-g4__card-top');
        echo html_writer::tag('span', '📖', ['class' => 'cfr-gift-g4__card-icon', 'aria-hidden' => 'true']);
        // In 'Cadeaux reçus', an already activated gift needs no redundant badge.
        // Keep informative states visible for an activation still in progress.
        if ((string)$gift->status !== 'active') {
            echo html_writer::tag('span', s($g4(CommerceGiftCustomerPresentation::status_key((string)$gift->status))),
                ['class' => 'cfr-gift-g4__status']);
        }
        echo html_writer::end_div();
        echo html_writer::tag('h3', s($g4('cfr_gift_g4_product')), ['class' => 'cfr-gift-g4__product']);
        // Recipient view intentionally contains no price or payment reference.
        if ((string)$gift->status === 'active' && (int)$gift->rightsholderuserid === (int)$USER->id) {
            echo html_writer::link(new moodle_url('/local/campusreader/my.php'),
                s($g4('cfr_gift_g4_open_library')), ['class' => 'btn btn-outline-primary']);
        }
        echo html_writer::end_tag('article');
    }
    echo html_writer::end_div();
}
echo html_writer::end_tag('section');
echo html_writer::end_div();
echo $OUTPUT->footer();
