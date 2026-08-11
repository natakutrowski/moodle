<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\switching;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\runtime\CommerceShadowRuntimeHook;
use local_subscriptions\payment\dto\InternalEvent;

final class CommerceRuntimeDispatcher {
    public function __construct(
        private readonly ?CommerceRuntimeConfiguration $configuration = null,
        private readonly ?CommerceNativeRuntimeExecutor $native = null,
        private readonly mixed $fallbackLogger = null
    ) {
    }

    public function checkout_completed(
        InternalEvent $event,
        string $entrypoint,
        callable $legacy,
        ?callable $native = null
    ): void {
        $configuration = $this->configuration ?? new CommerceRuntimeConfiguration();
        $mode = $configuration->get_mode();

        if ($mode === CommerceRuntimeMode::LEGACY) {
            $legacy();
            return;
        }
        if ($mode === CommerceRuntimeMode::SHADOW) {
            $legacy();
            CommerceShadowRuntimeHook::after_checkout_completed($event, $entrypoint);
            return;
        }

        try {
            if ($native !== null) {
                $native();
                return;
            }

            ($this->native ?? new CommerceNativeRuntimeExecutor())->execute($event, $entrypoint);
        } catch (\Throwable $exception) {
            if (!$configuration->native_fallback_enabled()) {
                throw $exception;
            }
            $message =
                '[local_subscriptions] Native Commerce runtime fallback to Legacy: '
                . $exception->getMessage();
            if (is_callable($this->fallbackLogger)) {
                ($this->fallbackLogger)($message, $exception);
            } else {
                error_log($message);
            }

            $legacy();
        }
    }
}
