<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\digital\library;

use local_subscriptions\commerce\catalog\assets\CommerceCatalogResponsiveImageService;
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontUrlResolver;
use local_subscriptions\commerce\student\StudentCommercePurchaseFactory;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\url\CommerceCustomerPublicUrlResolver;
use moodle_url;

/**
 * Builds the unified customer read model for owned digital products.
 *
 * The library deliberately stays at product level. Purchase references,
 * payment data and acquisition provenance remain on the order pages.
 */
final class CommerceDigitalLibraryService {
    private CommerceCatalogDigitalFileManager $digitalfiles;

    public static function create(): self {
        global $DB;

        return new self(
            StudentCommercePurchaseFactory::create(),
            new CommercePurchaseReadRepository($DB),
            CommerceOrderPresentationService::create(),
            new CommerceLegacyStorefrontProductResolver($DB),
            CommerceStorefrontRepository::create($DB)
        );
    }

    public function __construct(
        private readonly object $studentpurchases,
        private readonly CommercePurchaseReadRepository $purchaserepository,
        private readonly CommerceOrderPresentationService $orderpresentationservice,
        private readonly CommerceLegacyStorefrontProductResolver $legacyproductresolver,
        private readonly CommerceStorefrontRepository $storefrontrepository
    ) {
        $this->digitalfiles = new CommerceCatalogDigitalFileManager(\context_system::instance());
    }

    public function get_for_customer(int $userid, string $email): CommerceDigitalLibrary {
        global $DB;

        $resources = [];
        $nativeorders = $this->purchaserepository->find_details_for_customer($userid, $email);
        $nativebylegacy = [];
        $nativebyreference = [];

        foreach ($nativeorders as $details) {
            $presentation = $this->orderpresentationservice->present($details);
            $nativebyreference[$presentation->reference] = $presentation;
            if ($details->legacyfamily !== null && $details->legacyid !== null) {
                $nativebylegacy[$details->legacyfamily . ':' . $details->legacyid] = $presentation;
            }

            foreach ($presentation->items as $itemindex => $item) {
                foreach ($item->accesses as $accessindex => $access) {
                    if (!in_array(strtolower($access->type), ['digital', 'digital_download', 'digital_product'], true)) {
                        continue;
                    }
                    if (!$access->available || $access->url === null) {
                        continue;
                    }

                    $context = $this->native_product_context($access, $item);
                    $sku = $context['sku'];
                    $product = $context['product'];
                    $downloads = $this->downloads_from_native_access(
                        $access,
                        $product,
                        $context['accessid'],
                        $context['aggregatecount'],
                        $context['aggregatelastdownloadat']
                    );
                    $key = $sku !== ''
                        ? 'product:sku:' . strtolower($sku)
                        : 'native:' . $presentation->reference . ':' . $itemindex . ':' . $accessindex;
                    $producturl = $this->public_product_url($sku);

                    $resource = new CommerceDigitalResourcePresentation(
                        $key,
                        $context['title'],
                        'native',
                        $producturl,
                        null,
                        null,
                        $presentation->paidat ?? $presentation->timecreated,
                        $downloads,
                        false,
                        ['sku' => $sku, 'itemtype' => $item->type],
                        $context['coverurl'],
                        !empty($context['productid'])
                            ? CommerceCatalogResponsiveImageService::create()->resolve(
                                (int)$context['productid'], 'resources'
                            )
                            : null
                    );
                    $resources[$key] = $this->prefer_richer_resource($resources[$key] ?? null, $resource);
                }
            }
        }

        /*
         * Manual CRM grants deliberately do not fabricate a purchase row. They
         * therefore cannot be discovered through CommercePurchaseReadRepository.
         * Project their persisted Native digital-access rows directly into the
         * owned-resource library. Purchased resources already present above win
         * through the shared product key, so this remains deduplicated.
         */
        foreach ($this->standalone_native_digital_resources($userid, $email) as $resource) {
            $resources[$resource->key] = $this->prefer_richer_resource(
                $resources[$resource->key] ?? null,
                $resource
            );
        }

        $studentcollection = $this->studentpurchases->get_for_customer($userid, $email);
        $legacypurchases = $studentcollection->get_digital_purchases();
        $productids = array_values(array_unique(array_filter(array_map(
            static fn(object $purchase): int => (int)($purchase->productid ?? 0),
            $legacypurchases
        ))));
        $products = $productids === [] ? [] : $DB->get_records_list('subscription_digital_product', 'id', $productids);
        $translations = $this->load_translations($productids);
        $language = current_language();

        foreach ($legacypurchases as $purchase) {
            $legacyid = (int)($purchase->id ?? 0);
            $reference = trim((string)($purchase->commerce_reference ?? ''));
            $native = $reference !== ''
                ? ($nativebyreference[$reference] ?? null)
                : ($nativebylegacy['digital:' . $legacyid] ?? null);

            if ($native !== null && $this->presentation_has_digital_access($native)) {
                continue;
            }

            $productid = (int)($purchase->productid ?? 0);
            $product = $products[$productid] ?? null;
            $translation = $translations[$productid][$language]
                ?? $translations[$productid]['fr']
                ?? null;
            $title = trim((string)($translation->title ?? $product->name ?? $purchase->productname ?? ''));
            if ($title === '') {
                $title = get_string('digital_library_resource_number', 'local_subscriptions', $productid ?: $legacyid);
            }

            $downloads = [];
            $token = trim((string)($purchase->download_token ?? ''));
            if ($token !== '') {
                $downloads[] = $this->legacy_download(
                    get_string('digital_download_classic', 'local_subscriptions'),
                    UrlFactory::digital_download(['token' => $token])->out(false),
                    'desktop',
                    trim((string)($product->filename ?? $purchase->filename ?? ''))
                );
                $mobilefilename = trim((string)($product->mobile_filename ?? $purchase->mobile_filename ?? ''));
                if ($mobilefilename !== '') {
                    $downloads[] = $this->legacy_download(
                        get_string('digital_download_mobile', 'local_subscriptions'),
                        UrlFactory::digital_download(['token' => $token, 'version' => 'mobile'])->out(false),
                        'mobile',
                        $mobilefilename
                    );
                }
            }

            $nativeproduct = $this->legacyproductresolver->resolve_digital_product($productid);
            $sku = $nativeproduct ? trim((string)$nativeproduct->sku) : '';
            $producturl = $this->public_product_url($sku);
            $key = $sku !== '' ? 'product:sku:' . strtolower($sku) : 'product:legacy:' . $productid;
            $resource = new CommerceDigitalResourcePresentation(
                $key,
                $title,
                'legacy',
                $producturl,
                null,
                null,
                (int)($purchase->payment_date ?? $purchase->creation_date ?? 0),
                $downloads,
                false,
                ['productid' => $productid, 'sku' => $sku],
                $this->legacy_cover_url($product)
            );
            $resources[$key] = $this->prefer_richer_resource($resources[$key] ?? null, $resource);
        }

        uasort($resources, static fn(
            CommerceDigitalResourcePresentation $a,
            CommerceDigitalResourcePresentation $b
        ): int => strcasecmp($a->title, $b->title));

        return new CommerceDigitalLibrary(array_values($resources), $userid, trim($email));
    }

    /**
     * Build Native digital resources which exist without a purchase row.
     *
     * This is the normal persistence shape for CRM manual grants.
     *
     * @return CommerceDigitalResourcePresentation[]
     */
    private function standalone_native_digital_resources(int $userid, string $email): array {
        global $DB;

        $email = trim(\core_text::strtolower($email));
        $params = ['userid' => $userid, 'email' => $email, 'status' => 'active'];
        $records = $DB->get_records_select(
            'local_subs_commerce_dig_access',
            '(beneficiaryuserid = :userid OR LOWER(beneficiaryemail) = :email) AND status = :status',
            $params,
            'timecreated ASC, id ASC'
        );

        $resources = [];
        $now = time();
        foreach ($records as $record) {
            if ((int)$record->validfrom > $now
                    || ($record->validuntil !== null && (int)$record->validuntil < $now)
                    || ($record->maxdownloads !== null && (int)$record->downloadcount >= (int)$record->maxdownloads)
                    || trim((string)$record->downloadtoken) === '') {
                continue;
            }

            $access = (object)[
                'grantreference' => (string)$record->grantreference,
                'url' => (new moodle_url('/local/subscriptions/digital_native_download.php', [
                    'token' => (string)$record->downloadtoken,
                    'version' => 'desktop',
                ]))->out(false),
                'metadata' => [
                    'productsku' => (string)$record->productsku,
                    'resourcekey' => (string)$record->resourcekey,
                ],
            ];
            $item = (object)[
                'label' => '',
                'metadata' => ['productsku' => (string)$record->productsku],
            ];

            $context = $this->native_product_context($access, $item);
            $downloads = $this->downloads_from_native_access(
                $access,
                $context['product'],
                (int)$record->id,
                (int)$record->downloadcount,
                $record->lastdownloadat === null ? null : (int)$record->lastdownloadat
            );
            if ($downloads === []) {
                continue;
            }

            $sku = $context['sku'];
            $key = $sku !== ''
                ? 'product:sku:' . strtolower($sku)
                : 'native:manual:' . (int)$record->id;

            $resources[] = new CommerceDigitalResourcePresentation(
                $key,
                $context['title'],
                'native',
                $this->public_product_url($sku),
                null,
                null,
                (int)$record->timecreated,
                $downloads,
                false,
                [
                    'sku' => $sku,
                    'source' => 'crm_manual_grant',
                    'grantreference' => (string)$record->grantreference,
                ],
                $context['coverurl'],
                !empty($context['productid'])
                    ? CommerceCatalogResponsiveImageService::create()->resolve(
                        (int)$context['productid'],
                        'resources'
                    )
                    : null
            );
        }

        return $resources;
    }

    /**
     * Resolve the direct customer-facing product Storefront URL.
     *
     * Mes Ressources is an owned surface: its explicit product-page link must
     * open the Storefront rather than restart public discovery in a Showroom.
     * The low-level customer resolver remains a safe fallback for incomplete
     * or legacy catalogue records.
     */
    private function public_product_url(string $sku): ?string {
        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            return null;
        }

        $product = $this->storefrontrepository->find_by_sku(
            $sku,
            current_language(),
            null,
            true
        );
        if ($product !== null) {
            return CommerceStorefrontUrlResolver::direct_storefront($product)->out(false);
        }

        return CommerceCustomerPublicUrlResolver::storefront($sku)->out(false);
    }

    private function downloads_from_native_access(
        object $access,
        ?\stdClass $product,
        ?int $accessid,
        ?int $aggregatecount,
        ?int $aggregatelastdownloadat
    ): array {
        $downloads = [];
        $resourcekey = trim((string)($access->metadata['resourcekey'] ?? ''));
        $desktopfile = $this->native_file_information(
            $product,
            CommerceCatalogDigitalFileManager::ROLE_DESKTOP,
            $resourcekey
        );
        $mobilefile = $this->native_file_information(
            $product,
            CommerceCatalogDigitalFileManager::ROLE_MOBILE,
            $resourcekey
        );

        // Prefer the files actually attached to the delivered digital product.
        // Presentation metadata may be incomplete for bundle component grants.
        $hasdesktop = $desktopfile['available'] || !empty($access->metadata['hasdesktop']);
        $hasmobile = $mobilefile['available'] || !empty($access->metadata['hasmobile']);
        $variantcount = (int)($hasdesktop || !$hasmobile) + (int)$hasmobile;
        $history = $this->native_download_history(
            $accessid,
            $aggregatecount,
            $aggregatelastdownloadat,
            $variantcount,
            $hasmobile && !$hasdesktop ? 'mobile' : 'desktop'
        );

        if ($hasdesktop || !$hasmobile) {
            $downloads[] = $this->native_download(
                get_string('digital_download_classic', 'local_subscriptions'),
                (string)$access->url,
                'desktop',
                CommerceCatalogDigitalFileManager::ROLE_DESKTOP,
                $desktopfile,
                $history['desktop']['count'],
                $history['desktop']['lastdownloadat']
            );
        }
        if ($hasmobile) {
            $url = new moodle_url((string)$access->url);
            $url->param('version', 'mobile');
            $downloads[] = $this->native_download(
                get_string('digital_download_mobile', 'local_subscriptions'),
                $url->out(false),
                'mobile',
                CommerceCatalogDigitalFileManager::ROLE_MOBILE,
                $mobilefile,
                $history['mobile']['count'],
                $history['mobile']['lastdownloadat']
            );
        }

        return $downloads;
    }


    /**
     * Resolve the actual digital product behind an access grant.
     *
     * Bundle purchase item labels describe the commercial container. The digital
     * library must instead present the concrete product delivered by the grant.
     *
     * @return array{sku:string,product:?\stdClass,accessid:?int,aggregatecount:?int,aggregatelastdownloadat:?int,title:string,coverurl:?string}
     */
    private function native_product_context(object $access, object $item): array {
        global $DB;

        $digitalaccess = false;
        $grantreference = trim((string)($access->grantreference ?? ''));
        if ($grantreference !== '') {
            $digitalaccess = $DB->get_record(
                'local_subs_commerce_dig_access',
                ['grantreference' => $grantreference],
                'id,productsku,downloadcount,lastdownloadat',
                IGNORE_MISSING
            );
        }

        $sku = trim((string)($digitalaccess->productsku
            ?? $access->metadata['productsku']
            ?? $item->metadata['productsku']
            ?? $item->metadata['sku']
            ?? ''));
        $product = $sku !== ''
            ? $DB->get_record(
                'local_subs_commerce_product',
                ['sku' => $sku],
                'id,sku,name,metadatajson',
                IGNORE_MISSING
            )
            : false;
        $storefront = $sku !== ''
            ? $this->storefrontrepository->find_by_sku($sku, current_language(), null, true)
            : null;

        $translationname = '';
        if ($product !== false) {
            $translationname = trim((string)$DB->get_field('local_subs_commerce_prod_tr', 'name', [
                'productid' => (int)$product->id,
                'language' => current_language(),
            ], IGNORE_MISSING));
        }
        $title = trim((string)($storefront?->get_name() ?? $translationname ?: ($product->name ?? '')));
        if ($title === '') {
            $title = trim((string)($item->label ?? ''));
        }
        if ($title === '') {
            $title = get_string('digital_library_resource_fallback', 'local_subscriptions');
        }

        return [
            'sku' => $sku,
            'product' => $product ?: null,
            'productid' => $storefront?->get_id() ?? ($product === false ? null : (int)$product->id),
            'accessid' => $digitalaccess === false ? null : (int)$digitalaccess->id,
            'aggregatecount' => $digitalaccess === false ? null : (int)$digitalaccess->downloadcount,
            'aggregatelastdownloadat' => $digitalaccess === false || $digitalaccess->lastdownloadat === null
                ? null
                : (int)$digitalaccess->lastdownloadat,
            'title' => $title,
            'coverurl' => $storefront?->get_cover_url('resources') ?? $this->native_cover_url($sku),
        ];
    }

    /**
     * Read per-asset download history from Moodle's standard event log.
     *
     * The legacy aggregate counter remains authoritative for download limits, but
     * cannot distinguish desktop from mobile. Events recorded by I5.6 provide the
     * customer-facing per-file counters without introducing a plugin table.
     *
     * @return array{desktop:array{count:int,lastdownloadat:?int},mobile:array{count:int,lastdownloadat:?int}}
     */
    private function native_download_history(
        ?int $accessid,
        ?int $aggregatecount,
        ?int $aggregatelastdownloadat,
        int $variantcount,
        string $singlevariant
    ): array {
        global $DB;

        $history = [
            'desktop' => ['count' => 0, 'lastdownloadat' => null],
            'mobile' => ['count' => 0, 'lastdownloadat' => null],
        ];
        if ($variantcount === 1 && $aggregatecount !== null) {
            $variant = isset($history[$singlevariant]) ? $singlevariant : 'desktop';
            $history[$variant]['count'] = max(0, $aggregatecount);
            $history[$variant]['lastdownloadat'] = $aggregatelastdownloadat;
            return $history;
        }
        if ($accessid === null || $accessid <= 0
                || !$DB->get_manager()->table_exists(new \xmldb_table('logstore_standard_log'))) {
            return $history;
        }

        $records = $DB->get_records('logstore_standard_log', [
            'eventname' => '\\local_subscriptions\\event\\digital_file_downloaded',
            'objectid' => $accessid,
        ], 'timecreated ASC', 'id,timecreated,other');

        foreach ($records as $record) {
            $other = json_decode((string)$record->other, true);
            $variant = is_array($other) ? (string)($other['variant'] ?? '') : '';
            if (!isset($history[$variant])) {
                continue;
            }
            $history[$variant]['count']++;
            $history[$variant]['lastdownloadat'] = (int)$record->timecreated;
        }

        return $history;
    }

    /**
     * Find the concrete file information for a Native product, including the
     * compatibility storage used by products imported before Native assets.
     *
     * @return array{available:bool,filename:?string,filesize:?int,filetype:?string}
     */
    private function native_file_information(?\stdClass $product, string $role, string $resourcekey): array {
        global $CFG, $DB;

        $filename = '';
        $filesize = null;
        if ($product !== null) {
            $file = $this->digitalfiles->get_file((int)$product->id, $role);
            if ($file !== null) {
                $filename = trim($file->get_filename());
                $filesize = $this->normalize_filesize($file->get_filesize());
            }

            if ($filename === '') {
                $metadata = json_decode((string)($product->metadatajson ?? ''), true);
                $key = $role === CommerceCatalogDigitalFileManager::ROLE_MOBILE
                    ? 'mobilefilename'
                    : 'filename';
                $filename = is_array($metadata) ? trim((string)($metadata[$key] ?? '')) : '';
            }
        }

        if ($filename === '' && preg_match('/^digital-product:(\d+)$/', $resourcekey, $matches) === 1) {
            $field = $role === CommerceCatalogDigitalFileManager::ROLE_MOBILE
                ? 'mobile_filename'
                : 'filename';
            $filename = trim((string)$DB->get_field('subscription_digital_product', $field, [
                'id' => (int)$matches[1],
            ], IGNORE_MISSING));
        }

        $filename = basename($filename);
        if ($filename !== '' && $filesize === null) {
            $path = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $filename;
            $filesize = is_readable($path) ? $this->normalize_filesize(filesize($path)) : null;
        }

        return [
            'available' => $filename !== '',
            'filename' => $filename !== '' ? $filename : null,
            'filesize' => $filesize,
            'filetype' => $this->file_type($filename),
        ];
    }

    /**
     * @param array{available:bool,filename:?string,filesize:?int,filetype:?string} $fileinformation
     */
    private function native_download(
        string $label,
        string $url,
        string $variant,
        string $role,
        array $fileinformation,
        ?int $downloadcount,
        ?int $lastdownloadat
    ): CommerceDigitalDownloadPresentation {
        return new CommerceDigitalDownloadPresentation(
            $label,
            $url,
            $variant,
            true,
            $fileinformation['filename'],
            $fileinformation['filetype'],
            $fileinformation['filesize'],
            $downloadcount,
            $lastdownloadat,
            [
                'historyavailable' => true,
                'assetkey' => $role,
            ]
        );
    }

    private function legacy_download(
        string $label,
        string $url,
        string $variant,
        string $filename
    ): CommerceDigitalDownloadPresentation {
        global $CFG;

        $filename = basename($filename);
        $path = $filename !== '' ? $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $filename : '';
        $filesize = $path !== '' && is_readable($path) ? filesize($path) : false;

        return new CommerceDigitalDownloadPresentation(
            $label,
            $url,
            $variant,
            true,
            $filename !== '' ? $filename : null,
            $this->file_type($filename),
            $this->normalize_filesize($filesize),
            null,
            null,
            [
                'historyavailable' => false,
                'assetkey' => $variant,
            ]
        );
    }

    private function native_cover_url(string $sku): ?string {
        if ($sku === '') {
            return null;
        }
        $product = $this->storefrontrepository->find_by_sku($sku, current_language(), null, true);
        return $product?->get_cover_url('resources');
    }

    private function legacy_cover_url(?object $product): ?string {
        global $CFG;

        $filename = basename(trim((string)($product->coverimage ?? '')));
        if ($filename === '' || !is_file($CFG->dirroot . '/local/subscriptions/pix/cover/' . $filename)) {
            return null;
        }
        return (new moodle_url('/local/subscriptions/pix/cover/' . rawurlencode($filename)))->out(false);
    }

    private function file_type(?string $filename): ?string {
        $extension = strtolower((string)pathinfo((string)$filename, PATHINFO_EXTENSION));
        return $extension !== '' ? $extension : null;
    }

    /**
     * Normalize file sizes returned by Moodle file APIs or filesystem helpers.
     *
     * Some storage implementations expose the size as a numeric string even though
     * stored_file::get_filesize() normally returns an integer. The presentation
     * model deliberately remains strictly typed, so normalization belongs here at
     * the infrastructure boundary.
     */
    private function normalize_filesize(mixed $filesize): ?int {
        if ($filesize === null || $filesize === false || $filesize === '') {
            return null;
        }

        if (is_int($filesize)) {
            return $filesize >= 0 ? $filesize : null;
        }

        if (is_float($filesize)) {
            return is_finite($filesize) && $filesize >= 0 ? (int)$filesize : null;
        }

        if (!is_string($filesize)) {
            return null;
        }

        $filesize = trim($filesize);
        if ($filesize === '' || !ctype_digit($filesize)) {
            return null;
        }

        $normalized = filter_var($filesize, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        return $normalized === false ? null : $normalized;
    }

    private function prefer_richer_resource(
        ?CommerceDigitalResourcePresentation $current,
        CommerceDigitalResourcePresentation $candidate
    ): CommerceDigitalResourcePresentation {
        if ($current === null) {
            return $candidate;
        }
        $currentfiles = count($current->downloads);
        $candidatefiles = count($candidate->downloads);
        if ($candidatefiles > $currentfiles || ($current->coverurl === null && $candidate->coverurl !== null)) {
            return $candidate;
        }
        return $current;
    }

    private function presentation_has_digital_access(object $presentation): bool {
        foreach ($presentation->items as $item) {
            foreach ($item->accesses as $access) {
                if (in_array(strtolower($access->type), ['digital', 'digital_download', 'digital_product'], true)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function load_translations(array $productids): array {
        global $DB;

        if ($productids === [] || !$DB->get_manager()->table_exists(new \xmldb_table('subscription_digital_product_i18n'))) {
            return [];
        }

        $records = $DB->get_records_list('subscription_digital_product_i18n', 'productid', $productids);
        $result = [];
        foreach ($records as $record) {
            $result[(int)$record->productid][(string)$record->lang] = $record;
        }
        return $result;
    }
}
