<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\method;

defined('MOODLE_INTERNAL') || die();

/** Shared visual identity for customer-facing Commerce payment methods. */
final class CommercePaymentMethodVisual {
    public static function icon_filename(?string $method): ?string {
        $method = self::normalise($method);
        if ($method === null) {
            return null;
        }

        return match ($method) {
            CommercePaymentMethod::CARD => 'card.svg',
            CommercePaymentMethod::APPLE_PAY => 'applepay.svg',
            CommercePaymentMethod::GOOGLE_PAY => 'googlepay.svg',
            CommercePaymentMethod::PAYPAL => 'paypal.svg',
            CommercePaymentMethod::LINK => 'link.svg',
            CommercePaymentMethod::KLARNA => 'klarna.svg',
            CommercePaymentMethod::ALFA_PAY => 'alfapay.svg',
            CommercePaymentMethod::SBP => 'sbp.svg',
            CommercePaymentMethod::SBERPAY => 'sberpay.svg',
            CommercePaymentMethod::MIR_PAY => 'mirpay.svg',
        };
    }

    public static function icon_url(?string $method): ?\moodle_url {
        $filename = self::icon_filename($method);
        if ($filename === null) {
            return null;
        }

        return new \moodle_url(
            '/local/subscriptions/pix/providers/' . $filename
        );
    }

    public static function icon_path(?string $method): ?string {
        $filename = self::icon_filename($method);
        if ($filename === null) {
            return null;
        }

        $path = dirname(__DIR__, 4) . '/pix/providers/' . $filename;
        return is_readable($path) ? $path : null;
    }

    public static function icon_html(
        ?string $method,
        string $label,
        int $size = 20,
        string $class = 'commerce-payment-method-icon'
    ): string {
        $url = self::icon_url($method);
        if ($url === null) {
            return '';
        }

        return \html_writer::empty_tag('img', [
            'src' => $url->out(false),
            'alt' => $label,
            'title' => $label,
            'aria-label' => $label,
            'class' => $class,
            'width' => $size,
            'height' => $size,
            'loading' => 'lazy',
        ]);
    }

    public static function label_with_icon(
        ?string $method,
        string $label,
        int $size = 20
    ): string {
        $icon = self::icon_html($method, $label, $size);
        if ($icon === '') {
            return \html_writer::span(
                s($label),
                'commerce-payment-method-name'
            );
        }

        return \html_writer::span(
            $icon . ' ' . \html_writer::span(
                s($label),
                'commerce-payment-method-name'
            ),
            'commerce-payment-method-badge'
        );
    }

    private static function normalise(?string $method): ?string {
        $method = strtolower(trim((string)$method));
        if ($method === '') {
            return null;
        }

        return in_array($method, CommercePaymentMethod::KNOWN, true)
            ? $method
            : null;
    }
}
