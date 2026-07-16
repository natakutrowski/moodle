<?php

namespace local_subscriptions\crm\success\signals;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\contracts\SuccessSignalRuleInterface;

/**
 * Registry of Customer Success signal rules.
 */
final class SuccessSignalRuleRegistry {

    /**
     * @var array<string,SuccessSignalRuleInterface>
     */
    private array $rules = [];

    /**
     * @param SuccessSignalRuleInterface[] $rules
     */
    public function __construct(
        array $rules = []
    ) {
        foreach ($rules as $rule) {
            $this->register($rule);
        }
    }

    public function register(
        SuccessSignalRuleInterface $rule
    ): void {
        $key = $rule->key();

        if (
            preg_match(
                '/^[a-z][a-z0-9_]{1,99}$/',
                $key
            ) !== 1
        ) {
            throw new \InvalidArgumentException(
                'Invalid Customer Success signal rule key.'
            );
        }

        if (isset($this->rules[$key])) {
            throw new \InvalidArgumentException(
                'Duplicate Customer Success signal rule key: ' . $key
            );
        }

        $this->rules[$key] = $rule;
    }

    public function get(
        string $rulekey
    ): ?SuccessSignalRuleInterface {
        return $this->rules[$rulekey] ?? null;
    }

    /**
     * @return SuccessSignalRuleInterface[]
     */
    public function all(): array {
        return array_values($this->rules);
    }

    /**
     * @return string[]
     */
    public function keys(): array {
        return array_keys($this->rules);
    }
}