<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\legal\entity;

defined('MOODLE_INTERNAL') || die();

/** Registry of stable Commerce legal entities backed by plugin configuration. */
final class CommerceLegalEntityRegistry {
    public const FR_MAIN = 'fr_main';
    public const RU_MAIN = 'ru_main';

    private const DEFINITIONS = [
        self::FR_MAIN => [
            'registeredcountry' => 'FR',
            'prefix' => 'legal_entity_fr_',
            'legacyprefix' => 'invoice_eur_',
        ],
        self::RU_MAIN => [
            'registeredcountry' => 'RU',
            'prefix' => 'legal_entity_ru_',
            'legacyprefix' => 'invoice_rub_',
        ],
    ];

    /** @return string[] */
    public function keys(): array {
        return array_keys(self::DEFINITIONS);
    }

    public function has(string $key): bool {
        return isset(self::DEFINITIONS[$this->normalise_key($key)]);
    }

    public function get(string $key): CommerceLegalEntity {
        $key = $this->normalise_key($key);
        if (!isset(self::DEFINITIONS[$key])) {
            throw new \coding_exception('Unknown Commerce legal entity: ' . $key);
        }

        $definition = self::DEFINITIONS[$key];
        return new CommerceLegalEntity(
            $key,
            $definition['registeredcountry'],
            $this->read($definition, 'name'),
            $this->read($definition, 'address'),
            $this->read($definition, 'legal'),
            $this->read($definition, 'registration'),
            $this->read($definition, 'tax_identifier'),
            $this->read($definition, 'email'),
            $this->read($definition, 'phone'),
            $this->read($definition, 'website'),
            $this->read($definition, 'tax_notice'),
            $this->read($definition, 'footer')
        );
    }

    /** @return array<string, CommerceLegalEntity> */
    public function all(): array {
        $entities = [];
        foreach ($this->keys() as $key) {
            $entities[$key] = $this->get($key);
        }
        return $entities;
    }

    private function read(array $definition, string $field): string {
        $config = get_config('local_subscriptions');
        $key = $definition['prefix'] . $field;
        if (is_object($config) && property_exists($config, $key)) {
            return trim((string)$config->{$key});
        }

        // I2 compatibility bridge: only fields that existed in the old invoice profiles can fall back.
        $legacyfield = in_array($field, ['name', 'address', 'legal', 'email', 'phone', 'website', 'tax_notice', 'footer'], true)
            ? $field
            : null;
        if ($legacyfield !== null) {
            $legacykey = $definition['legacyprefix'] . $legacyfield;
            if (is_object($config) && property_exists($config, $legacykey)) {
                return trim((string)$config->{$legacykey});
            }
        }
        return '';
    }

    private function normalise_key(string $key): string {
        return strtolower(trim($key));
    }
}
