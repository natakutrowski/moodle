<?php
// CampusFR: edit trusted Articles / Alf external contact links only.
declare(strict_types=1);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings as BridgeSettings;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());
$id = required_param('id', PARAM_INT);
$repository = new CommerceShowroomCmsRepository($DB);
$record = $repository->get($id);
if (!$record) {
    throw new moodle_exception('invalidrecord');
}
$settings = BridgeSettings::from_json((string)$record->settingsjson);
if ($settings['id'] !== 'articles_alf_reimport') {
    throw new invalid_parameter_exception('Ce showroom ne contient pas le design Articles / Alf.');
}
$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/bridge_contacts.php', ['id' => $id]);
$contacterrors = [];
$contactvalues = [
    'telegram' => $settings['telegramUrl'],
    'whatsapp' => $settings['whatsappUrl'],
];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    $contactvalues = [
        'telegram' => required_param('telegramurl', PARAM_RAW),
        'whatsapp' => required_param('whatsappurl', PARAM_RAW),
    ];
    // Validate each field separately, without surfacing a Moodle exception page.
    foreach ($contactvalues as $kind => $value) {
        try {
            BridgeSettings::validate_contact_url($kind, $value);
        } catch (\invalid_parameter_exception $exception) {
            $contacterrors[$kind] = $kind === 'telegram'
                ? 'Lien Telegram invalide. Utilise https://t.me/identifiant ou un lien officiel d’invitation.'
                : 'Lien WhatsApp invalide. Utilise https://wa.me/33612345678, avec éventuellement ?text=Message, ou un lien officiel api.whatsapp.com/send.';
        }
    }
    if (!$contacterrors) {
        $original = (string)$record->settingsjson;
        $newjson = BridgeSettings::merge_contact_links(
            $original, $contactvalues['telegram'], $contactvalues['whatsapp']
        );
        // Fail closed when another administrator edited the showroom concurrently.
        $tx = $DB->start_delegated_transaction();
        $fresh = $repository->get($id);
        if (!$fresh || (string)$fresh->settingsjson !== $original) {
            throw new moodle_exception('confirmsesskeybad');
        }
        $DB->update_record('local_subs_showroom', (object)[
            'id' => $id,
            'settingsjson' => $newjson,
            'timemodified' => time(),
            'usermodified' => (int)$USER->id,
        ]);
        $tx->allow_commit();
        redirect($url, 'Liens de contact enregistrés.', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}
CrmPageConfigurator::configure($PAGE, context_system::instance(), $url,
    'Liens Telegram / WhatsApp — Articles / Alf', 'local-subscriptions-bridge-contacts');
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, context_system::instance());
echo html_writer::tag('h1', 'Liens Telegram / WhatsApp', ['class' => 'h3']);
echo html_writer::tag('p', 'Showroom : ' . s((string)$record->name) . '. '
    . 'Les champs vides masquent leurs boutons dans le showroom.', ['class' => 'text-muted']);
echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false),
    'style' => 'max-width:720px']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
foreach ([
    'telegram' => ['Telegram', 'https://t.me/nom_du_compte', $contactvalues['telegram']],
    'whatsapp' => ['WhatsApp', 'https://wa.me/33612345678', $contactvalues['whatsapp']],
] as $channel => $config) {
    echo html_writer::tag('label', $config[0], ['for' => $channel . 'url', 'class' => 'form-label mt-3']);
    echo html_writer::empty_tag('input', [
        'type' => 'url', 'id' => $channel . 'url', 'name' => $channel . 'url',
        'value' => $config[2], 'placeholder' => $config[1], 'maxlength' => 512,
        'class' => 'form-control' . (isset($contacterrors[$channel]) ? ' is-invalid' : ''),
        'aria-invalid' => isset($contacterrors[$channel]) ? 'true' : 'false',
    ]);
    if (isset($contacterrors[$channel])) {
        echo html_writer::div(s($contacterrors[$channel]), 'invalid-feedback d-block');
    }
}
echo html_writer::tag('p', 'Seuls les liens HTTPS officiels Telegram et WhatsApp sont autorisés. '
    . 'Pour WhatsApp, indique le numéro au format international sans + ni espaces. '
    . 'Les liens WhatsApp avec un message prérempli (?text=) sont acceptés.',
    ['class' => 'form-text my-3']);
echo html_writer::tag('button', 'Enregistrer les liens', ['type' => 'submit',
    'class' => 'btn btn-primary mt-2']);
echo html_writer::end_tag('form');
echo html_writer::tag('p', html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
    'Retour à la liste des showrooms'), ['class' => 'mt-4']);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
