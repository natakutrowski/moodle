<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrantPlan;
use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use moodle_database;
use moodle_url;

/**
 * Builds the existing premium access-mail presentation from a non-purchase grant.
 */
final class CommerceGrantAccessMailContextFactory {
    public function __construct(private readonly moodle_database $db) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    /**
     * @return array{recipient:CommerceMailRecipient,context:CommerceMailContext,language:string}
     */
    public function build(
        int $userid,
        int $rootproductid,
        CommerceEntitlementGrantPlan $plan
    ): array {
        $user = $this->db->get_record(
            'user',
            ['id' => $userid, 'deleted' => 0],
            '*',
            MUST_EXIST
        );

        $language = clean_param((string)$user->lang, PARAM_LANG);
        if ($language === '') {
            $language = clean_param(current_language(), PARAM_LANG) ?: 'fr';
        }

        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($this->db, $hydrator);
        $root = $products->find_by_id($rootproductid);
        if ($root === null) {
            throw new \moodle_exception(
                'commerce_manual_grant_product_unavailable',
                'local_subscriptions'
            );
        }

        $resolver = CommerceProductDisplayNameResolver::create($this->db);
        $rootname = $resolver->resolve(
            [$root->get_sku()],
            $language,
            $root->get_name()
        );

        $accesses = [];
        foreach ($plan->get_grants() as $grant) {
            $accesses = array_merge(
                $accesses,
                $this->accesses_for_grant($grant, $language, $resolver)
            );
        }

        $itemtype = match ($root->get_type()) {
            'course_access' => 'course',
            'digital_download' => 'digital',
            'bundle' => 'bundle',
            'service' => 'service',
            default => 'product',
        };

        $item = [
            'type' => $itemtype,
            'title' => $rootname,
            'productsku' => $root->get_sku(),
            'coverurl' => $this->cover_by_product_id(
                $rootproductid,
                CommerceProductCoverContext::CHECKOUT
            ),
            'quantity' => 1,
            'accesses' => $accesses,
        ];

        $fullname = fullname($user);
        $recipient = new CommerceMailRecipient(
            (string)$user->email,
            $fullname,
            (int)$user->id
        );

        return [
            'recipient' => $recipient,
            'language' => $language,
            'context' => new CommerceMailContext([
                'customer' => [
                    'firstname' => (string)$user->firstname,
                    'fullname' => $fullname,
                ],
                'items' => [$item],
                'links' => [
                    'resources' => (new moodle_url('/local/subscriptions/my_digital_products.php'))->out(false),
                    'courses' => (new moodle_url('/my/courses.php'))->out(false),
                    'campus' => (new moodle_url('/mon-campus'))->out(false),
                ],
                'grantaccess' => [
                    'source' => 'crm_manual_grant',
                    'sourcereference' => $plan->get_purchase_reference(),
                    'rootproductid' => $rootproductid,
                    'rootsku' => $root->get_sku(),
                ],
            ]),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function accesses_for_grant(
        \local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant $grant,
        string $language,
        CommerceProductDisplayNameResolver $resolver
    ): array {
        $sku = $grant->get_product_sku();
        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => $sku],
            'id,sku,name,type,metadatajson',
            IGNORE_MISSING
        );

        $title = $resolver->resolve(
            [$sku],
            $language,
            $product ? (string)$product->name : $sku
        );
        $cover = $product
            ? $this->cover_by_product_id((int)$product->id, CommerceProductCoverContext::CHECKOUT)
            : '';

        if ($grant->get_type() === 'course_access') {
            $configuration = $grant->get_configuration();
            $courseid = (int)($configuration['courseid'] ?? 0);
            if ($courseid <= 0 && preg_match('/^course:(\d+)(?::|$)/', $grant->get_resource_key(), $matches)) {
                $courseid = (int)$matches[1];
            }
            if ($courseid <= 0) {
                return [];
            }

            return [[
                'kind' => 'course',
                'label' => '',
                'title' => $title,
                'productsku' => $sku,
                'coverurl' => $cover,
                'url' => (new moodle_url('/course/view.php', ['id' => $courseid]))->out(false),
            ]];
        }

        if ($grant->get_type() !== 'digital_download') {
            return [];
        }

        $access = $this->db->get_record(
            'local_subs_commerce_dig_access',
            ['grantreference' => $grant->get_reference(), 'status' => 'active'],
            'id,downloadtoken,productsku',
            IGNORE_MISSING
        );
        if ($access === false || trim((string)$access->downloadtoken) === '') {
            return [];
        }

        $hasdesktop = false;
        $hasmobile = false;
        if ($product) {
            $files = new CommerceCatalogDigitalFileManager(\context_system::instance());
            $hasdesktop = $files->get_file(
                (int)$product->id,
                CommerceCatalogDigitalFileManager::ROLE_DESKTOP
            ) !== null;
            $hasmobile = $files->get_file(
                (int)$product->id,
                CommerceCatalogDigitalFileManager::ROLE_MOBILE
            ) !== null;

            $metadata = json_decode((string)$product->metadatajson, true);
            if (is_array($metadata)) {
                $hasdesktop = $hasdesktop || trim((string)($metadata['filename'] ?? '')) !== '';
                $hasmobile = $hasmobile || trim((string)($metadata['mobilefilename'] ?? '')) !== '';
            }
        }

        $downloadpath = '/local/subscriptions/digital_native_download.php';
        $result = [];

        if ($hasdesktop || !$hasmobile) {
            $result[] = [
                'kind' => 'download',
                'variant' => 'desktop',
                'label' => '',
                'title' => $title,
                'productsku' => $sku,
                'coverurl' => $cover,
                'url' => (new moodle_url($downloadpath, [
                    'token' => (string)$access->downloadtoken,
                    'version' => 'desktop',
                ]))->out(false),
            ];
        }
        if ($hasmobile) {
            $result[] = [
                'kind' => 'download',
                'variant' => 'mobile',
                'label' => '',
                'title' => $title,
                'productsku' => $sku,
                'coverurl' => $cover,
                'url' => (new moodle_url($downloadpath, [
                    'token' => (string)$access->downloadtoken,
                    'version' => 'mobile',
                ]))->out(false),
            ];
        }

        return $result;
    }

    private function cover_by_product_id(int $productid, string $context): string {
        return (string)(CommerceProductCoverService::create()
            ->resolve($productid, $context)
            ->get_url() ?? '');
    }
}
