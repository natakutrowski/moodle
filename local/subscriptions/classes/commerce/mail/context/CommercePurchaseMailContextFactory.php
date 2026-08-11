<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\context;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;
use local_subscriptions\commerce\mail\CommerceMailRecipient;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayText;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter;
use moodle_database;
use moodle_url;

/** Builds the serialisable transactional-mail context from one Native purchase. */
final class CommercePurchaseMailContextFactory {

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePurchaseReadRepository $purchases,
        private readonly CommerceOrderPresentationService $orders
    ) {
    }

    public static function create(): self {
        global $DB;
        $purchases = new CommercePurchaseReadRepository($DB);
        return new self($DB, $purchases, new CommerceOrderPresentationService($DB, $purchases));
    }

    /** @return array{recipient:CommerceMailRecipient,context:CommerceMailContext,language:string,purchaseid:int} */
    public function build_by_reference(string $reference): array {
        $details = $this->purchases->find_by_reference(trim($reference));
        if ($details === null) {
            throw new \RuntimeException('The Native Commerce purchase required for transactional mail was not found.');
        }

        $order = $this->orders->present($details);
        $customer = $details->summary->customer;
        $name = $customer->display_name();
        $recipient = new CommerceMailRecipient($customer->email, $name, $customer->userid);
        $language = $this->resolve_language($customer->userid, $details->metadata);

        return [
            'recipient' => $recipient,
            'context' => new CommerceMailContext($this->context_from_order($order, $name, $language)),
            'language' => $language,
            'purchaseid' => $order->purchaseid,
        ];
    }

    /** @return array<string,mixed> */
    public function context_from_order(
        CommerceOrderPresentation $order,
        string $customername = '',
        ?string $language = null
    ): array {
        $language = clean_param($language ?: current_language(), PARAM_LANG) ?: 'fr';
        $items = [];
        foreach ($order->items as $item) {
            $accesses = [];
            foreach ($item->accesses as $access) {
                if (!$access->available || $access->url === null) {
                    continue;
                }

                $kind = $access->type === 'course_access'
                    ? 'course'
                    : ($access->type === 'digital_download' ? 'download' : 'link');

                // Internal action keys such as open_course/download_file are never
                // customer-facing labels. The mail template owns translated wording.
                $technicalactions = ['open_course', 'download_file', 'open_access'];
                $label = trim((string)$access->label);
                if (in_array(strtolower($label), $technicalactions, true)) {
                    $label = '';
                }

                $accesssku = trim((string)($access->metadata['productsku'] ?? ''));
                $base = [
                    'kind' => $kind,
                    'label' => $label,
                    'title' => $this->translated_product_name_by_sku($accesssku, $language),
                    'productsku' => $accesssku,
                    'coverurl' => $this->product_cover_by_sku(
                        $accesssku,
                        CommerceProductCoverContext::CHECKOUT
                    ),
                    'url' => $access->url,
                    'filename' => (string)($access->metadata['filename'] ?? ''),
                    'filetype' => (string)($access->metadata['filetype'] ?? ''),
                    'filesize' => (string)($access->metadata['filesizeformatted'] ?? $access->metadata['filesize'] ?? ''),
                ];

                if ($kind === 'download') {
                    $hasdesktop = !empty($access->metadata['hasdesktop']);
                    $hasmobile = !empty($access->metadata['hasmobile']);

                    if ($hasdesktop) {
                        $desktop = $base;
                        $desktop['variant'] = 'desktop';
                        $desktop['url'] = (new moodle_url($access->url, ['version' => 'desktop']))->out(false);
                        $accesses[] = $desktop;
                    }
                    if ($hasmobile) {
                        $mobile = $base;
                        $mobile['variant'] = 'mobile';
                        $mobile['url'] = (new moodle_url($access->url, ['version' => 'mobile']))->out(false);
                        $accesses[] = $mobile;
                    }

                    // Transitional products may not expose explicit variant metadata yet.
                    if (!$hasdesktop && !$hasmobile) {
                        $base['variant'] = 'desktop';
                        $accesses[] = $base;
                    }
                    continue;
                }

                $accesses[] = $base;
            }

            $itemsku = trim((string)($item->metadata['sku'] ?? $item->metadata['productsku'] ?? $item->reference));
            $items[] = [
                'type' => $this->normalise_item_type($item->type),
                'title' => $this->translated_item_name($item->label, $item->metadata, $language),
                'productsku' => $itemsku,
                'coverurl' => $this->product_cover_by_sku(
                    $itemsku,
                    CommerceProductCoverContext::CHECKOUT
                ),
                'description' => (string)($item->metadata['description'] ?? ''),
                'quantity' => $item->quantity,
                'grossformatted' => $this->format_money($item->grossminor, $item->currency),
                'discountformatted' => $this->format_money($item->discountminor, $item->currency),
                'totalformatted' => $this->format_money($item->netminor, $item->currency),
                'grossminor' => $item->grossminor,
                'discountminor' => $item->discountminor,
                'netminor' => $item->netminor,
                'producturl' => $this->product_url($item->metadata),
                'accesses' => $accesses,
            ];
        }

        $pricingpresenter = new CommercePersistedCommercialPricingPresenter();
        $itempricingmodels = [];
        foreach ($order->items as $orderitem) {
            $itempricingmodels[] = $pricingpresenter->item(
                $orderitem->metadata,
                $orderitem->grossminor,
                $orderitem->discountminor,
                $orderitem->netminor,
                $orderitem->quantity
            );
        }
        $orderpricing = $pricingpresenter->order(
            $order->metadata,
            $itempricingmodels,
            $order->totalminor
        );

        $promotioncodes = array_values(array_filter(
            (array)($order->metadata['promotion_codes'] ?? []),
            'is_string'
        ));

        $ispersonaloffer = false;
        foreach ($order->items as $orderitem) {
            $metadata = is_array($orderitem->metadata ?? null)
                ? $orderitem->metadata
                : [];
            if (strtolower(trim((string)($metadata['operation'] ?? ''))) === 'personaloffer') {
                $ispersonaloffer = true;
                break;
            }
        }

        $adjustmentminor = (int)$orderpricing['adjustmentminor'];
        $personalofferminor = $ispersonaloffer ? $adjustmentminor : 0;
        $promocodeminor = !$ispersonaloffer && $promotioncodes !== []
            ? $adjustmentminor
            : 0;
        $otherdiscountminor = max(
            0,
            $adjustmentminor - $personalofferminor - $promocodeminor
        );

        $payment = $order->payment;
        return [
            'customer' => ['fullname' => $customername],
            'purchase' => [
                'reference' => (new CommercePublicOrderReference())->from_internal($order->reference, $order->timecreated),
                'grossformatted' => $this->format_money((int)$orderpricing['initialminor'], $order->currency),
                'discountformatted' => $this->format_money((int)$orderpricing['totalreductionminor'], $order->currency),
                'hasdiscount' => (bool)$orderpricing['haspricing'],
                'hasproductpromotion' => (bool)$orderpricing['haspromotion'],
                'productpromotionformatted' => $this->format_money((int)$orderpricing['promotionminor'], $order->currency),
                'hastrialdiscount' => (bool)$orderpricing['hastrial'],
                'trialdiscountformatted' => $this->format_money((int)$orderpricing['trialminor'], $order->currency),
                'hasownedcredit' => (bool)$orderpricing['hascredit'],
                'ownedcreditformatted' => $this->format_money((int)$orderpricing['creditminor'], $order->currency),
                'haspromocode' => $promocodeminor > 0,
                'promocodeformatted' => $this->format_money($promocodeminor, $order->currency),
                'promotioncodes' => implode(', ', $promotioncodes),
                'haspersonaloffer' => $personalofferminor > 0,
                'personalofferformatted' => $this->format_money($personalofferminor, $order->currency),
                'hasotherdiscount' => $otherdiscountminor > 0,
                'otherdiscountformatted' => $this->format_money($otherdiscountminor, $order->currency),
                'totalformatted' => $this->format_money($order->totalminor, $order->currency),
            ],
            'items' => $items,
            'payment' => $payment === null ? [] : [
                'provider' => strtolower(trim((string)$payment->provider)),
                'providerlabel' => ucfirst((string)$payment->provider),
                'transactionreference' => (string)($payment->transactionid ?? $payment->providerreference ?? ''),
                'status' => strtolower(trim((string)$payment->status)),
                'amountformatted' => $this->format_money($payment->amountminor, $payment->currency),
            ],
            'links' => [
                'order' => (new moodle_url('/local/subscriptions/order_details.php', ['reference' => $order->reference]))->out(false),
                'purchases' => (new moodle_url('/mes-achats'))->out(false),
                'resources' => (new moodle_url('/local/subscriptions/my_digital_products.php'))->out(false),
                'courses' => (new moodle_url('/my/courses.php'))->out(false),
                'campus' => (new moodle_url('/mon-campus'))->out(false),
            ],
        ];
    }

    private function product_cover_by_sku(string $sku, string $context): string {
        if ($sku === '') {
            return '';
        }
        $productid = $this->database->get_field(
            'local_subs_commerce_product',
            'id',
            ['sku' => $sku],
            IGNORE_MISSING
        );
        if (!$productid) {
            return '';
        }
        return (string)(CommerceProductCoverService::create()
            ->resolve((int)$productid, $context)
            ->get_url() ?? '');
    }

    /** @param array<string,mixed> $metadata */
    private function translated_item_name(string $fallback, array $metadata, string $language): string {
        $sku = trim((string)($metadata['sku'] ?? $metadata['productsku'] ?? ''));
        $translated = $this->translated_product_name_by_sku($sku, $language);
        return $translated !== '' ? $translated : $fallback;
    }

    private function translated_product_name_by_sku(string $sku, string $language): string {
        if ($sku === '') {
            return '';
        }

        $productid = $this->database->get_field(
            'local_subs_commerce_product',
            'id',
            ['sku' => $sku],
            IGNORE_MISSING
        );
        if (!$productid) {
            return '';
        }

        $requested = strtolower(trim($language));
        $base = explode('_', str_replace('-', '_', $requested))[0];
        foreach (array_values(array_unique(array_filter([$requested, $base, 'fr', 'en', 'ru']))) as $candidate) {
            $name = trim((string)$this->database->get_field(
                'local_subs_commerce_prod_tr',
                'name',
                ['productid' => (int)$productid, 'language' => $candidate],
                IGNORE_MISSING
            ));
            if ($name !== '') {
                return CommerceProductDisplayText::title($name);
            }
        }

        $record = $this->database->get_record_sql(
            "SELECT name
               FROM {local_subs_commerce_prod_tr}
              WHERE productid = :productid
           ORDER BY CASE language WHEN 'fr' THEN 0 WHEN 'en' THEN 1 WHEN 'ru' THEN 2 ELSE 3 END,
                    language ASC, id ASC",
            ['productid' => (int)$productid],
            IGNORE_MISSING
        );

        return CommerceProductDisplayText::title((string)($record->name ?? ''));
    }

    private function resolve_language(?int $userid, array $metadata): string {
        if ($userid !== null) {
            $language = $this->database->get_field('user', 'lang', ['id' => $userid]);
            if (is_string($language) && trim($language) !== '') {
                return clean_param($language, PARAM_LANG);
            }
        }
        foreach (['customerlang', 'language', 'lang'] as $key) {
            $language = trim((string)($metadata[$key] ?? ''));
            if ($language !== '') {
                return clean_param($language, PARAM_LANG);
            }
        }
        return clean_param(current_language(), PARAM_LANG) ?: 'fr';
    }

    private function normalise_item_type(string $type): string {
        $type = strtolower(trim($type));
        return match (true) {
            str_contains($type, 'course'), str_contains($type, 'subscription') => 'course',
            str_contains($type, 'digital') => 'digital',
            str_contains($type, 'bundle') => 'bundle',
            str_contains($type, 'service') => 'service',
            default => 'product',
        };
    }

    /** @param array<string,mixed> $metadata */
    private function product_url(array $metadata): string {
        foreach (['producturl', 'storefronturl'] as $key) {
            $url = trim((string)($metadata[$key] ?? ''));
            if ($url !== '') {
                return $url;
            }
        }
        $sku = trim((string)($metadata['sku'] ?? $metadata['productsku'] ?? ''));
        return $sku === '' ? '' : (new moodle_url('/boutique', ['sku' => $sku]))->out(false);
    }

    private function format_money(int $minor, string $currency): string {
        return format_float($minor / 100, 2) . ' ' . strtoupper(trim($currency));
    }
}
