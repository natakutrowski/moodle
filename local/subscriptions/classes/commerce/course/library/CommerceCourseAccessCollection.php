<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\library;

defined('MOODLE_INTERNAL') || die();

/** Course-indexed collection returned to consumers such as local_campus. */
final class CommerceCourseAccessCollection implements \Countable, \IteratorAggregate {
    /** @var array<int, CommerceCourseAccessPresentation> */
    private array $items = [];

    /** @param CommerceCourseAccessPresentation[] $items */
    public function __construct(array $items) {
        foreach ($items as $item) {
            if (!$item instanceof CommerceCourseAccessPresentation) {
                throw new \coding_exception('Invalid item in a course access enrichment collection.');
            }
            $this->items[$item->courseid] = $item;
        }
        ksort($this->items);
    }

    public function get(int $courseid): CommerceCourseAccessPresentation {
        return $this->items[$courseid] ?? CommerceCourseAccessPresentation::unknown($courseid);
    }

    /** @return array<int, CommerceCourseAccessPresentation> */
    public function all(): array {
        return $this->items;
    }

    public function to_array(): array {
        return array_map(
            static fn(CommerceCourseAccessPresentation $item): array => $item->to_array(),
            $this->items
        );
    }

    public function count(): int {
        return count($this->items);
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }
}
