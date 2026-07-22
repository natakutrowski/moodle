<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandContextAwareProviderInterface;
use local_subscriptions\commandcenter\actions\CommandActionKeys;
use local_subscriptions\commandcenter\repositories\AdminActionRepository;

final class AdminActionProvider implements CommandProviderInterface, CommandContextAwareProviderInterface {

    private AdminActionRepository $repository;

    public function __construct(?AdminActionRepository $repository = null) {
        $this->repository = $repository ?? new AdminActionRepository();
    }

    public function is_action_provider(): bool {
        return true;
    }

    public function search_with_context(CommandContext $context, int $limit = 10): array {
        return $this->search($context->query(), $limit);
    }

    public function search(CommandQuery $query, int $limit = 10): array {
        if ($query->has_direct_entity()) {
            return [];
        }

        $text = trim($query->text());

        if ($query->is_action_mode() && $text === '') {
            return $this->all_actions($limit);
        }

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        $results = [];

        foreach ($this->repository->all() as $action) {
            $score = $this->score($text, $action);

            if ($query->is_action_mode()) {
                $score += 25;
            }

            if ($score <= 0) {
                continue;
            }

            $results[] = $this->build_result($action, $score);
        }

        usort($results, static function(array $a, array $b): int {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        return array_slice($results, 0, $limit);
    }

    private function all_actions(int $limit): array {
        $results = [];

        foreach ($this->repository->all() as $action) {
            $results[] = $this->build_result($action, 80);
        }

        return array_slice($results, 0, $limit);
    }

    private function build_result(
        array $action,
        int $score
    ): array {
        $result = CommandResult::create()
            ->icon($action['icon'])
            ->type(CommandTypes::action())
            ->group(
                'actions',
                get_string(
                    'command_center_group_actions',
                    'local_subscriptions'
                )
            )
            ->action_label(
                $action['actionlabel']
                    ?? get_string(
                        'command_center_action_open',
                        'local_subscriptions'
                    )
            )
            ->shortcut('>')
            ->title($action['title'])
            ->subtitle($action['subtitle'])
            ->url($action['url'])
            ->action(
                $action['actionkey']
                    ?? CommandActionKeys::OPEN_URL,
                $action['payload'] ?? [
                    'url' => $action['url'],
                ]
            )
            ->score($score)
            ->meta(
                'provider',
                'admin_actions'
            )
            ->meta(
                'entity',
                'action'
            );

        if (
            !empty(
                $action['confirmation']
            )
        ) {
            $result->confirmation(
                (string)$action['confirmation']
            );
        }

        if (
            !empty(
                $action['danger']
            )
        ) {
            $result->danger();
        }

        return $result->to_array();
    }

    private function score(string $query, array $action): int {
        return CommandScorer::best(
            CommandScorer::exact_or_prefix($query, $action['title'], 110, 100, 75),
            CommandScorer::keywords($query, $action['keywords'], 85)
        );
    }
}