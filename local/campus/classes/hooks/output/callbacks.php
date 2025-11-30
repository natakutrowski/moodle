<?php
namespace local_campus\hooks\output;

defined('MOODLE_INTERNAL') || die();

final class callbacks {
    /**
     * Équivalent “nouveau hook” de l’ancien local_campus_before_http_headers().
     * Appelé juste avant l’envoi des headers de la page.
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $SESSION, $CFG;

        // Afficher une alerte sur la page de login si un compte suspendu vient d’échouer la connexion.
        // (Flag posé par notre observer user_login_failed.)
        $islogin = isset($_SERVER['SCRIPT_NAME'])
            && (bool)preg_match('~/login/index\.php$~', $_SERVER['SCRIPT_NAME']);

        if (!$islogin || empty($SESSION->local_campus_login_notice)) {
            return;
        }

        $notice = $SESSION->local_campus_login_notice;
        unset($SESSION->local_campus_login_notice);

        $link = $notice['subscribe'] ?? (new \moodle_url('/local/subscriptions/subscribe.php'))->out(false);
        $msg  = get_string('login_suspended_html', 'local_campus', (object)['link' => $link]);

        \core\notification::add($msg, \core\output\notification::NOTIFY_WARNING);
    }
}
