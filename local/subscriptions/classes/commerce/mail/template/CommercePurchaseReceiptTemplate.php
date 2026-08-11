<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailAttachment;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\order\invoice\CommerceInvoicePdfService;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

final class CommercePurchaseReceiptTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string { return CommerceMailType::PURCHASE_RECEIPT; }
    protected function subject_key(): string { return 'commerce_mail_purchase_receipt_subject'; }
    protected function template_name(): string { return 'purchase_receipt'; }


    protected function primary_action_label(array $context): ?string {
        return !empty($context['links']['hascampus'])
            ? get_string('commerce_mail_access_my_campus', 'local_subscriptions')
            : null;
    }

    protected function primary_action_url(array $context): ?string {
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
