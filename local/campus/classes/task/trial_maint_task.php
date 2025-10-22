<?php
namespace local_campus\task;

defined('MOODLE_INTERNAL') || die();

class trial_maint_task extends \core\task\scheduled_task {
    public function get_name() { return get_string('cron_trial_maint','local_campus'); }

    public function execute() {
        global $DB, $CFG;
        // ✅ bon fichier core
        require_once($CFG->libdir . '/enrollib.php');

        // (facultatif mais recommandé si tu appelles des méthodes spécifiques du plugin "manual")
        require_once($CFG->dirroot . '/enrol/manual/lib.php');

        // si tu envoies des mails depuis la tâche
        require_once($CFG->dirroot . '/local/subscriptions/classes/mailer.php');


        $now  = time();
        $days = (int)get_config('local_campus', 'trialdays') ?: 7;
        $deleteafter = (int)get_config('local_campus', 'deleteafterdays') ?: 60;
        $trialids = self::trial_courses();

        $threeago = $now - (3 * DAYSECS);

        // J+3 : essai encore actif (expiresat > now), créé il y a ≥ 3 jours, reminder3 non envoyé
        $sql3 = "SELECT *
                FROM {local_campus_trial}
                WHERE reminder3_sent IS NULL
                    AND expiresat > :now
                    AND timecreated <= :threeago";

        $users3 = $DB->get_records_sql($sql3, [
            'now'      => $now,
            'threeago' => $threeago,
        ]);

        foreach ($users3 as $t) {
            // destinataire (priorité au user lié s’il existe encore)
            $user = null;
            if (!empty($t->userid)) {
                $user = $DB->get_record('user', ['id'=>$t->userid, 'deleted'=>0], '*', IGNORE_MISSING);
            }
            $firstname = $user->firstname ?? $t->firstname ?? '';
            $toemail   = $t->email; // toujours depuis la table trial

            // 1 cours d’essai pour le lien “Continuer”
            $trialids = self::trial_courses();
            $firsttrial = !empty($trialids) ? reset($trialids) : 0;

            $continueurl = $firsttrial
            ? (new \moodle_url('/local/campus/course.php', ['trial'=>$firsttrial, 'checktrial'=>1]))->out(false)
            : (new \moodle_url('/'))->out(false);

            $subscribeurl = (new \moodle_url('/subscribe.php'))->out(false);

            $daysleft = max(0, (int)ceil(($t->expiresat - $now) / DAYSECS));
            $coursefullname = '';
            if (!empty($trialids)) {
                $one = $DB->get_record('course', ['id'=>reset($trialids)], 'fullname', IGNORE_MISSING);
                $coursefullname = $one->fullname ?? '';
            }

            \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_REM3, [
                'toemail'         => $toemail,
                'firstname'       => $firstname,
                'continue_url'    => $continueurl,
                'subscribe_url'   => $subscribeurl,
                'course_fullname' => $coursefullname,
                'daysleft'        => $daysleft,
            ]);

            $t->reminder3_sent = $now;
            $DB->update_record('local_campus_trial', $t);
        }


        // 2) Relance expiration J+7 (non envoyée)
        // J+7 : expiré (now >= expiresat) et reminder7 non envoyé
        $sql7 = "SELECT *
                FROM {local_campus_trial}
                WHERE reminder7_sent IS NULL
                    AND expiresat <= :now";

        $users7 = $DB->get_records_sql($sql7, ['now'=>$now]);

        foreach ($users7 as $t) {
            // tente d’avoir un prénom lisible
            $user = null;
            if (!empty($t->userid)) {
                $user = $DB->get_record('user', ['id'=>$t->userid, 'deleted'=>0], '*', IGNORE_MISSING);
            }
            $firstname = $user->firstname ?? $t->firstname ?? '';
            $toemail   = $t->email;

            $trialids = self::trial_courses();
            $coursefullname = '';
            if (!empty($trialids)) {
                $one = $DB->get_record('course', ['id'=>reset($trialids)], 'fullname', IGNORE_MISSING);
                $coursefullname = $one->fullname ?? '';
            }

            \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_TRIAL_EXPIRED, [
                'toemail'         => $toemail,
                'firstname'       => $firstname,
                'subscribe_url'   => (new \moodle_url('/subscribe.php'))->out(false),
                'course_fullname' => $coursefullname,
            ]);

            // marquage expiré
            $t->reminder7_sent = $now;
            $t->status = 2; // expiré
            $DB->update_record('local_campus_trial', $t);

            // désinscrire et suspendre si besoin (optionnel)
            if (!empty($t->userid) && enrol_is_enabled('manual')) {
                self::unenrol_from_trial_courses($t->userid, $trialids);
                $DB->set_field('user', 'suspended', 1, ['id'=>$t->userid]);
            }
        }


        // 3) Suppression (optionnelle) X jours après expiration
        $deleteafter = (int)get_config('local_campus', 'deleteafterdays'); // -1 = jamais, 0 = immédiat
        if ($deleteafter !== -1) {
            $border = ($deleteafter === 0) ? $now : ($now - ($deleteafter * 86400));
            $sqlDel = "SELECT * FROM {local_campus_trial}
                    WHERE status = 2 AND expiresat < :border";
            foreach ($DB->get_records_sql($sqlDel, ['border'=>$border]) as $t) {
                if ($t->userid) {
                    // Sécurité : ne supprime que si l’utilisateur n’a pas d’autres inscriptions “non-essai”
                    $trialids = self::trial_courses();
                    list($notin, $params) = $DB->get_in_or_equal($trialids ?: [0], SQL_PARAMS_NAMED, 'p', false);
                    $params['uid'] = $t->userid;
                    $hasother = $DB->record_exists_sql("
                        SELECT 1
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON e.id = ue.enrolid
                        WHERE ue.userid = :uid
                        AND e.courseid $notin
                    ", $params);

                    if (!$hasother) {
                        require_once($CFG->dirroot.'/user/lib.php');
                        delete_user($DB->get_record('user', ['id'=>$t->userid]));
                    }
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
        if (!$ids) return [];
        list($in, $p) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        return array_keys($DB->get_records_select('course', "id $in", $p, '', 'id'));
    }

    private static function unenrol_from_trial_courses(int $userid, array $courseids): void {
        if (!$courseids) return;
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
}
