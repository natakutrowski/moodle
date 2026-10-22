<?php

declare(strict_types=1);

namespace local_subscriptions\output;

use core\hook\output\before_footer_html_generation;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseResumePresenter;
use local_subscriptions\commerce\checkout\guest\CommerceProvisionalGuestAccountContext;

defined('MOODLE_INTERNAL') || die();

/** Output hooks for contextual Commerce / Guest Checkout guidance. */
final class hook_callbacks {
    public static function before_footer(before_footer_html_generation $hook): void {
        $page = $hook->renderer->get_page();

        self::register_direct_purchase_nav_badge($page);
        self::add_provisional_login_notice($hook, $page);
    }

    private static function register_direct_purchase_nav_badge(\moodle_page $page): void {
        $resume = CommerceDirectPurchaseResumePresenter::present();
        if (!$resume['hasdirectpurchase']) {
            return;
        }

        $page->requires->js_call_amd(
            'local_subscriptions/direct_purchase_nav_badge',
            'init',
            [[
                'label' => $resume['directpurchaseresumetitle'],
                'resumeUrl' => $resume['directpurchaseresumeurl'],
            ]]
        );
    }

    private static function add_provisional_login_notice(
        before_footer_html_generation $hook,
        \moodle_page $page
    ): void {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $islogin = ($page->pagetype ?? '') === 'login-index' || str_ends_with($script, '/login/index.php');
        if (!$islogin || isloggedin() && !isguestuser()) {
            return;
        }

        $context = CommerceProvisionalGuestAccountContext::resolve();
        if ($context === null) {
            return;
        }

        // CSS for this notice is part of the plugin-level stylesheet.
        // before_footer is too late to register a new stylesheet with Moodle.
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
