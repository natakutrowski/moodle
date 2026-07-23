<?php

namespace local_subscriptions\commerce\purchase\handler;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\CommercePurchaseRequestItem;

/**
 * Registry of business PurchaseHandlers.
 */
final class CommercePurchaseHandlerRegistry {

    /**
     * @var array<string,CommercePurchaseHandler>
     */
    private array $handlers = [];

    /**
     * @param CommercePurchaseHandler[] $handlers
     */
    public function __construct(
        array $handlers = []
    ) {
        foreach ($handlers as $handler) {
            if (!$handler instanceof CommercePurchaseHandler) {
                throw new \coding_exception(
                    'The Commerce handler registry received an invalid handler.'
                );
            }

            $this->register($handler);
        }
    }

    public function register(
        CommercePurchaseHandler $handler
    ): void {
        $key = $this->normalise_key(
            $handler->get_key()
        );

        if (isset($this->handlers[$key])) {
            throw new CommercePurchaseHandlerConflictException(
                'A Commerce PurchaseHandler is already registered for key: '
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
    ): CommercePurchaseHandler {
        $key = $this->normalise_key($key);

        if (!isset($this->handlers[$key])) {
            throw new CommercePurchaseHandlerNotFoundException(
                'No Commerce PurchaseHandler is registered for key: '
                . $key
            );
        }

        return $this->handlers[$key];
    }

    public function resolve(
        CommercePurchaseRequestItem $item
    ): CommercePurchaseHandler {
        $matches = [];

        foreach ($this->handlers as $handler) {
            if ($handler->supports($item)) {
                $matches[] = $handler;
            }
        }

        if ($matches === []) {
            throw new CommercePurchaseHandlerNotFoundException(
                sprintf(
                    'No Commerce PurchaseHandler supports item "%s".',
                    $item->get_item()->get_reference()
                )
            );
        }

        if (count($matches) > 1) {
            $keys = array_map(
                static fn(
                    CommercePurchaseHandler $handler
                ): string => $handler->get_key(),
                $matches
            );

            throw new CommercePurchaseHandlerConflictException(
                sprintf(
                    'Multiple Commerce PurchaseHandlers support item "%s": %s',
                    $item->get_item()->get_reference(),
                    implode(', ', $keys)
                )
            );
        }

        return $matches[0];
    }

    /**
     * @return CommercePurchaseHandler[]
     */
    public function all(): array {
        return array_values(
            $this->handlers
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
                'Invalid Commerce PurchaseHandler key: '
                . $key
            );
        }

        return $key;
    }
}