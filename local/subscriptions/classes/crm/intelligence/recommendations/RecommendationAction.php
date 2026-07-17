<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable action proposed by a CRM recommendation.
 *
 * Recommendation actions are advisory. This object describes an action that
 * may be presented to an administrator, but it never executes the action.
 *
 * Actual execution remains delegated to the existing recommendation action
 * registry and its permission-aware executors.
 */
final class RecommendationAction {

    /**
     * Open a CRM user profile.
     */
    public const OPEN_USER_PROFILE = 'open_user_profile';

    /**
     * Open an Inbox conversation.
     */
    public const OPEN_INBOX_THREAD = 'open_inbox_thread';

    /**
     * Open a Work Item.
     */
    public const OPEN_WORK_ITEM = 'open_work_item';

    /**
     * Open a payment record.
     */
    public const OPEN_PAYMENT = 'open_payment';

    /**
     * Create an internal CRM note.
     */
    public const CREATE_NOTE = 'create_note';

    /**
     * Suggest the creation of a Work Item.
     *
     * The action must not create the Work Item automatically.
     */
    public const PROPOSE_WORK_ITEM = 'propose_work_item';

    /**
     * Prepare an email without sending it automatically.
     */
    public const PREPARE_EMAIL = 'prepare_email';

    /**
     * Open the CRM Assistant with a recommendation already selected.
     */
    public const OPEN_CRM_ASSISTANT = 'open_crm_assistant';

    /**
     * Prepare a coordinated human intervention.
     *
     * This action does not contact the user and does not create a Work Item.
     */
    public const PREPARE_COORDINATED_FOLLOW_UP = 'prepare_coordinated_follow_up';

    /**
     * Existing legacy email actions.
     */
    public const EMAIL_TRIAL_CONVERSION = 'email_trial_conversion';
    public const EMAIL_UPGRADE = 'email_upgrade';
    public const EMAIL_WINBACK = 'email_winback';
    public const EMAIL_DIGITAL_PRODUCT = 'email_digital_product';

    /**
     * @param string $key Stable identifier of this proposed action.
     * @param string $action Action understood by RecommendationActionRegistry.
     * @param array $params Serializable execution or navigation parameters.
     * @param bool $primary Whether this is the preferred recommendation action.
     * @param bool $confirmationrequired Whether explicit confirmation is needed.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $action,
        public readonly array $params = [],
        public readonly bool $primary = false,
        public readonly bool $confirmationrequired = false
    ) {
        if (!$this->is_valid_key($this->key)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation action key.'
            );
        }

        if (!$this->is_valid_action($this->action)) {
            throw new \InvalidArgumentException(
                'Invalid recommendation action identifier.'
            );
        }

        $this->validate_params($this->params);
    }

    /**
     * Stable action identity used by APIs and renderers.
     */
    public function identity(): string {
        return $this->key . ':' . $this->action;
    }

    /**
     * Serialize this proposed action.
     */
    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'action' => $this->action,
            'identity' => $this->identity(),
            'params' => $this->params,
            'primary' => $this->primary,
            'confirmationrequired' => $this->confirmationrequired,
        ];
    }

    /**
     * Validate a stable technical key.
     */
    private function is_valid_key(string $key): bool {
        return preg_match('/^[a-z][a-z0-9_.-]{1,99}$/', $key) === 1;
    }

    /**
     * Validate an action identifier.
     *
     * Action identifiers remain extensible because existing and future
     * executors are registered independently in RecommendationActionRegistry.
     */
    private function is_valid_action(string $action): bool {
        return preg_match('/^[a-z][a-z0-9_.-]{1,99}$/', $action) === 1;
    }

    /**
     * Ensure action parameters remain safely serializable.
     */
    private function validate_params(array $params): void {
        foreach ($params as $key => $value) {
            if (
                !is_string($key) ||
                preg_match('/^[a-z][a-z0-9_]{0,49}$/', $key) !== 1
            ) {
                throw new \InvalidArgumentException(
                    'Recommendation action parameter keys must be stable technical strings.'
                );
            }

            if (!$this->is_valid_param_value($value)) {
                throw new \InvalidArgumentException(
                    'Recommendation action parameters contain an unsupported value.'
                );
            }
        }
    }

    /**
     * Validate action parameter values recursively.
     */
    private function is_valid_param_value(mixed $value): bool {
        if ($value === null || is_scalar($value)) {
            return true;
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $nestedvalue) {
            if (!$this->is_valid_param_value($nestedvalue)) {
                return false;
            }
        }

        return true;
    }
}