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

    // Supprimer des nœuds courants (selon thème/clé)
    foreach (['myhome','mycourses','calendar','privatefiles','badges','messages','grades','competencies','profile'] as $key) {
        if ($node = $nav->find($key, navigation_node::TYPE_ROOTNODE)) { $node->remove(); }
        if ($node = $nav->find($key, navigation_node::TYPE_CUSTOM))    { $node->remove(); }
        if ($node = $nav->find($key, navigation_node::TYPE_CONTAINER)) { $node->remove(); }
        if ($node = $nav->find($key, navigation_node::TYPE_SETTING))   { $node->remove(); }
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
        'trial_firstname','trial_lastname','trial_email'
    ], 'local_campus');

    // Liens CGU / Privacy (même logique que checkout.php)
    $policyurl = (new moodle_url('/local/subscriptions/privacy.php'))->out(false);
    if (class_exists('\local_subscriptions\support\Region')) {
        $urls = \local_subscriptions\support\Region::policyUrls(); // ['terms'=>..., 'policy'=>...]
        if (!empty($urls['policy'])) { $policyurl = (string)$urls['policy']; }
    }

    // Chaînes côté PHP
    $title     = get_string('trial_popup_title', 'local_campus');
    $lead      = get_string('trial_popup_lead',  'local_campus');
    $expired   = get_string('trial_expired_msg', 'local_campus');
    $lblFirst  = get_string('trial_firstname',   'local_campus');
    $lblLast   = get_string('trial_lastname',    'local_campus');
    $lblEmail  = get_string('trial_email',       'local_campus');
    // Label avec 2 liens (i18n avec placeholders)
    $tosHtml   = get_string('trial_tos_html', 'local_campus', $policyurl);
    $btnCancel = get_string('cancel');
    $btnCont   = get_string('trial_btn_continue','local_campus');
    $btnSub    = get_string('trial_btn_subscribe','local_campus');
    $btnClose  = get_string('close','local_campus');
    $sesskey   = sesskey();

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
        <p class="lead mb-3"><strong>'.s($lead).'</strong></p>

        <div id="campusTrialExpired" class="alert alert-warning d-none">'.$expired.'</div>

        <div id="campusTrialFormWrap">
          <div class="row g-2">
            <div class="col-sm-6">
              <label class="form-label">'.s($lblFirst).'</label>
              <input type="text" name="firstname" id="trialFirst" class="form-control" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">'.s($lblLast).'</label>
              <input type="text" name="lastname" id="trialLast" class="form-control" required>
            </div>
            <div class="col-12">
              <label class="form-label">'.s($lblEmail).'</label>
              <input type="email" name="email" id="trialEmail" class="form-control" required>
            </div>
          </div>

          <div class="form-check my-3">
            <input class="form-check-input" type="checkbox" id="trialAcceptTos" required>
            <label class="form-check-label" for="trialAcceptTos">'.$tosHtml.'</label>
          </div>

          <div id="campusTrialError" class="alert alert-danger d-none"></div>
        </div>
      </form>

      <div class="modal-footer">
        <a id="campusTrialSubscribe" href="/subscribe.php" class="default-btn d-none">
        '.s($btnSub).'
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.s($btnCancel).'</button>
        <button type="submit" form="campusTrialForm" id="campusTrialContinue" class="default-btn" disabled>
        '.s($btnCont).'
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  function onReady(fn){ if(document.readyState!=="loading"){ fn(); } else { document.addEventListener("DOMContentLoaded", fn); } }

  onReady(function(){
    var modalEl   = document.getElementById("campusTrialModal");
    if(!modalEl){ return; }

    var formEl    = document.getElementById("campusTrialForm");
    var errBox    = document.getElementById("campusTrialError");
    var redirEl   = document.getElementById("campusTrialRedirectId");
    var expiredEl = document.getElementById("campusTrialExpired");
    var formWrap  = document.getElementById("campusTrialFormWrap");
    var btnSub    = document.getElementById("campusTrialSubscribe");
    var btnCont   = document.getElementById("campusTrialContinue");

    var fFirst = document.getElementById("trialFirst");
    var fLast  = document.getElementById("trialLast");
    var fEmail = document.getElementById("trialEmail");
    var fTos   = document.getElementById("trialAcceptTos");

    // Bootstrap modal si dispo
    var bsModal = null;
    try{ if (window.bootstrap && window.bootstrap.Modal) { bsModal = new window.bootstrap.Modal(modalEl); } }catch(e){}

    function showModal(){
      if (bsModal) { bsModal.show(); return; }
      modalEl.classList.add("show");
      modalEl.style.display = "block";
      modalEl.removeAttribute("aria-hidden");
      modalEl.setAttribute("aria-modal","true");
      document.body.classList.add("modal-open");
      if (!document.querySelector(".modal-backdrop")) {
        var bd = document.createElement("div");
        bd.className = "modal-backdrop fade show";
        document.body.appendChild(bd);
      }
    }
    function hideModal(){
        // Déplacer le focus avant de cacher (évite le warning ARIA)
        if (document.activeElement && modalEl.contains(document.activeElement)) {
            document.activeElement.blur();
        }
        if (bsModal) { bsModal.hide(); return; }
        modalEl.classList.remove("show");
        modalEl.style.display = "none";
        modalEl.setAttribute("aria-hidden","true");
        modalEl.removeAttribute("aria-modal");
        document.body.classList.remove("modal-open");
        var bd = document.querySelector(".modal-backdrop"); if (bd) bd.remove();
    }
    // Délégation pour tous les éléments qui ferment la modale (croix & Annuler)
    document.addEventListener("click", function(e){
        var closer = e.target.closest(\'[data-bs-dismiss="modal"]\');
        if (closer && modalEl.contains(closer)) { e.preventDefault(); hideModal(); }
    });

    // Validation dynamique : bouton "Continuer" activé uniquement si tout est OK
    function valid(){
      if (!formEl) return false;
      var ok = true;
      if (!fFirst.value.trim()) ok = false;
      if (!fLast.value.trim())  ok = false;
      if (!fEmail.value.trim()) ok = false;
      if (fEmail.validity && !fEmail.validity.valid) ok = false;
      if (fTos && !fTos.checked) ok = false;
      btnCont.disabled = !ok;
      return ok;
    }
    ["input","change","keyup"].forEach(function(ev){
      [fFirst,fLast,fEmail,fTos].forEach(function(el){ if(el){ el.addEventListener(ev, valid); } });
    });

    window.campusTrialOpen = function(redirectid){
      if (redirEl)   redirEl.value = redirectid || "";
      if (expiredEl) expiredEl.classList.add("d-none");
      if (formWrap)  formWrap.classList.remove("d-none");
      if (btnSub)    btnSub.classList.add("d-none");
      if (errBox){ errBox.classList.add("d-none"); errBox.textContent=""; }
      if (btnSub)  btnSub.classList.add("d-none");    // par défaut on ne montre pas “S’abonner”
      if (btnCont) btnCont.classList.remove("d-none"); // on garde “Continuer”
      btnCont.disabled = true; // réinit validation

      showModal();
    };

    function check(redirectid){
      var url = M.cfg.wwwroot + "/local/campus/trial_check.php?redirectid=" + encodeURIComponent(redirectid);
      fetch(url, {credentials:"same-origin", headers:{"X-Requested-With":"fetch"}})
        .then(function(res){ return res.json(); })
        .then(function(j){
          if (j.status === "ok" && j.redirect) { window.location.href = j.redirect; return; }
          if (j.status === "expired") {
            if (redirEl)   redirEl.value = redirectid;
            if (expiredEl) expiredEl.classList.remove("d-none");
            if (formWrap)  formWrap.classList.add("d-none");
            if (btnSub)    btnSub.classList.remove("d-none");
            document.cookie = "campus_trial=; Max-Age=0; path=/";
            btnCont.disabled = true;
            if (btnCont) btnCont.classList.add("d-none");   // ← cache le bouton Continuer
            if (btnSub)  btnSub.classList.remove("d-none"); // ← affiche S’abonner
            showModal();
            return;
          }
          campusTrialOpen(redirectid); // needs_form
        })
        .catch(function(){ campusTrialOpen(redirectid); });
    }
    window.campusTrialCheck = check;

    document.addEventListener("click", function(e){
      var a = e.target.closest("[data-campus-trial-redirect]");
      if (!a) return;
      e.preventDefault();
      check(a.getAttribute("data-campus-trial-redirect"));
    });

    if (formEl){
      formEl.addEventListener("submit", function(e){
        e.preventDefault();
        if (!valid()) return;
        if (errBox){ errBox.classList.add("d-none"); errBox.textContent=""; }

        var fd = new FormData(formEl);
        fetch(M.cfg.wwwroot + "/local/campus/trial_gate.php", {
          method: "POST", body: fd, credentials: "same-origin",
          headers: {"X-Requested-With":"fetch"}
        })
        .then(function(res){ return res.json(); })
        .then(function(j){
          if (j.status === "ok" && j.redirect) { window.location.href = j.redirect; return; }
          if (j.status === "expired") {
            if (expiredEl) expiredEl.classList.remove("d-none");
            if (formWrap)  formWrap.classList.add("d-none");
            if (btnSub)    btnSub.classList.remove("d-none");
            document.cookie = "campus_trial=; Max-Age=0; path=/";
            btnCont.disabled = true;
            if (btnCont) btnCont.classList.add("d-none");
            if (btnSub)  btnSub.classList.remove("d-none");
            showModal();
            return;
          }
          throw new Error(j && j.message ? j.message : "Error");
        })
        .catch(function(err){
          if (errBox){
            errBox.textContent = (err && err.message) ? err.message : String(err);
            errBox.classList.remove("d-none");
          }
        });
      });
    }

    document.addEventListener("click", function(e){
      if (e.target.matches("#campusTrialModal .btn-close")) { hideModal(); }
    });
  });
})();
</script>';
}


