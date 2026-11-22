<?php
/**
 * D2.16.1: verifies paid Native Commerce Reader entitlements in Moodle PHPUnit DB.
 * No Stripe calls, emails delivered or changes to the DEV site's database.
 * @coversNothing
 */
declare(strict_types=1);
namespace local_campusreader;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePaidPurchaseCompleter;
use local_subscriptions\commerce\mail\CommerceMailAuditCopyPolicy;
use local_subscriptions\commerce\mail\CommerceMailRuntime;
use local_subscriptions\commerce\mail\context\CommercePurchaseMailContextFactory;
use local_subscriptions\commerce\mail\service\CommerceTransactionalPurchaseMailService;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\purchase\revocation\CommercePurchaseRightsRevocationService;
use local_subscriptions\payment\dto\InternalEvent;
use local_campusreader\local\publication_access;

final class native_reader_paid_cycle_test extends \advanced_testcase {
    public function test_paid_reader_grant_is_permanent_and_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$user, $publication, $purchaseid, $reference, $payments, $event] = $this->fixture();
        $this->setUser($user);
        self::assertFalse(publication_access::can_view($publication));
        $completer = $this->completer($payments);
        $completer->complete($event);
        self::assertSame(CommercePurchaseStatus::FULFILLED,
            $DB->get_field(CommercePersistenceSchema::TABLE_PURCHASE, 'status', ['id' => $purchaseid]));
        $grants = array_values($DB->get_records('local_subs_commerce_grant', ['purchasereference' => $reference]));
        self::assertCount(1, $grants);
        self::assertSame('active', $grants[0]->status);
        self::assertSame('reader_access', $grants[0]->type);
        self::assertSame(publication_access::key((string)$publication->slug), $grants[0]->resourcekey);
        self::assertSame((string)$publication->productsku, $grants[0]->productsku);
        self::assertSame((int)$user->id, (int)$grants[0]->beneficiaryuserid);
        self::assertNull($grants[0]->validuntil);
        self::assertTrue(publication_access::can_view($publication));
        $completer->complete($event);
        self::assertSame(1, $DB->count_records('local_subs_commerce_grant', ['purchasereference' => $reference]));
        self::assertSame(1, $DB->count_records('local_subs_commerce_ful_attempt', [
            'grantreference' => $grants[0]->grantreference,
        ]));
    }

    public function test_explicit_revocation_removes_only_paid_reader_right(): void {
        global $DB;
        $this->resetAfterTest(true);
        [$user, $publication, , $reference, $payments, $event] = $this->fixture();
        $this->setUser($user);
        $this->completer($payments)->complete($event);
        self::assertTrue(publication_access::can_view($publication));
        $revoker = CommercePurchaseRightsRevocationService::create($DB);
        $result = $revoker->revoke($reference, (int)get_admin()->id, 'D2.16.1 PHPUnit only');
        self::assertSame(1, $result['revoked']);
        self::assertFalse(publication_access::can_view($publication));
        self::assertSame('revoked', (string)$DB->get_field('local_subs_commerce_grant', 'status', [
            'purchasereference' => $reference,
        ]));
        self::assertSame(0, $revoker->revoke($reference, (int)get_admin()->id, 'Repeat')['revoked']);
    }

    public function test_unrelated_user_cannot_see_paid_publication(): void {
        $this->resetAfterTest(true);
        [$user, $publication, , , $payments, $event] = $this->fixture();
        $this->setUser($user);
        $this->completer($payments)->complete($event);
        self::assertTrue(publication_access::can_view($publication));
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        self::assertFalse(publication_access::can_view($publication));
    }

    /** Isolated synthetic product, publication, purchase and PAID status. No provider requests. */
    private function fixture(): array {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $suffix = strtolower(bin2hex(random_bytes(7)));
        $sku = 'QA.READER.D2161.' . strtoupper($suffix);
        $slug = 'qa-reader-d2161-' . $suffix;
        $now = time();
        $anthologyid = (int)$DB->insert_record('local_cr_anthology', (object)[
            'slug' => $slug, 'title' => 'D2.16.1 QA', 'subtitle' => '', 'status' => 'draft',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $snapshot = ['schema' => 1, 'anthology' => ['title' => 'D2.16.1 QA'], 'chapters' => []];
        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $revisionid = (int)$DB->insert_record('local_cr_anthrev', (object)[
            'anthologyid' => $anthologyid, 'revno' => 1, 'digest' => hash('sha256', $encoded),
            'snapshotjson' => $encoded, 'state' => 'approved', 'createdby' => (int)get_admin()->id,
            'timecreated' => $now, 'approvedby' => (int)get_admin()->id, 'approvedat' => $now,
        ]);
        $publicationid = (int)$DB->insert_record('local_cr_publication', (object)[
            'slug' => $slug, 'kind' => 'anthology', 'anthologyid' => $anthologyid,
            'revisionid' => $revisionid, 'collectionid' => 0, 'calendarjson' => '{}',
            'productsku' => $sku, 'status' => 'published', 'timecreated' => $now,
            'timemodified' => $now, 'publishedat' => $now,
        ]);
        $publication = $DB->get_record('local_cr_publication', ['id' => $publicationid], '*', MUST_EXIST);
        $catalogue = (new CommerceCatalogFactory($DB))->admin();
        $catalogue->save_product(new CommerceProduct(
            $sku, CommerceProductType::SERVICE, CommerceProductStatus::ACTIVE, 'D2.16.1 Reader QA'
        ));
        $price = $catalogue->set_price(new CommerceProductPrice(
            $sku, CommerceMoney::from_minor(199, 'EUR')
        ));
        $catalogue->replace_definition($sku, [], [new CommerceProductEntitlementDefinition(
            $sku, 'reader_access', publication_access::key($slug), null, 1, []
        )]);
        $uuid = bin2hex(random_bytes(16));
        $reference = 'qa-rdr-d2161-' . $suffix;
        $purchaseid = (int)$DB->insert_record(CommercePersistenceSchema::TABLE_PURCHASE, (object)[
            'purchaseuuid' => $uuid, 'reference' => $reference, 'type' => CommerceItem::TYPE_SERVICE,
            'legacyfamily' => null, 'legacyid' => null, 'userid' => (int)$user->id,
            'customeremail' => (string)$user->email, 'status' => CommercePurchaseStatus::PAYMENT_PENDING,
            'currency' => 'EUR', 'subtotalminor' => 199, 'discountminor' => 0, 'totalminor' => 199,
            'customerjson' => json_encode(['firstname' => $user->firstname, 'lastname' => $user->lastname], JSON_THROW_ON_ERROR),
            'snapshotjson' => '{}', 'metadatajson' => '{}', 'snapshotversion' => 1,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record(CommercePersistenceSchema::TABLE_ITEM, (object)[
            'purchaseid' => $purchaseid, 'position' => 0, 'itemtype' => CommerceItem::TYPE_SERVICE,
            'itemreference' => $sku, 'label' => 'D2.16.1 Reader QA', 'quantity' => 1,
            'currency' => 'EUR', 'unitminor' => 199, 'grossminor' => 199,
            'discountminor' => 0, 'netminor' => 199, 'pricingjson' => '{}',
            'fulfillmentjson' => json_encode(['priceid' => $price->get_id()], JSON_THROW_ON_ERROR),
            'metadatajson' => json_encode(['priceid' => $price->get_id(),
                'catalogue_type' => CommerceProductType::SERVICE], JSON_THROW_ON_ERROR),
        ]);
        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create($uuid, 'stripe', 199, 'EUR');
        $attempt = $payments->update_status((int)$attempt->get_id(), CommercePaymentAttemptStatus::PAID,
            'pi_qa_reader_' . $suffix, null, $now);
        $event = new InternalEvent('checkout_completed', ['meta' => [
            'provider' => 'stripe', 'commerce_payment_id' => (string)$attempt->get_id(),
            'commerce_purchase_uuid' => $uuid,
        ]]);
        return [$user, $publication, $purchaseid, $reference, $payments, $event];
    }

    private function completer(CommercePaymentRepository $payments): CommerceNativePaidPurchaseCompleter {
        global $DB;
        $mail = new CommerceTransactionalPurchaseMailService(
            CommercePurchaseMailContextFactory::create(), CommerceMailRuntime::queue_service(),
            null, new CommerceMailAuditCopyPolicy()
        );
        return new CommerceNativePaidPurchaseCompleter($DB, $payments, $mail);
    }
}
