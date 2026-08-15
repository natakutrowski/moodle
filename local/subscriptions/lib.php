<?php
// This file is part of local_subscriptions plugin.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib/user_subs_lib.php');

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
    global $USER;

    if ((int)$USER->id !== (int)$user->id) {
        return;
    }

    $category = new category(
        'local_subscriptions_customer_space',
        get_string('profile_customer_space_title', 'local_subscriptions'),
        'contact'
    );
    $tree->add_category($category);

    $links = [
        [
            'name' => 'local_subscriptions_profile_campus',
            'label' => get_string('commerce_customer_hub_title', 'local_subscriptions'),
            'url' => \local_subscriptions\url\UrlFactory::my_campus(),
        ],
        [
            'name' => 'local_subscriptions_profile_courses',
            'label' => get_string('profile_link_courses', 'local_subscriptions'),
            'url' => new moodle_url(\local_subscriptions\subscription_config::campus_my_courses_page()),
        ],
        [
            'name' => 'local_subscriptions_profile_resources',
            'label' => get_string('profile_link_resources', 'local_subscriptions'),
            'url' => new moodle_url(\local_subscriptions\subscription_config::customer_digital_library_page()),
        ],
        [
            'name' => 'local_subscriptions_profile_purchases',
            'label' => get_string('profile_link_purchases', 'local_subscriptions'),
            'url' => new moodle_url(\local_subscriptions\subscription_config::customer_purchases_page()),
        ],
    ];

    foreach ($links as $link) {
        $tree->add_node(new node(
            'local_subscriptions_customer_space',
            $link['name'],
            $link['label'],
            null,
            $link['url']
        ));
    }
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
    global $DB, $CFG;

    // 1) Base = prénom+nom, sinon partie locale de l’email, sinon "user".
    $base = trim($firstname . $lastname);
    if ($base === '' && $email !== '') {
        $base = preg_replace('/@.*/', '', $email); // local-part
    }
    if ($base === '' || $base === null) {
        $base = 'user';
    }

    // 2) Minuscules (gère l’UTF-8).
    $base = core_text::strtolower($base);

    // 3) Laisser Moodle décider d’abord : nettoie selon PARAM_USERNAME
    //    (prend en compte $CFG->extendedusernamechars).
    $username = clean_param($base, PARAM_USERNAME); // validation officielle Moodle
    // Réf : la validation des usernames vit dans lib/moodlelib.php → clean_param(PARAM_USERNAME).
    // (Nous testons ensuite si tout a été "mangé".) 

    // 4) Si le nettoyage aboutit à vide (ex: site sans "extended chars" + nom non latin),
    //    translittérer vers ASCII de façon robuste (ICU si dispo, sinon iconv).
    if ($username === '' || $username === false) {
        if (class_exists('\\Transliterator')) {
            // Any-Latin → Latin-ASCII ; supprime les diacritiques ; minuscules.
            $tr = \Transliterator::create('Any-Latin; Latin-ASCII; NFKD; [:Nonspacing Mark:] Remove; NFC; Lower()');
            if ($tr) {
                $base = $tr->transliterate($base);
            }
        } else {
            $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        }
        $base = core_text::strtolower((string)$base);
        // Conserver exactement l’ensemble permis par Moodle quand "extended" est désactivé.
        $username = preg_replace('/[^a-z0-9._@-]/', '', (string)$base);
    }

    // 5) Fallback si encore vide.
    if ($username === '' || $username === false) {
        $username = 'user';
    }

    // 6) Tronquer à 100 caractères (limite du champ username).
    $maxlen  = 100; // cf. modèle de données de Moodle
    $username = core_text::substr($username, 0, $maxlen);

    // 7) Unicité (on suffixe 2..9999 si nécessaire).
    if (!$DB->record_exists('user', ['username' => $username])) {
        return $username;
    }
    for ($i = 2; $i < 10000; $i++) {
        $suffix = (string)$i;
        $cut    = $maxlen - core_text::strlen($suffix);
        $try    = core_text::substr($username, 0, max(1, $cut)) . $suffix;
        if (!$DB->record_exists('user', ['username' => $try])) {
            return $try;
        }
    }

    // 8) Dernier recours : timestamp.
    $rand = (string)time();
    $cut  = $maxlen - core_text::strlen($rand);
    return core_text::substr($username, 0, max(1, $cut)) . $rand;
}


/**
 * Formate une durée en secondes en texte humain (approx simple).
 */
function local_subs_human_duration(int $sec): string {
    if ($sec < 60) return $sec.'s';
    $m = intdiv($sec, 60); if ($m < 60) return $m.' min';
    $h = intdiv($m, 60);   if ($h < 24) return $h.' h';
    $d = intdiv($h, 24);   if ($d < 30) return $d.' j';
    $mo = intdiv($d, 30);  if ($mo < 12) { $rest = $d - $mo*30; return $mo.' mois'.($rest? ' '.$rest.' j':'' ); }
    $y = intdiv($mo, 12);  $restm = $mo - $y*12; return $y.' an'.($y>1?'s':'').($restm? ' '.$restm.' mois':'' );
}

/**
 * Construit le HTML de la popover d’upgrade à partir de l’option Advisor.
 * $opt['extra'] doit contenir: upgrade_window, spent_window/ spent_overlap (selon ta version), target_price, t_consumed_sec, base_formula
 * $opt['amount'] est le montant final proposé.
 * On complète en recalculant, si besoin, le breakdown exact via Advisor::quote_upgrade().
 */
function local_subs_render_upgrade_popover(array $opt, \stdClass $currplan, \stdClass $targetplan, \stdClass $currsub, string $currency): string {
    // 1) lire $extra / $win
    $extra = $opt['extra'] ?? [];
    $win   = $extra['upgrade_window'] ?? null;
    $tcons = (int)($extra['t_consumed_sec'] ?? 0);
    $spent = (float)($extra['spent_window'] ?? ($extra['spent_overlap'] ?? 0.0));
    $p2hint= (float)($extra['target_price'] ?? 0.0);

    // 2) récupérer P1/P2 (prix plans) SANS dépendre d’un breakdown
    global $DB;
    $C  = mb_strtoupper($currency);
    $p1 = (float)($DB->get_field('subscription_plan_price', 'price',
        ['planid'=>$currplan->id, 'currency'=>$C]) ?: 0);
    if ($p1 <= 0) {
        $any = $DB->get_records('subscription_plan_price', ['planid'=>$currplan->id], '', 'price', 0, 1);
        if ($any) { $p1 = (float)reset($any)->price; }
    }
    $p2 = $p2hint > 0 ? $p2hint : (float)($DB->get_field('subscription_plan_price', 'price',
        ['planid'=>$targetplan->id, 'currency'=>$C]) ?: 0);
    if ($p2 <= 0) {
        $any = $DB->get_records('subscription_plan_price', ['planid'=>$targetplan->id], '', 'price', 0, 1);
        if ($any) { $p2 = (float)reset($any)->price; }
    }

    // 3) durées + fenêtre + t
    $d1 = \local_subscriptions\domain\SubscriptionAdvisor::duration_to_seconds($currplan->duration_key ?? '1year');
    $d2 = \local_subscriptions\domain\SubscriptionAdvisor::duration_to_seconds($targetplan->duration_key ?? '1year');
    $startTs = !empty($win['start']) ? (int)$win['start'] : (int)$currsub->start_date;
    $endTs   = !empty($win['end'])   ? (int)$win['end']   : ($startTs + $d2);
    $t  = max(0, min($d2, time() - $startTs));

    // 4) breakdown : Advisor::quote_upgrade si dispo, sinon fallback « fenêtre complète »
    $b = [];
    if (class_exists('\local_subscriptions\domain\SubscriptionAdvisor')
        && method_exists('\local_subscriptions\domain\SubscriptionAdvisor', 'quote_upgrade')) {
        $q = \local_subscriptions\domain\SubscriptionAdvisor::quote_upgrade($currsub, $currplan, $targetplan, $C);
        $b = $q['breakdown'] ?? [];
        if (!$spent) { $spent = (float)($q['spent_window'] ?? $q['spent_overlap'] ?? 0.0); }
    }
    if (empty($b)) {
        $past   = $d1 > 0 ? $p1 * ($t / $d1) : 0.0;          // P1 * t/D1
        $future = $d2 > 0 ? $p2 * (($d2 - $t) / $d2) : 0.0;  // P2 * (D2−t)/D2
        $base   = round($past + $future, 2);
        $b = [
            'rate_current_per_s' => $d1 > 0 ? round($p1 / $d1, 10) : 0.0,
            'rate_target_per_s'  => $d2 > 0 ? round($p2 / $d2, 10) : 0.0,
            'used_sec'           => $t,
            'remain_sec'         => max(0, $d2 - $t),
            'extend_sec'         => max(0, $d2 - $d1),
            'part_remaining'     => round($past, 2),   // « passée »
            'part_extension'     => round($future, 2), // « à venir »
            'base'               => $base,
            'cap'                => null,
        ];
        if (!$tcons) { $tcons = $t; }
    }

    // 5) fabriquer le HTML BRUT du modèle (pas d’echappement ici)
    $start = userdate($startTs);
    $end   = userdate($endTs);
    $partPast   = isset($b['part_remaining']) ? number_format((float)$b['part_remaining'],2) : '—';
    $partFuture = isset($b['part_extension']) ? number_format((float)$b['part_extension'],2) : '—';
    $baseTxt    = isset($b['base']) ? number_format((float)$b['base'],2) : '—';
    $capTxt     = isset($b['cap'])  ? number_format((float)$b['cap'] ,2) : '—';
    $final      = number_format((float)($opt['amount'] ?? 0), 2);
    $elapsedTxt = local_subs_human_duration($tcons ?: $t);

    $tt  = '<div style="max-width:360px">';
    $tt .= '<div class="fw-semibold mb-1">'.get_string('upgrade_details_summary','local_subscriptions').'</div>';
    $tt .= '<div class="text-muted small mb-2">'.get_string('upgrade_window_label','local_subscriptions', $start.' → '.$end).'</div>';
    $tt .= '<ul class="small ps-3">';
    $tt .= '<li>'.get_string('upgrade_tariffs','local_subscriptions',
            (object)['p1'=>number_format($p1,2).' '.$C, 'p2'=>number_format($p2,2).' '.$C]).'</li>';
    $tt .= '<li>'.get_string('upgrade_consumed_since_t0','local_subscriptions', $elapsedTxt).'</li>';
    $tt .= '<li>'.get_string('upgrade_equation_past','local_subscriptions',
            (object)['p1'=>number_format($p1,2).' '.$C, 't'=>'t', 'd1'=>local_subs_human_duration($d1), 'val'=>$partPast.' '.$C]).'</li>';
    $tt .= '<li>'.get_string('upgrade_equation_future','local_subscriptions',
            (object)['p2'=>number_format($p2,2).' '.$C, 'd2'=>local_subs_human_duration($d2), 't'=>'t', 'val'=>$partFuture.' '.$C]).'</li>';
    $tt .= '<li>'.get_string('upgrade_spent_window','local_subscriptions', number_format($spent,2).' '.$C).'</li>';
    $tt .= '<li>'.get_string('upgrade_base_cap','local_subscriptions',
            (object)['base'=>$baseTxt.' '.$C, 'cap'=>$capTxt? $capTxt.' '.$C : '—']).'</li>';
    $tt .= '</ul>';
    $tt .= '<div class="mt-2">'.get_string('upgrade_final_amount','local_subscriptions', $final.' '.$C).'</div>';
    $tt .= '</div>';

    return $tt; // ← renvoyer le HTML BRUT (pas d’echappement)

}

/**
 * Corps HTML explicatif pour le calcul d'une upgrade.
 *
 * @param array      $opt        Option d'upgrade (une entrée d'$options venant d'advise_options).
 * @param \stdClass  $currplan   Plan actuel.
 * @param \stdClass  $targetplan Plan cible.
 * @param \stdClass  $currsub    Souscription actuelle.
 * @param string     $currency   Devise (EUR/RUB).
 * @return string    HTML
 */
function local_subs_upgrade_calc_body(array $opt, \stdClass $currplan, \stdClass $targetplan, \stdClass $currsub, string $currency): string {
    $e  = $opt['extra'] ?? [];
    $bd = $e['upgrade_breakdown'] ?? [];

    $win    = $e['upgrade_window']       ?? ['start'=>0,'end'=>0];
    $spent  = (float)($e['spent_window'] ?? 0.0);
    $baseUp = (float)($e['upgrade_base_amount'] ?? 0.0);
    $disc   = (int)  ($e['discount_percent']    ?? 0);
    $final  = (float)($e['upgrade_final_amount'] ?? $opt['amount']);

    $P1        = (float)($bd['P1'] ?? 0.0);
    $P2        = (float)($bd['P2'] ?? 0.0);
    $baseTotal = (float)($bd['base_total'] ?? 0.0);
    $partPast  = (float)($bd['part_past']   ?? 0.0);
    $partFut   = (float)($bd['part_future'] ?? 0.0);

    $winStart = (int)($win['start'] ?? 0);
    $winEnd   = (int)($win['end']   ?? 0);

    $startStr = $winStart ? userdate($winStart) : '';
    $endStr   = $winEnd   ? userdate($winEnd)   : '';

    $out = '';

    // 1) Fenêtre
    if ($startStr && $endStr) {
        $out .= html_writer::div(
            get_string('upgrade_window_label', 'local_subscriptions', (object)['start'=>$startStr,'end'=>$endStr]),
            'mb-2'
        );
    }

    // 2) Tarifs de référence
    $out .= html_writer::start_tag('ul');

    $out .= html_writer::tag('li',
        get_string('upgrade_ref_prices', 'local_subscriptions', (object)[
            'current' => ls_format_money($P1, $currency),
            'target'  => ls_format_money($P2, $currency),
        ])
    );

    // 3) Part passée / future (optionnel, mais plus lisible que la formule)
    if ($partPast > 0) {
        $out .= html_writer::tag('li',
            get_string('upgrade_part_past', 'local_subscriptions', ls_format_money($partPast, $currency))
        );
    }
    if ($partFut > 0) {
        $out .= html_writer::tag('li',
            get_string('upgrade_part_future', 'local_subscriptions', ls_format_money($partFut, $currency))
        );
    }

    if ($baseTotal > 0) {
        $out .= html_writer::tag('li',
            get_string('upgrade_base_total', 'local_subscriptions', ls_format_money($baseTotal, $currency))
        );
    }

    if ($spent > 0) {
        $out .= html_writer::tag('li',
            get_string('upgrade_already_paid', 'local_subscriptions', ls_format_money($spent, $currency))
        );
    }

    $out .= html_writer::end_tag('ul');

    // 4) Upgrade avant promo
    if ($baseUp > 0) {
        $out .= html_writer::div(
            get_string('upgrade_base_minus_paid', 'local_subscriptions', (object)[
                'base'  => ls_format_money($baseTotal, $currency),
                'paid'  => ls_format_money($spent, $currency),
                'diff'  => ls_format_money($baseUp, $currency),
            ]),
            'mt-2'
        );
    }

    // 5) Promo éventuelle
    if ($disc > 0 && $final < $baseUp) {
        $out .= html_writer::div(
            get_string('upgrade_discount_line', 'local_subscriptions', (object)[
                'pct'    => $disc,
                'before' => ls_format_money($baseUp, $currency),
                'after'  => ls_format_money($final, $currency),
            ]),
            'mt-1'
        );
    }

    // 6) Montant proposé
    $out .= html_writer::div(
        get_string('upgrade_amount_proposed', 'local_subscriptions', ls_format_money($final, $currency)),
        'mt-2 fw-semibold'
    );

    return $out;
}


function local_subscriptions_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): bool {
    if ($context->contextlevel !== CONTEXT_SYSTEM) {
        return false;
    }

    $allowedareas = [
        'plan_desc',
        'scope_desc',
        'inbox_attachment',
        'catalog_media',
        \local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService::FILEAREA,
        \local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService::FILEAREA,
        \local_subscriptions\commerce\mail\library\CommerceMailLibraryHeaderImageService::FILEAREA,
        \local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailImageService::FILEAREA,
        \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailBannerService::FILEAREA,
        \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailFooterImageService::FILEAREA,
        \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockMediaManager::FILEAREA,
        \local_subscriptions\commerce\showroom\cms\CommerceShowroomSocialImageService::FILEAREA,
    ];

    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    /*
     * Les descriptions de plans et de scopes conservent
     * leur comportement historique.
     *
     * Les pièces jointes Inbox nécessitent une authentification
     * et la capacité de lecture de la CRM Inbox.
     */
    if (in_array($filearea, [
        \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailBannerService::FILEAREA,
        \local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignMailFooterImageService::FILEAREA,
    ], true)) {
        if (count($args) < 2) {
            return false;
        }
        $itemid = (int)array_shift($args);
        $filename = clean_param((string)array_pop($args), PARAM_FILE);
        $filepath = '/' . (count($args) ? implode('/', $args) . '/' : '');
        $file = get_file_storage()->get_file(
            $context->id,
            'local_subscriptions',
            $filearea,
            $itemid,
            $filepath,
            $filename
        );
        if (!$file || $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 86400, 0, false, $options + ['cacheability' => 'public']);
        return true;
    }

    if (in_array($filearea, [
        \local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService::FILEAREA,
        \local_subscriptions\commerce\mail\library\CommerceMailLibraryHeaderImageService::FILEAREA,
    ], true)) {
        if (count($args) < 2) {
            return false;
        }
        $itemid = (int)array_shift($args);
        $filename = clean_param((string)array_pop($args), PARAM_FILE);
        $filepath = '/' . (count($args) ? implode('/', $args) . '/' : '');
        $file = get_file_storage()->get_file(
            $context->id,
            'local_subscriptions',
            $filearea,
            $itemid,
            $filepath,
            $filename
        );
        if (!$file || $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 86400, 0, false, $options + ['cacheability' => 'public']);
        return true;
    }

    if (
        $filearea
        === \local_subscriptions\commerce\showroom\cms\CommerceShowroomSocialImageService::FILEAREA
    ) {
        if (count($args) < 2) {
            return false;
        }
        $itemid = (int)array_shift($args);
        $filename = clean_param((string)array_pop($args), PARAM_FILE);
        if ($itemid <= 0 || $filename === '') {
            return false;
        }
        $filepath = '/' . (count($args) ? implode('/', $args) . '/' : '');
        $file = get_file_storage()->get_file(
            $context->id,
            'local_subscriptions',
            $filearea,
            $itemid,
            $filepath,
            $filename
        );
        if (!$file || $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 86400, 0, false, $options + ['cacheability' => 'public']);
        return true;
    }

    if (
        $filearea
        === \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockMediaManager::FILEAREA
    ) {
        if (count($args) < 2) {
            return false;
        }

        $itemid = (int)array_shift($args);
        $filename = clean_param(
            (string)array_pop($args),
            PARAM_FILE
        );
        if ($itemid <= 0 || $filename === '') {
            return false;
        }

        $filepath = '/'
            . (count($args) ? implode('/', $args) . '/' : '');
        $file = get_file_storage()->get_file(
            $context->id,
            'local_subscriptions',
            $filearea,
            $itemid,
            $filepath,
            $filename
        );
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file(
            $file,
            86400,
            0,
            false,
            $options + ['cacheability' => 'public']
        );
        return true;
    }

    if ($filearea === 'catalog_media') {
        if (count($args) < 2) {
            return false;
        }
        $itemid = (int)array_shift($args);
        $filename = array_pop($args);
        if ($itemid <= 0 || $filename === null || trim((string)$filename) === '') {
            return false;
        }
        $filepath = '/' . (count($args) ? implode('/', $args) . '/' : '');
        $file = get_file_storage()->get_file(
            $context->id,
            'local_subscriptions',
            'catalog_media',
            $itemid,
            $filepath,
            clean_param((string)$filename, PARAM_FILE)
        );
        if (!$file || $file->is_directory()) {
            return false;
        }
        send_stored_file($file, 86400, 0, false, $options);
        return true;
    }

    if (
        $filearea
        === \local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService::FILEAREA
    ) {
        if (count($args) < 2) {
            return false;
        }

        $itemid = (int)array_shift($args);
        $filename = clean_param(
            (string)array_pop($args),
            PARAM_FILE
        );
        if ($itemid <= 0 || $filename === '') {
            return false;
        }

        $filepath = '/'
            . (count($args) ? implode('/', $args) . '/' : '');
        $file = get_file_storage()->get_file(
            $context->id,
            'local_subscriptions',
            $filearea,
            $itemid,
            $filepath,
            $filename
        );
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file(
            $file,
            86400,
            0,
            false,
            $options + ['cacheability' => 'public']
        );
        return true;
    }

    if ($filearea === 'inbox_attachment') {
        require_login();

        require_capability(
            \local_subscriptions\admin\Capabilities::VIEW_INBOX,
            $context
        );

        $forcedownload = true;
    }

    if (count($args) < 2) {
        return false;
    }

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);

    if (
        $itemid <= 0 ||
        $filename === null ||
        trim((string)$filename) === ''
    ) {
        return false;
    }

    if ($filearea === 'inbox_attachment') {
        global $DB;

        $filename = clean_param(
            (string)$filename,
            PARAM_FILE
        );

        if ($filename === '') {
            return false;
        }

        $sql = "
            SELECT 1
              FROM {local_subscriptions_inbox_attachment} a

              JOIN {local_subscriptions_inbox_message} m
                ON m.id = a.messageid

              JOIN {local_subscriptions_inbox_thread} t
                ON t.id = m.threadid

             WHERE a.fileitemid = :fileitemid
               AND a.filename = :filename
               AND a.downloadstatus = :storedstatus
               AND t.locallydeleted = 0
        ";

        $allowed = $DB->record_exists_sql(
            $sql,
            [
                'fileitemid' =>
                    $itemid,

                'filename' =>
                    $filename,

                'storedstatus' =>
                    \local_subscriptions\crm\inbox\domain\InboxAttachmentStatus::STORED,
            ]
        );

        if (!$allowed) {
            return false;
        }
    }
    
    $filepath = !empty($args)
        ? '/' . implode('/', $args) . '/'
        : '/';

    $file = get_file_storage()->get_file(
        $context->id,
        'local_subscriptions',
        $filearea,
        $itemid,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file(
        $file,
        0,
        0,
        $forcedownload,
        $options
    );

    return true;
}

/**
 * Injecte la popup d’abonnement (HTML + JS AMD).
 * À appeler sur toutes les pages où tu affiches un bouton "S’abonner".
 *
 * Usage: local_subscriptions_inject_subscribe_modal($PAGE);
 */
function local_subscriptions_inject_subscribe_modal(\moodle_page $PAGE): void {
    global $USER;

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $isguest = (!isloggedin() || isguestuser());
    $istrial = \local_subscriptions\trial_manager::user_has_active_trial((int)$USER->id);

    // On n'affiche la modale que pour :
    //  - invités
    //  - comptes d'essai
    if (!$isguest && !$istrial) {
        return;
    }

    $title = get_string('subscribe_to_campus', 'local_subscriptions');
    $btnClose = get_string('close','local_subscriptions');

    // Appel AMD
    $PAGE->requires->js_call_amd('local_subscriptions/subs_modal', 'init');

    // HTML de la modale
echo '
<div class="modal fade" id="subsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">'.$title.'</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.s($btnClose).'"></button>
      </div>
      <div class="modal-body p-0 position-relative">

        <div id="subsModalLoader"
             class="subs-modal-loader align-items-center justify-content-center">
          <div class="spinner-border" role="status" aria-hidden="true"></div>
        </div>

        <iframe id="subsModalFrame"
                src=""
                style="width:100%;height:70vh;border:0;"
                loading="lazy"
                title="'.s($title).'">
        </iframe>
      </div>
    </div>
  </div>
</div>';


}

/**
 * Serves CRM Inbox attachments.
 *
 * @param stdClass $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
