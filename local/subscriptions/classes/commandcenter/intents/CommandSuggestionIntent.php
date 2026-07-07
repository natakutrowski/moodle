<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\actions\CommandActionKeys;

final class CommandSuggestionIntent extends AbstractCommandIntent {

    public function match(CommandContext $context): array {
        $tokens = $context->tokens();

        if (!$context->is_action_mode()) {
            return [];
        }

        if (count($tokens) > 2) {
            return [];
        }

        $text = $context->normalized_text();

        if ($text === '' || $text === '>') {
            return $this->default_suggestions();
        }

        return $this->filtered_suggestions($tokens);
    }

    private function default_suggestions(): array {
        return [
            $this->suggest(
                CommandIcons::EMAIL,
                get_string('command_suggestion_email_user_title', 'local_subscriptions'),
                get_string('command_suggestion_email_user_subtitle', 'local_subscriptions'),
                '> email  ',
                900
            ),
            $this->suggest(
                CommandIcons::NOTE,
                get_string('command_suggestion_note_user_title', 'local_subscriptions'),
                get_string('command_suggestion_note_user_subtitle', 'local_subscriptions'),
                '> note  ',
                890
            ),
            $this->suggest(
                CommandIcons::RESEND_EMAIL,
                get_string('command_suggestion_resend_purchase_title', 'local_subscriptions'),
                get_string('command_suggestion_resend_purchase_subtitle', 'local_subscriptions'),
                '> resend  ',
                880
            ),
            $this->suggest(
                CommandIcons::SEARCH,
                get_string('command_suggestion_check_purchase_title', 'local_subscriptions'),
                get_string('command_suggestion_check_purchase_subtitle', 'local_subscriptions'),
                '> check  ',
                870
            ),
        ];
    }

    private function filtered_suggestions(array $tokens): array {
        $matches = [];

        foreach ($this->suggestion_config() as $suggestion) {
            if ($this->matches_tokens($tokens, $suggestion['aliases'])) {
                $matches[] = $this->suggest(
                    $suggestion['icon'],
                    $suggestion['title'],
                    $suggestion['subtitle'],
                    $suggestion['example'],
                    $suggestion['score']
                );
            }
        }

        return $matches;
    }

    private function suggestion_config(): array {
        return [
            [
                'icon' => CommandIcons::EMAIL,
                'title' => get_string('command_suggestion_email_user_title', 'local_subscriptions'),
                'subtitle' => get_string('command_suggestion_email_user_subtitle', 'local_subscriptions'),
                'example' => '> email user 12',
                'score' => 900,
                'aliases' => CommandIntentAliases::EMAIL,
            ],
            [
                'icon' => CommandIcons::NOTE,
                'title' => get_string('command_suggestion_note_user_title', 'local_subscriptions'),
                'subtitle' => get_string('command_suggestion_note_user_subtitle', 'local_subscriptions'),
                'example' => '> note user 12',
                'score' => 890,
                'aliases' => CommandIntentAliases::NOTE,
            ],
            [
                'icon' => CommandIcons::RESET_PASSWORD,
                'title' => get_string('command_suggestion_reset_user_title', 'local_subscriptions'),
                'subtitle' => get_string('command_suggestion_reset_user_subtitle', 'local_subscriptions'),
                'example' => '> reset user 12',
                'score' => 880,
                'aliases' => CommandIntentAliases::RESET,
            ],
            [
                'icon' => CommandIcons::RESEND_EMAIL,
                'title' => get_string('command_suggestion_resend_purchase_title', 'local_subscriptions'),
                'subtitle' => get_string('command_suggestion_resend_purchase_subtitle', 'local_subscriptions'),
                'example' => '> resend purchase 7',
                'score' => 870,
                'aliases' => CommandIntentAliases::RESEND,
            ],
            [
                'icon' => CommandIcons::SEARCH,
                'title' => get_string('command_suggestion_check_purchase_title', 'local_subscriptions'),
                'subtitle' => get_string('command_suggestion_check_purchase_subtitle', 'local_subscriptions'),
                'example' => '> check purchase 7',
                'score' => 860,
                'aliases' => CommandIntentAliases::CHECK,
            ],
        ];
    }

    private function matches_tokens(array $tokens, array $aliases): bool {
        foreach ($tokens as $token) {
            foreach ($aliases as $alias) {
                $alias = CommandContext::normalize_text($alias);

                if ($token !== '' && strpos($alias, $token) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function suggest(
        string $icon,
        string $title,
        string $subtitle,
        string $example,
        int $score
    ): CommandIntentMatch {
        $result = CommandResult::create()
            ->icon($icon)
            ->type(CommandTypes::action())
            ->group('intent', get_string('command_center_group_intents', 'local_subscriptions'))
            ->action_label(get_string('command_center_action_suggestion', 'local_subscriptions'))
            ->shortcut($example)
            ->title($title)
            ->subtitle($subtitle)
            ->url('#')
            ->fill_query($example)
            ->score($score)
            ->meta('provider', 'intent')
            ->meta('entity', 'suggestion');

        return CommandIntentMatch::create($result, $score);
    }
}