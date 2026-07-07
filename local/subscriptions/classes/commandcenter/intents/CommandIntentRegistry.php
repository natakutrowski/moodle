<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

final class CommandIntentRegistry {

    /**
     * @return CommandIntentInterface[]
     */
    public static function all(): array {
        return [
            new CommandSuggestionIntent(),
            new OpenDirectEntityIntent(),
            new UserQuickActionIntent(),
            new PurchaseQuickActionIntent(),
        ];
    }

    public static function resolver(): CommandIntentResolver {
        return new CommandIntentResolver(self::all());
    }
}