<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\invoice;

defined('MOODLE_INTERNAL') || die();

use core_user;
use local_subscriptions\commerce\order\presentation\CommerceBundleComponentResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\pricing\CommercePersistedCommercialPricingPresenter;
use local_subscriptions\payment\Provider;
use moodle_database;

/** Generates the canonical customer invoice used by HTTP download and transactional mail. */
final class CommerceInvoicePdfService {
    public function __construct(private readonly moodle_database $database) {}

    public static function create(): self {
        global $DB;
        return new self($DB);
    }

    public function generate(CommerceOrderPresentation $order): CommerceInvoiceDocument {
        global $CFG, $SITE;
        require_once($CFG->libdir . '/pdflib.php');

        $context = \context_system::instance();
        $publicreference = (new CommercePublicOrderReference())->from_internal($order->reference, $order->timecreated);
        $profile = (new CommerceInvoiceProfileResolver())->resolve($order->currency, $order->provider);
        $sellername = $profile['name'] !== '' ? $profile['name'] : format_string($SITE->fullname, true, ['context' => $context]);
        $money = static fn(int $minor, string $currency): string => format_float($minor / 100, 2) . ' ' . strtoupper($currency);
        $customer = $this->customer($order);
        $customeremail = trim((string)($customer->email ?? '')) ?: $order->customeremail;
        $customername = trim(fullname($customer));
        $customerphone = trim((string)($customer->phone1 ?? '')) ?: trim((string)($customer->phone2 ?? ''));
        $countries = get_string_manager()->get_list_of_countries();
        $customeraddress = array_values(array_filter([
            trim((string)($customer->address ?? '')),
            trim((string)($customer->city ?? '')),
            !empty($customer->country) ? ($countries[(string)$customer->country] ?? (string)$customer->country) : '',
        ], static fn(string $value): bool => $value !== ''));

        $pricingpresenter =
            new CommercePersistedCommercialPricingPresenter();
        $itempricing = [];
        $itempersonaloffers = [];
        $haspersonaloffer = false;
        foreach ($order->items as $item) {
            $itemmetadata = is_array($item->metadata ?? null) ? $item->metadata : [];
            $itemispersonaloffer = strtolower(trim((string)($itemmetadata['operation'] ?? ''))) === 'personaloffer';
            $itempersonaloffers[] = $itemispersonaloffer;
            $haspersonaloffer = $haspersonaloffer || $itemispersonaloffer;

            $itempricing[] = $pricingpresenter->item(
                $item->metadata,
                $item->grossminor,
                $item->discountminor,
                $item->netminor,
                $item->quantity
            );
        }
        $orderpricing = $pricingpresenter->order(
            $order->metadata,
            $itempricing,
            $order->totalminor
        );
        $promotioncodes = array_values(array_filter(array_map(
            'trim',
            (array)($order->metadata['promotion_codes'] ?? [])
        )));
        $provider = strtolower(trim((string)($order->payment?->provider ?? $order->provider ?? '')));
        $transactionid = trim((string)($order->payment?->transactionid ?? $order->payment?->providerreference ?? ''));
        $bundleresolver = new CommerceBundleComponentResolver($this->database);

        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('CampusFR');
        $pdf->SetAuthor($sellername);
        $pdf->SetTitle(get_string('commerce_i410_invoice_title', 'local_subscriptions', $publicreference));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 18, 18);
        $pdf->AddPage();

        $pluginroot = dirname(__DIR__, 4);
        $headertop = 18.0;
        $logopath = $pluginroot . '/pix/branding/logo_invoice.png';
        if (is_readable($logopath)) {
            $pdf->Image($logopath, 154, $headertop, 38, 0, 'PNG', '', '', false, 600, '', false, false, 0, true);
        }
        $pdf->SetXY(18, $headertop);
        $pdf->SetFont('freesans', 'B', 22);
        $pdf->Write(10, get_string('commerce_i410_invoice', 'local_subscriptions'));
        $pdf->Ln(14);
        $pdf->SetFont('freesans', '', 10);
        $pdf->Write(6, $sellername);
        if ($profile['address'] !== '') { $pdf->Ln(); $pdf->Write(6, $profile['address']); }
        if ($profile['legal'] !== '') { $pdf->Ln(); $pdf->Write(6, $profile['legal']); }
        foreach (['email', 'phone', 'website'] as $field) {
            if ($profile[$field] !== '') { $pdf->Ln(); $pdf->Write(6, $profile[$field]); }
        }

        $pdf->Ln(12);
        $pdf->SetFont('freesans', 'B', 11);
        $pdf->Write(6, get_string('commerce_i410_invoice_reference', 'local_subscriptions') . ': ' . $publicreference);
        $pdf->Ln();
        $pdf->SetFont('freesans', '', 10);
        $pdf->Write(6, get_string('commerce_invoice_purchase_date', 'local_subscriptions') . ': ' . userdate($order->paidat ?? $order->timecreated));
        $pdf->Ln(8);
        $pdf->SetFont('freesans', 'B', 11);
        $pdf->Write(6, get_string('commerce_i410_invoice_customer', 'local_subscriptions'));
        $pdf->SetFont('freesans', '', 10);
        foreach (array_values(array_filter([$customername, $customeremail, $customerphone, ...$customeraddress])) as $line) {
            $pdf->Ln();
            $pdf->Write(6, $line);
        }

        $pdf->Ln(10);
        $html = '<table border="1" cellpadding="6"><thead><tr>'
            . '<th width="55%"><b>' . s(get_string('commerce_i410_invoice_item', 'local_subscriptions')) . '</b></th>'
            . '<th width="15%"><b>' . s(get_string('commerce_i410_invoice_quantity', 'local_subscriptions')) . '</b></th>'
            . '<th width="30%"><b>' . s(get_string('commerce_i410_invoice_total', 'local_subscriptions')) . '</b></th>'
            . '</tr></thead><tbody>';
        foreach ($order->items as $index => $item) {
            $pricing = $itempricing[$index];
            $itemlabel = s(format_string($item->label));
            if ($item->type === 'bundle') {
                $components = $bundleresolver->resolve($item);
                if ($components !== []) {
                    $itemlabel .= '<br><span style="font-size:8pt;color:#666666;">'
                        . s(get_string('commerce_invoice_bundle_includes', 'local_subscriptions')) . '</span>';
                    foreach ($components as $component) {
                        $itemlabel .= '<br><span style="font-size:8pt;color:#666666;">• '
                            . (int)$component['quantity'] . ' × ' . s((string)$component['name']) . '</span>';
                    }
                }
            }
            if ($pricing['haspricing']) {
                $itemlabel .= '<br><span style="font-size:8pt;color:#666666;">'
                    . s(get_string(
                        'commerce_trial_storefront_initial_price',
                        'local_subscriptions'
                    ))
                    . ': ' . s($money(
                        (int)$pricing['initialminor'],
                        $item->currency
                    )) . '</span>';

                if ($pricing['haspromotion']) {
                    $itemlabel .= '<br><span style="font-size:8pt;color:#237a3b;">'
                        . s(get_string(
                            'commerce_pricing_initial_promotion',
                            'local_subscriptions'
                        ))
                        . ': −' . s($money(
                            (int)$pricing['promotionminor'],
                            $item->currency
                        )) . '</span>';
                }
                if ($pricing['hastrial']) {
                    $itemlabel .= '<br><span style="font-size:8pt;color:#237a3b;">'
                        . s(get_string(
                            'commerce_trial_storefront_badge',
                            'local_subscriptions'
                        ))
                        . ': −' . s($money(
                            (int)$pricing['trialminor'],
                            $item->currency
                        )) . '</span>';
                }
                if ($pricing['hascredit']) {
                    $creditlabel = (string)$pricing['fromlabel'] !== ''
                        ? get_string(
                            'commerce_pricing_owned_credit',
                            'local_subscriptions',
                            (string)$pricing['fromlabel']
                        )
                        : get_string(
                            'commerce_invoice_owned_credit',
                            'local_subscriptions'
                        );
                    $itemlabel .= '<br><span style="font-size:8pt;color:#237a3b;">'
                        . s($creditlabel)
                        . ': −' . s($money(
                            (int)$pricing['creditminor'],
                            $item->currency
                        )) . '</span>';
                }
                if ($pricing['hasotherdiscount']) {
                    $discountlabel = !empty($itempersonaloffers[$index])
                        ? get_string('commerce_personal_offer_order_discount_label', 'local_subscriptions')
                        : get_string('commerce_invoice_other_discount', 'local_subscriptions');
                    $itemlabel .= '<br><span style="font-size:8pt;color:#237a3b;">'
                        . s($discountlabel)
                        . ': −' . s($money(
                            (int)$pricing['otherdiscountminor'],
                            $item->currency
                        )) . '</span>';
                }

                $itemlabel .= '<br><span style="font-size:8pt;font-weight:bold;">'
                    . s(get_string(
                        'commerce_invoice_item_paid_price',
                        'local_subscriptions'
                    ))
                    . ': ' . s($money(
                        (int)$pricing['finalminor'],
                        $item->currency
                    )) . '</span>';
            }

            $html .= '<tr><td width="55%">' . $itemlabel
                . '</td><td width="15%">' . (int)$item->quantity
                . '</td><td width="30%"><b>'
                . s($money(
                    (int)$pricing['finalminor'],
                    $item->currency
                ))
                . '</b></td></tr>';
        }

        if ($orderpricing['haspricing']) {
            $html .= '<tr><td width="70%" colspan="2">'
                . s(get_string(
                    'commerce_cart_list_total',
                    'local_subscriptions'
                ))
                . '</td><td width="30%">'
                . s($money(
                    (int)$orderpricing['initialminor'],
                    $order->currency
                )) . '</td></tr>';

            foreach ([
                [
                    'condition' => 'haspromotion',
                    'label' => 'commerce_pricing_initial_promotion',
                    'amount' => 'promotionminor',
                ],
                [
                    'condition' => 'hastrial',
                    'label' => 'commerce_trial_storefront_badge',
                    'amount' => 'trialminor',
                ],
                [
                    'condition' => 'hascredit',
                    'label' => 'commerce_invoice_owned_credit',
                    'amount' => 'creditminor',
                ],
                [
                    'condition' => 'hasadjustment',
                    'label' => $haspersonaloffer
                        ? 'commerce_personal_offer_order_discount_label'
                        : 'commerce_invoice_other_discount',
                    'amount' => 'adjustmentminor',
                ],
            ] as $row) {
                if (!$orderpricing[$row['condition']]) {
                    continue;
                }
                $html .= '<tr><td width="70%" colspan="2">'
                    . '<span style="color:#237a3b;">'
                    . s(get_string(
                        $row['label'],
                        'local_subscriptions'
                    ))
                    . '</span></td><td width="30%">'
                    . '<span style="color:#237a3b;">−'
                    . s($money(
                        (int)$orderpricing[$row['amount']],
                        $order->currency
                    ))
                    . '</span></td></tr>';
            }

            if ($promotioncodes !== []) {
                $html .= '<tr><td width="70%" colspan="2">'
                    . s(get_string(
                        'commerce_invoice_promotion_code',
                        'local_subscriptions'
                    ))
                    . '</td><td width="30%">'
                    . s(implode(', ', $promotioncodes))
                    . '</td></tr>';
            }

            $html .= '<tr><td width="70%" colspan="2"><b>'
                . s(get_string(
                    'commerce_cart_total_reductions',
                    'local_subscriptions'
                ))
                . '</b></td><td width="30%"><b>−'
                . s($money(
                    (int)$orderpricing['totalreductionminor'],
                    $order->currency
                ))
                . '</b></td></tr>';
        }

        $html .= '<tr><td width="70%" colspan="2"><b>' . s(get_string('commerce_invoice_total_paid', 'local_subscriptions'))
            . '</b></td><td width="30%"><b>' . s($money($order->totalminor, $order->currency)) . '</b></td></tr></tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        if ($provider !== '' || $transactionid !== '') {
            $pdf->Ln(5);
            $pdf->SetFont('freesans', 'B', 11);
            $pdf->Write(6, get_string('commerce_invoice_payment_information', 'local_subscriptions'));
            $pdf->Ln();
            $pdf->SetFont('freesans', '', 10);
            if ($provider !== '') {
                $pdf->Write(6, get_string('commerce_invoice_payment_provider', 'local_subscriptions') . ': ');
                $iconpath = $pluginroot . '/pix/providers/' . $provider . '.svg';
                $liney = $pdf->GetY();
                $contentx = $pdf->GetX() + 2.0;
                if (is_readable($iconpath)) {
                    $iconsize = 5.0;
                    $pdf->ImageSVG($iconpath, $contentx, $liney + ((6.0 - $iconsize) / 2.0), $iconsize, $iconsize);
                    $pdf->SetXY($contentx + $iconsize + 0.5, $liney);
                }
                $pdf->Write(6, Provider::get($provider));
                $pdf->Ln();
            }
            if ($transactionid !== '') {
                $pdf->Write(6, get_string('commerce_invoice_transaction_id', 'local_subscriptions') . ': ' . $transactionid);
                $pdf->Ln();
            }
        }

        if ($profile['taxnotice'] !== '') {
            $pdf->Ln(5);
            $pdf->SetFont('freesans', '', 9);
            $pdf->MultiCell(0, 5, $profile['taxnotice']);
        }
        $pdf->Ln(7);
        $pdf->SetFont('freesans', '', 9);
        $pdf->SetX(18);
        $pdf->MultiCell(0, 5, get_string('commerce_invoice_generated_at', 'local_subscriptions', userdate(time())), 0, 'L', false, 1);
        if ($profile['footer'] !== '') {
            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->MultiCell(0, 5, $profile['footer'], 0, 'L', false, 1);
        }

        $filename = 'facture-' . strtolower($publicreference) . '.pdf';
        return new CommerceInvoiceDocument($filename, (string)$pdf->Output('', 'S'));
    }

    private function customer(CommerceOrderPresentation $order): \stdClass {
        if ($order->userid !== null && $order->userid > 0) {
            $customer = core_user::get_user($order->userid, '*', IGNORE_MISSING);
            if ($customer !== false && $customer !== null) { return $customer; }
        }
        return (object)[
            'firstname' => '', 'lastname' => '', 'firstnamephonetic' => '', 'lastnamephonetic' => '',
            'middlename' => '', 'alternatename' => '', 'email' => $order->customeremail,
            'phone1' => '', 'phone2' => '', 'address' => '', 'city' => '', 'country' => '',
        ];
    }
}
