<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_edly\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds the global CampusFR customer navigation exposed by the Edly theme.
 */
final class customer_navigation {
    /**
     * @return array<string, mixed>
     */
    public static function build(\moodle_page $page): array {
        global $OUTPUT, $USER;

        if (!isloggedin() || isguestuser()) {
            return [
                'enabled' => false,
                'items' => [],
            ];
        }

        $items = [
            self::item('campus', get_string('commerce_customer_hub_title', 'local_subscriptions'), self::url('my_campus', '/local/subscriptions/mon_campus.php'), 'fa-solid fa-house'),
            self::item('courses', get_string('commerce_customer_hub_courses', 'local_subscriptions'), self::url('my_courses', '/local/campus/mycourses.php'), 'fa-solid fa-graduation-cap'),
            self::item('resources', get_string('commerce_customer_hub_resources', 'local_subscriptions'), self::url('my_digital_products', '/local/subscriptions/my_digital_products.php'), 'fa-solid fa-folder-open'),
            self::item('purchases', get_string('commerce_customer_hub_purchases', 'local_subscriptions'), self::url('my_purchases', '/local/subscriptions/my_purchases.php'), 'fa-solid fa-receipt'),
            self::item('shop', get_string('commerce_customer_hub_shop', 'local_subscriptions'), self::url('storefront', '/local/subscriptions/digital_catalog.php'), 'fa-solid fa-store'),
        ];

        $currentpath = self::normalise_path($page->url->out_as_local_url(false));
        foreach ($items as &$item) {
            $item['active'] = self::normalise_path((string)$item['url']) === $currentpath;
        }
        unset($item);

        $picture = $OUTPUT->user_picture($USER, [
            'size' => 35,
            'link' => false,
            'alttext' => false,
            'class' => 'campus-topbar-user__picture',
        ]);

        $languages = self::languages($page);
        $cartcount = self::cart_count();
        $campusurl = self::url('my_campus', '/local/subscriptions/mon_campus.php');
        $systemcontext = \context_system::instance();
        $isadmin = has_capability('moodle/site:config', $systemcontext);
        $sitecontext = \context_course::instance(SITEID);
        $canswitchrole = $isadmin
            && has_capability('moodle/role:switchroles', $sitecontext);
        $currenturl = $page->url->out(false);

        return [
            'enabled' => true,
            'label' => get_string('commerce_customer_hub_title', 'local_subscriptions'),
            'campusurl' => $campusurl,
            'campusactive' => self::normalise_path($campusurl) === $currentpath,
            'items' => $items,
            'showcart' => true,
            'carturl' => self::url('cart', '/local/subscriptions/cart.php'),
            'cartlabel' => get_string('commerce_cart_view', 'local_subscriptions'),
            'cartcount' => $cartcount,
            'hascartitems' => $cartcount > 0,
            'profileurl' => self::url('my_profile', '/user/profile.php'),
            'logouturl' => (new \moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
            'logoutlabel' => get_string('logout'),
            'fullname' => fullname($USER),
            'userpicture' => $picture,
            'viewprofilelabel' => get_string('viewprofile'),
            'languages' => $languages,
            'haslanguages' => count($languages) > 1,
            'languagelabel' => get_string('language'),
            'currentlanguageflag' => self::language_flag(current_language()),
            'isadmin' => $isadmin,
            'editmodelabel' => get_string('editmode'),
            'crmurl' => (new \moodle_url('/crm'))->out(false),
            'crmlabel' => get_string('customernavigation_crm', 'theme_edly'),
            'crmitems' => $isadmin ? self::crm_admin_items($systemcontext) : [],
            'adminurl' => (new \moodle_url('/admin/search.php'))->out(false),
            'adminlabel' => get_string('customernavigation_moodleadmin', 'theme_edly'),
            'adminitems' => $isadmin ? self::moodle_admin_items() : [],
            'preferencesurl' => (new \moodle_url('/user/preferences.php'))->out(false),
            'preferenceslabel' => get_string('preferences'),
            'canswitchrole' => $canswitchrole,
            'switchroleurl' => (new \moodle_url('/course/switchrole.php', [
                'id' => SITEID,
                'returnurl' => $currenturl,
            ]))->out(false),
            'switchrolelabel' => get_string('switchroleto'),
        ];
    }


    /**
     * Builds the public navigation displayed to guests and anonymous visitors.
     *
     * @return array<string, mixed>
     */
    public static function build_guest(\moodle_page $page): array {
        if (isloggedin() && !isguestuser()) {
            return ['enabled' => false];
        }

        $sharedbuilder = '\local_campus\navigation\GuestNavigationBuilder';
        if (class_exists($sharedbuilder)) {
            $navigation = $sharedbuilder::build($page);
        } else {
            $campusurl = self::url('my_campus', '/local/subscriptions/mon_campus.php');
            $navigation = [
                'shopurl' => self::url('storefront', '/boutique'),
                'loginurl' => (new \moodle_url('/login/index.php', [
                    'returnurl' => $campusurl,
                ]))->out(false),
                'languages' => self::languages($page),
                'currentlanguageflag' => self::language_flag(current_language()),
            ];
            $navigation['haslanguages'] = count($navigation['languages']) > 1;
        }

        return array_replace($navigation, [
            'enabled' => true,
            'shoplabel' => get_string('commerce_customer_hub_shop', 'local_subscriptions'),
            'loginlabel' => get_string('login'),
            'languagelabel' => get_string('language'),
        ]);
    }

    /** @return array<string, mixed> */
    private static function item(string $key, string $label, string $url, string $icon): array {
        return [
            'key' => $key,
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'active' => false,
        ];
    }

    /**
     * Builds the CRM shortcuts from the CRM navigation registry when available.
     *
     * @return array<int, array<string, string>>
     */
    private static function crm_admin_items(\context $context): array {
        $registryclass = '\\local_subscriptions\\crm\\navigation\\CrmNavigationRegistry';
        if (!class_exists($registryclass)) {
            return [self::admin_menu_item(
                get_string('customernavigation_crm', 'theme_edly'),
                (new \moodle_url('/crm'))->out(false),
                'fa-solid fa-gauge-high'
            )];
        }

        try {
            $registry = new $registryclass();
            $items = [];
            foreach ($registry->visible_items($context) as $item) {
                $items[] = self::admin_menu_item(
                    (string)$item->label,
                    $item->url->out(false),
                    '',
                    (string)$item->icon
                );
            }
            return $items;
        } catch (\Throwable) {
            // Keep the admin topbar usable while local_subscriptions is upgrading.
            return [self::admin_menu_item(
                get_string('customernavigation_crm', 'theme_edly'),
                (new \moodle_url('/crm'))->out(false),
                'fa-solid fa-gauge-high'
            )];
        }
    }

    /** @return array<int, array<string, string>> */
    private static function moodle_admin_items(): array {
        $sections = [
            ['root', 'fa-solid fa-sliders'],
            ['users', 'fa-solid fa-users'],
            ['courses', 'fa-solid fa-graduation-cap'],
            ['grades', 'fa-solid fa-chart-column'],
            ['modules', 'fa-solid fa-puzzle-piece'],
            ['appearance', 'fa-solid fa-palette'],
            ['server', 'fa-solid fa-server'],
            ['reports', 'fa-solid fa-chart-line'],
            ['development', 'fa-solid fa-code'],
        ];

        $items = [];
        foreach ($sections as [$key, $icon]) {
            $items[] = self::admin_menu_item(
                get_string('customernavigation_admin_' . $key, 'theme_edly'),
                (new \moodle_url('/admin/search.php'))->out(false) . '#link' . $key,
                $icon
            );
        }
        return $items;
    }

    /** @return array<string, string> */
    private static function admin_menu_item(string $label, string $url, string $icon = '', string $symbol = ''): array {
        return [
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'symbol' => $symbol,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function languages(\moodle_page $page): array {
        $translations = get_string_manager()->get_list_of_translations();
        $current = current_language();
        $result = [];

        foreach ($translations as $code => $name) {
            $url = new \moodle_url($page->url);
            $url->param('lang', $code);
            $result[] = [
                'code' => $code,
                'name' => $name,
                'flag' => self::language_flag($code),
                'url' => $url->out(false),
                'active' => $code === $current,
            ];
        }

        return $result;
    }

    private static function language_flag(string $language): string {
        $language = strtolower($language);
        if (str_starts_with($language, 'fr')) {
            return '🇫🇷';
        }
        if (str_starts_with($language, 'ru')) {
            return '🇷🇺';
        }
        return '🇬🇧';
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
                // Keep the navigation available while the plugin is upgrading.
            }
        }

        return (new \moodle_url($fallback))->out(false);
    }

    private static function cart_count(): int {
        global $USER;
        try {
            $factory = '\\local_subscriptions\\commerce\\cart\\service\\CommerceCartRuntimeFactory';
            $region = '\\local_subscriptions\\support\\Region';
            if (!class_exists($factory) || !class_exists($region)) {
                return 0;
            }
            $currency = $region::default_currency_for($region::detect_country());
            $snapshot = $factory::create()->snapshot((int)$USER->id, $currency, current_language());
            return count($snapshot->get_items());
        } catch (\Throwable) {
            return 0;
        }
    }

    private static function normalise_path(string $url): string {
        $parts = parse_url($url);
        $path = is_array($parts) ? (string)($parts['path'] ?? '') : $url;
        return '/' . trim($path, '/');
    }
}
