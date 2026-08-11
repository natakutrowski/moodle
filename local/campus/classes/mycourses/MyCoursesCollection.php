<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

/** Ordered collection used by the My courses controller and page model. */
final class MyCoursesCollection implements \Countable, \IteratorAggregate {
    /** @var array<int, MyCoursePresentation> */
    private array $items = [];

    /** @param MyCoursePresentation[] $items */
    public function __construct(array $items) {
        foreach ($items as $item) {
            if (!$item instanceof MyCoursePresentation) {
                throw new \coding_exception('Invalid item in My courses collection.');
            }
            $this->items[$item->courseid()] = $item;
        }
    }

    /** @return array<int, MyCoursePresentation> */
    public function all(): array {
        return $this->items;
    }

    /** @return array<int, \stdClass> */
    public function course_records(): array {
        $records = [];
        foreach ($this->items as $courseid => $item) {
            $records[$courseid] = $item->course;
        }
        return $records;
    }

    /** @return array<int, MyCoursePresentation[]> */
    public function grouped_by_category(): array {
        $groups = [];
        foreach ($this->items as $item) {
            $groups[$item->categoryid()][] = $item;
        }
        return $groups;
    }

    /** @return array<int, float> */
    public function progress_map(): array {
        $result = [];
        foreach ($this->items as $courseid => $item) {
            if ($item->progress !== null) {
                $result[$courseid] = $item->progress;
            }
        }
        return $result;
    }

    /** @return array<int, array{done:int,total:int}> */
    public function progress_counts(): array {
        $result = [];
        foreach ($this->items as $courseid => $item) {
            if ($item->completedactivities !== null && $item->totalactivities !== null) {
                $result[$courseid] = [
                    'done' => $item->completedactivities,
                    'total' => $item->totalactivities,
                ];
            }
        }
        return $result;
    }

    /** @return int[] */
    public function completed_course_ids(): array {
        return array_values(array_map(
            static fn(MyCoursePresentation $item): int => $item->courseid(),
            array_filter($this->items, static fn(MyCoursePresentation $item): bool => $item->completed)
        ));
    }

    /** @return array<int, bool> */
    public function trial_course_map(): array {
        $result = [];
        foreach ($this->items as $courseid => $item) {
            if ($item->trial) {
                $result[$courseid] = true;
            }
        }
        return $result;
    }

    public function count(): int {
        return count($this->items);
    }

    public function getIterator(): \Traversable {
        return new \ArrayIterator($this->items);
    }
}
