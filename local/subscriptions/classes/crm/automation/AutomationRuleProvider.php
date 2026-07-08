<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationRuleProvider {

    /**
     * @return AutomationRule[]
     */
    public function default_rules(): array {
        return [
            $this->trial_expired_followup(),
            $this->payment_failed_tag(),
            $this->digital_purchase_paid_note(),
            $this->subscription_expired_note(),
            $this->payment_note_adds_payment_tag(),
            $this->sensitive_tag_adds_note(),
            $this->vip_tag_adds_note(),
        ];
    }

    private function trial_expired_followup(): AutomationRule {
        return new AutomationRule(
            0,
            'trial_expired_followup',
            'Trial expiré — relance CRM',
            AutomationTrigger::make(AutomationTriggerKeys::TRIAL_EXPIRED),
            [
                AutomationCondition::make(AutomationConditionKeys::MISSING_TAG, [
                    'tagkey' => 'followup',
                ]),
            ],
            [
                AutomationAction::make(AutomationActionKeys::ADD_TAG, [
                    'tagkey' => 'followup',
                ]),
                AutomationAction::make(AutomationActionKeys::CREATE_NOTE, [
                    'type' => 'followup',
                    'content' => 'Automatisation CRM : essai expiré, utilisateur à relancer.',
                ]),
            ],
            true,
            100,
            ['source' => 'default']
        );
    }

    private function payment_failed_tag(): AutomationRule {
        return new AutomationRule(
            0,
            'payment_failed_tag',
            'Paiement échoué — tag problème paiement',
            AutomationTrigger::make(AutomationTriggerKeys::PAYMENT_FAILED),
            [
                AutomationCondition::make(AutomationConditionKeys::MISSING_TAG, [
                    'tagkey' => 'payment_issue',
                ]),
            ],
            [
                AutomationAction::make(AutomationActionKeys::ADD_TAG, [
                    'tagkey' => 'payment_issue',
                ]),
                AutomationAction::make(AutomationActionKeys::CREATE_NOTE, [
                    'type' => 'payment',
                    'content' => 'Automatisation CRM : paiement échoué détecté.',
                ]),
            ],
            true,
            90,
            ['source' => 'default']
        );
    }

    private function digital_purchase_paid_note(): AutomationRule {
        return new AutomationRule(
            0,
            'digital_purchase_paid_note',
            'Achat digital payé — note CRM',
            AutomationTrigger::make(AutomationTriggerKeys::DIGITAL_PURCHASE_PAID),
            [],
            [
                AutomationAction::make(AutomationActionKeys::CREATE_NOTE, [
                    'type' => 'general',
                    'content' => 'Automatisation CRM : achat digital payé détecté.',
                ]),
            ],
            true,
            80,
            ['source' => 'default']
        );
    }

    private function subscription_expired_note(): AutomationRule {
        return new AutomationRule(
            0,
            'subscription_expired_note',
            'Abonnement expiré — note CRM',
            AutomationTrigger::make(AutomationTriggerKeys::SUBSCRIPTION_EXPIRED),
            [],
            [
                AutomationAction::make(AutomationActionKeys::CREATE_NOTE, [
                    'type' => 'access',
                    'content' => 'Automatisation CRM : abonnement expiré détecté.',
                ]),
            ],
            true,
            110,
            ['source' => 'default']
        );
    }

    private function payment_note_adds_payment_tag(): AutomationRule {
        return new AutomationRule(
            0,
            'payment_note_adds_payment_tag',
            'Note paiement — tag problème paiement',
            AutomationTrigger::make(AutomationTriggerKeys::NOTE_ADDED),
            [
                AutomationCondition::make(AutomationConditionKeys::EVENT_NOTE_TYPE_IS, [
                    'type' => 'payment',
                ]),
                AutomationCondition::make(AutomationConditionKeys::MISSING_TAG, [
                    'tagkey' => 'payment_issue',
                ]),
            ],
            [
                AutomationAction::make(AutomationActionKeys::ADD_TAG, [
                    'tagkey' => 'payment_issue',
                ]),
            ],
            true,
            120,
            ['source' => 'default']
        );
    }

    private function sensitive_tag_adds_note(): AutomationRule {
        return new AutomationRule(
            0,
            'sensitive_tag_adds_note',
            'Tag sensible — note CRM',
            AutomationTrigger::make(AutomationTriggerKeys::TAG_ADDED),
            [
                AutomationCondition::make(AutomationConditionKeys::EVENT_TAG_IS, [
                    'tagkey' => 'sensitive',
                ]),
            ],
            [
                AutomationAction::make(AutomationActionKeys::CREATE_NOTE, [
                    'type' => 'sensitive',
                    'content' => 'Automatisation CRM : utilisateur marqué comme cas sensible.',
                ]),
            ],
            true,
            130,
            ['source' => 'default']
        );
    }

    private function vip_tag_adds_note(): AutomationRule {
        return new AutomationRule(
            0,
            'vip_tag_adds_note',
            'Tag VIP — note CRM',
            AutomationTrigger::make(AutomationTriggerKeys::TAG_ADDED),
            [
                AutomationCondition::make(AutomationConditionKeys::EVENT_TAG_IS, [
                    'tagkey' => 'vip',
                ]),
            ],
            [
                AutomationAction::make(AutomationActionKeys::CREATE_NOTE, [
                    'type' => 'general',
                    'content' => 'Automatisation CRM : utilisateur marqué VIP.',
                ]),
            ],
            true,
            140,
            ['source' => 'default']
        );
    }

}