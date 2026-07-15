<?php

namespace local_subscriptions\crm\help\onboarding;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use local_subscriptions\subscription_config;

final class HelpOnboardingRegistry {

    public function steps(): array {
        $steps = [
            new HelpOnboardingStep(
                'discover_dashboard',
                get_string(
                    'crm_onboarding_step_dashboard_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_dashboard_desc',
                    'local_subscriptions'
                ),
                '📊',
                new moodle_url(
                    subscription_config::admin_dashboard_page()
                ),
                10
            ),

            new HelpOnboardingStep(
                'use_command_center',
                get_string(
                    'crm_onboarding_step_command_center_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_command_center_desc',
                    'local_subscriptions'
                ),
                '⌨️',
                new moodle_url(
                    subscription_config::admin_help_article_page(),
                    ['id' => 'command_center_shortcuts']
                ),
                20
            ),

            new HelpOnboardingStep(
                'explore_users',
                get_string(
                    'crm_onboarding_step_users_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_users_desc',
                    'local_subscriptions'
                ),
                '👤',
                new moodle_url(
                    subscription_config::admin_users_page()
                ),
                30
            ),

            new HelpOnboardingStep(
                'review_intelligence',
                get_string(
                    'crm_onboarding_step_intelligence_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_intelligence_desc',
                    'local_subscriptions'
                ),
                '🧠',
                new moodle_url(
                    subscription_config::admin_users_page(),
                    ['intelligence' => 'hot_lead']
                ),
                40
            ),

            new HelpOnboardingStep(
                'review_digital_purchases',
                get_string(
                    'crm_onboarding_step_digital_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_digital_desc',
                    'local_subscriptions'
                ),
                '📦',
                new moodle_url(
                    subscription_config::digital_purchases_admin_page()
                ),
                50
            ),

            new HelpOnboardingStep(
                'discover_inbox',
                get_string(
                    'crm_onboarding_step_inbox_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_inbox_desc',
                    'local_subscriptions'
                ),
                '📥',
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
                55
            ),

            new HelpOnboardingStep(
                'discover_automations',
                get_string(
                    'crm_onboarding_step_automations_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_automations_desc',
                    'local_subscriptions'
                ),
                '⚙️',
                new moodle_url(
                    subscription_config::automation_rules_admin_page()
                ),
                60
            ),

            new HelpOnboardingStep(
                'read_help_center',
                get_string(
                    'crm_onboarding_step_help_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_help_desc',
                    'local_subscriptions'
                ),
                '💡',
                new moodle_url(
                    subscription_config::admin_help_page()
                ),
                70
            ),

            new HelpOnboardingStep(
                'understand_architecture',
                get_string(
                    'crm_onboarding_step_architecture_title',
                    'local_subscriptions'
                ),
                get_string(
                    'crm_onboarding_step_architecture_desc',
                    'local_subscriptions'
                ),
                '🛠️',
                new moodle_url(
                    subscription_config::admin_help_article_page(),
                    ['id' => 'developer_architecture']
                ),
                80
            ),
        ];

        usort(
            $steps,
            static fn(
                HelpOnboardingStep $a,
                HelpOnboardingStep $b
            ): int => $a->priority <=> $b->priority
        );

        return $steps;
    }

    public function get_step(string $stepid): ?HelpOnboardingStep {
        foreach ($this->steps() as $step) {
            if ($step->id === $stepid) {
                return $step;
            }
        }

        return null;
    }

    public function exists(string $stepid): bool {
        return $this->get_step($stepid) !== null;
    }
}