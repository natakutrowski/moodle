<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry of product type descriptors available to the Commerce catalogue.
 */
final class CommerceProductTypeRegistry {

    /** @var array<string, CommerceProductTypeInterface> */
    private array $types = [];

    /**
     * @param CommerceProductTypeInterface[] $types
     */
    public function __construct(array $types = []) {
        foreach ($types as $type) {
            $this->register($type);
        }
    }

    public static function create_default(): self {
        return new self([
            new CourseAccessProductType(),
            new DigitalDownloadProductType(),
            new BundleProductType(),
            new ServiceProductType(),
        ]);
    }

    public function register(CommerceProductTypeInterface $type): void {
        $code = $type->get_code();

        if (isset($this->types[$code])) {
            throw new \coding_exception('Commerce product type "' . $code . '" is already registered.');
        }

        $this->types[$code] = $type;
    }

    public function has(string $code): bool {
        return isset($this->types[strtolower(trim($code))]);
    }

    public function get(string $code): CommerceProductTypeInterface {
        $code = strtolower(trim($code));

        if (!isset($this->types[$code])) {
            throw new \coding_exception('Unknown Commerce product type "' . $code . '".');
        }

        return $this->types[$code];
    }

    /**
     * @return CommerceProductTypeInterface[]
     */
    public function all(): array {
        return array_values($this->types);
    }
}
