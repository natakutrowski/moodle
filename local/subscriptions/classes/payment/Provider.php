<?php
namespace local_subscriptions\payment;
final class Provider {
    public const STRIPE = 'stripe';
    public const ALFA   = 'alfa';

    /** Provider par défaut piloté par settings (fallback Stripe) */
    public static function defaultProvider(): string {
        $val = get_config('local_subscriptions', 'provider_default');
        $val = strtolower((string)$val);
        return in_array($val, [self::STRIPE, self::ALFA], true) ? $val : self::STRIPE;
    }

    /** Retourne le nom affichable localisé du provider. */
    public static function get(?string $code): string {
        $code = strtolower((string)$code);
        switch ($code) {
            case self::ALFA:   return get_string('provider_alfa', 'local_subscriptions');
            case self::STRIPE: return get_string('provider_stripe', 'local_subscriptions');
            default:
                return $code !== '' ? ucfirst($code) : get_string('notavailable', 'local_subscriptions');
        }
    }

    /** 'test' | 'live' (lit les settings) */
    public static function env(?string $code): string {
        $code = strtolower((string)$code);
        if ($code === self::ALFA) {
            $env = get_config('local_subscriptions', 'alfa_env') ?: 'test';
        } elseif ($code === self::STRIPE) {
            $env = get_config('local_subscriptions', 'stripe_env') ?: 'test';
        } else {
            $env = 'test';
        }
        return ($env === 'live') ? 'live' : 'test';
    }

    /** Texte d’environnement localisé (ex: 'Test' / 'Live') */
    public static function env_text(?string $code): string {
        return self::env($code) === 'live'
            ? get_string('env_live', 'local_subscriptions')
            : get_string('env_test', 'local_subscriptions');
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
    public static function label_with_icon(?string $code): string {
        $name = self::get($code);
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
        $base  = self::label_with_icon($code);
        $envtx = self::env_text($code);
        $env   = self::env($code); // 'test'|'live'
        $badge = \html_writer::span($envtx, 'ls-env-badge ls-env-'.$env);
        return \html_writer::span($base.' '.$badge, 'ls-provider-with-env');
    }

    /** Variante texte simple (si ta 2e colonne est échappée) */
    public static function text_with_env(?string $code): string {
        return self::get($code).' — '.self::env_text($code);
    }

}
