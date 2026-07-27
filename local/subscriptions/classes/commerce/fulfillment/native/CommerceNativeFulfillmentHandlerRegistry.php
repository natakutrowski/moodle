<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native;

defined('MOODLE_INTERNAL') || die();

/**
 * Strict registry of Native Commerce grant fulfillment handlers.
 */
final class CommerceNativeFulfillmentHandlerRegistry {
    /** @var array<string, CommerceNativeFulfillmentHandler> */
    private array $handlers = [];

    /**
     * @param CommerceNativeFulfillmentHandler[] $handlers
     */
    public function __construct(array $handlers = []) {
        foreach ($handlers as $handler) {
            $this->register($handler);
        }
    }

    public function register(CommerceNativeFulfillmentHandler $handler): void {
        $type = self::normalize_type($handler->get_grant_type());

        if (isset($this->handlers[$type])) {
            throw new \coding_exception(
                sprintf('A Native Commerce fulfillment handler is already registered for "%s".', $type)
            );
        }

        $this->handlers[$type] = $handler;
    }

    public function supports(string $type): bool {
        return isset($this->handlers[self::normalize_type($type)]);
    }

    public function resolve(string $type): CommerceNativeFulfillmentHandler {
        $type = self::normalize_type($type);

        if (!isset($this->handlers[$type])) {
            throw new CommerceNativeFulfillmentHandlerNotFoundException($type);
        }

        return $this->handlers[$type];
    }

    /**
     * @return string[]
     */
    public function registered_types(): array {
        $types = array_keys($this->handlers);
        sort($types);
        return $types;
    }

    private static function normalize_type(string $type): string {
        $type = strtolower(trim($type));

        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $type)) {
            throw new \coding_exception('Invalid Native Commerce fulfillment grant type.');
        }

        return $type;
    }
}
