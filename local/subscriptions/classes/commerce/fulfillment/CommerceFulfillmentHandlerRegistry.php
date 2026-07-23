<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry of Commerce fulfillment handlers.
 */
final class CommerceFulfillmentHandlerRegistry {

    /**
     * @var array<string,CommerceFulfillmentHandler>
     */
    private array $handlers = [];

    /**
     * @param CommerceFulfillmentHandler[] $handlers
     */
    public function __construct(
        array $handlers = []
    ) {
        foreach ($handlers as $handler) {
            if (!$handler instanceof CommerceFulfillmentHandler) {
                throw new \coding_exception(
                    'Invalid Commerce fulfillment handler.'
                );
            }

            $this->register($handler);
        }
    }

    public function register(
        CommerceFulfillmentHandler $handler
    ): void {
        $key = $this->normalise_key(
            $handler->get_key()
        );

        if (isset($this->handlers[$key])) {
            throw new \coding_exception(
                'A Commerce fulfillment handler is already registered for key: '
                . $key
            );
        }

        $this->handlers[$key] = $handler;
    }

    public function has(
        string $key
    ): bool {
        return isset(
            $this->handlers[
                $this->normalise_key($key)
            ]
        );
    }

    public function get(
        string $key
    ): CommerceFulfillmentHandler {
        $key = $this->normalise_key(
            $key
        );

        if (!isset($this->handlers[$key])) {
            throw new CommerceFulfillmentHandlerNotFoundException(
                'No Commerce fulfillment handler is registered for key: '
                . $key
            );
        }

        return $this->handlers[$key];
    }

    public function resolve(
        CommerceFulfillmentOperation $operation
    ): CommerceFulfillmentHandler {
        if ($this->has($operation->get_key())) {
            $handler = $this->get(
                $operation->get_key()
            );

            if ($handler->supports($operation)) {
                return $handler;
            }
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($operation)) {
                return $handler;
            }
        }

        throw new CommerceFulfillmentHandlerNotFoundException(
            sprintf(
                'No Commerce fulfillment handler supports operation "%s" with key "%s".',
                $operation->get_reference(),
                $operation->get_key()
            )
        );
    }

    /**
     * @return string[]
     */
    public function keys(): array {
        return array_keys(
            $this->handlers
        );
    }

    private function normalise_key(
        string $key
    ): string {
        $key = strtolower(
            trim($key)
        );

        if (
            $key === ''
            || !preg_match(
                '/^[a-z][a-z0-9_]*$/',
                $key
            )
        ) {
            throw new \coding_exception(
                'Invalid Commerce fulfillment handler key: '
                . $key
            );
        }

        return $key;
    }
}