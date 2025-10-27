<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

/**
 * Récupère un plan à éditer avec le scope associé.
 */
function local_subscriptions_get_plan_for_edit(int $id): array {
    global $DB;
    require_sesskey();

    $plan = $DB->get_record('subscription_plan', ['id' => $id], '*', MUST_EXIST);
    $accessscopeid = $plan->accessscopeid;
    $duration_key = $plan->duration_key;
    return [$plan, $accessscopeid, $duration_key];
}

/**
 * Supprime un plan et ses traductions après vérification.
 */
function local_subscriptions_delete_plan(int $id): void {
    global $DB;
    require_sesskey();

    if (!$DB->record_exists('subscription_plan', ['id' => $id])) {
        throw new moodle_exception(
			'plannotfound',
			'local_subscriptions',
			new moodle_url(subscription_config::manage_page(), ['tab' => 'plans'])
		);
    }
    
    // ajouter ici check qu'un plan n'est pas utilisé par un utilisateur

    $transaction = $DB->start_delegated_transaction();
    try {
        $DB->delete_records('subscription_plan_translation', ['planid' => $id]);
        $DB->delete_records('subscription_plan_price', ['planid' => $id]);
        $DB->delete_records('subscription_plan', ['id' => $id]);
        $transaction->allow_commit();
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
                get_string('plandeleted', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        throw new moodle_exception(get_string('plandeleteerror', 'local_subscriptions'), '', '', null, $e->getMessage());
    }
}

/**
 * Récupère les plans avec leurs traductions dans la langue courante.
 */
function local_subscriptions_get_all_plans_with_translations(string $lang, string $orderdir): array {
    global $DB;

    return $DB->get_records_sql("
        SELECT s.*, t.name AS translated_name, t.description AS translated_description
        FROM {subscription_plan} s
        LEFT JOIN {subscription_plan_translation} t
            ON s.id = t.planid AND t.lang = ?
        ORDER BY s.name " . $orderdir, [$lang]);
}

function local_subscriptions_get_plan_translations(int $planid = 0): array {
    global $DB;

    return $planid
        ? $DB->get_records('subscription_plan_translation', ['planid' => $planid])
        : $DB->get_records('subscription_plan_translation');
}

function local_subscriptions_delete_plan_translation(int $id): void {
    global $DB;
    $DB->delete_records('subscription_plan_translation', ['id' => $id]);
}

function local_subscriptions_save_plan_translation(): void {
    global $DB, $CFG;

    require_sesskey();

    $id = optional_param('id', 0, PARAM_INT);
    $planid = required_param('planid', PARAM_INT);

    $lang = required_param('lang', PARAM_LANG);
    $name = required_param('name', PARAM_TEXT);

    // description depuis l’éditeur riche (HTML)
    $editor = required_param_array('description_editor', PARAM_RAW); // attend ['text'=>..., 'format'=>...]

    $texthtml   = '';
    $textformat = FORMAT_HTML;
    $draftid    = 0;

    if (is_array($editor)) {
        $texthtml   = (string)($editor['text'] ?? '');
        $textformat = (int)($editor['format'] ?? FORMAT_HTML);
        $draftid    = (int)($editor['itemid'] ?? 0);
    }

    $context = \context_system::instance();
    $editoroptions = [
        'maxfiles'  => 50,
        'maxbytes'  => $CFG->maxbytes,
        'trusttext' => false,
        'subdirs'   => 0,
        'context'   => $context,
    ];

    $now = time();

    if ($id) {
        // UPDATE existant : itemid = $id
        $data = (object)[
            'id'                   => $id,
            'description'          => $texthtml,
            'descriptionformat'    => $textformat,
            'description_editor'   => [
                'text'   => $texthtml,
                'format' => $textformat,
                'itemid' => $draftid,
            ],
        ];
        // Sauvegarde draft -> filearea & réécrit @@PLUGINFILE@@
        $data = file_postupdate_standard_editor(
            $data, 'description', $editoroptions, $context,
            'local_subscriptions', 'plan_desc', $id
        );

        $rec = (object)[
            'id'              => $id,
            'planid'          => $planid,
            'lang'            => $lang,
            'name'            => $name,
            'description'     => $data->description,
            'descriptionformat'=> $data->descriptionformat,
            'last_update'     => $now,
        ];
        $DB->update_record('subscription_plan_translation', $rec);

    } else {
        // INSERT : on crée d'abord pour obtenir $newid (itemid file area)
        $exists = $DB->record_exists('subscription_plan_translation', [
            'planid' => $planid, 'lang' => $lang
        ]);
        if ($exists) {
            redirect(new moodle_url(subscription_config::plans_translations_page(), [
                'add' => $planid,
            ]), get_string('errorduplicatetranslation', 'local_subscriptions'),
               null, \core\output\notification::NOTIFY_ERROR);
        }

        $newid = $DB->insert_record('subscription_plan_translation', (object)[
            'planid'          => $planid,
            'lang'            => $lang,
            'name'            => $name,
            'description'     => '',             // sera rempli après rewrite
            'descriptionformat'=> FORMAT_HTML,
            'creation_date'   => $now,
            'last_update'     => $now,
        ]);

        $data = (object)[
            'id'                 => $newid,
            'description'        => $texthtml,
            'descriptionformat'  => $textformat,
            'description_editor' => [
                'text'   => $texthtml,
                'format' => $textformat,
                'itemid' => $draftid,
            ],
        ];
        $data = file_postupdate_standard_editor(
            $data, 'description', $editoroptions, $context,
            'local_subscriptions', 'plan_desc', $newid
        );

        $DB->update_record('subscription_plan_translation', (object)[
            'id'               => $newid,
            'description'      => $data->description,
            'descriptionformat'=> $data->descriptionformat,
            'last_update'      => $now,
        ]);
    }

    redirect(new moodle_url(subscription_config::plans_translations_page(), ['planid' => $planid]));
}

/**
 * Récupère tous les prix pour un plan donné.
 */
function local_subscriptions_get_plan_prices(int $planid): array {
    global $DB;
    return $DB->get_records('subscription_plan_price', ['planid' => $planid], 'currency ASC');
}

/**
 * Récupère un prix par ID.
 */
function local_subscriptions_get_price(int $id) {
    global $DB;
    return $DB->get_record('subscription_plan_price', ['id' => $id], '*', MUST_EXIST);
}

function local_subscriptions_get_config_price(int $planid, string $currency): float {
    global $DB;
    $rec = $DB->get_record('subscription_plan_price',
        ['planid' => $planid, 'currency' => strtoupper($currency)],
        'price', MUST_EXIST);
    return round((float)$rec->price, 2);
}

function local_subs_money_to_minor_units(string $amountDec, string $currency): int {
    $scaleMap = ['RUB'=>2,'EUR'=>2,'USD'=>2,'JPY'=>0];
    $scale = $scaleMap[strtoupper($currency)] ?? 2;
    // Normaliser pour éviter "24 900,00"
    $norm = preg_replace('/[^\d,.\-]/', '', $amountDec);
    $norm = str_replace(',', '.', $norm);
    if (function_exists('bcmul') && function_exists('bcpow')) {
        $minor = bcmul($norm, bcpow('10', (string)$scale, 0), 0);
        return (int)$minor;
    }
    return (int) round((float)$norm * (10 ** $scale), 0);
}


/**
 * Supprime un prix.
 */
function local_subscriptions_delete_price(int $id): void {
    global $DB;
    $DB->delete_records('subscription_plan_price', ['id' => $id]);
}

function local_subscriptions_get_used_currencies_for_plan(int $planid): array {
    global $DB;
    return $DB->get_fieldset_select('subscription_plan_price', 'currency', 'planid = ?', [$planid]);
}

function local_subscriptions_plan_has_translation(int $planid): bool {
    global $DB;
    return $DB->record_exists('subscription_plan_translation', ['planid' => $planid]);
}

function local_subscriptions_plan_has_price(int $planid): bool {
    global $DB;
    return $DB->record_exists('subscription_plan_price', ['planid' => $planid]);
}

// --- ADD: normalise une clé de durée en nb de jours (approx pour mois/ans) ---
function parse_duration_key_days(?string $key): int {
    $k = trim(mb_strtolower((string)$key));
    if ($k === '' || $k === 'lifetime' || $k === 'illimite' || $k === 'unlimited') {
        return PHP_INT_MAX; // passe en fin de liste
    }
    if (preg_match('/^(\d+)\s*(day|week|month|year)s?$/', $k, $m)) {
        $n = (int)$m[1];
        return match ($m[2]) {
            'day'   => max(1, $n),
            'week'  => max(1, 7  * $n),
            'month' => max(1, 30 * $n),  // approx
            'year'  => max(1, 365* $n),  // approx
        };
    }
    // anciens formats simples (back-compat)
    return match ($k) {
        '1month'  => 30,
        '3months' => 90,
        '6months' => 180,
        '1year'   => 365,
        '2years'  => 730,
        '3years'  => 1095,
        default   => 999999, // inconnu -> bas de liste
    };
}

// --- ADD: comparator pour usort (durée, puis récurrent d'abord, puis nom) ---
function compare_plans_by_duration(object $a, object $b): int {
    $da = parse_duration_key_days($a->duration_key ?? '');
    $db = parse_duration_key_days($b->duration_key ?? '');
    if ($da !== $db) {
        return $da <=> $db;
    }
    $ra = (int)($a->is_recurring ?? 0);
    $rb = (int)($b->is_recurring ?? 0);
    if ($ra !== $rb) {
        return $rb <=> $ra; // récurrents avant non-récurrents
    }
    return strcasecmp((string)($a->name ?? ''), (string)($b->name ?? ''));
}

// --- ADD: utilitaire pratique qui renvoie un nouvel array trié ---
function sort_plans_by_duration(array $plans, bool $preservekeys=false): array {
    if ($preservekeys) {
        uasort($plans, __NAMESPACE__ . '\compare_plans_by_duration');
        return $plans;
    }
    $list = array_values($plans);
    usort($list, __NAMESPACE__ . '\compare_plans_by_duration');
    return $list;
}

/**
 * Nom à afficher d’un PLAN avec fallback de traduction.
 * - Utilise $plan->translated_name si déjà joint
 * - Sinon va chercher dans subscription_plan_translation(lang)
 * - Retourne un texte prêt à afficher (format_string)
 */
function local_subscriptions_plan_display_name(\stdClass $plan, ?string $lang = null): string {
    global $DB;
    $lang = $lang ?? current_language();

    if (!empty($plan->translated_name)) {
        return format_string($plan->translated_name);
    }

    static $cache = []; // [planid:lang] => name
    $key = ((int)$plan->id).':'.$lang;
    if (!isset($cache[$key])) {
        $t = $DB->get_record('subscription_plan_translation',
            ['planid' => $plan->id, 'lang' => $lang], 'name', IGNORE_MISSING);
        $cache[$key] = format_string($t->name ?? ($plan->name ?? ''));
    }
    return $cache[$key];
}

/**
 * Retourne la meilleure TRADUCTION de description de PLAN selon l’ordre :
 * $lang → 'fr' → première dispo → null.
 * Champs utiles : id, description, descriptionformat.
 */
function local_subscriptions_get_plan_translation_best_for_desc(int $planid, ?string $lang=null): ?\stdClass {
    global $DB;
    $lang = $lang ?? current_language();

    // 1) langue courante
    $t = $DB->get_record('subscription_plan_translation',
        ['planid'=>$planid, 'lang'=>$lang],
        'id,description,descriptionformat', IGNORE_MISSING);
    if ($t) return $t;

    // 2) fr (si ce n'est pas déjà fr)
    if ($lang !== 'fr') {
        $t = $DB->get_record('subscription_plan_translation',
            ['planid'=>$planid, 'lang'=>'fr'],
            'id,description,descriptionformat', IGNORE_MISSING);
        if ($t) return $t;
    }

    // 3) toute autre existante
    return $DB->get_record('subscription_plan_translation',
        ['planid'=>$planid],
        'id,description,descriptionformat', IGNORE_MISSING) ?: null;
}

/**
 * HTML de la description d’un PLAN, avec rewrite pluginfile + format_text.
 * - $empty rend '-' si aucune description trouvée.
 * - filearea: 'plan_desc' ; itemid = translation.id (fallback sur planid si aucun fichier).
 */
function local_subscriptions_plan_description_html(
    int $planid,
    ?\context $context = null,
    ?string $lang = null,
    string $empty = '-'
): string {
    global $CFG, $DB;
    $context = $context ?? \context_system::instance();
    $lang    = $lang ?? current_language();

    $t = local_subscriptions_get_plan_translation_best_for_desc($planid, $lang);
    $desc   = (string)($t->description ?? '');
    $format = (int)($t->descriptionformat ?? FORMAT_HTML);

    if ($desc === '') {
        return $empty;
    }

    if (strpos($desc, '@@PLUGINFILE@@') !== false) {
        $fs = get_file_storage();
        $preferred = !empty($t->id) ? (int)$t->id : 0;
        $hasfiles = $preferred
            ? $fs->get_area_files($context->id, 'local_subscriptions', 'plan_desc', $preferred, 'id', false)
            : [];
        $itemid = $hasfiles ? $preferred : $planid;

        $desc = file_rewrite_pluginfile_urls($desc, 'pluginfile.php', $context->id,
            'local_subscriptions', 'plan_desc', $itemid);
    }

    return format_text($desc, $format, [
        'context'     => $context,
        'noclean'     => false,
        'overflowdiv' => true,
    ]);
}