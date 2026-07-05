<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandProviderRunner {

    public function run(CommandProviderInterface $provider, CommandQuery $query, int $limit): array {
        try {
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