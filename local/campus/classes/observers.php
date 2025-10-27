<?php
namespace local_campus;

defined('MOODLE_INTERNAL') || die();

class observers {

    /**
     * Quand l'utilisateur se connecte, on force sa "page d'accueil" sur "Mes cours"
     * si ce n'est PAS un invité/compte d'essai.
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $USER, $SESSION;

        $userid = (int)$event->userid;
        if (!$userid || isguestuser($userid)) {
            return;
        }

        // Exclure les comptes "essai"
        $sysctx = \context_system::instance();
        $istrial = self::has_system_role_shortname($userid, 'triallimited', $sysctx->id);
        if ($istrial) {
            return;
        }

        // 1) Préférence 'My courses' pour les prochaines connexions
        if ((int)get_user_preferences('defaulthomepage', -1, $userid) !== HOMEPAGE_MYCOURSES) {
            set_user_preference('defaulthomepage', HOMEPAGE_MYCOURSES, $userid);
        }

        // 2) **Écraser la redirection EN COURS** vers My courses (prime sur la page d’origine)
        //$SESSION->wantsurl = (new \moodle_url('/my/index.php'))->out(false);
        // si tu veux viser explicitement ta page réécrite :
        $SESSION->wantsurl = (new \moodle_url('/my/courses.php'))->out(false);
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

}
