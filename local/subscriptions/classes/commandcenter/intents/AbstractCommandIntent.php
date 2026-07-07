<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

abstract class AbstractCommandIntent implements CommandIntentInterface {

    protected function first_token(array $tokens): string {
        return $tokens[0] ?? '';
    }

    protected function second_token(array $tokens): string {
        return $tokens[1] ?? '';
    }

    protected function token_int(string $token): int {
        return ctype_digit($token) ? (int)$token : 0;
    }

    protected function has_verb(array $tokens, array $verbs): bool {
        foreach ($tokens as $token) {
            if (in_array($token, $verbs, true)) {
                return true;
            }
        }

        return false;
    }

    protected function has_alias(array $tokens, array $aliases): bool {
        return CommandIntentAliases::contains($tokens, $aliases);
    }

    protected function first_int(array $tokens): int {
        return CommandIntentAliases::first_int($tokens);
    }

    protected function entity(array $tokens): ?string {
        return CommandEntityAliases::first_entity($tokens);
    }

    protected function is_entity(array $tokens, string $entity): bool {
        return CommandEntityAliases::contains($tokens, $entity);
    }    

}