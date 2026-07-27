<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\dualwrite\CommerceDualWriteResult;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

/**
 * Starts Shadow validation after a successful Native projection.
 *
 * This boundary is shared by webhook, browser-return, repair and admin paths,
 * unlike EventRouter which is not used by every historical checkout entrypoint.
 */
final class CommerceDualWriteShadowObserver {
    public static function after_synchronise(CommerceDualWriteResult $result, string $trigger): void {
        if (!$result->is_successful()) {
            return;
        }

        if ((string)get_config('local_subscriptions', 'commerce_runtime_mode') !== CommerceRuntimeMode::SHADOW) {
            return;
        }

        if (!(bool)get_config('local_subscriptions', 'commerce_fulfillment_shadow_enabled')) {
            return;
        }

        if (!CommerceShadowTriggerPolicy::should_observe($result->get_family(), $trigger)) {
            return;
        }

        try {
            $reference = self::resolve_reference($result->get_family(), $result->get_legacy_id());
            if ($reference === null) {
                debugging(
                    'Commerce Shadow skipped after dual-write: Native purchase reference was not resolved.',
                    DEBUG_DEVELOPER
                );
                return;
            }

            $context = CommerceShadowTriggerContext::from_dualwrite($result->get_family(), $trigger);
            CommerceShadowRuntimeHook::run_projected_purchase(
                $reference,
                $context->get_source(),
                $context->get_entrypoint()
            );
        } catch (\Throwable $exception) {
            debugging(
                'Commerce Shadow dual-write observer error [' . get_class($exception) . ']: '
                    . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }

    private static function resolve_reference(string $family, int $legacyid): ?string {
        global $DB;

        if ($legacyid <= 0) {
            return null;
        }

        $record = $DB->get_record(
            'local_subscriptions_commerce_purchase',
            [
                'legacyfamily' => strtolower(trim($family)),
                'legacyid' => $legacyid,
            ],
            'reference',
            IGNORE_MISSING
        );

        if (!$record) {
            return null;
        }

        $reference = trim((string)$record->reference);
        return $reference !== '' ? $reference : null;
    }

}