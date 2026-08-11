<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\phaseh;

defined('MOODLE_INTERNAL') || die();

/** Immutable final certification report for Commerce 7.95 phase H. */
final class CommercePhaseHCertificationReport {
    /**
     * @param array<int, array<string, mixed>> $sections
     */
    public function __construct(
        public readonly bool $certified,
        public readonly array $sections
    ) {
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        $summary = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'skip' => 0];
        foreach ($this->sections as $section) {
            foreach ($section['checks'] as $check) {
                $status = strtolower((string)$check['status']);
                if (array_key_exists($status, $summary)) {
                    $summary[$status]++;
                }
            }
        }

        return [
            'phase' => '7.95H7',
            'certified' => $this->certified,
            'summary' => $summary,
            'sections' => $this->sections,
        ];
    }
}
