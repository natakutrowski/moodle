<?php

namespace local_subscriptions\crm\help;

defined('MOODLE_INTERNAL') || die();

final class HelpRegistry {

    public function categories(): array {
        $categories = [
            new HelpCategory(
                'getting_started',
                get_string('crm_help_category_getting_started', 'local_subscriptions'),
                get_string('crm_help_category_getting_started_desc', 'local_subscriptions'),
                '🚀',
                10
            ),
            new HelpCategory(
                'daily_work',
                get_string('crm_help_category_daily_work', 'local_subscriptions'),
                get_string('crm_help_category_daily_work_desc', 'local_subscriptions'),
                '🧭',
                20
            ),
            new HelpCategory(
                'users',
                get_string('crm_help_category_users', 'local_subscriptions'),
                get_string('crm_help_category_users_desc', 'local_subscriptions'),
                '👤',
                30
            ),
            new HelpCategory(
                'digital',
                get_string('crm_help_category_digital', 'local_subscriptions'),
                get_string('crm_help_category_digital_desc', 'local_subscriptions'),
                '📦',
                40
            ),
            new HelpCategory(
                'commerce',
                get_string(
                    'crm_help_category_commerce',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_category_commerce_desc',
                    'local_subscriptions'
                ),
                '💳',
                42
            ),
            new HelpCategory(
                'inbox',
                get_string(
                    'crm_help_category_inbox',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_category_inbox_desc',
                    'local_subscriptions'
                ),
                '📨',
                45
            ),

            new HelpCategory(
                'work_management',
                get_string(
                    'crm_help_category_work_management',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_category_work_management_desc',
                    'local_subscriptions'
                ),
                '✅',
                47
            ),

            new HelpCategory(
                'automation',
                get_string('crm_help_category_automation', 'local_subscriptions'),
                get_string('crm_help_category_automation_desc', 'local_subscriptions'),
                '⚙️',
                50
            ),
            new HelpCategory(
                'intelligence',
                get_string('crm_help_category_intelligence', 'local_subscriptions'),
                get_string('crm_help_category_intelligence_desc', 'local_subscriptions'),
                '🧠',
                60
            ),
            new HelpCategory(
                'shortcuts',
                get_string('crm_help_category_shortcuts', 'local_subscriptions'),
                get_string('crm_help_category_shortcuts_desc', 'local_subscriptions'),
                '⌨️',
                70
            ),
            new HelpCategory(
                'developer',
                get_string('crm_help_category_developer', 'local_subscriptions'),
                get_string('crm_help_category_developer_desc', 'local_subscriptions'),
                '🛠️',
                90
            ),
        ];

        usort(
            $categories,
            static fn(HelpCategory $a, HelpCategory $b): int =>
                $a->priority <=> $b->priority
        );

        return $categories;
    }

    public function articles(): array {
        $articles = [
            new HelpArticle(
                'crm_overview',
                'getting_started',
                get_string(
                    'crm_help_article_overview_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_overview_summary',
                    'local_subscriptions'
                ),
                'crm_overview.md',
                [
                    'crm',
                    'dashboard',
                    'command center',
                    'intelligence',
                ],
                [
                    HelpContext::GENERAL,
                    HelpContext::DASHBOARD,
                ],
                10
            ),

            new HelpArticle(
                'dashboard_periods',
                'daily_work',
                get_string(
                    'crm_help_article_dashboard_periods_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_dashboard_periods_summary',
                    'local_subscriptions'
                ),
                'dashboard_periods.md',
                [
                    'dashboard',
                    'today',
                    'week',
                    'month',
                    'kpi',
                ],
                [
                    HelpContext::DASHBOARD,
                ],
                20
            ),

            new HelpArticle(
                'user_explorer_filters',
                'users',
                get_string(
                    'crm_help_article_user_filters_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_user_filters_summary',
                    'local_subscriptions'
                ),
                'user_explorer_filters.md',
                [
                    'users',
                    'filters',
                    'segments',
                    'score',
                    'risk',
                    'vip',
                    'hot lead',
                ],
                [
                    HelpContext::USER_EXPLORER,
                    HelpContext::INTELLIGENCE,
                ],
                30
            ),

            new HelpArticle(
                'digital_payment_issues',
                'digital',
                get_string(
                    'crm_help_article_digital_issues_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_digital_issues_summary',
                    'local_subscriptions'
                ),
                'digital_payment_issues.md',
                [
                    'pending',
                    'failed',
                    'cancelled',
                    'email',
                    'token',
                    'payment',
                ],
                [
                    HelpContext::DIGITAL_PURCHASES,
                    HelpContext::DASHBOARD,
                ],
                40
            ),
            new HelpArticle(
                'commerce_overview',
                'commerce',
                get_string(
                    'crm_help_article_commerce_overview_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_commerce_overview_summary',
                    'local_subscriptions'
                ),
                'commerce_overview.md',
                [
                    'commerce',
                    'purchase',
                    'subscription',
                    'digital',
                    'checkout',
                    'payment',
                    'fulfillment',
                    'stripe',
                    'alfa',
                    'architecture',
                ],
                [
                    HelpContext::COMMERCE,
                    HelpContext::SUBSCRIPTIONS,
                    HelpContext::DIGITAL_PURCHASES,
                    HelpContext::GENERAL,
                ],
                41
            ),
            new HelpArticle(
                'commerce_operations',
                'commerce',
                get_string(
                    'crm_help_article_commerce_operations_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_commerce_operations_summary',
                    'local_subscriptions'
                ),
                'commerce_operations.md',
                [
                    'commerce',
                    'production',
                    'checkout',
                    'fulfillment',
                    'kill switch',
                    'rollback',
                    'stripe',
                    'alfa',
                    'provider',
                    'configuration',
                ],
                [
                    HelpContext::COMMERCE,
                    HelpContext::ADMIN_TOOLS,
                    HelpContext::GENERAL,
                ],
                42
            ),
            new HelpArticle(
                'commerce_diagnostics',
                'commerce',
                get_string(
                    'crm_help_article_commerce_diagnostics_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_commerce_diagnostics_summary',
                    'local_subscriptions'
                ),
                'commerce_diagnostics.md',
                [
                    'commerce',
                    'audit',
                    'diagnostic',
                    'certification',
                    'integrity',
                    'idempotency',
                    'fulfillment',
                    'runtime',
                    'payment request',
                    'webhook',
                ],
                [
                    HelpContext::COMMERCE,
                    HelpContext::ADMIN_TOOLS,
                    HelpContext::GENERAL,
                ],
                43
            ),
            new HelpArticle(
                'commerce_extension',
                'commerce',
                get_string(
                    'crm_help_article_commerce_extension_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_commerce_extension_summary',
                    'local_subscriptions'
                ),
                'commerce_extension.md',
                [
                    'commerce',
                    'developer',
                    'provider',
                    'product',
                    'purchase handler',
                    'fulfillment handler',
                    'registry',
                    'extension',
                    'architecture',
                    'testing',
                ],
                [
                    HelpContext::COMMERCE,
                    HelpContext::GENERAL,
                ],
                44,
                true
            ),

            new HelpArticle(
                'command_center_shortcuts',
                'shortcuts',
                get_string(
                    'crm_help_article_shortcuts_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_shortcuts_summary',
                    'local_subscriptions'
                ),
                'command_center_shortcuts.md',
                [
                    'keyboard',
                    'command center',
                    'shortcut',
                    'search',
                    'recent',
                    'favorites',
                ],
                [
                    HelpContext::COMMAND_CENTER,
                    HelpContext::GENERAL,
                ],
                50
            ),

            new HelpArticle(
                'developer_architecture',
                'developer',
                get_string(
                    'crm_help_article_developer_architecture_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_developer_architecture_summary',
                    'local_subscriptions'
                ),
                'developer_architecture.md',
                [
                    'repository',
                    'service',
                    'renderer',
                    'architecture',
                    'security',
                    'javascript',
                ],
                [
                    HelpContext::GENERAL,
                ],
                100,
                true
            ),

            new HelpArticle(
                'crm_inbox',
                'inbox',
                get_string(
                    'crm_help_article_inbox_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_inbox_summary',
                    'local_subscriptions'
                ),
                'crm_inbox.md',
                [
                    'inbox',
                    'email',
                    'support',
                    'conversation',
                    'reply',
                    'assignment',
                    'imap',
                    'smtp',
                    'contact',
                ],
                [
                    HelpContext::INBOX,
                    HelpContext::INBOX_DIAGNOSTICS,
                    HelpContext::USER_PROFILE,
                    HelpContext::GENERAL,
                ],
                45
            ),
            
            new HelpArticle(
                'crm_inbox_ai',
                'inbox',
                get_string(
                    'crm_help_article_inbox_ai_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_inbox_ai_summary',
                    'local_subscriptions'
                ),
                'crm_inbox_ai.md',
                [
                    'inbox',
                    'ai',
                    'assistant',
                    'summary',
                    'translation',
                    'reply',
                    'urgence',
                    'résumé',
                    'traduction',
                    'ответ',
                    'перевод',
                ],
                [
                    HelpContext::INBOX,
                    HelpContext::INBOX_AI,
                    HelpContext::GENERAL,
                ],
                46
            ),

            new HelpArticle(
                'crm_inbox_diagnostics',
                'inbox',
                get_string(
                    'crm_help_article_inbox_diagnostics_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_inbox_diagnostics_summary',
                    'local_subscriptions'
                ),
                'crm_inbox_diagnostics.md',
                [
                    'inbox',
                    'diagnostics',
                    'diagnostic',
                    'imap',
                    'smtp',
                    'connexion',
                    'connection',
                    'synchronisation',
                    'sync',
                    'attachments',
                    'pièces jointes',
                    'errors',
                    'erreurs',
                    'openai',
                    'provider',
                    'quota',
                    'cache',
                    'диагностика',
                    'ошибки',
                    'синхронизация',
                ],
                [
                    HelpContext::INBOX,
                    HelpContext::INBOX_DIAGNOSTICS,
                    HelpContext::INBOX_AI,
                    HelpContext::GENERAL,
                ],
                47
            ),

            new HelpArticle(
                'crm_work_management',
                'work_management',
                get_string(
                    'crm_help_article_work_management_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_help_article_work_management_summary',
                    'local_subscriptions'
                ),
                'crm_work_management.md',
                [
                    'work item',
                    'work items',
                    'ticket',
                    'task',
                    'tasks',
                    'team',
                    'assignment',
                    'priority',
                    'status',
                    'subtask',
                    'comment',
                    'workflow',
                    'travail',
                    'tâche',
                    'ticket',
                    'équipe',
                    'assignation',
                    'priorité',
                    'статус',
                    'задача',
                    'тикет',
                    'команда',
                    'назначение',
                ],
                [
                    HelpContext::WORK_ITEMS,
                    HelpContext::INBOX,
                    HelpContext::USER_PROFILE,
                    HelpContext::DASHBOARD,
                    HelpContext::AUTOMATIONS,
                    HelpContext::GENERAL,
                ],
                48
            ),

        ];

        usort(
            $articles,
            static fn(HelpArticle $a, HelpArticle $b): int =>
                $a->priority <=> $b->priority
        );

        return $articles;
    }

    public function get_category(string $id): ?HelpCategory {
        foreach ($this->categories() as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        return null;
    }

    public function get_article(string $id): ?HelpArticle {
        foreach ($this->articles() as $article) {
            if ($article->id === $id) {
                return $article;
            }
        }

        return null;
    }

    public function articles_by_category(string $categoryid): array {
        return array_values(array_filter(
            $this->articles(),
            static fn(HelpArticle $article): bool =>
                $article->categoryid === $categoryid
        ));
    }

    public function articles_by_context(string $context): array {
        $context = HelpContext::normalize($context);

        return array_values(array_filter(
            $this->articles(),
            static fn(HelpArticle $article): bool =>
                $article->matches_context($context)
        ));
    }

    public function category_exists(string $categoryid): bool {
        return $this->get_category($categoryid) !== null;
    }

}