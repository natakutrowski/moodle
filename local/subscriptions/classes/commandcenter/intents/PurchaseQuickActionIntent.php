<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\actions\CommandActionKeys;

final class PurchaseQuickActionIntent extends AbstractCommandIntent {

    public function match(CommandContext $context): array {
        $tokens = $context->tokens();

        $haspurchaseentity = $this->is_entity($tokens, CommandEntityAliases::PURCHASE);
        $haspurchaseaction = $this->has_alias($tokens, CommandIntentAliases::RESEND) ||
            $this->has_alias($tokens, CommandIntentAliases::CHECK);

        if (!$haspurchaseentity && !$haspurchaseaction) {
            return [];
        }

        $purchaseid = $this->first_int($tokens);

        if ($purchaseid <= 0) {
            return [];
        }

        $matches = [];

        if ($this->has_alias($tokens, CommandIntentAliases::RESEND)) {
            $matches[] = $this->build(
                CommandIcons::RESEND_EMAIL,
                get_string('command_intent_resend_purchase_email', 'local_subscriptions'),
                CommandActionKeys::PURCHASE_RESEND_EMAIL,
                ['purchaseid' => $purchaseid],
                $purchaseid,
                2300,
                true
            );
        }

        if ($this->has_alias($tokens, CommandIntentAliases::CHECK)) {
            $matches[] = $this->build(
                CommandIcons::SEARCH,
                get_string('command_intent_check_purchase', 'local_subscriptions'),
                CommandActionKeys::PURCHASE_CHECK_PROVIDER,
                ['purchaseid' => $purchaseid],
                $purchaseid,
                2200,
                false
            );
        }

        return $matches;
    }

    private function build(
        string $icon,
        string $title,
        string $actionkey,
        array $payload,
        int $purchaseid,
        int $score,
        bool $confirmation
    ): CommandIntentMatch {
        $result = CommandResult::create()
            ->icon($icon)
            ->type(CommandTypes::action())
            ->group('intent', get_string('command_center_group_intents', 'local_subscriptions'))
            ->action_label(get_string('command_center_action_execute', 'local_subscriptions'))
            ->shortcut('↵')
            ->title($title . ' #' . $purchaseid)
            ->subtitle(get_string('command_intent_purchase_quick_action_subtitle', 'local_subscriptions'))
            ->url('#')
            ->action($actionkey, $payload)
            ->score($score)
            ->meta('provider', 'intent')
            ->meta('entity', 'purchase');

        if ($confirmation) {
            $result->confirmation(get_string('command_confirm_purchase_resend_email', 'local_subscriptions'));
        }

        return CommandIntentMatch::create($result, $score);
    }
}