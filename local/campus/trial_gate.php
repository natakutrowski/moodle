<?php
define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/local/subscriptions/classes/mailer.php');
require_once($CFG->dirroot.'/local/subscriptions/lib/user_subs_lib.php'); // ← pour local_subscriptions_generate_unique_username()


global $PAGE;
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url($_SERVER['PHP_SELF']));

header('Content-Type: application/json; charset=utf-8');

/**
 * Retourne l'instance enrol 'manual' d'un cours, en la créant si besoin.
 */
function local_campus_ensure_manual_instance(int $courseid): stdClass {
    global $DB, $CFG;
    require_once($CFG->libdir.'/enrollib.php');

    $instances = enrol_get_instances($courseid, true);
    foreach ($instances as $inst) {
        if ($inst->enrol === 'manual') {
            return $inst;
        }
    }
    $plugin = enrol_get_plugin('manual');
    if (!$plugin) {
        throw new moodle_exception('enrol_plugin_manual_missing', 'error');
    }
    $course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);
    $plugin->add_instance($course, ['status'=>ENROL_INSTANCE_ENABLED]);

    // re-fetch
    $instances = enrol_get_instances($courseid, true);
    foreach ($instances as $inst) {
        if ($inst->enrol === 'manual') {
            return $inst;
        }
    }
    throw new moodle_exception('cannot_create_manual_instance', 'error');
}


try {
    require_sesskey();

    $redirectid = required_param('redirectid', PARAM_INT);
    $firstname  = required_param('firstname', PARAM_TEXT);
    $lastname   = required_param('lastname', PARAM_TEXT);
    $email      = required_param('email', PARAM_RAW_TRIMMED);

    $password   = required_param('password', PARAM_RAW);

    $phonecountry = optional_param('phonecountry', '', PARAM_RAW_TRIMMED);
    $phonenumber  = optional_param('phonenumber', '', PARAM_RAW_TRIMMED);

    // Nettoyage basique du numéro de téléphone
    $rawphone = trim($phonenumber ?? '');
    $rawphone = preg_replace('/\s+/', '', $rawphone);

    $phonefull = '';
    if ($rawphone !== '') {
        if (strpos($rawphone, '+') === 0) {
            // L'utilisateur a déjà mis un indicatif
            $phonefull = $rawphone;
        } else if (!empty($phonecountry)) {
            $phonefull = $phonecountry . $rawphone;
        } else {
            $phonefull = $rawphone;
        }
    }

    // Deviner le code pays ISO2 à partir de l'indicatif (ex: "+33" -> "FR")
    $phoneiso = null;
    if (!empty($phonecountry) && function_exists('local_campus_iso_from_phonecode')) {
        $phoneiso = local_campus_iso_from_phonecode($phonecountry);
    }


    if (core_text::strlen($password) < 8) {
        throw new moodle_exception('trial_password_min', 'local_campus');
    }

    require_once($CFG->libdir.'/moodlelib.php');
    // Signature correcte : check_password_policy($password, $user = null, &$errmsg)
    $errmsg = null;
    if (!empty($CFG->passwordpolicy) && function_exists('check_password_policy')) {
        if (!check_password_policy($password, $errmsg, null)) {
            throw new moodle_exception('trial_password_policy_error', 'local_campus', '', (string)(html_to_text($errmsg) ?? ''));
        }
    }

    if (!validate_email($email)) {
        throw new moodle_exception('invalidemail');
    }

    $trialids = local_campus_trial_course_ids();
    if (!$trialids || !in_array($redirectid, $trialids, true)) {
        throw new moodle_exception('invalidcourseid');
    }

    $email = core_text::strtolower($email);
    $now   = time();
    $days  = (int)get_config('local_campus','trialdays'); // -1=jamais, 0=immédiat, >0 = jours
    if ($days < 0) { $days = 7; } // fallback
    if ($days == 0) { 
        $expires = $now + (100 * 365.25 * DAYSECS);
    } else {
        $expires = $now + ($days * DAYSECS);
    }

    $trial = $DB->get_record('local_campus_trial', ['email'=>$email]);


    // --- Si l'email a déjà une souscription ACTIVE non-essai -> proposer la connexion ---
    $uid = $DB->get_field('user', 'id', ['email' => $email, 'deleted' => 0], IGNORE_MISSING);
    if ($uid) {
        $trialplanid = (int)(get_config('local_subscriptions','trial_plan_id') ?? 0);

        // On construit la clause dynamiquement pour éviter les placeholders dupliqués
        $sql = "SELECT 1
                FROM {user_subscription}
                WHERE userid = :u
                AND status = :active
                AND end_date > :now";
        $params = [
            'u'      => (int)$uid,
            'active' => \local_subscriptions\constants\Status::ACTIVE,
            'now'    => time(),
        ];
        if ($trialplanid > 0) {
            $sql .= " AND planid <> :trialplanid";
            $params['trialplanid'] = $trialplanid;
        }

        $haspaid = $DB->record_exists_sql($sql, $params);

        if ($haspaid) {
            $loginurl = (new moodle_url('/login/index.php', [
                'returnurl' => (new moodle_url('/course/view.php', ['id'=>$redirectid]))->out(false)
            ]))->out(false);

            echo json_encode([
                'status'  => 'already_subscribed',
                'message' => get_string('trial_already_subscribed', 'local_campus'),
                'login'   => $loginurl
            ]);
            exit;
        }
    }



    /* ===========================
       1) ESSAI ACTIF (RECONNEXION)
       =========================== */
    if ($trial && (int)$trial->expiresat >= $now) {
        $realemail = $email;

        // 1) On essaie d'abord de retrouver l'utilisateur lié au trial,
        //    sinon on retombe sur l'email. Mais on NE TOUCHE PAS au mot de passe.
        $user = null;

        if (!empty($trial->userid)) {
            $user = $DB->get_record('user', ['id' => $trial->userid, 'deleted' => 0], '*', IGNORE_MISSING);
        }
        if (!$user) {
            $user = $DB->get_record('user', ['email' => $realemail, 'deleted' => 0], '*', IGNORE_MISSING);
        }

        if ($user) {
            // An active Trial keeps its existing password, but any provisional
            // Guest Checkout identity must still be finalised before login.
            $user = local_campus_finalise_trial_account(
                $user,
                $firstname,
                $lastname,
                $realemail,
                null,
                $phonefull,
                $phoneiso
            );
        } else {
            // Rare recovery path: recreate the account with the credentials
            // explicitly chosen in the Trial form.
            $username = local_subscriptions_generate_unique_username(
                $firstname ?? '',
                $lastname ?? '',
                $realemail
            );
            $user = create_user_record($username, $password, 'manual');
            $user = local_campus_finalise_trial_account(
                $user,
                $firstname,
                $lastname,
                $realemail,
                $password,
                $phonefull,
                $phoneiso
            );
        }

        // 2) Garantir l'essai via trial_manager (idempotent)
        require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');
        if (!\local_subscriptions\trial_manager::user_has_active_trial((int)$user->id)) {
            \local_subscriptions\trial_manager::start_trial((int)$user->id);
        }

        // 3) Mettre à jour la ligne local_campus_trial pour lier au bon user
        if ((int)$trial->userid !== (int)$user->id) {
            $DB->set_field('local_campus_trial', 'userid', $user->id, ['id' => $trial->id]);
        }

        // 4) Login + cookie + redirect vers Mes cours d'essai
        try {
            complete_user_login($user);
        } catch (\Throwable $e) {
            \core\session\manager::set_user($user);
        }
        local_campus_set_cookie((int)$trial->id, (int)$trial->expiresat);

        $mycoursesurl = (new moodle_url('/local/campus/mycourses.php'))->out(false);
        echo json_encode(['status' => 'ok', 'redirect' => $mycoursesurl]);
        exit;
    }



    /* ============================
       2) ESSAI EXPIRÉ (PAS DE RÉOUVERTURE)
       ============================ */
    if ($trial && (int)$trial->expiresat < $now) {
        local_campus_clear_cookie();
        echo json_encode([
            'status'    => 'expired',
            'message'   => get_string('trial_expired_msg','local_campus'),
            'subscribe' => (new moodle_url('/boutique'))->out(false)
        ]); exit;
    }

    /* ============================
       3) PREMIÈRE FOIS (AUCUNE LIGNE TRIAL)
       ============================ */
    $realemail = $email;

    // a) Crée (ou convertit) un user manual sur l'email réel
    $existing = $DB->get_record(
        'user',
        ['email' => $realemail, 'deleted' => 0],
        '*',
        IGNORE_MISSING
    );

    if ($existing) {
        // Important: this may be an abandoned provisional Guest Checkout
        // account (username checkout_* + forced password reset).
        $user = local_campus_finalise_trial_account(
            $existing,
            $firstname,
            $lastname,
            $realemail,
            $password,
            $phonefull,
            $phoneiso
        );
    } else {
        $username = local_subscriptions_generate_unique_username(
            $firstname ?? '',
            $lastname ?? '',
            $realemail
        );
        $user = create_user_record($username, $password, 'manual');
        $user = local_campus_finalise_trial_account(
            $user,
            $firstname,
            $lastname,
            $realemail,
            $password,
            $phonefull,
            $phoneiso
        );
    }

    // b) Démarre l'essai via trial_manager (inscriptions auto)
    require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');
    \local_subscriptions\trial_manager::start_trial((int)$user->id);

    // c) Insère (ou met à jour) la ligne d’essai locale + cookie (UX)
    $trialid = 0;
    $prev = $DB->get_record('local_campus_trial', ['email'=>$realemail], '*', IGNORE_MISSING);
    if ($prev) {
        $prev->userid     = $user->id;
        $prev->timecreated= $now;
        $prev->expiresat  = $expires;
        $prev->status     = 1;
        $DB->update_record('local_campus_trial', $prev);
        $trialid = (int)$prev->id;
    } else {
        $trialid = (int)$DB->insert_record('local_campus_trial', (object)[
            'email'      => $realemail,
            'firstname'  => $firstname,
            'lastname'   => $lastname,
            'userid'     => $user->id,
            'timecreated'=> $now,
            'expiresat'  => $expires,
            'status'     => 1,
            'ipaddress'  => getremoteaddr(),
            'useragent'  => ($_SERVER['HTTP_USER_AGENT'] ?? '')
        ]);
    }
    local_campus_set_cookie($trialid, (int)$expires);

    // d) Login immédiat
    try { complete_user_login($user); } catch (\Throwable $e) { \core\session\manager::set_user($user); }

    // e) Mail de bienvenue (avec identifiants)
    $loginurl     = (new moodle_url('/login/index.php'))->out(false);
    $changepwurl  = (new moodle_url('/login/change_password.php'))->out(false);
    $subscribeurl = (new moodle_url('/boutique'))->out(false);
    $mycoursesurl = (new moodle_url('/local/campus/mycourses.php'))->out(false);
    $reseturl     = (new moodle_url('/login/forgot_password.php'))->out(false);

    \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_STARTED, [
        'toemail'             => $realemail,
        'firstname'           => $firstname ?? '',
        'email'               => $realemail,
        'username'            => (string)$user->username, // si jamais tu en as besoin ailleurs
        'phone'               => $phonefull,
        'login_url'           => $loginurl,
        'change_password_url' => $changepwurl,
        'reset_url'           => $reseturl,
        'subscribe_url'       => $subscribeurl,
        'mycourses_url'       => $mycoursesurl,
    ]);

    // e bis) Marquer un message de bienvenue à afficher sur la page Mes cours
    set_user_preference('local_campus_trial_welcome_pending', 1, $user);


    // f) Redirect
    $mycoursesurl = (new moodle_url('/local/campus/mycourses.php'))->out(false);
    echo json_encode(['status' => 'ok', 'redirect' => $mycoursesurl]);
    exit;

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
