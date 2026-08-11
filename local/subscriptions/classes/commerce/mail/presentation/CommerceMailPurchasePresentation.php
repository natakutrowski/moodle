<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailContext;

/**
 * Serialisable purchase presentation consumed by Native transactional mail templates.
 */
final class CommerceMailPurchasePresentation {

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<string,mixed> $payment
     * @param array<string,string> $links
     */
    private function __construct(
        private readonly string $customername,
        private readonly string $reference,
        private readonly string $grossformatted,
        private readonly string $discountformatted,
        private readonly bool $hasdiscount,
        private readonly bool $hasproductpromotion,
        private readonly string $productpromotionformatted,
        private readonly bool $hastrialdiscount,
        private readonly string $trialdiscountformatted,
        private readonly bool $hasownedcredit,
        private readonly string $ownedcreditformatted,
        private readonly bool $haspromocode,
        private readonly string $promocodeformatted,
        private readonly string $promotioncodes,
        private readonly bool $haspersonaloffer,
        private readonly string $personalofferformatted,
        private readonly bool $hasotherdiscount,
        private readonly string $otherdiscountformatted,
        private readonly string $totalformatted,
        private readonly array $items,
        private readonly array $payment,
        private readonly array $links
    ) {
    }

    public static function from_context(CommerceMailContext $context): self {
        $customer = self::array_value($context->get('customer', []));
        $purchase = self::array_value($context->get('purchase', []));
        $payment = self::array_value($context->get('payment', []));
        $links = self::array_value($context->get('links', []));
        $items = self::normalise_items($context->get('items', $purchase['items'] ?? []));

        return new self(
            self::first_string($customer, ['firstname', 'fullname', 'name']),
            self::first_string($purchase, ['reference', 'commercialreference']),
            self::first_string($purchase, ['grossformatted', 'gross']),
            self::first_string($purchase, ['discountformatted', 'discount']),
            !empty($purchase['hasdiscount']),
            !empty($purchase['hasproductpromotion']),
            self::first_string($purchase, ['productpromotionformatted']),
            !empty($purchase['hastrialdiscount']),
            self::first_string($purchase, ['trialdiscountformatted']),
            !empty($purchase['hasownedcredit']),
            self::first_string($purchase, ['ownedcreditformatted']),
            !empty($purchase['haspromocode']),
            self::first_string($purchase, ['promocodeformatted']),
            self::first_string($purchase, ['promotioncodes']),
            !empty($purchase['haspersonaloffer']),
            self::first_string($purchase, ['personalofferformatted']),
            !empty($purchase['hasotherdiscount']),
            self::first_string($purchase, ['otherdiscountformatted']),
            self::first_string($purchase, ['totalformatted', 'total']),
            $items,
            self::normalise_payment($payment),
            self::normalise_links($links, $purchase)
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function export(): array {
        return [
            'customername' => $this->customername,
            'hascustomername' => $this->customername !== '',
            'reference' => $this->reference,
            'hasreference' => $this->reference !== '',
            'grossformatted' => $this->grossformatted,
            'hasgross' => $this->grossformatted !== '',
            'discountformatted' => $this->discountformatted,
            'hasdiscount' => $this->hasdiscount && $this->discountformatted !== '',
            'hasproductpromotion' => $this->hasproductpromotion && $this->productpromotionformatted !== '',
            'productpromotionformatted' => $this->productpromotionformatted,
            'hastrialdiscount' => $this->hastrialdiscount && $this->trialdiscountformatted !== '',
            'trialdiscountformatted' => $this->trialdiscountformatted,
            'hasownedcredit' => $this->hasownedcredit && $this->ownedcreditformatted !== '',
            'ownedcreditformatted' => $this->ownedcreditformatted,
            'haspromocode' => $this->haspromocode && $this->promocodeformatted !== '',
            'promocodeformatted' => $this->promocodeformatted,
            'promotioncodes' => $this->promotioncodes,
            'haspromotioncodes' => $this->promotioncodes !== '',
            'haspersonaloffer' => $this->haspersonaloffer && $this->personalofferformatted !== '',
            'personalofferformatted' => $this->personalofferformatted,
            'hasotherdiscount' => $this->hasotherdiscount && $this->otherdiscountformatted !== '',
            'otherdiscountformatted' => $this->otherdiscountformatted,
            'totalformatted' => $this->totalformatted,
            'hastotal' => $this->totalformatted !== '',
            'items' => $this->items,
            'hasitems' => $this->items !== [],
            'payment' => $this->payment,
            'haspayment' => !empty($this->payment['hasdetails']),
            'links' => $this->links,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function normalise_items(mixed $items): array {
        global $CFG;

        if (!is_array($items)) {
            return [];
        }

        $normalised = [];
        foreach ($items as $position => $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = strtolower(trim((string)($item['type'] ?? $item['producttype'] ?? 'product')));
            if (!in_array($type, ['course', 'digital', 'bundle', 'service', 'product'], true)) {
                $type = 'product';
            }

            $title = trim((string)($item['title'] ?? $item['name'] ?? ''));
            if ($title === '') {
                $title = get_string('commerce_mail_product_fallback', 'local_subscriptions');
            }

            $quantity = max(1, (int)($item['quantity'] ?? 1));
            $accesses = self::normalise_accesses($item['accesses'] ?? $item['actions'] ?? []);

            $bundlecomponents = $type === 'bundle'
                ? self::group_bundle_components($accesses)
                : [];

            $placeholdertype = match ($type) {
                'course' => 'course',
                'digital' => 'digital',
                'bundle' => 'bundle',
                default => 'product',
            };
            $placeholderurl = rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/placeholder-'
                . $placeholdertype . '.png';

            $normalised[] = [
                'position' => (int)$position + 1,
                'type' => $type,
                'iscourse' => $type === 'course',
                'isdigital' => $type === 'digital',
                'isbundle' => $type === 'bundle',
                'isservice' => $type === 'service',
                'title' => $title,
                'productsku' => trim((string)($item['productsku'] ?? '')),
                'coverurl' => self::safe_url($item['coverurl'] ?? ''),
                'hascover' => self::safe_url($item['coverurl'] ?? '') !== '',
                'placeholderurl' => $placeholderurl,
                'description' => trim((string)($item['description'] ?? '')),
                'hasdescription' => trim((string)($item['description'] ?? '')) !== '',
                'quantity' => $quantity,
                'showquantity' => $quantity > 1,
                'grossformatted' => trim((string)($item['grossformatted'] ?? '')),
                'hasgross' => trim((string)($item['grossformatted'] ?? '')) !== '',
                'discountformatted' => trim((string)($item['discountformatted'] ?? '')),
                'hasdiscount' => ((int)($item['discountminor'] ?? 0)) > 0,
                'totalformatted' => trim((string)($item['totalformatted'] ?? $item['total'] ?? '')),
                'hastotal' => trim((string)($item['totalformatted'] ?? $item['total'] ?? '')) !== '',
                'producturl' => self::safe_url($item['producturl'] ?? ''),
                'hasproducturl' => self::safe_url($item['producturl'] ?? '') !== '',
                'accesses' => $accesses,
                'hasaccesses' => $accesses !== [],
                'bundlecomponents' => $bundlecomponents,
                'hasbundlecomponents' => $bundlecomponents !== [],
            ];
        }

        return $normalised;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function normalise_accesses(mixed $accesses): array {
        if (!is_array($accesses)) {
            return [];
        }

        $normalised = [];
        foreach ($accesses as $access) {
            if (!is_array($access)) {
                continue;
            }

            $url = self::safe_url($access['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $kind = strtolower(trim((string)($access['kind'] ?? $access['type'] ?? 'link')));
            if (!in_array($kind, ['course', 'download', 'link'], true)) {
                $kind = 'link';
            }

            $variant = strtolower(trim((string)($access['variant'] ?? '')));
            $normalised[] = [
                'kind' => $kind,
                'iscourse' => $kind === 'course',
                'isdownload' => $kind === 'download',
                'isdesktop' => $kind === 'download' && $variant === 'desktop',
                'ismobile' => $kind === 'download' && $variant === 'mobile',
                'variant' => $variant,
                'title' => trim((string)($access['title'] ?? '')),
                'hastitle' => trim((string)($access['title'] ?? '')) !== '',
                'productsku' => trim((string)($access['productsku'] ?? '')),
                'coverurl' => self::safe_url($access['coverurl'] ?? ''),
                'hascover' => self::safe_url($access['coverurl'] ?? '') !== '',
                'label' => trim((string)($access['label'] ?? '')),
                'url' => $url,
                'filename' => trim((string)($access['filename'] ?? '')),
                'hasfilename' => trim((string)($access['filename'] ?? '')) !== '',
                'filetype' => strtoupper(trim((string)($access['filetype'] ?? ''))),
                'hasfiletype' => trim((string)($access['filetype'] ?? '')) !== '',
                'filesize' => trim((string)($access['filesize'] ?? '')),
                'hasfilesize' => trim((string)($access['filesize'] ?? '')) !== '',
            ];
        }

        return $normalised;
    }

    /**
     * @param array<int,array<string,mixed>> $accesses
     * @return array<int,array<string,mixed>>
     */
    private static function group_bundle_components(array $accesses): array {
        global $CFG;

        $groups = [];
        foreach ($accesses as $access) {
            $key = trim((string)($access['productsku'] ?? ''));
            if ($key === '') {
                $key = trim((string)($access['title'] ?? ''));
            }
            if ($key === '') {
                $key = 'component-' . count($groups);
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'title' => trim((string)($access['title'] ?? '')),
                    'hastitle' => trim((string)($access['title'] ?? '')) !== '',
                    'productsku' => trim((string)($access['productsku'] ?? '')),
                    'coverurl' => trim((string)($access['coverurl'] ?? '')),
                    'hascover' => trim((string)($access['coverurl'] ?? '')) !== '',
                    'accesses' => [],
                    'iscourse' => !empty($access['iscourse']),
                    'isdigital' => !empty($access['isdownload']),
                ];
            }
            $groups[$key]['accesses'][] = $access;
        }

        return array_values(array_map(
            static function(array $group) use ($CFG): array {
                $group['hasaccesses'] = $group['accesses'] !== [];
                $placeholdertype = !empty($group['iscourse'])
                    ? 'course'
                    : (!empty($group['isdigital']) ? 'digital' : 'product');
                $group['placeholderurl'] = rtrim((string)$CFG->wwwroot, '/')
                    . '/local/subscriptions/pix/email/placeholder-'
                    . $placeholdertype . '.png';
                return $group;
            },
            $groups
        ));
    }

    /**
     * @param array<string,mixed> $payment
     * @return array<string,mixed>
     */
    private static function normalise_payment(array $payment): array {
        global $CFG;

        $provider = strtolower(self::first_string($payment, ['provider']));
        $providerlabel = self::first_string($payment, ['providerlabel', 'provider']);
        $transaction = self::first_string($payment, ['transactionreference', 'transactionid']);
        $statusraw = strtolower(self::first_string($payment, ['status']));
        $amount = self::first_string($payment, ['amountformatted', 'amount']);

        $providericons = [
            'stripe' => 'stripe.png',
            'alfa' => 'alfa.png',
        ];
        $providericon = isset($providericons[$provider])
            ? rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/' . $providericons[$provider]
            : '';

        $statuskey = match ($statusraw) {
            'paid', 'completed', 'captured', 'succeeded', 'success' =>
                'commerce_mail_payment_status_paid_value',
            'pending', 'processing', 'created' =>
                'commerce_mail_payment_status_pending_value',
            'failed', 'error' =>
                'commerce_mail_payment_status_failed_value',
            'cancelled', 'canceled' =>
                'commerce_mail_payment_status_cancelled_value',
            default => '',
        };

        $statuslabel = $statuskey !== ''
            ? get_string($statuskey, 'local_subscriptions')
            : ($statusraw !== '' ? ucfirst($statusraw) : '');

        return [
            'provider' => $provider,
            'providerlabel' => $providerlabel,
            'hasprovider' => $providerlabel !== '',
            'providericonurl' => $providericon,
            'hasprovidericon' => $providericon !== '',
            'transactionreference' => $transaction,
            'hastransactionreference' => $transaction !== '',
            'status' => $statuslabel,
            'statusraw' => $statusraw,
            'hasstatus' => $statuslabel !== '',
            'statussuccess' => in_array($statusraw, ['paid', 'completed', 'captured', 'succeeded', 'success'], true),
            'statuswarning' => in_array($statusraw, ['pending', 'processing', 'created'], true),
            'statusdanger' => in_array($statusraw, ['failed', 'error'], true),
            'statusneutral' => in_array($statusraw, ['cancelled', 'canceled'], true)
                || ($statusraw !== '' && !in_array($statusraw, [
                    'paid', 'completed', 'captured', 'succeeded', 'success',
                    'pending', 'processing', 'created',
                    'failed', 'error',
                ], true)),
            'amountformatted' => $amount,
            'hasamount' => $amount !== '',
            'hasdetails' => $providerlabel !== '' || $transaction !== '' || $statuslabel !== '' || $amount !== '',
        ];
    }

    /**
     * @param array<string,mixed> $links
     * @param array<string,mixed> $purchase
     * @return array<string,string|bool>
     */
    private static function normalise_links(array $links, array $purchase): array {
        $order = self::safe_url($links['order'] ?? $links['orderurl'] ?? $purchase['orderurl'] ?? '');
        $purchases = self::safe_url($links['purchases'] ?? $links['purchasesurl'] ?? '');
        $resources = self::safe_url($links['resources'] ?? $links['resourcesurl'] ?? '');
        $courses = self::safe_url($links['courses'] ?? $links['coursesurl'] ?? '');
        $campus = self::safe_url($links['campus'] ?? $links['campusurl'] ?? '');

        return [
            'order' => $order,
            'hasorder' => $order !== '',
            'purchases' => $purchases,
            'haspurchases' => $purchases !== '',
            'resources' => $resources,
            'hasresources' => $resources !== '',
            'courses' => $courses,
            'hascourses' => $courses !== '',
            'campus' => $campus,
            'hascampus' => $campus !== '',
        ];
    }

    /** @return array<string,mixed> */
    private static function array_value(mixed $value): array {
        return is_array($value) ? $value : [];
    }

    /** @param array<string,mixed> $values */
    private static function first_string(array $values, array $keys): string {
        foreach ($keys as $key) {
            if (isset($values[$key]) && is_scalar($values[$key])) {
                $value = trim((string)$values[$key]);
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return '';
    }

    private static function safe_url(mixed $value): string {
        if (!is_scalar($value)) {
            return '';
        }
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        return preg_match('~^(?:https?://|/)~i', $value) ? $value : '';
    }
}
