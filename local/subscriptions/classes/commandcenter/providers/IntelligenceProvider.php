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
use local_subscriptions\crm\assistant\repositories\AssistantRecommendationRepository;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanOperationsRepository;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\subscription_config;
use moodle_url;

final class IntelligenceProvider implements CommandProviderInterface {

    private CustomerSuccessPlanOperationsRepository $successplans;

    public function __construct(
        ?CustomerSuccessPlanOperationsRepository $successplans = null
    ) {
        $this->successplans =
            $successplans ??
            new CustomerSuccessPlanOperationsRepository();
    }

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
        $this->append_assistant_result(
            $results,
            $text
        );

        $this->append_recommendation_results(
            $results,
            $text,
            $limit
        );

        $this->append_customer_success_plan_results(
            $results,
            $query,
            $text,
            $limit
        );

        $this->append_alert_results(
            $results,
            $text,
            $limit
        );

        $this->append_priority_results(
            $results,
            $text,
            $limit
        );

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

    private function append_assistant_result(
        array &$results,
        string $text
    ): void {
        $score = CommandScorer::best(
            CommandScorer::exact_or_prefix(
                $text,
                'crm assistant',
                115,
                100,
                80
            ),
            CommandScorer::exact_or_prefix(
                $text,
                'assistant crm',
                115,
                100,
                80
            ),
            CommandScorer::keywords(
                $text,
                'assistant',
                95
            ),
            CommandScorer::keywords(
                $text,
                'recommandations',
                90
            ),
            CommandScorer::keywords(
                $text,
                'recommendations',
                90
            ),
            CommandScorer::keywords(
                $text,
                'рекомендации',
                90
            ),
            CommandScorer::keywords(
                $text,
                'помощник',
                85
            )
        );

        if ($score <= 0) {
            return;
        }

        $url = (
            new moodle_url(
                subscription_config::
                    admin_crm_assistant_page()
            )
        )->out(false);

        $results[] = CommandResult::create()
            ->icon('🧭')
            ->type(CommandTypes::action())
            ->group(
                'actions',
                get_string(
                    'command_center_group_actions',
                    'local_subscriptions'
                )
            )
            ->action_label(
                get_string(
                    'command_center_action_open',
                    'local_subscriptions'
                )
            )
            ->shortcut('assistant')
            ->title(
                get_string(
                    'command_crm_assistant',
                    'local_subscriptions'
                )
            )
            ->subtitle(
                get_string(
                    'command_crm_assistant_desc',
                    'local_subscriptions'
                )
            )
            ->url($url)
            ->action(
                CommandActionKeys::OPEN_URL,
                [
                    'url' => $url,
                ]
            )
            ->score($score)
            ->meta(
                'provider',
                'crm_intelligence'
            )
            ->meta(
                'entity',
                'assistant'
            )
            ->to_array();
    }

    private function append_recommendation_results(
        array &$results,
        string $text,
        int $limit
    ): void {
        $base = CommandScorer::best(
            CommandScorer::keywords(
                $text,
                'recommandation',
                90
            ),
            CommandScorer::keywords(
                $text,
                'recommendation',
                90
            ),
            CommandScorer::keywords(
                $text,
                'priorité',
                85
            ),
            CommandScorer::keywords(
                $text,
                'urgent',
                85
            ),
            CommandScorer::keywords(
                $text,
                'critical',
                85
            ),
            CommandScorer::keywords(
                $text,
                'критично',
                85
            ),
            CommandScorer::keywords(
                $text,
                'срочно',
                85
            )
        );

        if ($base <= 0) {
            return;
        }

        $records =
            (new AssistantRecommendationRepository())
                ->search(
                    new \local_subscriptions\crm\assistant\dto\AssistantRecommendationCriteria(
                        limit: min(20, $limit)
                    )
                );

        foreach ($records as $recommendation) {
            if (
                !$recommendation->is_user_target()
            ) {
                continue;
            }

            $labelkey =
                'crm_assistant_recommendation_' .
                clean_param(
                    $recommendation->key,
                    PARAM_ALPHANUMEXT
                );

            $label =
                get_string_manager()->string_exists(
                    $labelkey,
                    'local_subscriptions'
                )
                    ? get_string(
                        $labelkey,
                        'local_subscriptions'
                    )
                    : $recommendation->key;

            $userid =
                (int)$recommendation->targetid;

            $results[] = CommandResult::create()
                ->icon('🧭')
                ->type(CommandTypes::user())
                ->group(
                    'users',
                    get_string(
                        'command_center_group_users',
                        'local_subscriptions'
                    )
                )
                ->action_label(
                    get_string(
                        'command_center_action_view',
                        'local_subscriptions'
                    )
                )
                ->shortcut(
                    'user:' . $userid
                )
                ->title($label)
                ->subtitle(
                    $recommendation->targetname ??
                    get_string(
                        'command_crm_recommendation_desc',
                        'local_subscriptions'
                    )
                )
                ->url(
                    (
                        new moodle_url(
                            subscription_config::
                                admin_user_view_page(),
                            [
                                'id' => $userid,
                            ]
                        )
                    )->out(false)
                )
                ->action(
                    CommandActionKeys::OPEN_USER,
                    [
                        'userid' => $userid,
                    ]
                )
                ->score(
                    $base +
                    min(
                        20,
                        (int)round(
                            $recommendation->priority /
                            10
                        )
                    )
                )
                ->meta(
                    'provider',
                    'crm_intelligence'
                )
                ->meta(
                    'entity',
                    'recommendation'
                )
                ->meta(
                    'recommendationid',
                    $recommendation->id
                )
                ->meta(
                    'userid',
                    $userid
                )
                ->to_array();
        }
    }

    private function append_customer_success_plan_results(
        array &$results,
        CommandQuery $query,
        string $text,
        int $limit
    ): void {

        $raw = trim($query->raw());

        if (
            preg_match(
                '/^plan:(\d+)$/i',
                $raw,
                $matches
            )
        ) {
            $planid = (int)$matches[1];

            $record =
                $this->successplans->get_plan_summary(
                    $planid
                );

            if ($record !== null) {
                $results[] =
                    $this->format_customer_success_plan(
                        $record,
                        150
                    );
            }

            return;
        }

        $blockedscore = CommandScorer::best(
            CommandScorer::exact_or_prefix(
                $text,
                'plans bloqués',
                120,
                105,
                85
            ),
            CommandScorer::exact_or_prefix(
                $text,
                'blocked plans',
                120,
                105,
                85
            ),
            CommandScorer::keywords(
                $text,
                'bloqués',
                90
            ),
            CommandScorer::keywords(
                $text,
                'blocked',
                90
            )
        );

        $criticalscore = CommandScorer::best(
            CommandScorer::exact_or_prefix(
                $text,
                'plans critiques',
                120,
                105,
                85
            ),
            CommandScorer::exact_or_prefix(
                $text,
                'critical plans',
                120,
                105,
                85
            ),
            CommandScorer::keywords(
                $text,
                'critique',
                90
            ),
            CommandScorer::keywords(
                $text,
                'critical',
                90
            )
        );

        $planscore = CommandScorer::best(
            CommandScorer::exact_or_prefix(
                $text,
                'plans customer success',
                110,
                95,
                75
            ),
            CommandScorer::exact_or_prefix(
                $text,
                'customer success plans',
                110,
                95,
                75
            ),
            CommandScorer::keywords(
                $text,
                'plans',
                70
            ),
            CommandScorer::keywords(
                $text,
                'customer success',
                85
            )
        );

        if (
            $blockedscore <= 0 &&
            $criticalscore <= 0 &&
            $planscore <= 0
        ) {
            return;
        }

        $records =
            $this->successplans->search_open_plans(
                priority:
                    $criticalscore >=
                    max($blockedscore, $planscore)
                        ? 'critical'
                        : null,

                blockedonly:
                    $blockedscore >
                    max($criticalscore, $planscore),

                limit:
                    min(20, $limit)
            );

        $score = max(
            $blockedscore,
            $criticalscore,
            $planscore
        );

        foreach ($records as $record) {
            $results[] =
                $this->format_customer_success_plan(
                    $record,
                    $score
                );
        }
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

    private function format_customer_success_plan(
        \stdClass $record,
        int $score
    ): array {
        $url = new moodle_url(
            subscription_config::
                admin_customer_success_plan_page(),
            [
                'id' => (int)$record->id,
            ]
        )->out(false);

        return CommandResult::create()
            ->icon('🗺️')
            ->type(CommandTypes::action())
            ->group(
                'actions',
                get_string(
                    'command_center_group_actions',
                    'local_subscriptions'
                )
            )
            ->action_label(
                get_string(
                    'command_center_action_open',
                    'local_subscriptions'
                )
            )
            ->shortcut(
                'plan:' . (int)$record->id
            )
            ->title(
                CustomerSuccessPlanPresentation::title(
                    (string)$record->objectivekey,
                    (string)$record->title
                )
            )
            ->subtitle(
                get_string(
                    'csplancommandsubtitle',
                    'local_subscriptions',
                    (object)[
                        'id' => (int)$record->id,
                        'status' =>
                            CustomerSuccessPlanPresentation::
                                status_label(
                                    (string)$record->status
                                ),
                        'priority' =>
                            CustomerSuccessPlanPresentation::
                                priority_label(
                                    (string)$record->priority
                                ),
                    ]
                )
            )
            ->url($url)
            ->action(
                CommandActionKeys::OPEN_URL,
                [
                    'url' => $url,
                ]
            )
            ->score($score)
            ->meta(
                'provider',
                'crm_intelligence'
            )
            ->meta(
                'entity',
                'customer_success_plan'
            )
            ->meta(
                'planid',
                (int)$record->id
            )
            ->meta(
                'userid',
                (int)$record->userid
            )
            ->to_array();
    }

    private static function alert_label(string $alertkey): string {
        $key = 'crm_intelligence_alert_' . clean_param($alertkey, PARAM_ALPHANUMEXT);

        return get_string_manager()->string_exists($key, 'local_subscriptions')
            ? get_string($key, 'local_subscriptions')
            : $alertkey;
    }
}