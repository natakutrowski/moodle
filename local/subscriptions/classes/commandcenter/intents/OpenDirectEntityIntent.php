<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\actions\CommandActionKeys;

final class OpenDirectEntityIntent extends AbstractCommandIntent {

    public function match(CommandContext $context): array {
        $tokens = $context->tokens();

        if (count($tokens) < 2) {
            return [];
        }

        $entity = $this->entity($tokens);
        $id = $this->first_int($tokens);

        if ($id <= 0) {
            return [];
        }

        $map = [
            CommandEntityAliases::USER => [
                'icon' => CommandIcons::USER,
                'label' => get_string('command_intent_open_user', 'local_subscriptions'),
                'action' => CommandActionKeys::OPEN_USER,
                'payloadkey' => 'userid',
            ],
            CommandEntityAliases::PURCHASE => [
                'icon' => CommandIcons::DIGITAL_PURCHASES,
                'label' => get_string('command_intent_open_purchase', 'local_subscriptions'),
                'action' => CommandActionKeys::OPEN_PURCHASE,
                'payloadkey' => 'purchaseid',
            ],
            CommandEntityAliases::PRODUCT => [
                'icon' => CommandIcons::DIGITAL_PRODUCTS,
                'label' => get_string('command_intent_open_product', 'local_subscriptions'),
                'action' => CommandActionKeys::OPEN_PRODUCT,
                'payloadkey' => 'productid',
            ],
            CommandEntityAliases::SUBSCRIPTION => [
                'icon' => CommandIcons::SUBSCRIPTIONS,
                'label' => get_string('command_intent_open_subscription', 'local_subscriptions'),
                'action' => CommandActionKeys::OPEN_SUBSCRIPTION,
                'payloadkey' => 'subscriptionid',
            ],
        ];

        if (!isset($map[$entity])) {
            return [];
        }

        $config = $map[$entity];

        $result = CommandResult::create()
            ->icon($config['icon'])
            ->type(CommandTypes::action())
            ->group('intent', get_string('command_center_group_intents', 'local_subscriptions'))
            ->action_label(get_string('command_center_action_execute', 'local_subscriptions'))
            ->shortcut('↵')
            ->title($config['label'] . ' #' . $id)
            ->subtitle(get_string('command_intent_direct_entity_subtitle', 'local_subscriptions'))
            ->url('#')
            ->action($config['action'], [
                $config['payloadkey'] => $id,
            ])
            ->score(2000)
            ->meta('provider', 'intent')
            ->meta('entity', $entity);

        return [
            CommandIntentMatch::create($result, 2000),
        ];
    }
}