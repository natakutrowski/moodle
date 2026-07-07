<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\CommandMenuItem;
use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandContextAwareProviderInterface;
use local_subscriptions\commandcenter\repositories\SubscriptionSearchRepository;
use local_subscriptions\commandcenter\actions\CommandActionKeys;
use local_subscriptions\subscription_config;
use moodle_url;

final class SubscriptionProvider implements CommandProviderInterface, CommandContextAwareProviderInterface {

    private SubscriptionSearchRepository $repository;

    public function __construct(?SubscriptionSearchRepository $repository = null) {
        $this->repository = $repository ?? new SubscriptionSearchRepository();
    }

    public function search_with_context(CommandContext $context, int $limit = 10): array {
        return $this->search($context->query(), $limit);
    }

    public function search(CommandQuery $query, int $limit = 10): array {
        if ($query->has_direct_entity() && !$query->is_direct_entity('subscription')) {
            return [];
        }

        if ($query->is_direct_entity('subscription')) {
            $subscription = $this->repository->find_by_id((int)$query->id());
            return $subscription ? $this->format_results([$subscription], (string)$query->id(), 120) : [];
        }

        $text = trim($query->text());

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        return $this->format_results(
            $this->repository->search($text, $limit),
            $text
        );
    }

    private function format_results(array $subscriptions, string $text, int $basescore = 0): array {
        $results = [];

        foreach ($subscriptions as $subscription) {
            $fullname = fullname($subscription);
            $score = $basescore ?: $this->score($text, $subscription, $fullname);

            $subtitle = get_string('command_center_subscription_subtitle', 'local_subscriptions', [
                'plan' => $subscription->planname,
                'status' => strtoupper($subscription->status),
                'period' => AdminFormatter::period(
                    (int)$subscription->start_date,
                    (int)$subscription->end_date
                ),
            ]);

            $results[] = CommandResult::create()
                ->icon(CommandIcons::SUBSCRIPTION)
                ->type(CommandTypes::subscription())
                ->group('subscriptions', get_string('command_center_group_subscriptions', 'local_subscriptions'))
                ->action_label(get_string('command_center_action_view', 'local_subscriptions'))
                ->shortcut('subscription:' . (int)$subscription->id)
                ->title($fullname)
                ->subtitle($subtitle)
                ->url((new moodle_url(subscription_config::user_subscription_view_page(), [
                    'id' => $subscription->id,
                ]))->out(false))
                ->action(CommandActionKeys::OPEN_SUBSCRIPTION, [
                    'subscriptionid' => (int)$subscription->id,
                ])
                ->menu_item(
                    CommandMenuItem::create()
                        ->icon('👁️')
                        ->label(get_string('command_menu_subscription_open', 'local_subscriptions'))
                        ->action(CommandActionKeys::OPEN_SUBSCRIPTION, [
                            'subscriptionid' => (int)$subscription->id,
                        ])
                        ->shortcut('O')
                )
                ->score($score)
                ->meta('provider', 'subscriptions')
                ->meta('entity', 'subscription')
                ->meta('id', (int)$subscription->id)
                ->to_array();
        }

        return $results;
    }

    private function score(string $query, object $subscription, string $fullname): int {
        return CommandScorer::best(
            CommandScorer::id($query, (int)$subscription->id),
            CommandScorer::email($query, (string)$subscription->email),
            CommandScorer::fullname($query, $fullname),
            CommandScorer::plan($query, (string)$subscription->planname),
            CommandScorer::transaction($query, (string)$subscription->transactionid),
            CommandScorer::status($query, (string)$subscription->status)
        );
    }
}