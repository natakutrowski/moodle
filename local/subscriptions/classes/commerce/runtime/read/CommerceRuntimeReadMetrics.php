<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

/** Request-local metrics for I7 runtime reads. */
final class CommerceRuntimeReadMetrics {
    private int $reads = 0;
    private int $legacyreads = 0;
    private int $nativereads = 0;
    private int $fallbacks = 0;
    private int $missing = 0;
    private int $different = 0;
    private int $invalid = 0;
    private float $legacydurationms = 0.0;
    private float $nativedurationms = 0.0;

    public function record(CommerceRuntimeReadResult $result): void {
        $this->reads++;
        if ($result->get_source() === CommerceRuntimeReadResult::SOURCE_LEGACY) { $this->legacyreads++; }
        if ($result->get_source() === CommerceRuntimeReadResult::SOURCE_NATIVE) { $this->nativereads++; }
        if ($result->used_fallback()) { $this->fallbacks++; }
        if ($result->get_status() === CommerceRuntimeReadResult::STATUS_MISSING) { $this->missing++; }
        if ($result->get_status() === CommerceRuntimeReadResult::STATUS_DIFFERENT) { $this->different++; }
        if ($result->get_status() === CommerceRuntimeReadResult::STATUS_INVALID) { $this->invalid++; }
        $this->legacydurationms += max(0.0, $result->get_legacy_duration_ms());
        $this->nativedurationms += max(0.0, $result->get_native_duration_ms());
    }

    public function to_array(): array {
        return [
            'reads' => $this->reads,
            'legacy_reads' => $this->legacyreads,
            'native_reads' => $this->nativereads,
            'fallbacks' => $this->fallbacks,
            'missing' => $this->missing,
            'different' => $this->different,
            'invalid' => $this->invalid,
            'native_success_rate' => $this->reads > 0 ? round(($this->nativereads / $this->reads) * 100, 3) : 0.0,
            'fallback_rate' => $this->reads > 0 ? round(($this->fallbacks / $this->reads) * 100, 3) : 0.0,
            'legacy_duration_ms' => round($this->legacydurationms, 3),
            'native_duration_ms' => round($this->nativedurationms, 3),
            'legacy_average_ms' => $this->legacyreads > 0 ? round($this->legacydurationms / $this->legacyreads, 3) : 0.0,
            'native_average_ms' => $this->nativereads > 0 ? round($this->nativedurationms / $this->nativereads, 3) : 0.0,
        ];
    }
}
