<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\postaction;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\native\batch\CommerceNativeFulfillmentBatchResult;

/** Runs non-critical actions after successful Native fulfillment results. */
final class CommerceNativePostFulfillmentCoordinator {
    /** @param CommerceNativePostFulfillmentAction[] $actions */
    public function __construct(private readonly array $actions = []) {
        $keys = [];
        foreach ($this->actions as $action) {
            if (!$action instanceof CommerceNativePostFulfillmentAction) {
                throw new \coding_exception('Invalid Native post-fulfillment action.');
            }
            $key = trim($action->get_key());
            if ($key === '' || isset($keys[$key])) {
                throw new \coding_exception('Native post-fulfillment action keys must be unique and non-empty.');
            }
            $keys[$key] = true;
        }
    }

    public function execute(CommerceNativeFulfillmentBatchResult $batch): CommerceNativePostFulfillmentReport {
        $results = [];
        foreach ($batch->get_results() as $fulfillmentresult) {
            if (!$fulfillmentresult->is_completed()) {
                continue;
            }
            foreach ($this->actions as $action) {
                if (!$action->supports($fulfillmentresult)) {
                    continue;
                }
                try {
                    $results[] = $action->execute($fulfillmentresult, $batch->get_context());
                } catch (\Throwable $exception) {
                    $results[] = new CommerceNativePostFulfillmentActionResult(
                        $action->get_key(),
                        CommerceNativePostFulfillmentActionResult::STATUS_FAILED,
                        $exception->getMessage(),
                        ['exception' => get_class($exception)]
                    );
                }
            }
        }
        return new CommerceNativePostFulfillmentReport($results);
    }
}
