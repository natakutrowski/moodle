<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\persistence;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Moodle database persistence for Native fulfillment state and attempts. */
final class MoodleCommerceNativeFulfillmentPersistenceRepository implements CommerceNativeFulfillmentPersistenceRepository {
    private const STATE_TABLE = 'local_subs_commerce_ful_state';
    private const ATTEMPT_TABLE = 'local_subs_commerce_ful_attempt';

    public function find_state(string $grantreference): ?\stdClass {
        global $DB;
        $record = $DB->get_record(self::STATE_TABLE, ['grantreference' => trim($grantreference)]);
        return $record === false ? null : $record;
    }

    public function begin_attempt(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context,
        string $handlerclass
    ): int {
        global $DB;
        $now = $context->get_triggered_at();
        $state = $this->find_state($grant->get_reference());

        if ($state === null) {
            $state = (object) [
                'grantreference' => $grant->get_reference(),
                'idempotencykey' => $grant->get_idempotency_key(),
                'granttype' => $grant->get_type(),
                'handlerclass' => $handlerclass,
                'status' => 'running',
                'attempts' => 1,
                'lastexecutionreference' => $context->get_execution_reference(),
                'lastsource' => $context->get_source(),
                'lastactoruserid' => $context->get_actor_user_id(),
                'lastpayloadjson' => '{}',
                'lastmessage' => null,
                'lasterrorclass' => null,
                'timecreated' => $now,
                'timestarted' => $now,
                'timecompleted' => null,
                'timemodified' => $now,
            ];
            $DB->insert_record(self::STATE_TABLE, $state);
        } else {
            $state->handlerclass = $handlerclass;
            $state->status = 'running';
            $state->attempts = ((int) $state->attempts) + 1;
            $state->lastexecutionreference = $context->get_execution_reference();
            $state->lastsource = $context->get_source();
            $state->lastactoruserid = $context->get_actor_user_id();
            $state->timestarted = $now;
            $state->timecompleted = null;
            $state->timemodified = $now;
            $DB->update_record(self::STATE_TABLE, $state);
        }

        return (int) $DB->insert_record(self::ATTEMPT_TABLE, (object) [
            'grantreference' => $grant->get_reference(),
            'idempotencykey' => $grant->get_idempotency_key(),
            'executionreference' => $context->get_execution_reference(),
            'granttype' => $grant->get_type(),
            'handlerclass' => $handlerclass,
            'status' => 'running',
            'dryrun' => $context->is_dry_run() ? 1 : 0,
            'source' => $context->get_source(),
            'actoruserid' => $context->get_actor_user_id(),
            'payloadjson' => '{}',
            'message' => null,
            'errorclass' => null,
            'timestarted' => $now,
            'timecompleted' => null,
        ]);
    }

    public function complete_attempt(
        int $attemptid,
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context,
        string $handlerclass,
        CommerceNativeFulfillmentResult $result
    ): void {
        global $DB;
        $now = time();
        $payloadjson = json_encode($result->get_payload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payloadjson === false) {
            throw new \coding_exception('Unable to encode Native fulfillment result payload.');
        }

        $attempt = $DB->get_record(self::ATTEMPT_TABLE, ['id' => $attemptid], '*', MUST_EXIST);
        $attempt->status = $result->get_status();
        $attempt->payloadjson = $payloadjson;
        $attempt->message = $result->get_message();
        $attempt->errorclass = $result->get_error_class();
        $attempt->timecompleted = $now;
        $DB->update_record(self::ATTEMPT_TABLE, $attempt);

        $state = $this->find_state($grant->get_reference());
        if ($state === null) {
            throw new \coding_exception('Native fulfillment state disappeared during execution.');
        }
        $state->handlerclass = $handlerclass;
        $state->status = $result->get_status();
        $state->lastexecutionreference = $context->get_execution_reference();
        $state->lastsource = $context->get_source();
        $state->lastactoruserid = $context->get_actor_user_id();
        $state->lastpayloadjson = $payloadjson;
        $state->lastmessage = $result->get_message();
        $state->lasterrorclass = $result->get_error_class();
        $state->timecompleted = $now;
        $state->timemodified = $now;
        $DB->update_record(self::STATE_TABLE, $state);
    }
}
