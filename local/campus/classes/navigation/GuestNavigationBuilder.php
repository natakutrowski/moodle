<?php

namespace local_campus\navigation;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared guest navigation model for Edly and the Campus landing banner.
 */
final class GuestNavigationBuilder {
    /** @return array<string, mixed> */
    public static function build(\moodle_page $page): array {
        $campusurl = self::url('my_campus', '/local/subscriptions/mon_campus.php');
        $shopurl = self::url('storefront', '/boutique');
        $provisional = null;
        $resolver = '\local_subscriptions\commerce\checkout\guest\CommerceProvisionalGuestAccountContext';
        if (class_exists($resolver)) {
            try {
                $reference = trim((string)($page->url->get_param('reference') ?? ''));
                $provisional = $resolver::resolve($reference === '' ? null : $reference);
            } catch (\Throwable) {
                $provisional = null;
            }
        }
        $path = parse_url($page->url->out(false), PHP_URL_PATH) ?: '';
        $isprovisionalcontext = $provisional !== null;
        $translations = get_string_manager()->get_list_of_translations();
        $current = current_language();
        $languages = [];

        foreach ($translations as $code => $name) {
            $url = new \moodle_url($page->url);
            $url->param('lang', $code);
            $languages[] = [
                'code' => $code,
                'name' => $name,
                'flag' => self::flag($code),
                'url' => $url->out(false),
                'active' => $code === $current,
            ];
        }

        return [
            'enabled' => true,
            'shopurl' => $shopurl,
            'loginurl' => $provisional !== null
                ? $provisional['activationurl']->out(false)
                : (new \moodle_url('/login/index.php', ['returnurl' => $campusurl]))->out(false),
            'languages' => $languages,
            'haslanguages' => count($languages) > 1,
            'currentlanguageflag' => self::flag($current),
            'showshop' => !$isprovisionalcontext,
            'loginrequiresaccountfinalisation' => $isprovisionalcontext,
            'loginicon' => $provisional !== null ? 'fa-solid fa-key' : 'fa-solid fa-right-to-bracket',
            'shoplabel' => self::string('commerce_customer_hub_shop', 'local_subscriptions', 'Boutique'),
            'loginlabel' => $provisional !== null
                ? self::string('commerce_guest_activation_nav_cta', 'local_subscriptions', 'Finaliser mon compte')
                : get_string('login'),
            'languagelabel' => get_string('language'),
        ];
    }

    private static function string(string $identifier, string $component, string $fallback): string {
        try {
            return get_string($identifier, $component);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private static function url(string $method, string $fallback): string {
        $factory = '\\local_subscriptions\\url\\UrlFactory';
        if (class_exists($factory) && is_callable([$factory, $method])) {
            try {
                $url = $factory::$method();
                if ($url instanceof \moodle_url) {
                    return $url->out(false);
                }
            } catch (\Throwable) {
                // Keep the public navigation available during upgrades.
            }
        }
        return (new \moodle_url($fallback))->out(false);
    }

    private static function flag(string $language): string {
        $language = strtolower($language);
        if (str_starts_with($language, 'fr')) {
            return '🇫🇷';
        }
        if (str_starts_with($language, 'ru')) {
            return '🇷🇺';
        }
        return '🇬🇧';
    }
}
