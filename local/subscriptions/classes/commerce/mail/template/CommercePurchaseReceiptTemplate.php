<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailAttachment;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\order\invoice\CommerceInvoicePdfService;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

final class CommercePurchaseReceiptTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PURCHASE_RECEIPT; }
    protected function subject_key(): string { return 'commerce_mail_purchase_receipt_subject'; }
    protected function template_name(): string { return 'purchase_receipt'; }


    /**
     * C5.0: specialise the editorial copy for the Alf donation journeys while
     * keeping the existing purchase_receipt type, outbox idempotency, invoice
     * attachment and technical receipt body unchanged.
     *
     * @param array<string,mixed> $context
     * @param array<string,mixed> $editorial
     * @return array<string,mixed>
     */
    protected function resolve_editorial(
        CommerceMailRequest $request,
        array $context,
        array $editorial
    ): array {
        $variant = '';
        if (!empty($context['hasalfdonationonly'])) {
            $variant = 'donation';
        } elseif (!empty($context['hasalfcourseanddonation'])) {
            $variant = 'course_donation';
        }

        if ($variant === '') {
            return $editorial;
        }

        $resolved = CommerceMailTemplateDefaults::get_alf_purchase_receipt(
            $variant,
            $request->get_language()
        ) + $editorial;

        // C5.14.2: the optional Alf photo belongs only to the donation-only
        // journey. The shared PURCHASE_RECEIPT Mail Studio template stores the
        // binary once, while this variant mapping prevents it from leaking into
        // standard receipts or the Cours + Don receipt.
        if ($variant === 'donation') {
            $footerimageurl = trim((string)($editorial['alfdonationfooterimageurl'] ?? ''));
            if ($footerimageurl !== '') {
                $resolved['footerimageurl'] = $footerimageurl;
            }
        }

        return $resolved;
    }

    protected function primary_action_label(array $context): ?string {
        if (!empty($context['hasonlydonations'])) {
            return !empty($context['links']['hasorder'])
                ? get_string('commerce_mail_view_order', 'local_subscriptions')
                : null;
        }

        return !empty($context['links']['hascampus'])
            ? get_string('commerce_mail_access_my_campus', 'local_subscriptions')
            : null;
    }

    protected function primary_action_url(array $context): ?string {
        if (!empty($context['hasonlydonations'])) {
            return !empty($context['links']['hasorder'])
                ? (string)$context['links']['order']
                : null;
        }

        return !empty($context['links']['hascampus'])
            ? (string)$context['links']['campus']
            : null;
    }

    protected function primary_action_icon(array $context): string {
        return 'external';
    }

    protected function attachments(CommerceMailRequest $request): array {
        global $DB;
        $auditcopy = $request->get_context()->get('auditcopy', []);
        if (
            is_array($auditcopy)
            && !empty($auditcopy['enabled'])
            && empty($auditcopy['includeattachment'])
        ) {
            return [];
        }

        $purchaseid = $request->get_purchase_id();
        if ($purchaseid === null) {
            return [];
        }
        $repository = new CommercePurchaseReadRepository($DB);
        $details = $repository->find_by_id($purchaseid);
        if ($details === null) {
            // Preview and isolated template tests may use a synthetic purchase ID.
            return [];
        }
        $order = (new CommerceOrderPresentationService($DB, $repository))->present($details);
        $document = (new CommerceInvoicePdfService($DB))->generate($order);
        return [new CommerceMailAttachment(
            $document->get_filename(),
            $document->get_mimetype(),
            $document->get_content()
        )];
    }
}
