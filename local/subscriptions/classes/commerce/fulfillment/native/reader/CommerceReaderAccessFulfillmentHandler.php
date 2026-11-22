<?php
// D2.4.3 — Native Commerce reader_access, fulfilled by active grant ledger.
declare(strict_types=1);
namespace local_subscriptions\commerce\fulfillment\native\reader;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentResult;
use local_campusreader\local\publication_access;

final class CommerceReaderAccessFulfillmentHandler implements CommerceNativeFulfillmentHandler {
    public function get_grant_type(): string { return 'reader_access'; }

    public function fulfill(CommerceEntitlementGrant $grant,
        CommerceNativeFulfillmentContext $context): CommerceNativeFulfillmentResult {
        if ($grant->get_type() !== 'reader_access' ||
                !preg_match('/\Areader:publication:([a-z0-9][a-z0-9-]{0,63})\z/D',
                    $grant->get_resource_key(), $m)) {
            throw new \coding_exception('Reader grant resource key must identify a publication.');
        }
        if ($grant->get_beneficiary_user_id() === null) {
            throw new \coding_exception('Reader access cannot be granted without a Moodle user ID.');
        }
        $pub = publication_access::publication($m[1]);
        if (!$pub || strtoupper((string)($pub->productsku ?? '')) !== $grant->get_product_sku()) {
            throw new \coding_exception('Reader grant does not match a linked publication.');
        }
        return $context->is_dry_run()
            ? CommerceNativeFulfillmentResult::skipped($grant, 'Validated reader access (dry-run).')
            : CommerceNativeFulfillmentResult::completed($grant, [
                'publication' => $m[1], 'resourcekey' => $grant->get_resource_key(),
            ], 'Reader access fulfilled by the Native grant ledger.');
    }
}
