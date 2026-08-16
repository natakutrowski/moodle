<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\mail\CommerceMailIdempotencyKey;
use local_subscriptions\commerce\mail\CommerceMailQueueProcessor;
use local_subscriptions\commerce\mail\CommerceMailQueueService;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\context\CommerceGrantAccessMailContextFactory;

/**
 * Queues one transactional access mail per root CRM grant.
 *
 * Bulk callers should use queue-only mode so the shared mail cron/throttling
 * remains responsible for provider-safe delivery.
 */
final class CommerceGrantAccessMailService {
    public function __construct(
        private readonly CommerceGrantAccessMailContextFactory $contexts,
        private readonly CommerceMailQueueService $queue,
        private readonly ?CommerceMailQueueProcessor $processor = null
    ) {
    }

    public static function create(): self {
        return new self(
            CommerceGrantAccessMailContextFactory::create(),
            CommerceMailRuntime::queue_service(),
            CommerceMailRuntime::processor()
        );
    }

    public function queue(
        int $userid,
        int $rootproductid,
        CommerceEntitlementGrantPlan $plan,
        bool $immediate = false,
        array $mailtemplatesnapshot = [],
        array $campaigncontext = []
    ): ?\stdClass {
        try {
            $mail = $this->contexts->build(
                $userid,
                $rootproductid,
                $plan,
                $mailtemplatesnapshot,
                $campaigncontext
            );

            $campaignid = (int)($campaigncontext['campaignid'] ?? 0);
            $memberid = (int)($campaigncontext['memberid'] ?? 0);
            $idempotencykey = $campaignid > 0 && $memberid > 0
                ? CommerceMailIdempotencyKey::normalise(
                    'grant-campaign:' . $campaignid
                    . ':member:' . $memberid
                    . ':' . CommerceMailType::GRANT_ACCESS
                )
                : CommerceMailIdempotencyKey::for_grant_source(
                    $plan->get_purchase_reference(),
                    CommerceMailType::GRANT_ACCESS
                );

            $record = $this->queue->queue(new CommerceMailRequest(
                CommerceMailType::GRANT_ACCESS,
                $mail['recipient'],
                $mail['context'],
                $mail['language'],
                $idempotencykey,
                null
            ));

            if ($immediate && $this->processor !== null) {
                $this->processor->process_ids([(int)$record->id]);
            }

            return $record;
        } catch (\Throwable $exception) {
            // Access delivery is the business transaction. A mail outage must never roll it back.
            debugging(
                '[Commerce Grant Mail] Access notification could not be queued: '
                . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
            return null;
        }
    }
}
