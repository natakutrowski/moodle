<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\entitlement\application;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry of Native Commerce entitlement application handlers.
 */
final class CommerceEntitlementApplicationHandlerRegistry {
    /** @var array<string, CommerceEntitlementApplicationHandler> */
    private array $handlers = [];

    /**
     * @param CommerceEntitlementApplicationHandler[] $handlers
     */
    public function __construct(array $handlers = []) {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    public function register(CommerceEntitlementApplicationHandler $handler): void {
        $type = strtolower(trim($handler->get_type()));

        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) {
            throw new \coding_exception('Invalid Commerce entitlement application handler type.');
        }

        $this->handlers[$type] = $handler;
    }

    public function supports(string $type): bool {
        return isset($this->handlers[strtolower(trim($type))]);
    }

    public function resolve(string $type): CommerceEntitlementApplicationHandler {
        $type = strtolower(trim($type));

        if (!$this->supports($type)) {
            throw new \RuntimeException(
                sprintf('No Commerce entitlement application handler is registered for "%s".', $type)
            );
        }

        return $this->handlers[$type];
    }
}
