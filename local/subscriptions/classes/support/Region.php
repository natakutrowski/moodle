<?php
namespace local_subscriptions\support;

defined('MOODLE_INTERNAL') || die();

final class Region {
    /**
     * Retourne un code pays ISO2 (RU, BY, FR...) fiable.
     * 1) ta fonction globale get_user_country_code() (ipwho.is)
     * 2) CF-IPCountry
     * 3) langue ru → RU (fallback léger)
     * 4) 'ZZ'
     */
    public static function detect_country(): string {
        // 1) fonction déjà existante (global scope)
        if (function_exists('\\get_user_country_code')) {
            $cc = strtoupper((string)\get_user_country_code());
            if ($cc && strlen($cc) === 2) {
                return $cc;
            }
        }

        // 2) Cloudflare
        $cf = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '';
        if ($cf && strlen($cf) === 2) {
            return strtoupper($cf);
        }

        // 3) langue
        if (current_language() === 'ru') {
            return 'RU';
        }

        return 'ZZ';
    }

    public static function is_ru_or_by(?string $cc): bool {
        $cc = strtoupper((string)$cc);
        return in_array($cc, ['RU','BY'], true);
    }

    /** Devise par défaut : RU/BY → RUB, sinon EUR (tu peux étendre) */
    public static function default_currency_for(string $cc): string {
        return self::is_ru_or_by($cc) ? 'RUB' : 'EUR';
    }

    /** Renvoie les URLs policy/terms selon RU/BY vs “rest of world” (+ fallbacks) */
    public static function policyUrls(): array {
        global $CFG;
        $cc = self::detect_country();
        $isru = self::is_ru_or_by($cc);
        $policy = get_config('local_subscriptions', $isru ? 'policy_url_ru' : 'policy_url_row') ?: ($CFG->wwwroot.'/privacy');
        $terms  = get_config('local_subscriptions', $isru ? 'terms_url_ru'  : 'terms_url_row')  ?: ($CFG->wwwroot.'/terms');
        return ['policy' => $policy, 'terms' => $terms];
    }
}
