<?php

declare(strict_types=1);

// Public endpoint: signed link, no login, no marketing email delivery.
require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;

$recipientid = required_param('r', PARAM_INT);
$token = required_param('t', PARAM_ALPHANUM);
$optouts = CommerceMarketingOptoutService::create($DB);
$email = $optouts->email_from_signed_link($recipientid, $token);
if ($email === null) {
    // Do not reveal whether an email address exists in the CRM.
    throw new \moodle_exception('invalidparameter');
}

$language = current_language();
$strings = match ($language) {
    'ru' => [
        'title' => 'Отписаться от рассылки CampusFR',
        'question' => 'Вы больше не хотите получать рекламные письма от CampusFR?',
        'confirm' => 'Отписаться',
        'done' => 'Готово! Вы больше не будете получать рекламные письма CampusFR.',
        'note' => 'Подтверждения заказов и другие важные сервисные письма останутся доступными.',
    ],
    'en' => [
        'title' => 'Unsubscribe from CampusFR emails',
        'question' => 'Stop receiving marketing emails from CampusFR?',
        'confirm' => 'Unsubscribe',
        'done' => 'You are unsubscribed from CampusFR marketing emails.',
        'note' => 'Order confirmations and essential service notifications are not affected.',
    ],
    default => [
        'title' => 'Se désabonner des emails CampusFR',
        'question' => 'Ne plus recevoir les emails commerciaux de CampusFR ?',
        'confirm' => 'Me désabonner',
        'done' => 'Votre désabonnement aux emails commerciaux CampusFR est confirmé.',
        'note' => 'Vous continuerez à recevoir les confirmations de commande et les messages de service indispensables.',
    ],
};

$PAGE->set_url(new moodle_url('/local/subscriptions/marketing_unsubscribe.php',
    ['r' => $recipientid, 't' => $token]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title($strings['title']);
$PAGE->set_heading($strings['title']);

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

// GET must not unsubscribe: link preview bots follow GET requests.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirmed = required_param('confirm', PARAM_INT);
    if ($confirmed !== 1) {
        throw new \moodle_exception('invalidparameter');
    }
    $optouts->suppress($email);
}

$done = $optouts->is_suppressed($email);
echo $OUTPUT->header();
echo $OUTPUT->heading($strings['title']);
echo html_writer::tag('p', s($done ? $strings['done'] : $strings['question']));
echo html_writer::tag('p', s($strings['note']));
if (!$done) {
    echo html_writer::start_tag('form', ['method' => 'post',
        'action' => $PAGE->url->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'confirm', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'submit',
        'value' => $strings['confirm'], 'class' => 'btn btn-primary']);
    echo html_writer::end_tag('form');
}
echo $OUTPUT->footer();
