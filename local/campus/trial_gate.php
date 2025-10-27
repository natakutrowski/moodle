<?php
define('AJAX_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/local/subscriptions/classes/mailer.php');

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
    $expires = $now + ($days * DAYSECS);

    $trial = $DB->get_record('local_campus_trial', ['email'=>$email]);

    /* ===========================
       1) ESSAI ACTIF (RECONNEXION)
       =========================== */
    if ($trial && (int)$trial->expiresat >= $now) {
        $user = null;
        if (!empty($trial->userid)) {
            $user = $DB->get_record('user', ['id'=>$trial->userid], '*', IGNORE_MISSING);
            if ($user && (int)$user->deleted) {
                $user = null; // considéré manquant
            } else if ($user && (int)$user->suspended) {
                // essai actif → on réactive
                $user->suspended = 0;
                user_update_user($user, false);
                $user = $DB->get_record('user', ['id'=>$trial->userid], '*', MUST_EXIST);
            }
        }

        if (!$user) {
            // Recréation du compte d’essai + réinscription
            $realemail = $email;

            $unamepref = (string)get_config('local_campus','trialusernameprefix') ?: 'trial_';
            $epref     = (string)get_config('local_campus','trialemailprefix')   ?: 'trial+';
            $forced    = trim((string)get_config('local_campus','trialemaildomain') ?: '');

            $baseuname = $unamepref . preg_replace('~[^a-z0-9._-]+~', '', core_text::strtolower(core_user::clean_field($realemail,'username')));
            if ($baseuname === $unamepref) { $baseuname .= 'user'; }
            $username = $baseuname; $i=1;
            while ($DB->record_exists('user', ['username'=>$username])) { $username = $baseuname.$i; $i++; }

            [$local, $domain] = array_pad(explode('@', $realemail, 2), 2, '');
            if ($forced !== '') { $domain = $forced; }
            if ($domain === '') { $domain = 'invalid'; }
            $pseudoemail = $epref . substr(sha1($realemail), 0, 10) . '@' . $domain;

            $nu = (object)[
                'username'   => $username,
                'email'      => $pseudoemail,
                'firstname'  => $firstname,
                'lastname'   => $lastname,
                'confirmed'  => 1,
                'auth'       => 'nologin',
                'password'   => random_string(16),
                'mnethostid' => $CFG->mnet_localhost_id,
                'suspended'  => 0,
            ];
            $newid = user_create_user($nu, false, false);

            $sysctx = \context_system::instance();
            $triallimited = $DB->get_record('role', ['shortname'=>'triallimited'], 'id', IGNORE_MISSING);
            if ($triallimited && !user_has_role_assignment($newid, $triallimited->id, $sysctx->id)) {
                role_assign($triallimited->id, $newid, $sysctx->id);
            }

            $user  = $DB->get_record('user', ['id'=>$newid], '*', MUST_EXIST);

            // Met à jour le lien dans la table trial
            $DB->set_field('local_campus_trial', 'userid', $user->id, ['id'=>$trial->id]);

            // Réinscription à TOUS les cours d’essai
            $manual = enrol_get_plugin('manual');
            $role   = $DB->get_record('role', ['shortname'=>(string)get_config('local_campus','trialrole') ?: 'trialstudent'], '*', IGNORE_MISSING);
            $roleid = $role ? (int)$role->id : 5;
            foreach ($trialids as $cid) {
                $inst = local_campus_ensure_manual_instance($cid);
                if (!$DB->record_exists('user_enrolments', ['enrolid'=>$inst->id, 'userid'=>$user->id])) {
                    $manual->enrol_user($inst, $user->id, $roleid, $now, 0);
                }
            }
        }

        // Login + cookie + redirect
        try { complete_user_login($user); }
        catch (\Throwable $e) { \core\session\manager::set_user($user); }
        local_campus_set_cookie((int)$trial->id, (int)$trial->expiresat);

        $url = (new moodle_url('/course/view.php', ['id'=>$redirectid]))->out(false);
        echo json_encode(['status'=>'ok','redirect'=>$url]); exit;
    }

    /* ============================
       2) ESSAI EXPIRÉ (PAS DE RÉOUVERTURE)
       ============================ */
    if ($trial && (int)$trial->expiresat < $now) {
        local_campus_clear_cookie();
        echo json_encode([
            'status'    => 'expired',
            'message'   => get_string('trial_expired_msg','local_campus'),
            'subscribe' => (new moodle_url('/subscribe.php'))->out(false)
        ]); exit;
    }

    /* ============================
       3) PREMIÈRE FOIS (AUCUNE LIGNE TRIAL)
       ============================ */
    $realemail = $email;

    $unamepref = (string)get_config('local_campus','trialusernameprefix') ?: 'trial_';
    $epref     = (string)get_config('local_campus','trialemailprefix')   ?: 'trial+';
    $forced    = trim((string)get_config('local_campus','trialemaildomain') ?: '');

    // username unique
    $baseuname = $unamepref . preg_replace('~[^a-z0-9._-]+~', '', core_text::strtolower(core_user::clean_field($realemail,'username')));
    if ($baseuname === $unamepref) { $baseuname .= 'user'; }
    $username = $baseuname; $i=1;
    while ($DB->record_exists('user',['username'=>$username])) { $username = $baseuname.$i; $i++; }

    // email pseudo non-collision
    [$local, $domain] = array_pad(explode('@', $realemail, 2), 2, '');
    if ($forced !== '') { $domain = $forced; }
    if ($domain === '') { $domain = 'invalid'; }
    $pseudoemail = $epref . substr(sha1($realemail), 0, 10) . '@' . $domain;


    $defaultuserlang = get_config('local_subscriptions', 'defaultuserlang'); // '' = hériter du site

    // 1) Crée le compte d’essai (non suspendu)
    $nu = (object)[
        'username'   => $username,
        'email'      => $pseudoemail,
        'firstname'  => $firstname,
        'lastname'   => $lastname,
        'confirmed'  => 1,
        'auth'       => 'nologin',
        'password'   => random_string(16),
        'mnethostid' => $CFG->mnet_localhost_id,
        'suspended'  => 0,
        'lang'       => !empty($CFG->lang) ? $CFG->lang : current_language(), // set to default language of the site
    ];

    // Hériter de la langue du site si réglage vide, sinon forcer.
    if (!empty($defaultuserlang)) {
        $nu->lang = strtolower($defaultuserlang);
    }


    $userid = user_create_user($nu, false, false);

    $sysctx = \context_system::instance();
    $triallimited = $DB->get_record('role', ['shortname'=>'triallimited'], 'id', IGNORE_MISSING);
    if ($triallimited && !user_has_role_assignment($userid, $triallimited->id, $sysctx->id)) {
        role_assign($triallimited->id, $userid, $sysctx->id);
    }

    $user   = $DB->get_record('user',['id'=>$userid],'*',MUST_EXIST);

    // 2) Inscrire à TOUS les cours d’essai
    $manual = enrol_get_plugin('manual');
    $role   = $DB->get_record('role', ['shortname'=>(string)get_config('local_campus','trialrole') ?: 'trialstudent'], '*', IGNORE_MISSING);
    $roleid = $role ? (int)$role->id : 5;

    foreach ($trialids as $cid) {
        $inst = local_campus_ensure_manual_instance($cid);
        if (!$DB->record_exists('user_enrolments', ['enrolid'=>$inst->id, 'userid'=>$userid])) {
            $manual->enrol_user($inst, $userid, $roleid, $now, 0);
        }
    }

    // 3) Insérer la ligne d’essai
    $ip  = getremoteaddr();
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $trialid = $DB->insert_record('local_campus_trial', (object)[
        'email'      => $realemail,
        'firstname'  => $firstname,
        'lastname'   => $lastname,
        'userid'     => $userid,
        'timecreated'=> $now,
        'expiresat'  => $expires,
        'status'     => 1,
        'ipaddress'  => $ip,
        'useragent'  => $ua
    ]);

    // 4) Cookie
    local_campus_set_cookie((int)$trialid, (int)$expires);

    // 5) Login “safe”
    try { complete_user_login($user); }
    catch (\Throwable $e) { \core\session\manager::set_user($user); }

    // 6) Mail “trial démarré”
    $langpref = get_config('local_subscriptions', 'defaultemaillang') ?: 'ru';

    \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_STARTED, [
        'toemail'      => $realemail,
        'firstname'    => $firstname ?? '',
        'subscribe_url'=> (new moodle_url('/subscribe.php'))->out(false),
        'lang'         => strtolower($langpref),
    ]);

    // 7) Redirect JSON
    $url = (new moodle_url('/course/view.php', ['id'=>$redirectid]))->out(false);
    echo json_encode(['status'=>'ok','redirect'=>$url]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
