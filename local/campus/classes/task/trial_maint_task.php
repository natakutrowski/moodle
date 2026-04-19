<?php
namespace local_campus\task;

defined('MOODLE_INTERNAL') || die();

class trial_maint_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('cron_trial_maint','local_campus');
    }

    public function execute() {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');
        require_once($CFG->dirroot.'/enrol/manual/lib.php');
        // ✅ chemin corrigé : la classe mailer est dans classes/
        require_once($CFG->dirroot.'/local/subscriptions/classes/mailer.php');
        require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');
        require_once($CFG->dirroot.'/user/lib.php');

        $now = time();

        // Langue préférée pour les mails (optionnelle)
        $langpref = get_config('local_subscriptions', 'defaultemaillang') ?: 'ru';
        $langpref = strtolower($langpref);

        // Paramètres généraux
        $trialdays    = (int) get_config('local_campus', 'trialdays') ?: 7;  // durée d'essai
        $suspendAfter = (int) (get_config('local_campus','trial_suspend_after')
                               ?? get_config('local_campus','trial_suspend_after_days'));
        if ($suspendAfter < 0) {
            $suspendAfter = 30; // J+30 => suspension
        } elseif ($suspendAfter == 0) {
            $suspendAfter = 100 * 365.25; // never
        }

        // Suppression J + deleteAfter : par défaut 31 jours après expiration
        $deleteAfterCfg = get_config('local_campus', 'trial_delete_after_days');
        if ($deleteAfterCfg) {
            $deleteAfter = 100 * 365.25; // never
        } else {
            if ($deleteAfterCfg === '' || $deleteAfterCfg === null) {
                // fallback ancien paramètre, sinon 31
                $legacy = get_config('local_campus', 'deleteafterdays');
                $deleteAfterCfg = ($legacy !== '' && $legacy !== null) ? $legacy : 31;
            }
            $deleteAfter = (int)$deleteAfterCfg;   // -1 = jamais supprimer
        }

        // Tous les essais connus
        $trials = $DB->get_records('local_campus_trial', null, '', '*');
        if (!$trials) {
            return true;
        }

        // Cours d’essai (désinscriptions à J)
        $trialCourseIds = self::trial_courses();
        $firstTrialId   = !empty($trialCourseIds) ? (int)reset($trialCourseIds) : 0;
        $coursefullname = '';
        if ($firstTrialId) {
            $one = $DB->get_record('course', ['id'=>$firstTrialId], 'fullname', IGNORE_MISSING);
            $coursefullname = $one->fullname ?? '';
        }

        // ID plan d’essai (pour tests "a-t-il un abo payant actif ?")
        $trialPlanId = (int) (get_config('local_subscriptions', 'trial_plan_id') ?? 0);
        
        foreach ($trials as $t) {
            $expiresAt      = (int)$t->expiresat;
            $ageSinceExpire = $now - $expiresAt;              // >0 si expiré
            $isExpired      = ($expiresAt > 0 && $now >= $expiresAt);


            // Sécurité : si l'utilisateur a au moins UNE subscription non-trial (quel que soit le statut),
            // on considère que le trial est "converti" et on ne lui envoie plus de mails / suspensions.
            $hasNonTrialSub = false;
            if (!empty($t->userid)) {
                $hasNonTrialSub = $DB->record_exists_sql("
                    SELECT 1
                    FROM {user_subscription} s
                    JOIN {subscription_plan} p ON p.id = s.planid
                    WHERE s.userid = :uid
                    AND (p.is_trial IS NULL OR p.is_trial = 0)
                ", ['uid' => (int)$t->userid]);
            }

            if ($hasNonTrialSub) {
                // On marque éventuellement ce trial comme "converti" (status = 3) et on passe au suivant.
                if ((int)$t->status !== 3) {
                    $t->status = 3;
                    $DB->update_record('local_campus_trial', $t);
                }
                continue; // ⬅️ rien d'autre pour ce trial : pas de mails, pas de suspension, pas de suppression.
            }

            // 🔎 Nouveau : ne traiter QUE les utilisateurs qui ont une subscription trial dans user_subscription
            $hasTrialSub = false;
            if (!empty($t->userid) && $trialPlanId > 0) {
                $hasTrialSub = $DB->record_exists_sql("
                    SELECT 1
                    FROM {user_subscription} s
                    WHERE s.userid = :uid
                    AND s.planid = :trialplanid
                ", [
                    'uid'         => (int)$t->userid,
                    'trialplanid' => $trialPlanId,
                ]);
            }

            // Si l'utilisateur n'a PAS de souscription trial dans le nouveau système,
            // on considère que cette ligne local_campus_trial est "legacy" → on la laisse tranquille.
            if (!$hasTrialSub) {
                // Optionnel : tu peux logguer pour vérifier
                // mtrace(\"trial_maint_task: skip trial id={$t->id} for userid={$t->userid} (no trial subscription)\");
                continue;
            }


            // ====== 0) Mail "fin de fenêtre remise" (basé sur une durée configurable) ======
            if (empty($t->reminder3_sent) && !$isExpired) {

                // Nombre de jours avant l'email de remise — config, sinon défaut = 2 jours
                $remdays = (int)get_config('local_campus', 'trial_discount_reminder_days');
                if ($remdays == 0) {
                    $remdays = 100 * 365.25; // never
                } elseif ($remdays <= 0) {
                    $remdays = 2; // défaut si non configuré
                }

                // Seuil "J + remdays"
                $threshold = (int)$t->timecreated + $remdays * DAYSECS;

                // Si la fenêtre de remise a une vraie deadline, on NE DÉPASSE PAS cette deadline
                if (!empty($t->userid)) {
                    $deadline = \local_subscriptions\trial_manager::discount_window_deadline((int)$t->userid);
                    if (!empty($deadline) && (int)$deadline < $threshold) {
                        $threshold = (int)$deadline;
                    }
                }

                if ($now >= $threshold && $now < $expiresAt) {
                    $continueurl = $firstTrialId
                        ? (new \moodle_url('/local/campus/course.php', ['trial'=>$firstTrialId, 'checktrial'=>1]))->out(false)
                        : (new \moodle_url('/'))->out(false);

                    \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_REM3, [
                        'toemail'         => (string)$t->email,
                        'firstname'       => (string)($t->firstname ?? ''),
                        'continue_url'    => $continueurl,
                        'subscribe_url'   => (new \moodle_url('/local/subscriptions/subscribe.php'))->out(false),
                        'course_fullname' => $coursefullname,
                        'daysleft'        => max(0, (int)ceil(($expiresAt - $now) / DAYSECS)),
                        'lang'            => $langpref,
                    ]);

                    $t->reminder3_sent = $now;
                    $DB->update_record('local_campus_trial', $t);
                }
            }


            // ====== 1) Passage à EXPIRÉ à J (et mail "fin d’essai") ======
            if ($isExpired && (int)$t->status !== 2) {
                $t->status = 2; // Expiré
                $DB->update_record('local_campus_trial', $t);

                // 🧼 Supprimer le cookie d’essai (UX)
                if (function_exists('local_campus_clear_cookie')) {
                    local_campus_clear_cookie();
                }

                // Désinscrire des cours d’essai (NE PAS suspendre ici)
                if (!empty($t->userid) && !empty($trialCourseIds) && enrol_is_enabled('manual')) {
                    self::unenrol_from_trial_courses((int)$t->userid, $trialCourseIds);
                }

                // Mail "fin d’essai" (avec info “compte actif jusqu’à J+suspendAfter”)
                $suspendTs = $expiresAt + $suspendAfter * DAYSECS;
                \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_EXPIRED, [
                    'toemail'         => (string)$t->email,
                    'firstname'       => (string)($t->firstname ?? ''),
                    'subscribe_url'   => (new \moodle_url('/local/subscriptions/subscribe.php'))->out(false),
                    'course_fullname' => $coursefullname,
                    'suspend_date'    => $suspendTs,
                    'lang'            => $langpref,
                ]);

                // Tracer “rappel J” si tu veux (ex. reminder7_sent = J)
                if (empty($t->reminder7_sent)) {
                    $t->reminder7_sent = $now;
                    $DB->update_record('local_campus_trial', $t);
                }
            }

            // ====== 2) Pré-suspension (J + suspendAfter − 2 jours) ======
            $pre = max(1, $suspendAfter - 2);
            if ($isExpired
                && empty($t->reminder_presuspend_sent)
                && $now >= ($expiresAt + $pre * DAYSECS)
                && $now <  ($expiresAt + $suspendAfter * DAYSECS)) {

                \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_PRE_SUSPEND, [
                    'toemail'       => (string)$t->email,
                    'firstname'     => (string)($t->firstname ?? ''),
                    'suspend_date'  => (int)$expiresAt + $suspendAfter*DAYSECS,
                    'subscribe_url' => (new \moodle_url('/local/subscriptions/subscribe.php'))->out(false),
                    'lang'          => $langpref,
                ]);

                $t->reminder_presuspend_sent = $now;
                $DB->update_record('local_campus_trial', $t);
            }

            // ====== 3) Suspension (J + suspendAfter) + mail "compte suspendu" ======
            if ($isExpired
                && empty($t->reminder_suspend_sent)
                && $now >= ($expiresAt + $suspendAfter * DAYSECS)) {

                // Suspendre si aucun abonnement payant ACTIF (hors plan d’essai)
                $hasPaid = (!empty($t->userid)) ? $DB->record_exists_sql("
                    SELECT 1 FROM {user_subscription}
                    WHERE userid = :uid
                      AND status = '".\local_subscriptions\constants\Status::ACTIVE."'
                      AND end_date > :now
                      ".($trialPlanId ? "AND planid <> :tpid" : ''),
                    ['uid'=>(int)$t->userid, 'now'=>$now, 'tpid'=>$trialPlanId]
                ) : false;

                if (!$hasPaid && !empty($t->userid)) {
                    $u = $DB->get_record('user', ['id'=>$t->userid, 'deleted'=>0], '*', IGNORE_MISSING);
                    if ($u && empty($u->suspended)) {
                        $u->suspended = 1;
                        user_update_user($u, false);

                        // 🔒 On coupe toutes les sessions actives de cet utilisateur
                        \core\session\manager::destroy_user_sessions($u->id);
                    }
                }

                // Mail “compte suspendu” + info suppression J+deleteAfter
                \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_SUSPENDED, [
                    'toemail'       => (string)$t->email,
                    'firstname'     => (string)($t->firstname ?? ''),
                    'suspend_date'  => (int)$expiresAt + $suspendAfter*DAYSECS,
                    'delete_date'   => (int)$expiresAt + $deleteAfter*DAYSECS,
                    'subscribe_url' => (new \moodle_url('/local/subscriptions/subscribe.php'))->out(false),
                    'lang'          => $langpref,
                ]);

                $t->reminder_suspend_sent = $now;
                $DB->update_record('local_campus_trial', $t);
            }

            // ====== 4) Suppression J + deleteAfter (si jamais d’abo payant) ======
            if ($deleteAfter >= 0 && $isExpired && $ageSinceExpire >= $deleteAfter * DAYSECS) {
                $deletedUser = false;

                if (!empty($t->userid)) {
                    $hasPaid = $DB->record_exists_sql("
                        SELECT 1 FROM {user_subscription}
                        WHERE userid = :uid
                          AND status = '".\local_subscriptions\constants\Status::ACTIVE."'
                          AND end_date > :now
                          ".($trialPlanId ? "AND planid <> :tpid" : ''),
                        ['uid' => (int)$t->userid, 'now'=>$now, 'tpid'=>$trialPlanId]
                    );

                    if (!$hasPaid) {
                        $deletedUser = $this->safe_delete_user_by_id((int)$t->userid);
                    }
                }

                // On supprime l’entrée dans local_campus_trial pour permettre un nouveau trial
                // quand on supprime le compte, ou si aucun userid n’était associé.
                if ($deletedUser || empty($t->userid)) {
                    $DB->delete_records('local_campus_trial', ['id' => $t->id]);
                    mtrace("trial_maint_task: deleted trial row id={$t->id}");
                }
            }
        }

        return true;
    }

    private static function trial_courses(): array {
        global $DB;
        $csv = (string)get_config('local_campus','trialcourses');
        $ids = array_filter(array_map('intval', preg_split('~[,\s]+~', $csv)), fn($v)=>$v>0);
        // Filtrer sur des cours existants
        if (!$ids) {
            return [];
        }
        list($in, $p) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        return array_keys($DB->get_records_select('course', "id $in", $p, '', 'id'));
    }

    private static function unenrol_from_trial_courses(int $userid, array $courseids): void {
        if (!$courseids) {
            return;
        }
        foreach ($courseids as $cid) {
            $instances = enrol_get_instances($cid, true);
            foreach ($instances as $inst) {
                if ($inst->enrol === 'manual') {
                    $plugin = enrol_get_plugin('manual');
                    if ($plugin) {
                        $plugin->unenrol_user($inst, $userid);
                    }
                }
            }
        }
    }

    /**
     * Supprime l'utilisateur en toute sécurité.
     * Retourne true si l'utilisateur a été réellement supprimé, false sinon.
     */
    private function safe_delete_user_by_id(int $userid): bool {
        // Récupérer l'utilisateur; ignorer s'il n'existe pas.
        $user = \core_user::get_user($userid, '*', IGNORE_MISSING);
        if (!$user) {
            mtrace("trial_maint_task: skip delete user={$userid} (not found)");
            return false;
        }

        // Ne jamais toucher aux comptes sensibles.
        if (in_array((int)$user->id, [1, 2])) { // 1 = admin, 2 = guest (selon site)
            mtrace("trial_maint_task: skip protected user id={$user->id}");
            return false;
        }

        // Déjà supprimé ? On saute.
        if (!empty($user->deleted)) {
            mtrace("trial_maint_task: skip user={$user->id} already deleted");
            return false;
        }

        // Supprimer correctement (signature = stdClass $user).
        delete_user($user);

        mtrace("trial_maint_task: deleted user={$user->id}");
        return true;
    }
}
