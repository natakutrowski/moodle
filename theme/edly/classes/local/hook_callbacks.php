<?php
namespace theme_edly\local;

use core\hook\output\before_footer_html_generation;

final class hook_callbacks {
    public static function before_footer(before_footer_html_generation $hook): void {
        $renderer = $hook->renderer;
        $page     = $renderer->get_page(); // <- public getter
        $pagetype = $page->pagetype ?? '';
        $script   = $_SERVER['SCRIPT_NAME'] ?? '';


        global $PAGE, $CFG;
             
        require_once($CFG->dirroot.'/local/subscriptions/lib.php');
        ob_start();
        local_subscriptions_inject_subscribe_modal($PAGE);
        $modal = ob_get_clean();
        $hook->add_html($modal);

        // Ne s'exécute que sur /login/change_password.php
        if ($pagetype !== 'login-change_password' && !str_ends_with($script, '/login/change_password.php')) {
            return;
        }

        // Appelle l’AMD qui ajoute l’icône "œil" (version vanilla).
        $page->requires->js_call_amd('theme_edly/password_eye_vanilla', 'init');
        // (Alternative possible : injecter directement un <script> via $hook->add_html(...)). :contentReference[oaicite:2]{index=2}

    }
}
