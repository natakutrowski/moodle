<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandIcons;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\CommandMenuItem;
use local_subscriptions\commandcenter\CommandContext;
use local_subscriptions\commandcenter\CommandContextAwareProviderInterface;
use local_subscriptions\commandcenter\repositories\UserSearchRepository;
use local_subscriptions\commandcenter\actions\CommandActionKeys;
use local_subscriptions\subscription_config;
use moodle_url;

final class UserProvider implements CommandProviderInterface, CommandContextAwareProviderInterface {

    private UserSearchRepository $repository;

    public function __construct(?UserSearchRepository $repository = null) {
        $this->repository = $repository ?? new UserSearchRepository();
    }

    public function search_with_context(CommandContext $context, int $limit = 10): array {
        return $this->search($context->query(), $limit);
    }

    public function search(CommandQuery $query, int $limit = 10): array {
        if ($query->has_direct_entity() && !$query->is_direct_entity('user')) {
            return [];
        }

        if ($query->is_direct_entity('user')) {
            $user = $this->repository->find_by_id((int)$query->id());
            return $user ? $this->format_results([$user], (string)$query->id(), 120) : [];
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

    private function format_results(array $users, string $text, int $basescore = 0): array {
        $results = [];

        foreach ($users as $user) {
            $fullname = fullname($user);
            $score = $basescore ?: $this->score($text, $user, $fullname);

            $subtitleparts = [];

            if (!empty($user->email)) {
                $subtitleparts[] = $user->email;
            }

            if (!empty($user->username)) {
                $subtitleparts[] = '@' . $user->username;
            }

            if (!empty($user->suspended)) {
                $subtitleparts[] = get_string('command_center_user_suspended', 'local_subscriptions');
                $score -= 20;
            }

            $results[] = CommandResult::create()
                ->icon(CommandIcons::USER)
                ->type(CommandTypes::user())
                ->group('users', get_string('command_center_group_users', 'local_subscriptions'))
                ->action_label(get_string('command_center_action_view', 'local_subscriptions'))
                ->shortcut('user:' . (int)$user->id)
                ->title($fullname)
                ->subtitle(implode(' · ', $subtitleparts))
                ->url((new moodle_url(subscription_config::admin_user_view_page(), [
                    'id' => $user->id,
                ]))->out(false))
                ->action(CommandActionKeys::OPEN_USER, [
                    'userid' => (int)$user->id,
                ])
                ->menu_item(
                    CommandMenuItem::create()
                        ->icon('✉️')
                        ->label(get_string('command_menu_user_email', 'local_subscriptions'))
                        ->action(CommandActionKeys::USER_EMAIL, [
                            'userid' => (int)$user->id,
                        ])
                        ->shortcut('E')
                )
                ->menu_item(
                    CommandMenuItem::create()
                        ->icon('📝')
                        ->label(get_string('command_menu_user_note', 'local_subscriptions'))
                        ->action(CommandActionKeys::USER_ADD_NOTE, [
                            'userid' => (int)$user->id,
                        ])
                        ->shortcut('N')
                )
                ->menu_item(
                    CommandMenuItem::create()
                        ->icon('🔑')
                        ->label(get_string('command_menu_user_reset_password', 'local_subscriptions'))
                        ->action(CommandActionKeys::USER_RESET_PASSWORD, [
                            'userid' => (int)$user->id,
                        ])
                        ->confirmation(get_string('command_confirm_user_reset_password', 'local_subscriptions'))
                        ->danger()
                        ->shortcut('R')
                )
                ->score($score)
                ->meta('provider', 'users')
                ->meta('entity', 'user')
                ->meta('id', (int)$user->id)
                ->to_array();
        }

        return $results;
    }

    private function score(string $query, object $user, string $fullname): int {
        return CommandScorer::best(
            CommandScorer::id($query, (int)$user->id),
            CommandScorer::email($query, (string)($user->email ?? '')),
            CommandScorer::username($query, (string)($user->username ?? '')),
            CommandScorer::fullname($query, $fullname)
        );
    }
}