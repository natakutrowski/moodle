<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\creditnote;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\invoice\CommerceInvoiceDocument;
use local_subscriptions\currency\CurrencyFormatter;
use local_subscriptions\payment\Provider;
use local_subscriptions\commerce\payment\refund\CommerceRefundReasonPresenter;

/** Generates the immutable customer credit-note PDF from persisted snapshots only. */
final class CommerceCreditNotePdfService {
    public function generate(CommerceIssuedCreditNote $note): CommerceInvoiceDocument {
        global $CFG, $SITE;
        require_once($CFG->libdir . '/pdflib.php');

        $seller = $note->seller;
        $customer = $note->customer;
        $sellername = trim((string)($seller['name'] ?? '')) ?: format_string($SITE->fullname);
        $currency = strtoupper((string)($note->financial['currency'] ?? ''));
        $amountminor = (int)($note->financial['refund_minor'] ?? 0);
        $amount = CurrencyFormatter::format_minor_code($amountminor, $currency);

        $customername = trim((string)($customer['fullname'] ?? ''));
        if ($customername === '') {
            $customername = trim(implode(' ', array_filter([
                trim((string)($customer['firstname'] ?? '')),
                trim((string)($customer['lastname'] ?? '')),
            ])));
        }

        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('CampusFR');
        $pdf->SetAuthor($sellername);
        $pdf->SetTitle(get_string('commerce_credit_note_pdf_title', 'local_subscriptions', $note->number));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 18, 18);
        $pdf->AddPage();

        $pluginroot = dirname(__DIR__, 4);
        $logopath = $pluginroot . '/pix/branding/logo_invoice.png';
        if (is_readable($logopath)) {
            $pdf->Image($logopath, 154, 18, 38, 0, 'PNG', '', '', false, 600, '', false, false, 0, true);
        }

        $pdf->SetXY(18, 18);
        $pdf->SetFont('freesans', 'B', 22);
        $pdf->Write(10, get_string('commerce_credit_note', 'local_subscriptions'));
        $pdf->Ln(14);
        $pdf->SetFont('freesans', '', 10);
        $pdf->Write(6, $sellername);
        foreach (['address', 'legal', 'email', 'phone', 'website'] as $field) {
            $value = trim((string)($seller[$field] ?? ''));
            if ($value !== '') {
                $pdf->Ln();
                $pdf->Write(6, $value);
            }
        }

        $pdf->Ln(12);
        $pdf->SetFont('freesans', 'B', 11);
        $pdf->Write(
            6,
            get_string('commerce_credit_note_number', 'local_subscriptions')
                . ': ' . $note->number
        );
        $pdf->Ln();
        $pdf->SetFont('freesans', '', 10);
        $pdf->Write(
            6,
            get_string('commerce_credit_note_original_invoice', 'local_subscriptions')
                . ': ' . (string)($note->metadata['invoice_number'] ?? '—')
        );
        $pdf->Ln();
        $pdf->Write(
            6,
            get_string('commerce_credit_note_issue_date', 'local_subscriptions')
                . ': ' . userdate($note->issuedat)
        );

        $pdf->Ln(11);
        $pdf->SetFont('freesans', 'B', 11);
        $pdf->Write(6, get_string('commerce_i410_invoice_customer', 'local_subscriptions'));
        $pdf->SetFont('freesans', '', 10);
        foreach (array_filter([
            $customername,
            trim((string)($customer['email'] ?? '')),
        ]) as $line) {
            $pdf->Ln();
            $pdf->Write(6, (string)$line);
        }

        $originaltotalminor = (int)($note->financial['original_invoice_total_minor'] ?? 0);
        $originaltotal = CurrencyFormatter::format_minor_code(
            $originaltotalminor,
            $currency
        );
        $originalpurchase = is_array($note->metadata['original_purchase'] ?? null)
            ? $note->metadata['original_purchase']
            : [];
        $originalitems = is_array($originalpurchase['items'] ?? null)
            ? $originalpurchase['items']
            : [];

        $pdf->Ln(10);
        $html = '<table border="1" cellpadding="6"><thead><tr>'
            . '<th width="70%"><b>'
            . s(get_string('commerce_credit_note_original_purchase', 'local_subscriptions'))
            . '</b></th><th width="30%"><b>'
            . s(get_string('commerce_i410_invoice_total', 'local_subscriptions'))
            . '</b></th></tr></thead><tbody>';

        if ($originalitems !== []) {
            foreach ($originalitems as $item) {
                $itemlabel = s((string)($item['label'] ?? ''));
                $quantity = max(1, (int)($item['quantity'] ?? 1));
                $itemcurrency = strtoupper((string)($item['currency'] ?? $currency));
                $itemtotal = CurrencyFormatter::format_minor_code(
                    (int)($item['net_minor'] ?? 0),
                    $itemcurrency
                );
                $html .= '<tr><td width="70%">'
                    . $quantity . ' × ' . $itemlabel
                    . '</td><td width="30%">'
                    . s($itemtotal)
                    . '</td></tr>';
            }
        } else {
            $html .= '<tr><td width="70%">'
                . s(get_string('commerce_credit_note_original_purchase_fallback', 'local_subscriptions'))
                . '</td><td width="30%">'
                . s($originaltotal)
                . '</td></tr>';
        }

        $html .= '<tr><td width="70%"><b>'
            . s(get_string('commerce_credit_note_original_purchase_total', 'local_subscriptions'))
            . '</b></td><td width="30%"><b>'
            . s($originaltotal)
            . '</b></td></tr>'
            . '<tr><td width="70%"><b>'
            . s(get_string('commerce_credit_note_refund_amount', 'local_subscriptions'))
            . '</b></td><td width="30%"><b>'
            . s($amount)
            . '</b></td></tr></tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $reason = trim((string)($note->metadata['refund_reason'] ?? ''));
        $provider = trim((string)($note->metadata['provider'] ?? ''));
        $providerrefundid = trim((string)($note->metadata['provider_refund_id'] ?? ''));

        $pdf->Ln(5);
        $pdf->SetFont('freesans', 'B', 11);
        $pdf->Write(6, get_string('commerce_credit_note_refund_information', 'local_subscriptions'));
        $pdf->SetFont('freesans', '', 10);
        if ($reason !== '') {
            $pdf->Ln();
            $pdf->MultiCell(
                0,
                5,
                get_string('commerce_credit_note_reason', 'local_subscriptions')
                    . ': '
                    . (new CommerceRefundReasonPresenter())->label($reason),
                0,
                'L',
                false,
                1
            );
        }
        if ($provider !== '') {
            $pdf->Write(
                6,
                get_string('commerce_invoice_payment_provider', 'local_subscriptions') . ': '
            );
            $iconpath = $pluginroot . '/pix/providers/' . $provider . '.svg';
            $liney = $pdf->GetY();
            $contentx = $pdf->GetX() + 2.0;
            if (is_readable($iconpath)) {
                $iconsize = 5.0;
                $pdf->ImageSVG(
                    $iconpath,
                    $contentx,
                    $liney + ((6.0 - $iconsize) / 2.0),
                    $iconsize,
                    $iconsize
                );
                $pdf->SetXY(
                    $contentx + $iconsize + 0.5,
                    $liney
                );
            }
            $pdf->Write(6, Provider::get($provider));
            $pdf->Ln();
        }
        if ($providerrefundid !== '') {
            $pdf->Write(
                6,
                get_string('commerce_credit_note_provider_refund_id', 'local_subscriptions')
                    . ': ' . $providerrefundid
            );
            $pdf->Ln();
        }

        $taxnotice = trim((string)($seller['tax_notice'] ?? ''));
        if ($taxnotice !== '') {
            $pdf->Ln(5);
            $pdf->SetFont('freesans', '', 9);
            $pdf->MultiCell(0, 5, $taxnotice, 0, 'L', false, 1);
        }

        $footer = trim((string)($seller['footer'] ?? ''));
        if ($footer !== '') {
            $pdf->Ln(5);
            $pdf->SetFont('freesans', '', 9);
            $pdf->MultiCell(0, 5, $footer, 0, 'L', false, 1);
        }

        return new CommerceInvoiceDocument(
            'avoir-' . strtolower($note->number) . '.pdf',
            (string)$pdf->Output('', 'S')
        );
    }
}
