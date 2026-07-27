<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable keyed collection of Commerce bundles.
 */
final class CommerceBundleCollection implements \Countable, \IteratorAggregate {

    /** @var array<string, CommerceBundle> */
    private array $bundles = [];

    /**
     * @param CommerceBundle[] $bundles
     */
    public function __construct(array $bundles = []) {
        foreach ($bundles as $bundle) {
            if (!$bundle instanceof CommerceBundle) {
                throw new \coding_exception('A Commerce bundle collection contains an invalid value.');
            }

            $sku = $bundle->get_sku();

            if (isset($this->bundles[$sku])) {
                throw new \coding_exception('A Commerce bundle collection cannot contain duplicate SKUs.');
            }

            $this->bundles[$sku] = $bundle;
        }
    }

    public function count(): int {
        return count($this->bundles);
    }

    public function is_empty(): bool {
        return $this->bundles === [];
    }

    public function has(string $sku): bool {
        return isset($this->bundles[strtoupper(trim($sku))]);
    }

    public function get(string $sku): ?CommerceBundle {
        return $this->bundles[strtoupper(trim($sku))] ?? null;
    }

    /**
     * @return CommerceBundle[]
     */
    public function all(): array {
        return array_values($this->bundles);
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->all());
    }
}
