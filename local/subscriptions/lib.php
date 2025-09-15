<?php
// This file is part of local_subscriptions plugin.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib/user_subs_lib.php');


// function local_subscriptions_before_http_headers() {
//     global $PAGE;
//     $PAGE->requires->css('/local/subscriptions/styles.css');
// }

/**
 * Get the full label for a language (e.g. "Français 🇫🇷").
 *
 * @param string $code ISO language code (e.g. 'fr', 'en').
 * @return string
 */
function local_subscriptions_get_lang_label(string $code): string {
    return local_subscriptions_get_lang_name($code) . ' ' . local_subscriptions_get_lang_flag($code);
}

/**
 * Get the native name of a language.
 *
 * @param string $code ISO language code.
 * @return string
 */
function local_subscriptions_get_lang_name(string $code): string {
    static $names = [
        'fr' => 'Français',
        'en' => 'English',
        'ru' => 'Русский',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
    ];

    return $names[$code] ?? strtoupper($code);
}

/**
 * Get the emoji flag for a language.
 *
 * @param string $code ISO language code.
 * @return string
 */
function local_subscriptions_get_lang_flag(string $code): string {
    static $flags = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'ru' => '🇷🇺',
        'es' => '🇪🇸',
        'de' => '🇩🇪',
        'it' => '🇮🇹',
    ];

    return $flags[$code] ?? '🌐';
}

use core_user\output\myprofile\tree;
use core_user\output\myprofile\node;
use core_user\output\myprofile\category;

function local_subscriptions_myprofile_navigation(tree $tree, stdClass $user) {
    global $USER, $PAGE;

    $context = \context_user::instance($user->id);

    // Autoriser si l'utilisateur voit son propre profil ou a la capacité d'en voir d'autres
    $isownprofile = ($USER->id === $user->id);

    if (!$isownprofile && !has_capability('moodle/user:viewdetails', $context, $USER)) {
        return;
    }

    $renderer = $PAGE->get_renderer('local_subscriptions');

    // Charge les abonnements
    $subscriptions = get_user_active_subscriptions($user->id);

    $content = $renderer->render_user_subscriptions_block($subscriptions);

    // if (empty($subscriptions)) {
    //     $content = get_string('no_subscriptions_found', 'local_subscriptions');
    // }

    $category = new category(
        'local_subscriptions', 
        get_string('subscriptions','local_subscriptions'),
        'contact');
    $tree->add_category($category);

    // Génère le lien vers la page de détails
    $url = new \moodle_url('/local/subscriptions/user_subscriptions.php', ['id' => $user->id]);

    // Crée un nœud dans la catégorie
    $node = new node(
        'local_subscriptions', // $parentcat — catégorie parente
        'zzz_local_subscriptions', // $name — identifiant unique du nœud
        get_string('your_subscriptions', 'local_subscriptions'), // $title
        null, // $after — tu peux mettre null ou un autre ID pour le placer après un nœud spécifique
        null, // $url — pas de lien, car on injecte du contenu directement
        $content // $content — ton bloc HTML
    );

    $tree->add_node($node);

    // Charger le JS AMD de ton plugin
    $PAGE->requires->js_call_amd('local_subscriptions/user_popover', 'init');
}

/**
 * Génère un username "propre" et UNIQUE à partir de prénom + nom.
 *
 * Règles :
 *  - minuscules
 *  - accents supprimés (translittération)
 *  - uniquement lettres a-z
 *  - concat prénom + nom
 *  - longueur max 100 chars (Moodle)
 *  - unicité assurée via suffixes 2,3,... si déjà pris
 *
 * @param string $firstname
 * @param string $lastname
 * @return string username unique
 */
/**
 * Génère un username Moodle unique à partir prénom/nom ou email.
 *
 * - minuscules
 * - accents supprimés
 * - uniquement lettres a-z
 * - fallback = partie locale de l'email ou "user"
 * - longueur max 100
 * - unicité en ajoutant suffixes numériques
 *
 * @param string $firstname
 * @param string $lastname
 * @param string $email
 * @return string username unique
 */
function local_subscriptions_generate_unique_username(string $firstname = '', string $lastname = '', string $email = ''): string {
    global $DB;

    // 1) Base = prénom+nom concaténés
    $base = trim($firstname . $lastname);

    // 2) Si vide → fallback = partie locale de l'email
    if ($base === '' && !empty($email)) {
        $local = explode('@', $email)[0];
        $base = $local;
    }

    // 3) Si toujours vide → fallback "user"
    if ($base === '') {
        $base = 'user';
    }

    // 4) Minuscule
    $base = core_text::strtolower($base);

    // 5) Translittération accents → ASCII
    $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);

    // 6) Garder uniquement [a-z]
    $base = preg_replace('/[^a-z]/', '', $base ?? '');

    // 7) Fallback encore si vide après nettoyage
    if ($base === '' || $base === false) {
        $base = 'user';
    }

    // 8) Tronquer à 100 chars max
    $maxlen = 100;
    $base   = mb_substr($base, 0, $maxlen);

    // 9) Vérifier unicité
    if (!$DB->record_exists('user', ['username' => $base])) {
        return $base;
    }

    // 10) Ajouter suffixes numériques si déjà pris
    for ($i = 2; $i < 10000; $i++) {
        $suffix = (string)$i;
        $cut    = $maxlen - strlen($suffix);
        $try    = mb_substr($base, 0, max(1, $cut)) . $suffix;

        if (!$DB->record_exists('user', ['username' => $try])) {
            return $try;
        }
    }

    // 11) Dernier recours: timestamp
    $rand = (string)time();
    $cut  = $maxlen - strlen($rand);
    return mb_substr($base, 0, max(1, $cut)) . $rand;
}

