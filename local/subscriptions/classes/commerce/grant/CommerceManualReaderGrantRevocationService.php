<?php
// CampusFR D2.4.3 QA — targeted revocation for MANUAL Reader grants only.
declare(strict_types=1);
namespace local_subscriptions\commerce\grant;
defined('MOODLE_INTERNAL') || die();

/** No purchase rights, course enrolments or other resource types are modified. */
final class CommerceManualReaderGrantRevocationService {
    private const GRANTS = 'local_subs_commerce_grant';
    private const FULFILLMENTS = 'local_subs_commerce_ful_state';
    private const PRODUCTS = 'local_subs_commerce_product';
    private const PURCHASES = 'local_subs_commerce_purchase';

    public function __construct(private readonly \moodle_database $db) {}

    /** @return array{grantreference:string,userid:int,status:string,sku:string} */
    public function preview(string $reference, int $publicationid): array {
        $info = $this->inspect($reference, $publicationid);
        return ['grantreference' => (string)$info['grant']->grantreference,
            'userid' => (int)$info['grant']->beneficiaryuserid,
            'status' => (string)$info['grant']->status, 'sku' => (string)$info['pub']->productsku];
    }

    /** @return array{revoked:int,userid:int,publicationid:int} */
    public function revoke(string $reference, int $publicationid, int $actoruserid, string $reason): array {
        $reason = trim($reason);
        if ($actoruserid < 2 || !is_siteadmin($actoruserid) ||
                $reason === '' || \core_text::strlen($reason) > 500 ||
                $publicationid < 1) {
            throw new \coding_exception('Site admin, publication and a reason (1–500 chars) are required.');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_subscriptions_reader_qa_revocation')
            ->get_lock('grant-' . hash('sha256', $reference), 10);
        if (!$lock) { throw new \RuntimeException('Unable to obtain the Reader QA grant lock.'); }
        try {
            $tx = $this->db->start_delegated_transaction();
            try {
                $info = $this->inspect($reference, $publicationid);
                $grant = $info['grant'];
                $output = ['revoked' => 0, 'userid' => (int)$grant->beneficiaryuserid,
                    'publicationid' => $publicationid];
                if ($grant->status === 'revoked') { $tx->allow_commit(); return $output; }
                if ($grant->status !== 'active') {
                    throw new \coding_exception('Reader QA grant not in an active revocable state.');
                }
                $metadata = $info['metadata'];
                $metadata['rights_revocation'] = [
                    'source' => 'admin_explicit_reader_qa_revocation',
                    'revokedat' => time(), 'revokedbyuserid' => $actoruserid,
                    'reason' => $reason,
                ];
                $this->db->update_record(self::GRANTS, (object)[
                    'id' => (int)$grant->id,
                    'status' => 'revoked',
                    'metadatajson' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'timemodified' => time(),
                ]);
                $output['revoked'] = 1;
                $tx->allow_commit();
                error_log('[CampusFR Reader QA] manual reader grant revoked pub=' . $publicationid .
                    ' grant=' . substr(hash('sha256', $reference), 0, 12));
                return $output;
            } catch (\Throwable $e) { $tx->rollback($e); throw $e; }
        } finally { $lock->release(); }
    }

    /** @return array{grant:\stdClass,pub:\stdClass,metadata:array} */
    private function inspect(string $reference, int $publicationid): array {
        if ($publicationid < 1 || !preg_match('/\Aent-manual-[a-f0-9]{32}\z/D', $reference)) {
            throw new \coding_exception('Only deterministic manual grants may be examined.');
        }
        $pub = $this->db->get_record('local_cr_publication', ['id' => $publicationid], '*', MUST_EXIST);
        $slug = (string)$pub->slug;
        $sku = strtoupper(trim((string)$pub->productsku));
        if (!(str_starts_with($slug, 'test-') || str_starts_with($slug, 'qa-') ||
                str_ends_with($slug, '-dev')) || !str_starts_with($sku, 'QA_') ||
                !preg_match('/\A[a-z0-9][a-z0-9-]{0,63}\z/D', $slug)) {
            throw new \coding_exception('Only QA-labelled Reader publications are supported.');
        }
        $prod = $this->db->get_record(self::PRODUCTS, ['sku' => $sku], '*', MUST_EXIST);
        if ((string)$prod->type !== 'service') {
            throw new \coding_exception('Reader QA revocation requires a Service product.');
        }
        $gr = $this->db->get_record(self::GRANTS, ['grantreference' => $reference], '*', MUST_EXIST);
        $key = 'reader:publication:' . $slug;
        if ((string)$gr->type !== 'reader_access' || (string)$gr->resourcekey !== $key ||
                strtoupper((string)$gr->productsku) !== $sku ||
                !in_array((string)$gr->status, ['active', 'revoked'], true) ||
                (int)$gr->beneficiaryuserid < 2) {
            throw new \coding_exception('Reader grant is out of QA scope.');
        }
        $metadata = json_decode((string)$gr->metadatajson, true);
        if (!is_array($metadata) || ($metadata['source'] ?? '') !== CommerceManualProductGrantService::SOURCE ||
                (int)($metadata['rootproductid'] ?? 0) !== (int)$prod->id ||
                strtoupper((string)($metadata['rootsku'] ?? '')) !== $sku) {
            throw new \coding_exception('Grant is not a manual Native QA grant for this product.');
        }
        $userid = (int)$gr->beneficiaryuserid;
        $sourceref = 'manual-u' . $userid . '-p' . (int)$prod->id . '-' .
            substr(hash('sha256', $sku), 0, 16);
        if ((string)$gr->purchasereference !== $sourceref ||
                $this->db->count_records(self::GRANTS, ['purchasereference' => $sourceref]) !== 1) {
            throw new \coding_exception('Grant source is ambiguous or contains several entitlements.');
        }
        // The manual source reference must NEVER be a real purchase.
        $purchasetable = \local_subscriptions\commerce\persistence\CommercePersistenceSchema::TABLE_PURCHASE;
        if ($this->db->record_exists($purchasetable, ['reference' => $sourceref])) {
            throw new \coding_exception('Purchase-sourced Reader rights cannot be revoked here.');
        }
        $state = $this->db->get_record(self::FULFILLMENTS,
            ['grantreference' => $reference], 'id,status', IGNORE_MISSING);
        if (!$state || (string)$state->status !== 'completed') {
            throw new \coding_exception('Reader manual fulfillment has not completed.');
        }
        return ['grant' => $gr, 'pub' => $pub, 'metadata' => $metadata];
    }
}
