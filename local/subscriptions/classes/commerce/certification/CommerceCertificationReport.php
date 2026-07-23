<?php

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/** Aggregate production-certification report. */
final class CommerceCertificationReport {
    /** @param CommerceCertificationCheck[] $checks */
    public function __construct(
        private readonly array $checks,
        private readonly int $baseline,
        private readonly int $generatedat
    ) {
    }

    public function checks(): array { return $this->checks; }
    public function baseline(): int { return $this->baseline; }
    public function generated_at(): int { return $this->generatedat; }

    public function global_status(): string {
        foreach ($this->checks as $check) {
            if ($check->status() === CommerceCertificationCheck::FAIL) {
                return 'BLOCKED';
            }
        }
        foreach ($this->checks as $check) {
            if ($check->status() === CommerceCertificationCheck::WARNING) {
                return 'READY_WITH_WARNINGS';
            }
        }
        return 'READY_FOR_PRODUCTION';
    }

    public function summary(): array {
        $summary = ['PASS' => 0, 'WARNING' => 0, 'FAIL' => 0, 'TOTAL' => count($this->checks)];
        foreach ($this->checks as $check) {
            $summary[$check->status()]++;
        }
        return $summary;
    }

    public function sections(): array {
        $sections = [];
        foreach ($this->checks as $check) {
            $sections[$check->section()][] = $check;
        }
        return $sections;
    }

    public function to_array(): array {
        return [
            'status' => $this->global_status(),
            'baseline' => $this->baseline,
            'generated_at' => $this->generatedat,
            'summary' => $this->summary(),
            'checks' => array_map(static fn(CommerceCertificationCheck $check): array => $check->to_array(), $this->checks),
        ];
    }

    public function to_markdown(): string {
        $lines = [
            '# Commerce Production Certification Report',
            '',
            '- Generated: ' . userdate($this->generatedat, '%Y-%m-%d %H:%M:%S %Z'),
            '- Baseline: ' . userdate($this->baseline, '%Y-%m-%d %H:%M:%S %Z'),
            '- Global status: **' . $this->global_status() . '**',
            '',
        ];
        foreach ($this->sections() as $section => $checks) {
            $lines[] = '## ' . $section;
            $lines[] = '';
            foreach ($checks as $check) {
                $symbol = $check->status() === 'PASS' ? '✅' : ($check->status() === 'WARNING' ? '⚠️' : '❌');
                $lines[] = '- ' . $symbol . ' **' . $check->status() . '** `' . $check->code() . '` — ' . $check->message();
            }
            $lines[] = '';
        }
        $summary = $this->summary();
        $lines[] = '## Summary';
        $lines[] = '';
        $lines[] = '- PASS: ' . $summary['PASS'];
        $lines[] = '- WARNING: ' . $summary['WARNING'];
        $lines[] = '- FAIL: ' . $summary['FAIL'];
        $lines[] = '';
        $lines[] = '## Decision';
        $lines[] = '';
        $lines[] = '**' . $this->global_status() . '**';
        $lines[] = '';
        return implode("\n", $lines);
    }
}
