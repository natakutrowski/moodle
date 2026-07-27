<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** Canonical G3 comparator for Legacy observations and Native Shadow reports. */
final class CommerceShadowComparator {
    public function compare(
        CommerceLegacyFulfillmentObservation $legacy,
        CommerceShadowExecutionReport $native
    ): CommerceShadowComparison {
        if ($legacy->get_purchase_reference() !== $native->get_purchase_reference()) {
            return new CommerceShadowComparison($native->get_purchase_reference(), CommerceShadowComparison::NOT_COMPARABLE, [
                ['field' => 'purchasereference', 'legacy' => $legacy->get_purchase_reference(), 'native' => $native->get_purchase_reference()],
            ]);
        }
        if (!$legacy->is_comparable()) {
            return new CommerceShadowComparison($native->get_purchase_reference(), CommerceShadowComparison::NOT_COMPARABLE, $legacy->get_issues());
        }
        if (!$native->is_successful()) {
            return new CommerceShadowComparison($native->get_purchase_reference(), CommerceShadowComparison::SHADOW_ERROR, $native->get_errors());
        }

        $legacyexact = $this->index($legacy->get_effects(), false);
        $nativeexact = $this->index($native->get_effects(), false);
        if ($legacyexact === $nativeexact) {
            return new CommerceShadowComparison($native->get_purchase_reference(), CommerceShadowComparison::EQUAL);
        }

        $legacyequivalent = $this->index($legacy->get_effects(), true);
        $nativeequivalent = $this->index($native->get_effects(), true);
        if ($legacyequivalent === $nativeequivalent) {
            return new CommerceShadowComparison(
                $native->get_purchase_reference(),
                CommerceShadowComparison::EQUIVALENT,
                [],
                ['reason' => 'Only non-material representation differences were found.']
            );
        }

        $differences = [];
        foreach (array_unique(array_merge(array_keys($legacyequivalent), array_keys($nativeequivalent))) as $key) {
            if (($legacyequivalent[$key] ?? null) !== ($nativeequivalent[$key] ?? null)) {
                $differences[] = [
                    'effect' => $key,
                    'legacy' => $legacyequivalent[$key] ?? null,
                    'native' => $nativeequivalent[$key] ?? null,
                ];
            }
        }
        return new CommerceShadowComparison($native->get_purchase_reference(), CommerceShadowComparison::DIFFERENT, $differences);
    }

    private function index(array $effects, bool $businessonly): array {
        $indexed = [];
        foreach ($effects as $effect) {
            if (!$effect instanceof CommerceShadowEffect) {
                throw new \coding_exception('Invalid Commerce Shadow effect.');
            }
            $payload = $effect->canonical_array();
            if ($businessonly) {
                $payload['attributes'] = $this->business_attributes($payload['type'], $payload['attributes']);
            }
            $indexed[$effect->identity_key()] = $payload;
        }
        ksort($indexed);
        return $indexed;
    }

    private function business_attributes(string $type, array $attributes): array {
        return (new CommerceShadowBusinessEffectNormalizer())->normalise($type, $attributes);
    }
}
