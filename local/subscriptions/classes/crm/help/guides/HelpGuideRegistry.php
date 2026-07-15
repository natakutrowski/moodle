<?php

namespace local_subscriptions\crm\help\guides;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\HelpContext;

final class HelpGuideRegistry {

    public function guides(): array {
        $guides = [
            $this->dashboard_guide(),
            $this->digital_payment_guide(),
            $this->hot_lead_guide(),
            $this->command_center_guide(),
            $this->user_profile_guide(),
            $this->inbox_guide(),
        ];

        usort(
            $guides,
            static fn(HelpGuide $a, HelpGuide $b): int =>
                $a->priority <=> $b->priority
        );

        return $guides;
    }

    public function get_guide(string $guideid): ?HelpGuide {
        foreach ($this->guides() as $guide) {
            if ($guide->id === $guideid) {
                return $guide;
            }
        }

        return null;
    }

    public function get_step(
        string $guideid,
        string $stepid
    ): ?HelpGuideStep {
        $guide = $this->get_guide($guideid);

        return $guide?->get_step($stepid);
    }

    public function guides_by_context(string $context): array {
        return array_values(array_filter(
            $this->guides(),
            static fn(HelpGuide $guide): bool =>
                $guide->matches_context($context)
        ));
    }

    private function dashboard_guide(): HelpGuide {
        return new HelpGuide(
            'dashboard_first_steps',
            get_string(
                'crm_help_guide_dashboard_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_help_guide_dashboard_desc',
                'local_subscriptions'
            ),
            '📊',
            [
                new HelpGuideStep(
                    'choose_period',
                    get_string(
                        'crm_help_guide_dashboard_period_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_dashboard_period_desc',
                        'local_subscriptions'
                    ),
                    '🗓️',
                    new moodle_url(
                        subscription_config::admin_dashboard_page()
                    ),
                    get_string(
                        'crm_help_guide_open_dashboard',
                        'local_subscriptions'
                    )
                ),

                new HelpGuideStep(
                    'review_kpis',
                    get_string(
                        'crm_help_guide_dashboard_kpis_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_dashboard_kpis_desc',
                        'local_subscriptions'
                    ),
                    '📈'
                ),

                new HelpGuideStep(
                    'review_issues',
                    get_string(
                        'crm_help_guide_dashboard_issues_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_dashboard_issues_desc',
                        'local_subscriptions'
                    ),
                    '⚠️'
                ),

                new HelpGuideStep(
                    'open_priority',
                    get_string(
                        'crm_help_guide_dashboard_priority_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_dashboard_priority_desc',
                        'local_subscriptions'
                    ),
                    '⭐'
                ),
            ],
            [
                HelpContext::DASHBOARD,
                HelpContext::GENERAL,
            ],
            10
        );
    }

    private function digital_payment_guide(): HelpGuide {
        return new HelpGuide(
            'handle_digital_payment',
            get_string(
                'crm_help_guide_digital_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_help_guide_digital_desc',
                'local_subscriptions'
            ),
            '💳',
            [
                new HelpGuideStep(
                    'open_queue',
                    get_string(
                        'crm_help_guide_digital_open_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_digital_open_desc',
                        'local_subscriptions'
                    ),
                    '📂',
                    new moodle_url(
                        subscription_config::digital_purchases_admin_page(),
                        ['status' => 'pending']
                    ),
                    get_string(
                        'crm_help_guide_open_pending',
                        'local_subscriptions'
                    )
                ),

                new HelpGuideStep(
                    'verify_status',
                    get_string(
                        'crm_help_guide_digital_verify_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_digital_verify_desc',
                        'local_subscriptions'
                    ),
                    '🔎'
                ),

                new HelpGuideStep(
                    'contact_buyer',
                    get_string(
                        'crm_help_guide_digital_contact_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_digital_contact_desc',
                        'local_subscriptions'
                    ),
                    '✉️'
                ),

                new HelpGuideStep(
                    'cancel_attempt',
                    get_string(
                        'crm_help_guide_digital_cancel_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_digital_cancel_desc',
                        'local_subscriptions'
                    ),
                    '🚫'
                ),
            ],
            [
                HelpContext::DIGITAL_PURCHASES,
                HelpContext::DASHBOARD,
            ],
            20
        );
    }

    private function hot_lead_guide(): HelpGuide {
        return new HelpGuide(
            'analyse_hot_lead',
            get_string(
                'crm_help_guide_hot_lead_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_help_guide_hot_lead_desc',
                'local_subscriptions'
            ),
            '🔥',
            [
                new HelpGuideStep(
                    'open_segment',
                    get_string(
                        'crm_help_guide_hot_lead_open_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_hot_lead_open_desc',
                        'local_subscriptions'
                    ),
                    '👥',
                    new moodle_url(
                        subscription_config::admin_users_page(),
                        ['intelligence' => 'hot_lead']
                    ),
                    get_string(
                        'crm_help_guide_open_hot_leads',
                        'local_subscriptions'
                    )
                ),

                new HelpGuideStep(
                    'review_score',
                    get_string(
                        'crm_help_guide_hot_lead_score_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_hot_lead_score_desc',
                        'local_subscriptions'
                    ),
                    '🧠'
                ),

                new HelpGuideStep(
                    'review_history',
                    get_string(
                        'crm_help_guide_hot_lead_history_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_hot_lead_history_desc',
                        'local_subscriptions'
                    ),
                    '🕒'
                ),

                new HelpGuideStep(
                    'choose_action',
                    get_string(
                        'crm_help_guide_hot_lead_action_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_hot_lead_action_desc',
                        'local_subscriptions'
                    ),
                    '🎯'
                ),
            ],
            [
                HelpContext::USER_EXPLORER,
                HelpContext::USER_PROFILE,
                HelpContext::INTELLIGENCE,
            ],
            30
        );
    }

    private function command_center_guide(): HelpGuide {
        return new HelpGuide(
            'master_command_center',
            get_string(
                'crm_help_guide_command_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_help_guide_command_desc',
                'local_subscriptions'
            ),
            '⌨️',
            [
                new HelpGuideStep(
                    'open_command_center',
                    get_string(
                        'crm_help_guide_command_open_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_command_open_desc',
                        'local_subscriptions'
                    ),
                    '⌘'
                ),

                new HelpGuideStep(
                    'search_entity',
                    get_string(
                        'crm_help_guide_command_search_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_command_search_desc',
                        'local_subscriptions'
                    ),
                    '🔍'
                ),

                new HelpGuideStep(
                    'use_keyboard',
                    get_string(
                        'crm_help_guide_command_keyboard_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_command_keyboard_desc',
                        'local_subscriptions'
                    ),
                    '↕️'
                ),

                new HelpGuideStep(
                    'manage_favorites',
                    get_string(
                        'crm_help_guide_command_favorites_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_command_favorites_desc',
                        'local_subscriptions'
                    ),
                    '⭐'
                ),
            ],
            [
                HelpContext::COMMAND_CENTER,
                HelpContext::DASHBOARD,
                HelpContext::GENERAL,
            ],
            40
        );
    }

    private function user_profile_guide(): HelpGuide {
        return new HelpGuide(
            'understand_user_profile',
            get_string(
                'crm_help_guide_profile_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_help_guide_profile_desc',
                'local_subscriptions'
            ),
            '👤',
            [
                new HelpGuideStep(
                    'review_identity',
                    get_string(
                        'crm_help_guide_profile_identity_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_profile_identity_desc',
                        'local_subscriptions'
                    ),
                    '🪪'
                ),

                new HelpGuideStep(
                    'review_timeline',
                    get_string(
                        'crm_help_guide_profile_timeline_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_profile_timeline_desc',
                        'local_subscriptions'
                    ),
                    '🕒'
                ),

                new HelpGuideStep(
                    'review_intelligence',
                    get_string(
                        'crm_help_guide_profile_intelligence_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_profile_intelligence_desc',
                        'local_subscriptions'
                    ),
                    '🧠'
                ),

                new HelpGuideStep(
                    'take_action',
                    get_string(
                        'crm_help_guide_profile_action_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_profile_action_desc',
                        'local_subscriptions'
                    ),
                    '⚡'
                ),
            ],
            [
                HelpContext::USER_PROFILE,
            ],
            50
        );
    }

    private function inbox_guide(): HelpGuide {
        return new HelpGuide(
            'handle_inbox_conversation',
            get_string(
                'crm_help_guide_inbox_title',
                'local_subscriptions'
            ),
            get_string(
                'crm_help_guide_inbox_desc',
                'local_subscriptions'
            ),
            '📨',
            [
                new HelpGuideStep(
                    'open_inbox',
                    get_string(
                        'crm_help_guide_inbox_open_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_open_desc',
                        'local_subscriptions'
                    ),
                    '📥',
                    new moodle_url(
                        subscription_config::
                            admin_inbox_page()
                    ),
                    get_string(
                        'crm_help_guide_open_inbox',
                        'local_subscriptions'
                    )
                ),

                new HelpGuideStep(
                    'identify_contact',
                    get_string(
                        'crm_help_guide_inbox_contact_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_contact_desc',
                        'local_subscriptions'
                    ),
                    '👤'
                ),

                new HelpGuideStep(
                    'assign_thread',
                    get_string(
                        'crm_help_guide_inbox_assign_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_assign_desc',
                        'local_subscriptions'
                    ),
                    '🧭'
                ),

                new HelpGuideStep(
                    'reply_thread',
                    get_string(
                        'crm_help_guide_inbox_reply_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_reply_desc',
                        'local_subscriptions'
                    ),
                    '✉️'
                ),

                new HelpGuideStep(
                    'use_ai_assistance',
                    get_string(
                        'crm_help_guide_inbox_ai_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_ai_desc',
                        'local_subscriptions'
                    ),
                    '✨',
                    new moodle_url(
                        subscription_config::
                            admin_help_article_page(),
                        [
                            'id' => 'crm_inbox_ai',
                        ]
                    ),
                    get_string(
                        'crm_help_guide_inbox_ai_action',
                        'local_subscriptions'
                    )
                ),

                new HelpGuideStep(
                    'close_thread',
                    get_string(
                        'crm_help_guide_inbox_close_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_close_desc',
                        'local_subscriptions'
                    ),
                    '✅'
                ),

                new HelpGuideStep(
                    'diagnose_inbox',
                    get_string(
                        'crm_help_guide_inbox_diagnostics_title',
                        'local_subscriptions'
                    ),
                    get_string(
                        'crm_help_guide_inbox_diagnostics_desc',
                        'local_subscriptions'
                    ),
                    '🩺',
                    new moodle_url(
                        subscription_config::
                            admin_help_article_page(),
                        [
                            'id' =>
                                'crm_inbox_diagnostics',
                        ]
                    ),
                    get_string(
                        'crm_help_guide_inbox_diagnostics_action',
                        'local_subscriptions'
                    )
                ),

            ],
            [
                HelpContext::INBOX,
                HelpContext::GENERAL,
            ],
            45
        );
    }    

}