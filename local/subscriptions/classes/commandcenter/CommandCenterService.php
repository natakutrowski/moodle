<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\providers\AdminActionProvider;
use local_subscriptions\commandcenter\providers\DigitalProductProvider;
use local_subscriptions\commandcenter\providers\DigitalPurchaseProvider;
use local_subscriptions\commandcenter\providers\SubscriptionProvider;
use local_subscriptions\commandcenter\providers\UserProvider;
use local_subscriptions\commandcenter\providers\IntelligenceProvider;
use local_subscriptions\commandcenter\providers\InboxProvider;
use local_subscriptions\commandcenter\providers\WorkItemProvider;
use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\intents\CommandIntentRegistry;

final class CommandCenterService {

    /**
     * @var CommandProviderInterface[]
     */
    private array $providers;

    private CommandProviderRunner $runner;

    public function __construct(?array $providers = null, ?CommandProviderRunner $runner = null) {
        $this->providers = $providers ?? [
            new AdminActionProvider(),
            new IntelligenceProvider(),
            new InboxProvider(),
            new WorkItemProvider(),
            new UserProvider(),
            new DigitalProductProvider(),
            new DigitalPurchaseProvider(),
            new SubscriptionProvider(),
        ];

        $this->runner = $runner ?? new CommandProviderRunner();
    }

    public function search(string $rawquery, int $limit = 20): CommandCollection {
        $query = CommandQuery::parse($rawquery);

        $context = CommandContext::from_command_query($query);
        $intentmatches = CommandIntentRegistry::resolver()->resolve($context, 5);

        if (!$query->is_action_mode() && \core_text::strlen($query->raw()) < 2) {
            return new CommandCollection($rawquery, []);
        }

        $providers = $this->providers;

        if ($query->is_action_mode()) {
            $providers = array_filter($providers, static function(CommandProviderInterface $provider): bool {
                return method_exists($provider, 'is_action_provider') && $provider->is_action_provider();
            });
        }

        $results = [];
        $perproviderlimit = max(3, (int)ceil($limit / max(1, count($providers))));

        foreach ($intentmatches as $match) {
            $results[] = $match->result()->to_array();
        }

        foreach ($providers as $provider) {
            foreach ($this->runner->run($provider, $query, $perproviderlimit) as $result) {
                $results[] = $result;
            }
        }

        usort($results, static function(array $a, array $b): int {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        return new CommandCollection($rawquery, array_slice($results, 0, $limit));
    }
}