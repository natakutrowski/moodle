<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\recommendation;

defined('MOODLE_INTERNAL') || die();

/** Ordered recommendation collection. */
final class CommerceCourseRecommendationCollection implements \Countable, \IteratorAggregate {
    /** @var CommerceCourseRecommendationPresentation[] */
    private array $items = [];

    /** @param CommerceCourseRecommendationPresentation[] $items */
    public function __construct(array $items = []) {
        foreach ($items as $item) {
            if (!$item instanceof CommerceCourseRecommendationPresentation) {
                throw new \coding_exception('Invalid course recommendation item.');
            }
            $this->items[$item->sku] = $item;
        }
    }

    /** @return CommerceCourseRecommendationPresentation[] */
    public function all(): array { return array_values($this->items); }
    public function count(): int { return count($this->items); }
    public function getIterator(): \Traversable { return new \ArrayIterator($this->all()); }
}
