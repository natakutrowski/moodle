<?php

namespace local_subscriptions\commerce\fulfillment\postaction;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentBatchResult;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;

/**
 * Executes optional actions after the critical access operations.
 *
 * A failure here never changes an already successful fulfillment result.
 */
final class CommercePostFulfillmentCoordinator {

    /**
     * @param CommercePostFulfillmentAction[] $actions
     */
    public function __construct(
        private readonly array $actions = []
    ) {
        foreach ($actions as $action) {
            if (!$action instanceof CommercePostFulfillmentAction) {
                throw new \coding_exception(
                    'Invalid Commerce post-fulfillment action.'
                );
            }
        }
    }

    public function execute(
        CommerceFulfillmentBatchResult $batch,
        ?CommerceFulfillmentContext $context = null
    ): CommercePostFulfillmentReport {
        $context ??= $batch->get_context();
        $results = [];

        foreach ($batch->get_results() as $fulfillmentresult) {
            if (
                $fulfillmentresult->get_status()
                    !== \local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult::STATUS_COMPLETED
            ) {
                continue;
            }

            foreach ($this->actions as $action) {
                if (!$action->supports($fulfillmentresult)) {
                    continue;
                }

                try {
                    $results[] = $action->execute(
                        $fulfillmentresult,
                        $context
                    );
                } catch (\Throwable $exception) {
                    $results[] = new CommercePostFulfillmentActionResult(
                        $action->get_key(),
                        CommercePostFulfillmentActionResult::STATUS_FAILED,
                        $exception->getMessage(),
                        ['exception' => get_class($exception)]
                    );
                }
            }
        }

        return new CommercePostFulfillmentReport($results);
    }
}
