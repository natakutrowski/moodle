<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\currency;

use local_subscriptions\currency\Currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Commerce activation layer over the central currency metadata registry.
 *
 * A known currency is only technically understood by CampusFR. It becomes a
 * Commerce currency when enabled here, and a product remains free to define
 * prices for only a subset of enabled currencies.
 */
final class CommerceCurrencyRegistry {
    private const DEFAULT_ENABLED = ['EUR', 'RUB'];

    /** @return string[] */
    public function known(): array {
        return Currency::known_codes();
    }

    /** @return string[] */
    public function enabled(): array {
        $configured = (string)get_config('local_subscriptions', 'commerce_enabled_currencies');
        $configured = $configured !== '' ? $configured : implode(',', self::DEFAULT_ENABLED);
        $codes = array_values(array_unique(array_filter(array_map(
            static fn(string $value): string => Currency::sanitize($value),
            explode(',', $configured)
        ))));

        $enabled = array_values(array_intersect($this->known(), $codes));
        return $enabled !== [] ? $enabled : self::DEFAULT_ENABLED;
    }

    public function is_enabled(string $code): bool {
        $code = Currency::sanitize($code);
        return $code !== '' && in_array($code, $this->enabled(), true);
    }

    public function options(): array {
        $options = [];
        foreach ($this->enabled() as $code) {
            $options[$code] = $this->option_label($code);
        }
        return $options;
    }

    /** Options for the administration setting, including currencies not enabled yet. */
    public function known_options(): array {
        $options = [];
        foreach ($this->known() as $code) {
            $options[$code] = $this->option_label($code);
        }
        return $options;
    }

    private function option_label(string $code): string {
        $marker = Currency::visual_marker($code);
        return ($marker !== '' ? $marker . ' ' : '') . $code . ' — ' . $this->label($code);
    }

    public function label(string $code): string {
        $code = Currency::sanitize($code);
        $key = 'commerce_currency_name_' . strtolower($code);
        if ($code !== '' && get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }
        return Currency::name($code);
    }

    /**
     * Enabled options plus known currencies already persisted on an entity.
     *
     * Disabling a currency must stop new commercial use without making old
     * catalogue records impossible to inspect or maintain.
     *
     * @param string[] $existingcodes
     * @return array<string, string>
     */
    public function options_including(array $existingcodes): array {
        $included = array_values(array_unique(array_merge(
            $this->enabled(),
            array_filter(array_map(
                static fn(string $code): string => Currency::sanitize($code),
                $existingcodes
            ), static fn(string $code): bool => Currency::is_known($code))
        )));

        $options = [];
        foreach ($this->known() as $code) {
            if (in_array($code, $included, true)) {
                $options[$code] = $this->option_label($code);
            }
        }
        return $options;
    }

    public function require_known(string $code): string {
        $code = Currency::sanitize($code);
        if ($code === '' || !Currency::is_known($code)) {
            throw new \coding_exception('Unknown Commerce currency: ' . $code);
        }
        return $code;
    }

    public function require_enabled(string $code): string {
        $code = $this->require_known($code);
        if (!$this->is_enabled($code)) {
            throw new \coding_exception('Disabled Commerce currency: ' . $code);
        }
        return $code;
    }
}
