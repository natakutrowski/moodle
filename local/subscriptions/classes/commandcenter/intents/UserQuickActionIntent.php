<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\actions\CommandActionKeys;

final class UserQuickActionIntent extends AbstractCommandIntent {

    public function match(CommandContext $context): array {
        $tokens = $context->tokens();

        $hasuserentity = $this->is_entity($tokens, CommandEntityAliases::USER);
        $hasuseraction = $this->has_alias($tokens, CommandIntentAliases::EMAIL) ||
            $this->has_alias($tokens, CommandIntentAliases::NOTE) ||
            $this->has_alias($tokens, CommandIntentAliases::RESET);

        if (!$hasuserentity && !$hasuseraction) {
            return [];
        }
        
        $userid = $this->first_int($tokens);

        if ($userid <= 0) {
            return [];
        }

        $matches = [];

        if ($this->has_alias($tokens, CommandIntentAliases::EMAIL)) {
            $matches[] = $this->build(
                CommandIcons::EMAIL,
                get_string('command_intent_email_user', 'local_subscriptions'),
                CommandActionKeys::USER_EMAIL,
                ['userid' => $userid],
                $userid,
                2200
            );
        }

        if ($this->has_alias($tokens, CommandIntentAliases::NOTE)) {
            $matches[] = $this->build(
                CommandIcons::NOTE,
                get_string('command_intent_note_user', 'local_subscriptions'),
                CommandActionKeys::USER_ADD_NOTE,
                ['userid' => $userid],
                $userid,
                2200
            );
        }

        if ($this->has_alias($tokens, CommandIntentAliases::RESET)) {
            $result = $this->build(
                CommandIcons::RESET_PASSWORD,
                get_string('command_intent_reset_user', 'local_subscriptions'),
                CommandActionKeys::USER_RESET_PASSWORD,
                ['userid' => $userid],
                $userid,
                2300
            );

            $matches[] = $result;
        }

        return $matches;
    }

    private function build(
        string $icon,
        string $title,
        string $actionkey,
        array $payload,
        int $userid,
        int $score
    ): CommandIntentMatch {
        $result = CommandResult::create()
            ->icon($icon)
            ->type(CommandTypes::action())
            ->group('intent', get_string('command_center_group_intents', 'local_subscriptions'))
            ->action_label(get_string('command_center_action_execute', 'local_subscriptions'))
            ->shortcut('↵')
            ->title($title . ' #' . $userid)
            ->subtitle(get_string('command_intent_user_quick_action_subtitle', 'local_subscriptions'))
            ->url('#')
            ->action($actionkey, $payload)
            ->score($score)
            ->meta('provider', 'intent')
            ->meta('entity', 'user');

        if ($actionkey === CommandActionKeys::USER_RESET_PASSWORD) {
            $result
                ->confirmation(get_string('command_confirm_user_reset_password', 'local_subscriptions'))
                ->danger();
        }

        return CommandIntentMatch::create($result, $score);
    }
}