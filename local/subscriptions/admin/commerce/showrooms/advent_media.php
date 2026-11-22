<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/../../../classes/commerce/showroom/bridge/CommerceShowroomAdventMediaService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomAdventMediaService as AdventMedia;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeSettings as BridgeSettings;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
$context = context_system::instance();
require_capability('local/subscriptions:manage_showrooms', $context);
$id = required_param('id', PARAM_INT);
if ($id <= 0) {
    throw new invalid_parameter_exception('Showroom invalide.');
}
$repo = new CommerceShowroomCmsRepository($DB);
$record = $repo->get($id);
if (!$record) {
    throw new moodle_exception('invalidrecord');
}
$settings = BridgeSettings::from_json((string)$record->settingsjson);
if ($settings['id'] !== 'advent_calendar_2026') {
    throw new invalid_parameter_exception('Réservé au showroom Calendrier de l’Avent.');
}
$url = new moodle_url('/local/subscriptions/admin/commerce/showrooms/advent_media.php', ['id' => $id]);
$service = new AdventMedia($context);
$errors = [];
$telegram = $settings['telegramUrl'];
$whatsapp = $settings['whatsappUrl'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    $telegram = required_param('telegramurl', PARAM_RAW);
    $whatsapp = required_param('whatsappurl', PARAM_RAW);
    try { $telegram = BridgeSettings::validate_contact_url('telegram', $telegram); }
    catch (\invalid_parameter_exception $e) { $errors[] = 'Telegram : lien HTTPS officiel requis (t.me).'; }
    try { $whatsapp = BridgeSettings::validate_contact_url('whatsapp', $whatsapp); }
    catch (\invalid_parameter_exception $e) { $errors[] = 'WhatsApp : utilise https://wa.me/33612345678.'; }

    $uploads = [];
    if (AdventMedia::has_upload('advent_audio')) {
        try { AdventMedia::validate_upload('advent_audio', true); $uploads[] = ['audio', 0]; }
        catch (\invalid_parameter_exception $e) { $errors[] = $e->getMessage(); }
    }
    $removeaudio = optional_param('remove_audio', 0, PARAM_BOOL) === 1;
    if ($removeaudio && AdventMedia::has_upload('advent_audio')) {
        $errors[] = 'Audio : choisis un nouveau fichier OU la suppression.';
    }
    $deletions = [];
    for ($day = 1; $day <= 24; $day++) {
        $field = 'advent_cover_' . $day;
        $remove = optional_param('remove_cover_' . $day, 0, PARAM_BOOL) === 1;
        if ($remove && AdventMedia::has_upload($field)) {
            $errors[] = 'Jour ' . $day . ' : choisis un fichier OU la suppression.';
        } else if (AdventMedia::has_upload($field)) {
            try { AdventMedia::validate_upload($field); $uploads[] = ['cover', $day]; }
            catch (\invalid_parameter_exception $e) { $errors[] = 'Jour ' . $day . ' : ' . $e->getMessage(); }
        } else if ($remove) {
            $deletions[] = $day;
        }
    }
    if (!$errors) {
        $raw = (string)$record->settingsjson;
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !is_array($decoded['designbridge'] ?? null)
            || ($decoded['designbridge']['id'] ?? '') !== 'advent_calendar_2026') {
            throw new invalid_parameter_exception('Configuration du design changée.');
        }
        $decoded['designbridge']['telegramUrl'] = $telegram;
        $decoded['designbridge']['whatsappUrl'] = $whatsapp;
        $newjson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $tx = $DB->start_delegated_transaction();
        $fresh = $repo->get($id);
        if (!$fresh || (string)$fresh->settingsjson !== $raw) {
            throw new moodle_exception('confirmsesskeybad');
        }
        foreach ($uploads as [$kind, $day]) {
            if ($kind === 'audio') { $service->store_audio($id); }
            else { $service->store_cover($id, $day); }
        }
        if ($removeaudio) { $service->delete_audio($id); }
        foreach ($deletions as $day) { $service->delete_cover($id, $day); }
        if ($newjson !== $raw) {
            $DB->update_record('local_subs_showroom', (object)[
                'id' => $id, 'settingsjson' => $newjson, 'timemodified' => time(),
                'usermodified' => (int)$USER->id,
            ]);
        }
        $tx->allow_commit();
        redirect($url, 'Médias et contacts Advent enregistrés.', null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
}

CrmPageConfigurator::configure($PAGE, $context, $url,
    'Calendrier de l’Avent — Médias et contacts', 'local-subscriptions-advent-media');
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo html_writer::tag('h1', 'Calendrier de l’Avent · Médias et contacts', ['class' => 'h3']);
echo html_writer::tag('p', 'Showroom : ' . s((string)$record->name)
    . ' — les couvertures et le MP3 sont stockés dans Moodle, sans recompiler Lovable.',
    ['class' => 'text-muted']);
foreach ($errors as $error) {
    echo html_writer::div(s($error), 'alert alert-danger');
}
echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data',
    'action' => $url->out(false), 'style' => 'max-width:940px']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('h2', 'Liens de contact', ['class' => 'h5 mt-4']);
foreach ([
    'telegram' => ['Telegram', 'https://t.me/identifiant', $telegram],
    'whatsapp' => ['WhatsApp', 'https://wa.me/33612345678', $whatsapp],
] as $channel => $values) {
    echo html_writer::tag('label', $values[0], ['for' => $channel . 'url', 'class' => 'form-label mt-2']);
    echo html_writer::empty_tag('input', ['type' => 'url', 'id' => $channel . 'url',
        'name' => $channel . 'url', 'value' => $values[2], 'placeholder' => $values[1],
        'maxlength' => 512, 'class' => 'form-control']);
}
echo html_writer::tag('p', 'Un lien vide masque son bouton. Seules les URL officielles sont acceptées.',
    ['class' => 'form-text']);
echo html_writer::tag('h2', 'Extrait audio', ['class' => 'h5 mt-4']);
echo html_writer::tag('label', 'Importer un MP3 (25 Mo maximum)',
    ['for' => 'advent_audio', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'advent_audio',
    'id' => 'advent_audio', 'accept' => '.mp3,audio/mpeg', 'class' => 'form-control mb-2']);
$audio = $service->audio_url($id);
if ($audio !== '') {
    echo html_writer::tag('p', html_writer::link($audio, 'Écouter le MP3 actuel',
        ['target' => '_blank', 'rel' => 'noopener']), ['class' => 'form-text']);
    echo html_writer::checkbox('remove_audio', '1', false, 'Supprimer l’extrait audio');
}
echo html_writer::tag('h2', 'Couvertures des 24 cases', ['class' => 'h5 mt-4']);
echo html_writer::tag('p', 'WebP / JPG / PNG, 5 Mo par image, au moins 200 × 200 px. '
    . 'Sans image, la carte reste sombre en aperçu.', ['class' => 'form-text']);
$covers = $service->cover_urls($id);
echo html_writer::start_div('row row-cols-1 row-cols-md-2 g-3');
for ($day = 1; $day <= 24; $day++) {
    echo html_writer::start_div('col');
    echo html_writer::start_div('border rounded p-3');
    echo html_writer::tag('label', 'Jour ' . $day, ['for' => 'advent_cover_' . $day,
        'class' => 'form-label fw-bold']);
    if (isset($covers[$day])) {
        echo html_writer::tag('p', html_writer::link($covers[$day], 'Voir la couverture actuelle',
            ['target' => '_blank', 'rel' => 'noopener']), ['class' => 'mb-2']);
        echo html_writer::div(html_writer::checkbox('remove_cover_' . $day, '1', false,
            'Supprimer la couverture'), 'form-check mb-2');
    }
    echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'advent_cover_' . $day,
        'id' => 'advent_cover_' . $day, 'accept' => '.webp,.jpg,.png,image/webp,image/jpeg,image/png',
        'class' => 'form-control']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::tag('button', 'Enregistrer les médias et contacts',
    ['type' => 'submit', 'class' => 'btn btn-primary mt-4']);
echo html_writer::end_tag('form');
echo html_writer::tag('p', html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $id]),
    'Retour à la configuration du showroom'), ['class' => 'mt-4']);
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
