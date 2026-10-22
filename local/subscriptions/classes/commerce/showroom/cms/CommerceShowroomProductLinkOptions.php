<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Builds safe product selectors for Showroom.
 */
final class CommerceShowroomProductLinkOptions {
    private CommerceProductRepository $products;
    private \moodle_database $database;

    /** Historical role/type contracts preserved for the Verbs Showroom. */
    private const LEGACY_ROLE_TYPES = [
        'course' => CommerceProductType::COURSE_ACCESS,
        'pdf' => CommerceProductType::DIGITAL_DOWNLOAD,
        'bundle' => CommerceProductType::BUNDLE,
    ];

    /** Additional strict A1 roles introduced in 7.98E.7.2. */
    private const STRICT_ROLE_TYPES = [
        'course' => CommerceProductType::COURSE_ACCESS,
        'course_fr' => CommerceProductType::COURSE_ACCESS,
        'course_ru' => CommerceProductType::COURSE_ACCESS,
        'pdf' => CommerceProductType::DIGITAL_DOWNLOAD,
        'bundle' => CommerceProductType::BUNDLE,
    ];


    public function __construct(\moodle_database $db) {
        $this->database = $db;
        $this->products = new CommerceProductRepository(
            $db,
            new CommerceCatalogHydrator()
        );
    }

    /**
     * Compatibility helper for the historical three-role configuration.
     *
     * @return array<string,array<string,string>>
     */
    public function grouped_options(array $current = []): array {
        $result = [];
        foreach (self::LEGACY_ROLE_TYPES as $role => $type) {
            $result[$role] = $this->options_for_type(
                $type,
                (string)($current[$role] ?? '')
            );
        }
        return $result;
    }

    /** @return array<string,string> */
    public function options_for_role(string $role, string $current = ''): array {
        $role = CommerceShowroomProductConfig::normalise_role($role);
        if ($role === null) {
            throw new \invalid_parameter_exception('Invalid Showroom product role.');
        }

        if (isset(self::STRICT_ROLE_TYPES[$role])) {
            return $this->options_for_type(self::STRICT_ROLE_TYPES[$role], $current);
        }

        return $this->all_options($current);
    }

    /** @return array<string,string> */
    public function all_options(string $current = ''): array {
        $options = [];
        foreach ($this->products->find_all() as $product) {
            $suffix = $product->is_active()
                ? ''
                : ' · ' . $product->get_status();
            $label = CommerceCatalogProductNameResolver::resolve_native_id(
                $this->database,
                (int)$product->get_id(),
                $product->get_name()
            );
            $options[$product->get_sku()] = '[' . $product->get_type() . '] ' . $label . $suffix;
        }

        $current = strtoupper(trim($current));
        if ($current !== '' && !isset($options[$current])) {
            $options[$current] = $current . ' — association actuelle (hors catalogue Native)';
        }

        return $options;
    }

    /**
     * Validate a role/SKU relationship while preserving the stricter legacy
     * type contracts for course/pdf/bundle.
     */
    public function normalise_role_sku(string $role, string $sku): string {
        $role = CommerceShowroomProductConfig::normalise_role($role);
        if ($role === null) {
            throw new \invalid_parameter_exception('Invalid Showroom product role.');
        }

        if (isset(self::STRICT_ROLE_TYPES[$role])) {
            return $this->normalise_sku($sku, self::STRICT_ROLE_TYPES[$role]);
        }

        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            return '';
        }

        $product = $this->products->find_by_sku($sku);
        if ($product === null) {
            throw new \invalid_parameter_exception('Unknown linked Commerce product: ' . $sku);
        }

        return $product->get_sku();
    }

    /**
     * Validate a selected SKU while allowing an empty relationship.
     */
    public function normalise_sku(string $sku, string $expectedtype): string {
        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            return '';
        }

        $product = $this->products->find_by_sku($sku);
        if ($product === null) {
            throw new \invalid_parameter_exception('Unknown linked Commerce product: ' . $sku);
        }
        if ($product->get_type() !== $expectedtype) {
            throw new \invalid_parameter_exception(
                'Linked Commerce product has an incompatible product type: ' . $sku
            );
        }

        return $product->get_sku();
    }

    /** @return array<string,string> */
    private function options_for_type(string $type, string $current): array {
        $options = [];
        foreach ($this->products->find_by_type($type) as $product) {
            $suffix = $product->is_active()
                ? ''
                : ' · ' . $product->get_status();
            $label = CommerceCatalogProductNameResolver::resolve_native_id(
                $this->database,
                (int)$product->get_id(),
                $product->get_name()
            );
            $options[$product->get_sku()] = $label . $suffix;
        }

        // Preserve visibility of an old/legacy relationship that is not
        // present in the Native catalogue yet. Saving is blocked unless the
        // selection points to a real compatible Native product.
        $current = strtoupper(trim($current));
        if ($current !== '' && !isset($options[$current])) {
            $options[$current] = $current . ' — association actuelle (hors catalogue Native)';
        }

        return $options;
    }
}
