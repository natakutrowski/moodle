<?php
// D2.4.3 QA — explicit, single-entitlement manual Reader test grant. DEV ONLY.
declare(strict_types=1);
namespace local_subscriptions\commerce\grant;
defined('MOODLE_INTERNAL') || die();

/** The normal Native manual grant service remains the only grant writer. */
final class CommerceManualReaderQaGrantService {
    public function __construct(private readonly \moodle_database $db) {}

    /**
     * Read-only readiness of one exact QA account and publication.
     * @return array{userid:int,productid:int,reference:string,sku:string,slug:string}
     */
    public function preview(int $publicationid, int $userid, int $actoruserid, string $reason): array {
        $reason = trim($reason);
        if ($publicationid < 1 || $userid <= 2 || $actoruserid < 2 ||
                !is_siteadmin($actoruserid) || $userid === $actoruserid ||
                \core_text::strlen($reason) < 4 || \core_text::strlen($reason) > 500) {
            throw new \coding_exception('An independent QA account, site administrator and reason are required.');
        }
        $pub = $this->db->get_record('local_cr_publication', ['id' => $publicationid], '*', MUST_EXIST);
        $slug = (string)$pub->slug;
        $sku = strtoupper(trim((string)$pub->productsku));
        if ((string)$pub->status !== 'published' ||
                !(str_starts_with($slug, 'test-') || str_starts_with($slug, 'qa-') ||
                  str_ends_with($slug, '-dev')) ||
                !preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug) ||
                !preg_match('/\AQA_[A-Z0-9_]{3,80}\z/D', $sku)) {
            throw new \coding_exception('Only a published QA-labelled Reader edition is eligible.');
        }
        $product = $this->db->get_record('local_subs_commerce_product', ['sku' => $sku], '*', MUST_EXIST);
        if ((string)$product->type !== 'service' || (string)$product->status !== 'active') {
            throw new \coding_exception('Native QA Service product must be active.');
        }
        // Ensure an active QA product cannot be purchased from the catalogue during this test.
        if ($this->db->record_exists('local_subs_commerce_prod_price',
                ['productid' => (int)$product->id, 'active' => 1])) {
            throw new \coding_exception('QA product must have no active purchase price.');
        }
        if ($this->db->record_exists('local_subs_commerce_prod_comp',
                ['parentproductid' => (int)$product->id])) {
            throw new \coding_exception('QA product cannot be a bundle.');
        }
        $ents = array_values($this->db->get_records('local_subs_commerce_prod_ent',
            ['productid' => (int)$product->id]));
        if (count($ents) !== 1 || (string)$ents[0]->type !== 'reader_access' ||
                (string)$ents[0]->resourcekey !== 'reader:publication:' . $slug) {
            throw new \coding_exception('Exactly one matching Reader entitlement is required.');
        }
        $user = $this->db->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if (!empty($user->suspended) || is_siteadmin($userid) ||
                !preg_match('/\A(?:qa|test)[._-][a-z0-9._-]+\z/iD', (string)$user->username) ||
                $this->db->record_exists('user_enrolments', ['userid' => $userid])) {
            throw new \coding_exception('Beneficiary must be a non-admin QA account without course enrolments.');
        }
        $service = new CommerceManualProductGrantService($this->db);
        $plan = $service->plan($userid, (int)$product->id, $actoruserid, $reason);
        $grants = $plan->get_grants();
        if (count($grants) !== 1) {
            throw new \coding_exception('Manual Native plan is not a single entitlement.');
        }
        $g = $grants[0];
        $ref = (string)$g->get_reference();
        if ($g->get_type() !== 'reader_access' ||
                $g->get_resource_key() !== 'reader:publication:' . $slug ||
                strtoupper($g->get_product_sku()) !== $sku ||
                $g->get_beneficiary_user_id() !== $userid ||
                !preg_match('/\Aent-manual-[a-f0-9]{32}\z/D', $ref)) {
            throw new \coding_exception('Planned Native grant is outside QA Reader scope.');
        }
        $source = (string)$plan->get_purchase_reference();
        $expected = 'manual-u' . $userid . '-p' . (int)$product->id . '-' .
            substr(hash('sha256', $sku), 0, 16);
        if ($source !== $expected ||
                $this->db->record_exists('local_subs_commerce_grant', ['grantreference' => $ref]) ||
                $this->db->record_exists('local_subs_commerce_grant', ['purchasereference' => $source]) ||
                $this->db->record_exists(
                    \local_subscriptions\commerce\persistence\CommercePersistenceSchema::TABLE_PURCHASE,
                    ['reference' => $source])) {
            throw new \coding_exception('QA right was already issued or manual source is not unique.');
        }
        return ['userid' => $userid, 'productid' => (int)$product->id,
            'reference' => $ref, 'sku' => $sku, 'slug' => $slug];
    }

    /** One explicit QA write. Any partial SQL fulfillment is rolled back. */
    public function grant(int $publicationid, int $userid, int $actoruserid, string $reason): array {
        $info = $this->preview($publicationid, $userid, $actoruserid, $reason);
        $lock = \core\lock\lock_config::get_lock_factory('local_subscriptions_reader_qa_revocation')
            ->get_lock('grant-' . hash('sha256', $info['reference']), 10);
        if (!$lock) { throw new \RuntimeException('Unable to lock QA Reader grant.'); }
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $info = $this->preview($publicationid, $userid, $actoruserid, $reason);
                $result = (new CommerceManualProductGrantService($this->db))
                    ->grant($userid, $info['productid'], $actoruserid, $reason);
                if ((int)$result['created'] !== 1 || (int)$result['identical'] !== 0 ||
                        count($result['results']) !== 1 ||
                        !$result['results'][0]->is_completed()) {
                    throw new \RuntimeException('QA Native fulfillment was not exactly one completed new right.');
                }
                $verified = (new CommerceManualReaderGrantRevocationService($this->db))
                    ->preview($info['reference'], $publicationid);
                if ($verified['status'] !== 'active' || $verified['userid'] !== $userid) {
                    throw new \RuntimeException('QA grant could not be verified after Native fulfillment.');
                }
                $tx->allow_commit();
                error_log('[CampusFR Reader QA] manual test grant issued pub=' . $publicationid .
                    ' userid=' . $userid . ' grant=' . substr(hash('sha256', $info['reference']), 0, 12));
                return $info;
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }
}
