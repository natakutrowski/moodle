<?php

declare(strict_types=1);

namespace local_subscriptions\output;

use core\hook\output\before_footer_html_generation;
use local_subscriptions\commerce\checkout\guest\CommerceProvisionalGuestAccountContext;

defined('MOODLE_INTERNAL') || die();

/** Output hooks for contextual Guest Checkout account guidance. */
final class hook_callbacks {
    public static function before_footer(before_footer_html_generation $hook): void {
        $page = $hook->renderer->get_page();
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $islogin = ($page->pagetype ?? '') === 'login-index' || str_ends_with($script, '/login/index.php');
        if (!$islogin || isloggedin() && !isguestuser()) {
            return;
        }

        $context = CommerceProvisionalGuestAccountContext::resolve();
        if ($context === null) {
            return;
        }

        $page->requires->css(new \moodle_url('/local/subscriptions/styles/provisional_account.css'));
        $page->requires->js_call_amd('local_subscriptions/provisional_account_notice', 'init');

        $html = \html_writer::start_div('commerce-provisional-login-notice', [
            'data-provisional-account-login-notice' => '1',
            'hidden' => 'hidden',
            'role' => 'status',
        ]);
        $html .= \html_writer::div('🔐', 'commerce-provisional-login-notice__icon', ['aria-hidden' => 'true']);
        $html .= \html_writer::start_div('commerce-provisional-login-notice__copy');
        $html .= \html_writer::tag('strong', get_string('commerce_guest_login_notice_title', 'local_subscriptions'));
        $html .= \html_writer::tag('p', get_string('commerce_guest_login_notice_message', 'local_subscriptions'));
        $html .= \html_writer::end_div();
        $html .= \html_writer::link(
            $context['activationurl'],
            get_string('commerce_guest_login_notice_cta', 'local_subscriptions'),
            ['class' => 'btn btn-primary']
        );
        $html .= \html_writer::end_div();
        $hook->add_html($html);
    }
}
