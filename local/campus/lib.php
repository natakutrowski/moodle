<?php
defined('MOODLE_INTERNAL') || die();

/** Secret HMAC pour cookies trial */
function local_campus_secret(): string {
    global $CFG;
    return hash('sha256', $CFG->siteidentifier . '::local_campus_trial');
}
function local_campus_sign(string $data): string {
    return hash_hmac('sha256', $data, local_campus_secret());
}
function local_campus_make_cookie(int $trialid, int $expiresat): string {
    $data = $trialid.'|'.$expiresat;
    $sig  = local_campus_sign($data);
    return rtrim(strtr(base64_encode($data.'|'.$sig), '+/', '-_'), '=');
}
function local_campus_parse_cookie(string $raw): ?array {
    $decoded = base64_decode(strtr($raw, '-_', '+/'), true);
    if ($decoded === false) return null;
    $parts = explode('|', $decoded);
    if (count($parts)!==3) return null;
    [$trialid,$expiresat,$sig] = $parts;
    if (!ctype_digit($trialid) || !ctype_digit($expiresat)) return null;
    $data = $trialid.'|'.$expiresat;
    if (!hash_equals(local_campus_sign($data), $sig)) return null;
    return ['trialid'=>(int)$trialid,'expiresat'=>(int)$expiresat];
}
function local_campus_set_cookie(int $trialid, int $expiresat): void {
    $value = local_campus_make_cookie($trialid, $expiresat);
    $params = [
        'expires'=>$expiresat, 'path'=>'/', 'secure'=>is_https(), 'httponly'=>true, 'samesite'=>'Lax'
    ];
    setcookie('campus_trial', $value, $params);
}

function local_campus_clear_cookie(): void {
    // même nom/chemin/hhttponly/samesite que set_cookie
    setcookie('campus_trial', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE['campus_trial']);
}

function local_campus_get_cookie(): ?array {
    if (empty($_COOKIE['campus_trial'])) return null;
    return local_campus_parse_cookie($_COOKIE['campus_trial']);
}

/** Récupère la liste des cours d’essai (config) */
function local_campus_trial_course_ids(): array {
    global $DB;
    $raw = get_config('local_campus','trialcourses');
    // admin_setting_configmultiselect stocke CSV
    $ids = array_filter(array_map('intval', preg_split('~[,\s]+~', (string)$raw)), fn($v)=>$v>0);
    if (!$ids) return [];
    list($in,$p) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
    return array_keys($DB->get_records_select('course', "id $in", $p, '', 'id'));
}

function local_campus_is_trial_user(): bool {
    global $USER, $DB;
    if (!isloggedin() || isguestuser()) return false;
    $r = $DB->get_record('role', ['shortname'=>'triallimited'], 'id', IGNORE_MISSING);
    if (!$r) return false;
    return user_has_role_assignment($USER->id, $r->id, \context_system::instance()->id);
}

/** Enlève des nodes de navigation quand l’utilisateur est en essai. */
/**
 * Appelé à chaque page pour permettre au plugin de modifier l'arbre de navigation global.
 */
function local_campus_extend_navigation(global_navigation $nav) : void {
    // Optionnel : on ne filtre que pour les comptes d'essai
    if (!function_exists('local_campus_is_trial_user') || !local_campus_is_trial_user()) {
        return;
    }
}


/**
 * Appelé pour enrichir/adapter la navigation de réglages (colonne de droite).
 */
function local_campus_extend_settings_navigation(\settings_navigation $settingsnav, \context $context): void {
    if (!function_exists('local_campus_is_trial_user') || !local_campus_is_trial_user()) {
        return;
    }

    foreach (['usercurrentsettings','usersettings','useraccount'] as $key) {
        $node = $settingsnav->get($key);
        if ($node instanceof \navigation_node) {
            // Parcourir la collection d’enfants et les retirer un par un
            foreach ($node->children as $child) {
                $child->remove();
            }
        }
    }
}


/**
 * Moodle 5.x : $tree est un core_user\output\myprofile\tree qui n'expose pas d'API stable pour retirer des nœuds.
 * Ce callback est laissé volontairement neutre. Le “cadenassage” des comptes d’essai
 * se fait via les capacités (rôle système triallimited en Interdire) et via
 * local_campus_extend_navigation()/local_campus_extend_settings_navigation().
 *
 * @param object $tree          Instance de core_user\output\myprofile\tree (Moodle 5.x)
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param mixed $courseid
 * @param mixed $category
 * @return void
 */
function local_campus_myprofile_navigation($tree, $user, $iscurrentuser, $courseid, $category = null): void {
    // Intentionnellement vide pour compatibilité Moodle 5.x
    return;
}

function local_campus_subscriber_course_ids(): array {
    $raw = get_config('local_campus','subscribercourses');
    if (!is_string($raw) || $raw === '') { return []; }
    $ids = array_values(array_unique(array_filter(array_map('intval',
        preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY)
    ), function($i){ return $i > 1; })));
    return $ids;
}


/**
 * Onglets en haut de page (Mes cours / Catalogue).
 */
function local_campus_render_tabs(array $tabs): string {
    global $OUTPUT;
    $html = '<div class="campus-tabs container mt-3 mb-4"><div class="btn-group" role="group">';
    foreach ($tabs as $t) {
        $cls = 'btn btn-outline-secondary campus-tab';
        if (!empty($t['active'])) $cls .= ' active';
        $url = is_string($t['url']) ? new moodle_url($t['url']) : $t['url'];
        $html .= html_writer::link($url, s($t['label']), ['class'=>$cls]);
    }
    $html .= '</div></div>';
    return $html;
}

function local_campus_header_tabs_html(bool $activeMy): string {
    $my  = new moodle_url('/local/campus/mycourses.php');
    $cat = new moodle_url('/local/campus/courses.php');

    $btnMy  = html_writer::link($my,  get_string('tab_mycourses','local_campus'),
        ['class'=>'btn btn-outline-secondary me-2'.($activeMy?' active':'')]);
    $btnCat = html_writer::link($cat, get_string('tab_catalogue','local_campus'),
        ['class'=>'btn btn-outline-secondary'.(!$activeMy?' active':'')]);

    return html_writer::div($btnMy.$btnCat, 'btn-group');
}

// Boutons inline « Mes cours | Catalogue » (pilules collées)
function local_campus_tabs_inline_html(bool $activeMy): string {
    $my  = new moodle_url('/local/campus/mycourses.php');
    $cat = new moodle_url('/local/campus/courses.php');

    $a1 = html_writer::link($my,  get_string('tab_mycourses','local_campus'),
        ['class'=>'seg', 'aria-current'=> $activeMy ? 'page' : null]);
    $a2 = html_writer::link($cat, get_string('tab_catalogue','local_campus'),
        ['class'=>'seg', 'aria-current'=> !$activeMy ? 'page' : null]);

    // Conteneur segmenté (overflow hidden + bord commun)
    return html_writer::div(
        html_writer::div($a1.$a2, 'campus-segment'),
        'campus-tabs-inline'
    );
}

/**
 * Retourne true si l’utilisateur a déjà affiché la page du cours (événement core\event\course_viewed présent dans le log standard).
 */
function local_campus_user_has_visited_course(int $userid, int $courseid): bool {
    global $DB;

    // On vérifie que le log standard est dispo.
    if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
        return false;
    }
    // Event ‘course viewed’ – nécessite le logstore standard actif.
    $params = [
        'uid'   => $userid,
        'cid'   => $courseid,
        'event' => '\core\event\course_viewed',
    ];
    return (bool)$DB->record_exists_select(
        'logstore_standard_log',
        'userid = :uid AND courseid = :cid AND eventname = :event',
        $params
    );
}



/**
 * URL "reprendre" : première activité visible et non terminée ; sinon page du cours.
 */
function local_campus_resume_url(stdClass $course, int $userid): string {
    $modinfo = get_fast_modinfo($course, $userid);
    $ci = new completion_info($course);
    foreach ($modinfo->get_cms() as $cm) {
        if (!$cm->uservisible || empty($cm->url)) continue;
        if (!$ci->is_enabled($cm)) continue;
        $data = $ci->get_data($cm, false, $userid);
        if ((int)$data->completionstate !== COMPLETION_COMPLETE) {
            return $cm->url->out(false);
        }
    }
    return (new moodle_url('/course/view.php', ['id'=>$course->id]))->out(false);
}

function local_campus_is_trial_user_byid(int $userid): bool {
    global $DB;
    $sysctx = context_system::instance();

    // Récupère l'id du rôle 'triallimited'
    static $trialroleid = null;
    if ($trialroleid === null) {
        $trialroleid = (int)$DB->get_field('role', 'id', ['shortname' => 'triallimited'], IGNORE_MISSING);
    }
    if (!$trialroleid) {
        return false;
    }

    // L'utilisateur a-t-il ce rôle AU CONTEXTE SYSTÈME ?
    return $DB->record_exists('role_assignments', [
        'userid'    => $userid,
        'contextid' => $sysctx->id,
        'roleid'    => $trialroleid,
    ]);
}

function local_campus_render_trial_discount_banner(?bool $showcta = null): void {
    global $USER, $CFG, $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

    $trial = \local_subscriptions\trial_manager::user_has_active_trial((int)$USER->id);
    if (!$trial) {
        return;
    }
    if (!\local_subscriptions\trial_manager::is_discount_window_open((int)$USER->id)) {
        return;
    }

    $discPct   = (int)(get_config('local_subscriptions','trial_discount_percent') ?? 15);
    $deadline  = (int)\local_subscriptions\trial_manager::discount_window_deadline((int)$USER->id);
    $subscribe = (new moodle_url('/local/subscriptions/subscribe.php'))->out(false);

    // Détection auto : pas de CTA sur subscribe.php
    if ($showcta === null) {
        $onSubscribe = (strpos($PAGE->url->out(false), '/local/subscriptions/subscribe.php') !== false);
        $showcta = !$onSubscribe;
    }

    $daysLabel = get_string('trial_days_word','local_campus'); // 'jours' / 'days' / 'дн.'

    // 🔁 On réutilise la même string que pour le checkout
    // ex RU : "🎁 -20% на подписку. Скидка доступна только"
    $prefix = get_string('checkout_discount_note_prefix', 'local_subscriptions', $discPct);

    echo html_writer::start_div('campus-trial-15 banner');
    echo html_writer::start_div('container d-flex align-items-center justify-content-between');

    // Bloc texte + countdown
    $text = html_writer::span(
                s($prefix).' ',
                'ttl'
            ) .
            html_writer::span(
                '', 'deadline fw-semibold',
                ['data-deadline' => $deadline, 'data-dayslabel' => $daysLabel]
            );

    echo html_writer::div($text, 'campus-trial-15-text me-3');

    // CTA à droite, un peu décalé
    if ($showcta) {
        echo html_writer::link(
            $subscribe,
            get_string('trial_discount_banner_cta','local_campus'),
            [
                'class'          => 'default-btn campus-trial-15-cta ms-4',
                'data-subs-modal'=> '1',
            ]
        );
    }

    echo html_writer::end_div();  // .container
    echo html_writer::end_div();  // .campus-trial-15

    // Compte à rebours X jours HH:mm:ss
    echo html_writer::script("
      (function(){
        var el = document.querySelector('.campus-trial-15 .deadline'); if (!el) return;
        var dl    = parseInt(el.getAttribute('data-deadline'), 10) * 1000;
        var dword = el.getAttribute('data-dayslabel') || 'd';
        function two(n){ return (n < 10 ? '0' : '') + n; }
        function tick(){
          var t = Math.max(0, Math.floor((dl - Date.now()) / 1000));
          var d = Math.floor(t / 86400);
          var h = Math.floor((t % 86400) / 3600);
          var m = Math.floor((t % 3600) / 60);
          var s = t % 60;
          var head = d > 0 ? (d + ' ' + dword + ' ') : '';
          el.textContent = head + two(h) + ':' + two(m) + ':' + two(s);
        }
        tick();
        setInterval(tick, 1000);
      })();
    ");
}

/**
 * Retourne le HTML du bandeau de remise trial (ou chaîne vide si non applicable),
 * pour pouvoir l'injecter dans un template Mustache.
 */
function local_campus_get_trial_discount_banner_html(): string {
    // On réutilise toute la logique existante de filtre (trial, fenêtre, etc.)
    if (!function_exists('local_campus_render_trial_discount_banner')) {
        return '';
    }

    // Filtrage supplémentaire par type de page (optionnel mais pratique)
    global $PAGE;
    $pagetype = $PAGE->pagetype; // ex : 'local-campus-mycourses', 'course-view-topics', 'user-profile'

    // On ne veut que sur mycourses, toutes les vues de cours, et profil
    if (
        $pagetype !== 'local-campus-mycourses'
        && strpos($pagetype, 'course-view-') !== 0
        && $pagetype !== 'user-profile'
    ) {
        return '';
    }

    ob_start();
    local_campus_render_trial_discount_banner();
    return trim(ob_get_clean());
}


function local_campus_render_subscription_expiry_banner(): void {
    global $USER, $DB, $CFG;

    if (!isloggedin() || isguestuser()) return;

    // Récupère la souscription payante active qui expire le plus tôt
    $now = time();
    $sub = $DB->get_record_sql("
        SELECT us.*, sp.name AS planname, sp.expiry_reminder_days, sp.expiry_reminder_enabled
          FROM {user_subscription} us
          JOIN {subscription_plan} sp ON sp.id = us.planid
         WHERE us.userid = :u
           AND us.status = 'ACTIVE'
           AND us.end_date > :now
           AND (sp.is_trial IS NULL OR sp.is_trial = 0)
         ORDER BY us.end_date ASC
         LIMIT 1
    ", ['u'=>(int)$USER->id,'now'=>$now]);

    if (!$sub) return;
    if (isset($sub->expiry_reminder_enabled) && (int)$sub->expiry_reminder_enabled === 0) {
        return;
    }

    // Jours plan → fallback global
    $planCsv = (string)($sub->expiry_reminder_days ?? '');
    $planDays = array_values(array_unique(array_filter(array_map('intval', preg_split('/[,\s;]+/', trim($planCsv))))));
    if (empty($planDays)) {
        $globalcsv = (string)(get_config('local_subscriptions','expiry_reminder_days') ?? '7');
        $planDays = array_values(array_unique(array_filter(array_map('intval', preg_split('/[,\s;]+/', trim($globalcsv))))));
    }
    if (empty($planDays)) return;

    $daysleft = (int)ceil( ((int)$sub->end_date - $now) / DAYSECS );

    // APRÈS : bandeau en continu dès le plus grand J configuré
    $threshold = max($planDays);     // ex. avec 7,3,1 => 7
    if ($daysleft > $threshold || $daysleft <= 0) return;
 
    $date = userdate((int)$sub->end_date, '%e %B %Y, %H:%M');
    $renew = (new moodle_url('/local/subscriptions/subscribe.php'))->out(false);

    $txt = get_string('sub_expiry_banner','local_campus', (object)[
        'plan' => format_string($sub->planname),
        'date' => $date,
        'days' => $daysleft
    ]);

    echo html_writer::div(
        html_writer::div(
            html_writer::div($txt, 'me-3') .
            html_writer::link($renew, get_string('subscribe_now','local_campus'), ['class'=>'btn btn-sm btn-primary ms-auto']),
            'alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2'
        ),
        'container mt-3'
    );
}



/**
 * Injecte la popup d'accès d’essai (même UI pour le bloc et la page vitrine).
 * - Clic sur un élément portant data-campus-trial-redirect="COURSEID"
 * - Check cookie via trial_check.php, sinon formulaire vers trial_gate.php
 */
function local_campus_inject_trial_ui(\moodle_page $PAGE): void {
    static $done = false;
    if ($done) { return; }
    $done = true;

    // Chaînes côté JS
    $PAGE->requires->strings_for_js([
        'trial_popup_title','trial_popup_lead','trial_popup_tos','trial_popup_accept',
        'trial_btn_continue','trial_btn_subscribe','trial_expired_msg',
        'trial_firstname','trial_lastname','trial_email',
        'trial_already_subscribed_html', 'trial_expired_html',
        'trial_password_mismatch'
    ], 'local_campus');

    // Pour le libellé "Login" utilisé côté JS
    $PAGE->requires->strings_for_js(['login'], 'moodle');

    // Liens Privacy + CGV (même logique que checkout.php)
    $policyurl = (new moodle_url('/local/subscriptions/privacy.php'))->out(false);
    $termsurl  = (new moodle_url('/local/subscriptions/terms.php'))->out(false);

    if (class_exists('\local_subscriptions\support\Region')) {
        $urls = \local_subscriptions\support\Region::policyUrls(); // ['terms'=>..., 'policy'=>...]
        if (!empty($urls['policy'])) { $policyurl = (string)$urls['policy']; }
        if (!empty($urls['terms']))  { $termsurl  = (string)$urls['terms'];  }
    }

    // Chaînes côté PHP
    $title     = get_string('trial_popup_title', 'local_campus');
    $lead      = get_string('trial_popup_lead',  'local_campus');
    $expired   = get_string('trial_expired_msg', 'local_campus');

    $footnote  = get_string('trial_footer_note', 'local_campus');

    // TOS avec 2 liens (policy + terms)
    $tosHtml   = get_string('trial_tos_html', 'local_campus', (object)[
        'policyurl' => $policyurl,
        'termsurl'  => $termsurl,
    ]);
    $btnCancel = get_string('cancel');
    $btnCont   = get_string('trial_btn_continue','local_campus');
    $btnSub    = get_string('trial_btn_subscribe','local_campus');
    $btnClose  = get_string('close','local_campus');
    $sesskey   = sesskey();

    // ✅ Champs mutualisés (prenom/nom/email/tel/mot de passe)
    // La fonction doit être définie dans local/campus/lib.php
    $fieldsHtml = local_campus_render_signup_fields('trial');

    echo '
<div class="modal fade" id="campusTrialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:16px">
      <div class="modal-header">
        <h5 class="modal-title">'.s($title).'</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="'.s($btnClose).'"></button>
      </div>

      <form id="campusTrialForm" class="modal-body p-4">
        <input type="hidden" name="sesskey" value="'.s($sesskey).'">
        <input type="hidden" name="redirectid" id="campusTrialRedirectId" value="">
        <p class="lead mb-3"><strong>'.$lead.'</strong></p>

        <div id="campusTrialExpired" class="alert alert-warning d-none">'.$expired.'</div>

        <div id="campusTrialFormWrap">
          '.$fieldsHtml.'

          <div class="form-check my-3">
            <input class="form-check-input" type="checkbox" id="trialAcceptTos" required>
            <label class="form-check-label" for="trialAcceptTos">'.$tosHtml.'</label>
          </div>

          <div id="campusTrialError" class="alert alert-danger d-none"></div>
        </div>
      </form>

      <div class="modal-footer trial-footer">
        <div class="trial-footer-buttons">
          <a id="campusTrialSubscribe" href="/local/subscriptions/subscribe.php" class="default-btn d-none">
            '.s($btnSub).'
          </a>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.s($btnCancel).'</button>
          <button type="submit" form="campusTrialForm" id="campusTrialContinue" class="default-btn" disabled>
            '.s($btnCont).'
          </button>
        </div>
        <div class="trial-footer-note small text-muted">
          '.s($footnote).'
        </div>
      </div>

    </div>
  </div>
</div>';

    // Charger le module AMD qui gère la popup
    $PAGE->requires->js_call_amd('local_campus/trial_popup', 'init');
}


/**
 * Charge et met en cache la liste exhaustive des indicatifs depuis le JSON.
 * Fichier attendu : local/campus/data/country_dial_codes.json
 *
 * Retourne un tableau [ISO2 => ['dial' => '+33', 'name_en' => 'France']].
 */
function local_campus_load_dial_codes(): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $file = __DIR__ . '/data/CountryCodes.json';
    if (!is_readable($file)) {
        $cache = [];
        return $cache;
    }

    $raw = file_get_contents($file);
    $items = json_decode($raw, true);
    if (!is_array($items)) {
        $cache = [];
        return $cache;
    }

    $out = [];

    foreach ($items as $item) {
        if (empty($item['code']) || empty($item['dial_code'])) {
            continue;
        }
        $iso  = strtoupper(trim($item['code']));
        $dial = trim($item['dial_code']);
        $name = trim($item['name'] ?? '');

        // On ignore les cas bizarres type "International Networks", etc.
        if ($iso === '' || $dial === '') {
            continue;
        }

        $out[$iso] = [
            'dial'    => $dial,
            'name_en' => $name,
        ];
    }

    $cache = $out;
    return $cache;
}

/**
 * Génère un emoji de drapeau à partir d'un code ISO à 2 lettres.
 * Fonctionne pour tous les codes ISO standards : FR -> 🇫🇷, RU -> 🇷🇺, etc.
 */
function local_campus_country_flag_emoji(string $iso): string {
    $iso = strtoupper(trim($iso));
    if (!preg_match('/^[A-Z]{2}$/', $iso)) {
        return '';
    }

    // Regional Indicator Symbols (A=U+1F1E6)
    $base = 0x1F1E6;

    $cp1 = $base + (ord($iso[0]) - ord('A'));
    $cp2 = $base + (ord($iso[1]) - ord('A'));

    // On construit les emojis via les entités HTML pour rester compatible.
    $emoji1 = mb_convert_encoding('&#'.$cp1.';', 'UTF-8', 'HTML-ENTITIES');
    $emoji2 = mb_convert_encoding('&#'.$cp2.';', 'UTF-8', 'HTML-ENTITIES');

    return $emoji1 . $emoji2;
}


/**
 * Devine un indicatif par défaut (toujours dial, pas iso).
 */
function local_campus_default_phone_country_code(): string {
    $lang = current_language();

    if (strpos($lang, 'ru') === 0) { return '+7'; }
    if (strpos($lang, 'fr') === 0) { return '+33'; }
    if (strpos($lang, 'en') === 0) { return '+1'; }

    return '+7'; // fallback pour ton audience
}

/**
 * Construit la liste utilisée dans le sélecteur :
 * [ISO2 => ['code'=>'+33', 'flag'=>'🇫🇷', 'name'=>'France', 'label'=>'🇫🇷 +33 (France)']]
 */
function local_campus_phone_country_list(): array {
    $dialcodes = local_campus_load_dial_codes();
    if (!$dialcodes) {
        return [];
    }

    $countries = get_string_manager()->get_list_of_countries(true); // noms traduits

    $out = [];
    foreach ($dialcodes as $iso => $info) {
        // On ne garde que les pays connus de Moodle
        if (!isset($countries[$iso])) {
            continue;
        }
        $dial = $info['dial'];
        $name = $countries[$iso];       // nom dans la langue de l’interface
        $flag = local_campus_country_flag_emoji($iso);

        $label = trim($flag.' '.$dial.' ('.$name.')');

        $out[$iso] = [
            'code'  => $dial,
            'flag'  => $flag,
            'name'  => $name,
            'label' => $label,
        ];
    }

    // Tri par label pour un joli menu
    uasort($out, function($a, $b) {
        return strcmp($a['label'], $b['label']);
    });

    return $out;
}

/**
 * Rend le <select> indicatif :
 * <option value="">Ind.</option>
 * <option value="+7">🇷🇺 +7 (Russie)</option> etc.
 */
function local_campus_phone_country_select_html(string $name, string $id, ?string $current = null): string {
    $countries   = local_campus_phone_country_list();
    $current     = $current ?: local_campus_default_phone_country_code();
    $placeholder = get_string('trial_phone_country_placeholder', 'local_campus');

    $out  = '<select name="'.s($name).'" id="'.s($id).'" class="form-select">';
    $out .= '<option value="">'.s($placeholder).'</option>';

    foreach ($countries as $iso => $info) {
        $code  = $info['code'];
        $label = $info['label'];
        $sel   = ($code === $current) ? ' selected' : '';
        $out  .= '<option value="'.s($code).'"'.$sel.'>'.s($label).'</option>';
    }

    $out .= '</select>';
    return $out;
}

/**
 * Renvoie un code ISO2 (ex: 'FR') à partir d'un indicatif (ex: '+33').
 * On prend simplement le premier code qui correspond dans la liste JSON.
 */
function local_campus_iso_from_phonecode(string $code): ?string {
    $code = trim($code);
    if ($code === '') {
        return null;
    }

    $dialcodes = local_campus_load_dial_codes(); // [ISO => ['dial'=>'+..', ...]]
    if (!$dialcodes) {
        return null;
    }

    foreach ($dialcodes as $iso => $info) {
        if (!empty($info['dial']) && $info['dial'] === $code) {
            return $iso;
        }
    }
    return null;
}

/**
 * Rend les champs d'inscription (nom, prénom, email, téléphone, mot de passe)
 * pour :
 *   - la popup d'essai (context = 'trial')
 *   - le checkout invité (context = 'checkout')
 *
 * $defaults permet de préremplir (email/prénom/nom) côté checkout.
 */
function local_campus_render_signup_fields(string $context, ?\stdClass $defaults = null): string {
    global $CFG;

    $defaults = $defaults ?? (object)[];
    $dfirst   = $defaults->firstname ?? '';
    $dlast    = $defaults->lastname  ?? '';
    $demail   = $defaults->email     ?? '';

    // Strings communes (on reprend exactement celles de la popup Trial).
    $lblFirst  = get_string('trial_firstname',   'local_campus');
    $lblLast   = get_string('trial_lastname',    'local_campus');
    $lblEmail  = get_string('trial_email',       'local_campus');
    $lblPass   = get_string('trial_password',    'local_campus');
    $phPass    = get_string('trial_password_ph', 'local_campus');
    $helpPass  = get_string('trial_password_help', 'local_campus');

    $phFirst   = get_string('trial_firstname_ph','local_campus');
    $phLast    = get_string('trial_lastname_ph', 'local_campus');
    $phEmail   = get_string('trial_email_ph',    'local_campus');

    $lblPhone  = get_string('trial_phone',       'local_campus');
    $phPhone   = get_string('trial_phone_ph',    'local_campus');
    $helpPhone = get_string('trial_phone_help',  'local_campus');

    $toggleShow = get_string('trial_password_toggle_show', 'local_campus');
    $toggleHide = get_string('trial_password_toggle_hide', 'local_campus');

    // Select indicatif (id différent selon contexte).
    $phoneSelectId = ($context === 'checkout') ? 'checkoutPhoneCountry' : 'trialPhoneCountry';
    $phoneSelect   = local_campus_phone_country_select_html('phonecountry', $phoneSelectId, null);

    // IDs + names selon le contexte
    if ($context === 'checkout') {
        $idFirst  = 'firstname';
        $idLast   = 'lastname';
        $idEmail  = 'email';
        $idPhone  = 'checkoutPhone';
        $idPass   = 'checkoutPass';

        $nameFirst = 'firstname';
        $nameLast  = 'lastname';
        $nameEmail = 'email';
        $namePhone = 'phone';
        $namePass  = 'password';

        $emailHintDiv = '<div class="text-warning small" id="ls_email_hint"></div>';
    } else { // 'trial'
        $idFirst  = 'trialFirst';
        $idLast   = 'trialLast';
        $idEmail  = 'trialEmail';
        $idPhone  = 'trialPhone';
        $idPass   = 'trialPass';

        $nameFirst = 'firstname';
        $nameLast  = 'lastname';
        $nameEmail = 'email';
        $namePhone = 'phonenumber';
        $namePass  = 'password';

        $emailHintDiv = ''; // pas de hint spécial dans la popup
    }

    $html = '';

    $html .= '<div class="row g-2">';

    // Prénom
    $html .= '
        <div class="col-sm-6">
          <label class="form-label">'.s($lblFirst).'</label>
          <input type="text"
                 name="'.s($nameFirst).'"
                 id="'.s($idFirst).'"
                 class="form-control"
                 required
                 placeholder="'.s($phFirst).'"
                 value="'.s($dfirst).'">
        </div>';

    // Nom
    $html .= '
        <div class="col-sm-6">
          <label class="form-label">'.s($lblLast).'</label>
          <input type="text"
                 name="'.s($nameLast).'"
                 id="'.s($idLast).'"
                 class="form-control"
                 required
                 placeholder="'.s($phLast).'"
                 value="'.s($dlast).'">
        </div>';

    // Email
    $html .= '
        <div class="col-12">
          <label class="form-label">'.s($lblEmail).'</label>
          <input type="email"
                 name="'.s($nameEmail).'"
                 id="'.s($idEmail).'"
                 class="form-control"
                 required
                 placeholder="'.s($phEmail).'"
                 value="'.s($demail).'">
          '.$emailHintDiv.'
        </div>';

    // Téléphone
    $html .= '
        <div class="col-12">
          <label class="form-label">'.s($lblPhone).'</label>

          <div class="campus-phone-wrapper">
            <div class="campus-phone-country">
              '.$phoneSelect.'
            </div>
            <input type="tel"
                   name="'.s($namePhone).'"
                   id="'.s($idPhone).'"
                   class="form-control campus-phone-input"
                   placeholder="'.s($phPhone).'"
                   required>
          </div>

          <div class="form-text">'.$helpPhone.'</div>
        </div>';

    // Mot de passe
    $html .= '
        <div class="col-12">
          <label class="form-label">'.s($lblPass).'</label>

          <div class="campus-password-wrapper">
            <input
                type="text"
                name="'.s($namePass).'"
                id="'.s($idPass).'"
                class="form-control"
                minlength="8"
                autocomplete="new-password"
                required
                placeholder="'.s($phPass).'">

            <button class="campus-password-toggle password-toggle"
                    type="button"
                    data-target="#'.s($idPass).'"
                    data-show-label="'.s($toggleShow).'"
                    data-hide-label="'.s($toggleHide).'"
                    aria-label="'.s($toggleHide).'"
                    title="'.s($toggleHide).'">
              <span class="password-toggle-icon" aria-hidden="true">🙈</span>
            </button>
          </div>

          <div class="form-text">'.$helpPass.'</div>
        </div>';

    $html .= '</div>'; // .row g-2

    return $html;
}


