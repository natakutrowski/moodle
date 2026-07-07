<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;

final class CommandIntentResolver {

    /** @var CommandIntentInterface[] */
    private array $intents;

    /**
     * @param CommandIntentInterface[] $intents
     */
    public function __construct(array $intents) {
        $this->intents = $intents;
    }

    /**
     * @return CommandIntentMatch[]
     */
    public function resolve(CommandContext $context, int $limit = 5): array {
        if (!$context->is_action_mode()) {
            return [];
        }

        $matches = [];

        foreach ($this->intents as $intent) {
            foreach ($intent->match($context) as $match) {
                $matches[] = $match;
            }
        }

        usort($matches, static function(CommandIntentMatch $a, CommandIntentMatch $b): int {
            return $b->score() <=> $a->score();
        });

        return array_slice($matches, 0, $limit);
    }
}