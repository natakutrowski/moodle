<?php

namespace local_subscriptions\commandcenter\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\actions\CommandActionKeys;
use local_subscriptions\commandcenter\CommandProviderInterface;
use local_subscriptions\commandcenter\CommandQuery;
use local_subscriptions\commandcenter\CommandResult;
use local_subscriptions\commandcenter\CommandScorer;
use local_subscriptions\commandcenter\CommandTypes;
use local_subscriptions\crm\intelligence\alerts\CrmAlertBuilder;
use local_subscriptions\crm\intelligence\priority\DailyPriorityBuilder;
use local_subscriptions\subscription_config;
use moodle_url;

final class IntelligenceProvider implements CommandProviderInterface {

    public function search(CommandQuery $query, int $limit = 10): array {
        if ($query->has_direct_entity()) {
            return [];
        }

        $text = trim($query->text());

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        $results = [];

        $this->append_dashboard_result($results, $text);
        $this->append_alert_results($results, $text, $limit);
        $this->append_priority_results($results, $text, $limit);

        usort($results, static function(array $a, array $b): int {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        return array_slice($results, 0, $limit);
    }

    private function append_dashboard_result(array &$results, string $text): void {
        $score = CommandScorer::best(
            CommandScorer::exact_or_prefix($text, 'crm intelligence', 110, 95, 70),
            CommandScorer::exact_or_prefix($text, 'dashboard intelligence', 100, 90, 65),
            CommandScorer::exact_or_prefix($text, 'priorités crm', 100, 85, 60),
            CommandScorer::exact_or_prefix($text, 'alertes crm', 100, 85, 60),
            CommandScorer::keywords($text, 'crm', 85),
            CommandScorer::keywords($text, 'intelligence', 85),
            CommandScorer::keywords($text, 'dashboard', 80),
            CommandScorer::keywords($text, 'priorités', 80),
            CommandScorer::keywords($text, 'priorities', 80),
            CommandScorer::keywords($text, 'alertes', 75),
            CommandScorer::keywords($text, 'alerts', 75),
            CommandScorer::keywords($text, 'риски', 75),
            CommandScorer::keywords($text, 'аналитика', 75)
        );

        if ($score <= 0) {
            return;
        }

        $url = (new moodle_url(subscription_config::admin_dashboard_page()))->out(false);

        $results[] = CommandResult::create()
            ->icon('🧠')
            ->type(CommandTypes::action())
            ->group('actions', get_string('command_center_group_actions', 'local_subscriptions'))
            ->action_label(get_string('command_center_action_open', 'local_subscriptions'))
            ->shortcut('crm')
            ->title(get_string('command_crm_intelligence_dashboard', 'local_subscriptions'))
            ->subtitle(get_string('command_crm_intelligence_dashboard_desc', 'local_subscriptions'))
            ->url($url)
            ->action(CommandActionKeys::OPEN_URL, ['url' => $url])
            ->score($score)
            ->meta('provider', 'crm_intelligence')
            ->meta('entity', 'dashboard')
            ->to_array();
    }

    private function append_alert_results(array &$results, string $text, int $limit): void {
        $base = CommandScorer::best(
            CommandScorer::keywords($text, 'alerte', 85),
            CommandScorer::keywords($text, 'alert', 85),
            CommandScorer::keywords($text, 'risque', 80),
            CommandScorer::keywords($text, 'risk', 80),
            CommandScorer::keywords($text, 'churn', 80),
            CommandScorer::keywords($text, 'crm', 70)
        );

        if ($base <= 0) {
            return;
        }

        foreach ((new CrmAlertBuilder())->build($limit) as $alert) {
            if (empty($alert->userid)) {
                continue;
            }

            $label = self::alert_label((string)$alert->key);

            $score = CommandScorer::best(
                CommandScorer::exact_or_prefix($text, $label, 100, 85, 60),
                $base
            );

            if ($score <= 0) {
                continue;
            }

            $userid = (int)$alert->userid;

            $results[] = CommandResult::create()
                ->icon('🚨')
                ->type(CommandTypes::user())
                ->group('users', get_string('command_center_group_users', 'local_subscriptions'))
                ->action_label(get_string('command_center_action_view', 'local_subscriptions'))
                ->shortcut('user:' . $userid)
                ->title($label)
                ->subtitle(get_string('command_crm_alert_desc', 'local_subscriptions'))
                ->url((new moodle_url(subscription_config::admin_user_view_page(), [
                    'id' => $userid,
                ]))->out(false))
                ->action(CommandActionKeys::OPEN_USER, [
                    'userid' => $userid,
                ])
                ->score($score)
                ->meta('provider', 'crm_intelligence')
                ->meta('entity', 'alert')
                ->meta('userid', $userid)
                ->meta('alert', (string)$alert->key)
                ->to_array();
        }
    }

    private function append_priority_results(array &$results, string $text, int $limit): void {
        $base = CommandScorer::best(
            CommandScorer::keywords($text, 'priorité', 90),
            CommandScorer::keywords($text, 'priorités', 90),
            CommandScorer::keywords($text, 'priority', 90),
            CommandScorer::keywords($text, 'priorities', 90),
            CommandScorer::keywords($text, 'relance', 80),
            CommandScorer::keywords($text, 'follow-up', 80),
            CommandScorer::keywords($text, 'crm', 70)
        );

        if ($base <= 0) {
            return;
        }

        foreach ((new DailyPriorityBuilder())->build($limit) as $priority) {
            $key = 'crm_intelligence_recommendation_' . clean_param($priority->key, PARAM_ALPHANUMEXT);

            $label = get_string_manager()->string_exists($key, 'local_subscriptions')
                ? get_string($key, 'local_subscriptions')
                : $priority->key;

            $userid = (int)$priority->userid;

            $results[] = CommandResult::create()
                ->icon('⭐')
                ->type(CommandTypes::user())
                ->group('users', get_string('command_center_group_users', 'local_subscriptions'))
                ->action_label(get_string('command_center_action_view', 'local_subscriptions'))
                ->shortcut('user:' . $userid)
                ->title($label)
                ->subtitle(get_string('command_crm_priority_desc', 'local_subscriptions'))
                ->url((new moodle_url(subscription_config::admin_user_view_page(), [
                    'id' => $userid,
                ]))->out(false))
                ->action(CommandActionKeys::OPEN_USER, [
                    'userid' => $userid,
                ])
                ->score($base + min(20, (int)round($priority->score / 10)))
                ->meta('provider', 'crm_intelligence')
                ->meta('entity', 'priority')
                ->meta('userid', $userid)
                ->meta('priority', (string)$priority->key)
                ->to_array();
        }
    }

    private static function alert_label(string $alertkey): string {
        $key = 'crm_intelligence_alert_' . clean_param($alertkey, PARAM_ALPHANUMEXT);

        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : $alertkey;
    }
}