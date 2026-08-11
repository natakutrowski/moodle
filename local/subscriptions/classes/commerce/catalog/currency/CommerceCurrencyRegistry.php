<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\currency;

defined('MOODLE_INTERNAL') || die();

final class CommerceCurrencyRegistry {
    private const SUPPORTED = ['EUR', 'RUB', 'USD', 'GBP', 'CHF', 'CAD', 'JPY'];

    public function enabled(): array {
        $configured = (string)get_config('local_subscriptions', 'commerce_enabled_currencies');
        $codes = array_filter(array_map(static fn(string $v): string => strtoupper(trim($v)), explode(',', $configured ?: 'EUR,RUB')));
        return array_values(array_intersect(self::SUPPORTED, array_unique($codes))) ?: ['EUR', 'RUB'];
    }

    public function options(): array {
        $options = [];
        foreach ($this->enabled() as $code) {
            $options[$code] = $code . ' — ' . $this->label($code);
        }
        return $options;
    }

    public function label(string $code): string {
        return match (strtoupper($code)) {
            'EUR' => 'Euro', 'RUB' => 'Rouble russe', 'USD' => 'Dollar américain', 'GBP' => 'Livre sterling',
            'CHF' => 'Franc suisse', 'CAD' => 'Dollar canadien', 'JPY' => 'Yen japonais', default => strtoupper($code),
        };
    }

    public function require_enabled(string $code): string {
        $code = strtoupper(trim($code));
        if (!in_array($code, $this->enabled(), true)) {
            throw new \coding_exception('Unsupported or disabled Commerce currency: ' . $code);
        }
        return $code;
    }
}
