<?php

namespace local_subscriptions\crm\automation;

use local_subscriptions\crm\automation\actions\AddTagAction;
use local_subscriptions\crm\automation\actions\CreateNoteAction;
use local_subscriptions\crm\automation\actions\RemoveTagAction;
use local_subscriptions\crm\automation\conditions\HasTagCondition;
use local_subscriptions\crm\automation\conditions\MissingTagCondition;
use local_subscriptions\crm\automation\conditions\EventNoteTypeIsCondition;
use local_subscriptions\crm\automation\conditions\EventTagIsCondition;

defined('MOODLE_INTERNAL') || die();

final class AutomationFactory {

    public static function runner(): AutomationRunner {
        $conditions = self::conditions();
        $actions = self::actions();

        $engine = new AutomationEngine(
            $conditions,
            $actions,
            new AutomationHistoryRepository()
        );

        return new AutomationRunner(
            new AutomationRepository(),
            $engine
        );
    }

    public static function conditions(): AutomationConditionRegistry {
        return (new AutomationConditionRegistry())
            ->register(new HasTagCondition())
            ->register(new MissingTagCondition())
            ->register(new EventTagIsCondition())
            ->register(new EventNoteTypeIsCondition());
    }

    public static function actions(): AutomationActionRegistry {
        return (new AutomationActionRegistry())
            ->register(new AddTagAction())
            ->register(new RemoveTagAction())
            ->register(new CreateNoteAction());
    }
}