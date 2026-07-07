<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandProviderRunner {

    public function run(CommandProviderInterface $provider, CommandQuery $query, int $limit): array {
        $context = CommandContext::from_command_query($query);

        try {
            if ($provider instanceof CommandContextAwareProviderInterface) {
                return $provider->search_with_context($context, $limit);
            }

            return $provider->search($query, $limit);
        } catch (\Throwable $e) {
            debugging(
                'Command Center provider failed: ' . get_class($provider) . ' - ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );

            return [];
        }
    }
}