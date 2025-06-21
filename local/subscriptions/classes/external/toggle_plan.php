<?php
namespace local_subscriptions\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;

class toggle_plan extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'Plan ID')
        ]);
    }

    public static function execute($id) {
        global $DB;

        self::validate_parameters(self::execute_parameters(), ['id' => $id]);
        require_capability('moodle/site:config', context_system::instance());

        $plan = $DB->get_record('subscription_plan', ['id' => $id], '*', MUST_EXIST);

        // Vérifie si on veut activer
        $wantactivate = !$plan->is_active;

        if ($wantactivate) {
            $hastranslation = local_subscriptions_plan_has_translation($plan->id);
            $hasprice = local_subscriptions_plan_has_price($plan->id);

            if (!$hastranslation || !$hasprice) {
                return [
                    'success' => false,
                    'is_active' => (bool)$plan->is_active,
                    'label' => get_string('planincomplete', 'local_subscriptions'),
                    'message' => get_string('cannotactivateplan', 'local_subscriptions'),
                ];
            }
        }

        $plan->is_active = !$plan->is_active;
        $plan->last_update = time();
        $DB->update_record('subscription_plan', $plan);

        return [
            'success' => true,
            'is_active' => (bool)$plan->is_active,
            'label' => get_string($plan->is_active ? 'deactivateplan' : 'activateplan', 'local_subscriptions')
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL),
            'is_active' => new external_value(PARAM_BOOL),
            'label' => new external_value(PARAM_TEXT),
            'message' => new external_value(PARAM_TEXT, 'Optional error message', VALUE_OPTIONAL),
        ]);
    }
}
