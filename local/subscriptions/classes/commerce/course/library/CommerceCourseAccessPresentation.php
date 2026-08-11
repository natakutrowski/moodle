<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\library;

defined('MOODLE_INTERNAL') || die();

/** Immutable Commerce enrichment for one Moodle course enrolment. */
final class CommerceCourseAccessPresentation {
    public function __construct(
        public readonly int $courseid,
        public readonly string $origin,
        public readonly CommerceCourseAccessPeriod $period,
        public readonly ?string $purchaseurl = null,
        public readonly ?string $commercialreference = null,
        public readonly ?string $productsku = null,
        public readonly ?string $accesslevel = null,
        public readonly string $source = 'none',
        public readonly array $sources = []
    ) {
        if ($courseid <= 0) {
            throw new \coding_exception('A course access presentation requires a positive course identifier.');
        }
        if (!in_array($origin, CommerceCourseAccessOrigin::all(), true)) {
            throw new \coding_exception('Invalid course access business origin.');
        }
    }

    public static function unknown(int $courseid): self {
        return new self(
            $courseid,
            CommerceCourseAccessOrigin::UNKNOWN,
            CommerceCourseAccessPeriod::unknown()
        );
    }

    public function to_array(): array {
        return [
            'courseid' => $this->courseid,
            'origin' => $this->origin,
            'period' => $this->period->to_array(),
            'purchaseurl' => $this->purchaseurl,
            'commercialreference' => $this->commercialreference,
            'productsku' => $this->productsku,
            'accesslevel' => $this->accesslevel,
            'source' => $this->source,
            'sources' => $this->sources,
        ];
    }
}
