<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

/** In-process metrics collector for native Commerce shadow reads. */
final class CommerceNativeReadMetrics {
    private int $reads = 0;
    private int $equal = 0;
    private int $missing = 0;
    private int $different = 0;
    private int $invalid = 0;
    private float $legacydurationms = 0.0;
    private float $nativedurationms = 0.0;

    public function record(string $status, float $legacydurationms, float $nativedurationms): void {
        $this->reads++;
        $this->legacydurationms += max(0.0, $legacydurationms);
        $this->nativedurationms += max(0.0, $nativedurationms);
        match ($status) {
            CommerceNativeReadResult::STATUS_EQUAL => $this->equal++,
            CommerceNativeReadResult::STATUS_MISSING_NATIVE => $this->missing++,
            CommerceNativeReadResult::STATUS_DIFFERENT => $this->different++,
            CommerceNativeReadResult::STATUS_INVALID_LEGACY => $this->invalid++,
            default => null,
        };
    }

    public function to_array(): array {
        return [
            'reads' => $this->reads,
            'equal' => $this->equal,
            'missing' => $this->missing,
            'different' => $this->different,
            'invalid' => $this->invalid,
            'legacy_duration_ms' => round($this->legacydurationms, 3),
            'native_duration_ms' => round($this->nativedurationms, 3),
            'legacy_average_ms' => $this->reads > 0 ? round($this->legacydurationms / $this->reads, 3) : 0.0,
            'native_average_ms' => $this->reads > 0 ? round($this->nativedurationms / $this->reads, 3) : 0.0,
        ];
    }
}
