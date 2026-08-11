<?php
namespace local_subscriptions\payment;

use local_subscriptions\payment\stripe\StripeConfiguration;
final class Provider {
    public const STRIPE = 'stripe';
    public const ALFA   = 'alfa';
    public const MANUAL = 'manual';
    public const CSV    = 'csv';
    public const DEV    = 'dev';
    public const TRIAL    = 'trial';

    public const KNOWN = [
        self::STRIPE,
        self::ALFA,
        self::MANUAL,
        self::CSV,
        self::DEV,
        self::TRIAL,
    ];

    /** Provider par défaut piloté par settings (fallback Stripe) */
    public static function defaultProvider(): string {
        $val = get_config('local_subscriptions', 'provider_default');
        $val = strtolower((string)$val);
        return in_array($val, [self::STRIPE, self::ALFA], true) ? $val : self::STRIPE;
    }

    /** Retourne le nom affichable localisé du provider. */
    public static function get(?string $code): string {
        $code = strtolower((string)$code);
        return match ($code) {
            self::STRIPE => get_string('provider_stripe', 'local_subscriptions'),
            self::ALFA   => get_string('provider_alfa', 'local_subscriptions'),
            self::MANUAL => get_string('provider_manual', 'local_subscriptions'),
            self::CSV    => get_string('provider_csv', 'local_subscriptions'),
            self::DEV    => get_string('provider_dev', 'local_subscriptions'),
            self::TRIAL  => get_string('provider_trial', 'local_subscriptions'),
            default      => $code !== '' ? ucfirst($code) : get_string('notavailable', 'local_subscriptions'),
        };
    }

    /** 'test' | 'live' (lit les settings) */
    public static function env(?string $code): string {
        $code = strtolower((string)$code);
        return match ($code) {
            self::ALFA   => get_config('local_subscriptions', 'alfa_env') ?: 'test',
            self::STRIPE => StripeConfiguration::active_profile(),
            default      => 'n/a',
        };
    }

    /** Texte d’environnement localisé (ex: 'Test' / 'Live') */
    public static function env_text(?string $code): string {
        $env = self::env($code);
        return match ($env) {
            'live_ei' => get_string('stripe_profile_live_ei', 'local_subscriptions'),
            'live_sas' => get_string('stripe_profile_live_sas', 'local_subscriptions'),
            'live' => get_string('stripe_profile_live_ei', 'local_subscriptions'),
            'test' => get_string('stripe_profile_test', 'local_subscriptions'),
            default => '', // pas de badge
        };
    }


    /** Clé pix/icone (sans extension) */
    public static function iconkey(?string $code): ?string {
        $code = strtolower((string)$code);
        return match ($code) {
            self::ALFA   => 'providers/alfa',
            self::STRIPE => 'providers/stripe',
            default      => null,
        };
    }

    /** URL de l’icône (ou null si inconnue) */
    public static function icon_url(?string $code): ?\moodle_url {
        global $OUTPUT;
        $key = self::iconkey($code);
        return $key ? $OUTPUT->image_url($key, 'local_subscriptions') : null;
    }

    /** HTML: icône + nom (prêt à injecter dans un tableau) */
    public static function label_with_icon(?string $code, string $mode = 'web'): string {
        $name = self::get($code);

        if ($mode === 'email') {
            if ($u = self::icon_email_url($code)) {
                $img = \html_writer::empty_tag('img', [
                    'src'=>$u->out(false), 'alt'=>$name, 'width'=>20, 'height'=>20,
                    'style'=>'vertical-align:-3px;margin-right:6px',
                ]);
                return \html_writer::span($img.\html_writer::span($name, 'ls-provider-name'), 'ls-provider-badge');
            }
            return \html_writer::span($name, 'ls-provider-name');
        }

        $url  = self::icon_url($code);
        if ($url) {
            $img = \html_writer::empty_tag('img', [
                'src'   => $url->out(false),
                'alt'   => $name,
                'class' => 'ls-provider-icon',
                'width' => 20,
                'height'=> 20,
                'loading'=>'lazy',
            ]);
            return \html_writer::span($img.' '.\html_writer::span($name, 'ls-provider-name'), 'ls-provider-badge');
        }
        return \html_writer::span($name, 'ls-provider-name');
    }


    /** HTML: icône + nom + badge d’env (si ton rendu accepte l’HTML) */
    public static function label_with_icon_env(?string $code): string {
        $base = self::label_with_icon($code);
        $env  = self::env($code);
        if (!in_array($env, ['test', 'live', 'live_ei', 'live_sas'], true)) {
            return $base;
        }
        $envtx = self::env_text($code);
        $badgeclass = $env === 'test' ? 'test' : 'live';
        $badge = \html_writer::span($envtx, 'ls-env-badge ls-env-'.$badgeclass);
        return \html_writer::span($base.' '.$badge, 'ls-provider-with-env');
    }

    /** Variante texte simple (si ta 2e colonne est échappée) */
    public static function text_with_env(?string $code): string {
        return self::get($code).' — '.self::env_text($code);
    }

    public static function is_local(?string $code): bool {
        return in_array(strtolower((string)$code), [self::MANUAL, self::CSV, self::DEV], true);
    }

    public static function icon_email_url(string $code): ?\moodle_url {
        global $CFG;
        $code = strtolower($code);
        $path = $CFG->wwwroot.'/local/subscriptions/pix/email/'.$code.'.png';
        // Optionnel: vérifier l’existence du fichier côté FS si tu veux
        return new \moodle_url($path);
    }



}
