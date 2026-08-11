<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/commerce/support/CommerceSupportRequestForm.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\support\CommerceSupportRequest;
use local_subscriptions\commerce\support\CommerceSupportRequestService;
use local_subscriptions\form\commerce\support\CommerceSupportRequestForm;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $OUTPUT, $PAGE, $SITE, $USER, $SESSION;

// Initialise the page context before any Moodle formatting API is used.
// format_string() consults $PAGE->context in Moodle 5.
$PAGE->set_context(context_system::instance());

$reference = optional_param('reference', '', PARAM_ALPHANUMEXT);
$submitted = optional_param('submitted', 0, PARAM_BOOL);
$service = CommerceOrderPresentationService::create();
$order = null;
$owneruserid = null;
$isguestaccess = false;
$publicreference = '';
$products = [];
$paymentstatus = '';
$fulfillmentstatus = '';

if ($reference !== '') {
    try {
        if (isloggedin() && !isguestuser()) {
            $owneruserid = (int)$USER->id;
            $order = $service->find_for_user($reference, $owneruserid, false, (string)$USER->email);
        } else {
            $isguestaccess = true;
            $guestsessions = new CommerceGuestCheckoutSessionRepository($DB);
            $guestsession = $guestsessions->find_by_purchase_reference($reference);
            $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
            if ($guestsession === null || $token === '' || !hash_equals($guestsession->get_token(), $token)) {
                throw new CommerceOrderPresentationAccessDeniedException('Guest session does not own this order.');
            }
            $owneruserid = (int)$guestsession->get_user_id();
            $order = $service->find_for_user($reference, $owneruserid);
        }
    } catch (CommerceOrderPresentationAccessDeniedException $exception) {
        throw new moodle_exception('nopermissions', 'error');
    }

    if ($order === null) {
        throw new moodle_exception('commerce_i2_order_not_found', 'local_subscriptions');
    }

    $publicreference = (new CommercePublicOrderReference())->from_internal(
        $order->reference,
        $order->timecreated
    );
    $products = array_map(
        static fn($item): string => trim((string)$item->label),
        $order->items
    );
    $paymentstatus = (string)$order->paymentstatus;
    $fulfillmentstatus = (string)$order->fulfillmentstatus;
} else if (isloggedin() && !isguestuser()) {
    $owneruserid = (int)$USER->id;
}

$pageparams = $reference !== '' ? ['reference' => $reference] : [];
$pageurl = UrlFactory::support($pageparams);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('commerce_support_page_title_generic', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/support_request.css'));

$customerrecord = null;
if ($owneruserid !== null && $owneruserid > 0) {
    $customerrecord = $DB->get_record(
        'user',
        ['id' => $owneruserid],
        'id,firstname,lastname,email,username,deleted',
        IGNORE_MISSING
    );
}
$customername = '';
if ($customerrecord && !$customerrecord->deleted) {
    $customername = trim(implode(' ', array_filter([
        trim((string)$customerrecord->firstname),
        trim((string)$customerrecord->lastname),
    ], static fn(string $value): bool => $value !== '')));
}
$customeremail = $customerrecord && !$customerrecord->deleted
    ? trim((string)$customerrecord->email)
    : '';

if ($order !== null) {
    if ($customername === '' || $customername === $customeremail) {
        $fallbackname = trim((string)($order->customername ?? ''));
        if ($fallbackname !== '' && $fallbackname !== (string)$order->customeremail) {
            $customername = $fallbackname;
        }
    }
    if ($customeremail === '') {
        $customeremail = trim((string)$order->customeremail);
    }
}
if ($customername === '') {
    $customername = $customeremail;
}

$identityeditable = $reference === ''
    && (!isloggedin() || isguestuser());

$form = new CommerceSupportRequestForm(null, ['context' => [
    'reference' => $reference,
    'publicreference' => $publicreference,
    'customer' => $customername,
    'email' => $customeremail,
    'identityeditable' => $identityeditable,
]]);
$form->set_data((object)[
    'reference' => $reference,
    'category' => $reference !== ''
        ? CommerceSupportRequest::CATEGORY_PAYMENT
        : CommerceSupportRequest::CATEGORY_OTHER,
    'subject' => $reference !== ''
        ? get_string('commerce_support_default_subject', 'local_subscriptions', $publicreference)
        : get_string('commerce_support_default_subject_generic', 'local_subscriptions'),
]);

if ($form->is_cancelled()) {
    if ($reference === '') {
        redirect($identityeditable ? UrlFactory::storefront() : UrlFactory::my_campus());
    }
    $cancelurl = $isguestaccess
        ? UrlFactory::order_result(['reference' => $reference])
        : UrlFactory::order_details(['reference' => $reference]);
    redirect($cancelurl);
}

if ($data = $form->get_data()) {
    $submittedfirstname = $identityeditable
        ? trim((string)($data->firstname ?? ''))
        : '';
    $submittedlastname = $identityeditable
        ? trim((string)($data->lastname ?? ''))
        : '';
    $submittedname = trim($submittedfirstname . ' ' . $submittedlastname);
    $requestcustomername = $identityeditable
        ? ($submittedname !== '' ? $submittedname : trim((string)$data->email))
        : $customername;
    $requestcustomeremail = $identityeditable
        ? trim((string)$data->email)
        : $customeremail;

    $request = new CommerceSupportRequest(
        $order?->reference ?? '',
        $publicreference,
        $owneruserid,
        $requestcustomername,
        $requestcustomeremail,
        (string)$data->category,
        (string)$data->subject,
        (string)$data->message,
        $paymentstatus,
        $fulfillmentstatus,
        $products
    );
    $threadid = CommerceSupportRequestService::create()->submit($request);
    $supportreference = CommerceSupportRequestService::public_reference($threadid);
    $SESSION->local_subscriptions_support_confirmation = [
        'supportreference' => $supportreference,
        'customername' => $requestcustomername,
        'customeremail' => $requestcustomeremail,
        'orderreference' => $publicreference,
        'category' => (string)$data->category,
        'subject' => (string)$data->subject,
    ];
    redirect(UrlFactory::support([
        'reference' => $reference,
        'submitted' => 1,
    ]));
}

$confirmation = [];
if ($submitted && !empty($SESSION->local_subscriptions_support_confirmation)) {
    $confirmation = (array)$SESSION->local_subscriptions_support_confirmation;
    unset($SESSION->local_subscriptions_support_confirmation);
}

$gustaveurl = $OUTPUT->image_url('support/gustave_support', 'local_subscriptions')->out(false);
$backurl = $reference === ''
    ? ($identityeditable ? UrlFactory::storefront() : UrlFactory::my_campus())
    : ($isguestaccess
        ? UrlFactory::order_result(['reference' => $reference])
        : UrlFactory::order_details(['reference' => $reference]));

echo $OUTPUT->header();
echo html_writer::start_div('commerce-support-request container py-4');
echo html_writer::link(
    $backurl,
    html_writer::tag('i', '', ['class' => 'fa-solid fa-arrow-left', 'aria-hidden' => 'true']) . ' ' .
        ($reference === ''
            ? ($identityeditable
                ? get_string('commerce_support_back_to_store', 'local_subscriptions')
                : get_string('commerce_support_back_to_campus', 'local_subscriptions'))
            : get_string('commerce_support_back_to_order', 'local_subscriptions')),
    ['class' => 'commerce-support-request__back']
);
echo html_writer::start_div('commerce-support-request__layout');
echo html_writer::start_tag('aside', ['class' => 'commerce-support-request__visual']);
echo html_writer::empty_tag('img', [
    'src' => $gustaveurl,
    'alt' => get_string('commerce_support_gustave_alt', 'local_subscriptions'),
    'class' => 'commerce-support-request__gustave',
]);
echo html_writer::tag('h2', get_string('commerce_support_visual_title', 'local_subscriptions'));
echo html_writer::tag('p', get_string('commerce_support_visual_text', 'local_subscriptions'));
echo html_writer::end_tag('aside');

echo html_writer::start_tag('section', ['class' => 'commerce-support-request__card']);
if ($submitted && $confirmation !== []) {
    echo html_writer::start_div('commerce-support-confirmation');
    echo html_writer::tag('span', html_writer::tag('i', '', [
        'class' => 'fa-solid fa-check',
        'aria-hidden' => 'true',
    ]), ['class' => 'commerce-support-confirmation__icon', 'aria-hidden' => 'true']);
    echo html_writer::tag('h1', get_string('commerce_support_confirmation_title', 'local_subscriptions'));
    echo html_writer::tag('p', get_string('commerce_support_confirmation_intro', 'local_subscriptions'), [
        'class' => 'commerce-support-confirmation__intro',
    ]);

    echo html_writer::start_tag('dl', ['class' => 'commerce-support-confirmation__summary']);
    $summary = [
        get_string('commerce_support_reference', 'local_subscriptions') => (string)($confirmation['supportreference'] ?? ''),
        get_string('commerce_support_customer', 'local_subscriptions') => trim(
            (string)($confirmation['customername'] ?? '') . "\n" .
            (string)($confirmation['customeremail'] ?? '')
        ),
        get_string('commerce_support_order', 'local_subscriptions') => (string)($confirmation['orderreference'] ?? ''),
        get_string('commerce_support_category', 'local_subscriptions') => !empty($confirmation['category'])
            ? get_string('commerce_support_category_' . $confirmation['category'], 'local_subscriptions')
            : '',
        get_string('commerce_support_subject', 'local_subscriptions') => (string)($confirmation['subject'] ?? ''),
    ];
    foreach ($summary as $label => $value) {
        $value = trim($value);
        if ($value === '') {
            continue;
        }
        echo html_writer::start_div('commerce-support-confirmation__row');
        echo html_writer::tag('dt', $label);
        echo html_writer::tag('dd', nl2br(s($value)));
        echo html_writer::end_div();
    }
    echo html_writer::end_tag('dl');
    $confirmationurl = $identityeditable
        ? UrlFactory::storefront()
        : UrlFactory::my_campus();
    $confirmationlabel = $identityeditable
        ? get_string('commerce_support_return_to_store', 'local_subscriptions')
        : get_string('commerce_support_return_to_campus', 'local_subscriptions');
    echo html_writer::link(
        $confirmationurl,
        html_writer::tag('i', '', [
            'class' => $identityeditable ? 'fa-solid fa-store' : 'fa-solid fa-house',
            'aria-hidden' => 'true',
        ]) . ' ' . $confirmationlabel,
        ['class' => 'btn btn-primary commerce-support-confirmation__button']
    );
    echo html_writer::end_div();
} else {
    echo html_writer::tag('h1', get_string('commerce_support_heading', 'local_subscriptions'), [
        'class' => 'commerce-support-request__title',
    ]);
    echo html_writer::tag('p', get_string('commerce_support_intro', 'local_subscriptions'), [
        'class' => 'commerce-support-request__intro',
    ]);
    $form->display();
}
echo html_writer::end_tag('section');
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();