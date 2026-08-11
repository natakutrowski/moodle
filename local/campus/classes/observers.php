<?php
namespace local_campus;

defined('MOODLE_INTERNAL') || die();

class observers {

    /**
     * Quand l'utilisateur se connecte, on force sa "page d'accueil" sur "Mes cours"
     * si ce n'est PAS un invité/compte d'essai.
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $CFG, $SESSION;

        $userid = (int)$event->userid;
        if (!$userid || isguestuser($userid) || \core\session\manager::is_loggedinas()) {
            return;
        }

        // Exclure les comptes "essai" (comme avant)
        $sysctx = \context_system::instance();
        if (self::has_system_role_shortname($userid, 'triallimited', $sysctx->id)) {
            return;
        }

        // 1) Préférence "Accueil = Mes cours" pour les connexions futures
        if ((int)get_user_preferences('defaulthomepage', -1, $userid) !== HOMEPAGE_MYCOURSES) {
            set_user_preference('defaulthomepage', HOMEPAGE_MYCOURSES, $userid);
        }

        // 2) Ne PAS écraser une redirection déjà prévue
        $haswants   = !empty($SESSION->wantsurl);
        $returnurl  = optional_param('returnurl', '', PARAM_URL);
        if ($haswants || !empty($returnurl)) {
            return; // on laisse Moodle renvoyer là où l’utilisateur allait
        }

        // 3) Sinon, destination par défaut = ta page Mes cours
        // (fallback sur /my/courses.php si besoin)
        $target = '/local/campus/mycourses.php';
        if (!file_exists($CFG->dirroot . $target)) {
            $target = '/my/courses.php';
        }
        $SESSION->wantsurl = (new \moodle_url($target))->out(false);
    }


    private static function has_system_role_shortname(int $userid, string $shortname, int $contextid): bool {
        global $DB;

        // Récupère l'id du rôle par shortname (mise en cache statique)
        static $rolecache = [];
        if (!array_key_exists($shortname, $rolecache)) {
            $rolecache[$shortname] = (int)$DB->get_field('role', 'id', ['shortname' => $shortname], IGNORE_MISSING);
        }
        $roleid = $rolecache[$shortname];
        if (!$roleid) {
            return false;
        }

        // Vérifie l’assignation du rôle AU CONTEXTE SYSTÈME
        return $DB->record_exists('role_assignments', [
            'userid'    => $userid,
            'contextid' => $contextid,
            'roleid'    => $roleid,
        ]);
    }

    public static function user_login_failed(\core\event\user_login_failed $event): void {
        global $DB, $SESSION, $CFG;

        $data = $event->get_data();
        $u = $data['other']['username'] ?? '';
        if (empty($u)) { return; }

        // Si la plateforme autorise la connexion par e-mail, tester aussi le champ email.
        $user = $DB->get_record('user', ['username' => $u, 'deleted' => 0]);
        if (!$user && !empty($CFG->authloginviaemail)) {
            $user = $DB->get_record('user', ['email' => $u, 'deleted' => 0]);
        }
        if (!$user) {
            return; // ne pas révéler l'existence d'un compte
        }
        if (!empty($user->suspended)) {
            $SESSION->local_campus_login_notice = [
                'type'     => 'suspended',
                'login'    => (new \moodle_url('/login/index.php'))->out(false),
                'subscribe'=> (new \moodle_url('/boutique'))->out(false),
            ];
        }
    }

}
