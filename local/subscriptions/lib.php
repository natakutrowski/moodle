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
    $tt .= '<div class="fw-semibold mb-1">'.get_string('upgrade_details_title','local_subscriptions').'</div>';
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
 * Corps HTML (BRUT) expliquant le calcul du prix d'upgrade.
 * À utiliser dans un <details> (ou ailleurs). Ne PAS échapper en sortie.
 */
function local_subs_upgrade_calc_body(array $opt, \stdClass $currplan, \stdClass $targetplan, \stdClass $currsub, string $currency): string {
    global $DB;

    $C     = mb_strtoupper($currency);
    $extra = $opt['extra'] ?? [];
    $win   = $extra['upgrade_window'] ?? null;

    // Prix des plans (dans la devise), fallback première ligne si devise absente.
    $p1 = (float)($DB->get_field('subscription_plan_price','price',['planid'=>$currplan->id,'currency'=>$C]) ?: 0);
    if ($p1 <= 0) { $any = $DB->get_records('subscription_plan_price',['planid'=>$currplan->id],'','price',0,1); if ($any) $p1 = (float)reset($any)->price; }
    $p2 = (float)($extra['target_price'] ?? 0);
    if ($p2 <= 0) { $p2 = (float)($DB->get_field('subscription_plan_price','price',['planid'=>$targetplan->id,'currency'=>$C]) ?: 0);
        if ($p2 <= 0) { $any = $DB->get_records('subscription_plan_price',['planid'=>$targetplan->id],'','price',0,1); if ($any) $p2 = (float)reset($any)->price; }
    }

    // Durées & fenêtre (secondes).
    $d1 = \local_subscriptions\domain\SubscriptionAdvisor::duration_to_seconds($currplan->duration_key ?? '1year');
    $d2 = \local_subscriptions\domain\SubscriptionAdvisor::duration_to_seconds($targetplan->duration_key ?? '1year');
    $t0 = !empty($win['start']) ? (int)$win['start'] : (int)$currsub->start_date;
    $tEnd = !empty($win['end']) ? (int)$win['end'] : ($t0 + $d2);
    $t  = max(0, min($d2, time() - $t0)); // consommation depuis t0 bornée à D2

    // Partie passée (tarif courant) et à venir (tarif cible), base de calcul.
    $past   = $d1 > 0 ? $p1 * ($t / $d1) : 0.0;               // P1 * t/D1
    $future = $d2 > 0 ? $p2 * (($d2 - $t) / $d2) : 0.0;       // P2 * (D2−t)/D2
    $base   = round($past + $future, 2);

    // Déjà payé dans la fenêtre (si Advisor::quote_upgrade a fourni ça).
    $spent = (float)($extra['spent_window'] ?? ($extra['spent_overlap'] ?? 0.0));

    // Montant final proposé (celui affiché à droite de la radio).
    $final = number_format((float)($opt['amount'] ?? 0), 2);

    // Utilitaires d'affichage.
    $start = userdate($t0);
    $end   = userdate($tEnd);
    $fmtP1 = number_format($p1, 2).' '.$C;
    $fmtP2 = number_format($p2, 2).' '.$C;
    $fmtPast   = number_format($past,   2).' '.$C;
    $fmtFuture = number_format($future, 2).' '.$C;
    $fmtBase   = number_format($base,   2).' '.$C;
    $fmtSpent  = number_format($spent,  2).' '.$C;

    // Durées "humaines" (simple).
    $human = function(int $sec): string {
        if ($sec < 60) return $sec.'s';
        $m = intdiv($sec,60); if ($m < 60) return $m.' min';
        $h = intdiv($m,60);   if ($h < 24) return $h.' h';
        $d = intdiv($h,24);   if ($d < 30) return $d.' j';
        $mo= intdiv($d,30);   if ($mo < 12) return $mo.' mois'.(($d-$mo*30)?' '.($d-$mo*30).' j':'');
        $y = intdiv($mo,12);  $rm = $mo - $y*12; return $y.' an'.($y>1?'s':'').($rm?' '.$rm.' mois':'');
    };

    // HTML BRUT (ne pas échapper; on l'injecte tel quel sous la radio).
    $html  = '<div class="text-muted small mb-2">'.get_string('upgrade_window_label','local_subscriptions', $start.' → '.$end).'</div>';
    $html .= '<ul class="small ps-3">';
    $html .= '<li>'.get_string('upgrade_tariffs','local_subscriptions', (object)['p1'=>$fmtP1,'p2'=>$fmtP2]).'</li>';
    $html .= '<li>'.get_string('upgrade_consumed_since_t0','local_subscriptions', $human($t)).'</li>';
    $html .= '<li>'.get_string('upgrade_equation_past','local_subscriptions', (object)['p1'=>$fmtP1,'t'=>'t','d1'=>$human($d1),'val'=>$fmtPast]).'</li>';
    $html .= '<li>'.get_string('upgrade_equation_future','local_subscriptions', (object)['p2'=>$fmtP2,'d2'=>$human($d2),'t'=>'t','val'=>$fmtFuture]).'</li>';
    $html .= '<li>'.get_string('upgrade_spent_window','local_subscriptions', $fmtSpent).'</li>';
    $html .= '<li>'.get_string('upgrade_base_cap','local_subscriptions', (object)['base'=>$fmtBase,'cap'=>'—']).'</li>';
    $html .= '</ul>';
    $html .= '<div class="mt-1">'.get_string('upgrade_final_amount','local_subscriptions', '<strong>'.$final.' '.$C.'</strong>').'</div>';

    return $html;
}
