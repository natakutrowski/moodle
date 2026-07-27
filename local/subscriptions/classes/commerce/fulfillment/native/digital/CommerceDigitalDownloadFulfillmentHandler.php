<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;

/** Grants a Native digital download authorization. */
final class CommerceDigitalDownloadFulfillmentHandler implements CommerceNativeFulfillmentHandler {
    public const GRANT_TYPE = 'digital_download';

    public function __construct(
        private readonly CommerceDigitalAccessRepository $repository = new MoodleCommerceDigitalAccessRepository()
    ) {
    }

    public function get_grant_type(): string {
        return self::GRANT_TYPE;
    }

    public function fulfill(
        CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context
    ): CommerceNativeFulfillmentResult {
        $configuration = $grant->get_configuration();
        $maxdownloads = $this->normalise_max_downloads($configuration['maxdownloads'] ?? null);
        $existing = $this->repository->find_by_grant_reference($grant->get_reference());
        $token = $existing !== null
            ? (string) $existing->downloadtoken
            : bin2hex(random_bytes(32));

        $payload = [
            'idempotencykey' => $grant->get_idempotency_key(),
            'resourcekey' => $grant->get_resource_key(),
            'productsku' => $grant->get_product_sku(),
            'beneficiaryuserid' => $grant->get_beneficiary_user_id(),
            'beneficiaryemail' => $grant->get_beneficiary_email(),
            'validfrom' => $grant->get_valid_from(),
            'validuntil' => $grant->get_valid_until(),
            'maxdownloads' => $maxdownloads,
        ];

        if ($context->is_dry_run()) {
            return CommerceNativeFulfillmentResult::skipped(
                $grant,
                'Dry-run: Native digital access was validated without database mutation.',
                $payload + ['dryrun' => true, 'action' => $existing === null ? 'would_create' : 'would_update']
            );
        }

        $outcome = $this->repository->grant($grant, $token, $maxdownloads, $context->get_triggered_at());

        return CommerceNativeFulfillmentResult::completed(
            $grant,
            $payload + $outcome,
            'Native digital download access was granted.'
        );
    }

    private function normalise_max_downloads(mixed $value): ?int {
        if ($value === null || $value === '' || (int) $value === 0) {
            return null;
        }
        $value = (int) $value;
        if ($value < 0) {
            throw new \coding_exception('A Native digital download limit cannot be negative.');
        }
        return $value;
    }
}
